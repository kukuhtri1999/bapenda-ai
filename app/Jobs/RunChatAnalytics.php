<?php

namespace App\Jobs;

use App\Models\AnalysisReport;
use App\Models\ChatMessage;
use App\Services\OpenAIService;
use Illuminate\Support\Str;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RunChatAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AnalysisReport $report;

    public function __construct(AnalysisReport $report)
    {
        $this->report = $report;
    }

    public function handle(OpenAIService $ai)
    {
        try {
            $this->report->update(['status' => 'running']);

            $start = $this->report->start_date;
            $end = $this->report->end_date;

            // detect sample flag from notes (notes stored as json)
            $notes = $this->report->notes ? json_decode($this->report->notes, true) : [];
            $sample = !empty($notes['sample']);

            // if sample mode, take at most N chats (random) and sample messages
            if ($sample) {
                $chatLimit = 200;
                $msgLimit = 2000;
            } else {
                $chatLimit = null;
                $msgLimit = null;
            }

            // if not explicitly sample and dataset is huge, auto-enable sampling to avoid long runs
            $approxCount = ChatMessage::whereBetween('sent_at', [$start, $end])->count();
            if (!$sample && $approxCount > 5000) {
                $sample = true;
                $chatLimit = 150;
                $msgLimit = 1200;
                // annotate report notes so user can see auto-sampling occurred
                $notes = is_array($notes) ? $notes : [];
                $notes['auto_sampled'] = true;
                $this->report->notes = json_encode($notes);
                $this->report->save();
            }

            if ($sample) {
                $chatQuery = ChatMessage::whereBetween('sent_at', [$start, $end]);
                if ($chatLimit) $chatQuery = $chatQuery->distinct()->limit($chatLimit);
                $chatIds = $chatQuery->pluck('chat_id')->toArray();
                $msgQuery = ChatMessage::whereIn('chat_id', $chatIds)->whereBetween('sent_at', [$start, $end])->inRandomOrder();
                if ($msgLimit) $msgQuery = $msgQuery->take($msgLimit);
                $messages = $msgQuery->get(['id', 'chat_id', 'role', 'content', 'metadata', 'sent_at']);
            } else {
                $messages = ChatMessage::whereBetween('sent_at', [$start, $end])->get(['id', 'chat_id', 'role', 'content', 'metadata', 'sent_at']);
            }
            $count = $messages->count();
            $this->report->update(['chat_count' => $count]);

            // Build per-chat transcripts (preserve chat id mapping)
            $grouped = [];
            foreach ($messages as $m) {
                $cid = $m->chat_id;
                if (!isset($grouped[$cid])) $grouped[$cid] = [];
                $grouped[$cid][] = $m->content;
            }
            $perChat = [];
            foreach ($grouped as $cid => $arr) {
                $perChat[] = ['chat_id' => $cid, 'text' => implode("\n", $arr)];
            }

            if (empty($perChat)) {
                $this->report->update(['summary_json' => ['topics' => [], 'sentiments' => [], 'geo' => [], 'recommendations' => []], 'status' => 'completed']);
                return;
            }

            // Local lightweight aggregation to reduce AI calls and tokens
            $wordCounts = [];
            $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            $geo = [];
            $commonIssues = [];
            $categoriesConfig = config('analytics.categories', []);
            $categoryCounts = array_fill_keys($categoriesConfig, 0);
            if (!isset($categoryCounts['lain_lain'])) $categoryCounts['lain_lain'] = 0;

            // small sentiment lexicon (very lightweight)
            $positiveWords = ['terima kasih', 'terimakasih', 'oke', 'baik', 'sukses', 'terlaksana', 'siap', 'berhasil'];
            $negativeWords = ['denda', 'telat', 'terlambat', 'gagal', 'masalah', 'kehilangan', 'hilang', 'rumit', 'maaf'];

            foreach ($perChat as $item) {
                $text = $item['text'];
                // simple tokenization
                $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text));
                $tokens = array_filter(array_map('trim', preg_split('/\s+/', $clean)), fn($t) => strlen($t) > 2);
                foreach ($tokens as $tok) {
                    $wordCounts[$tok] = ($wordCounts[$tok] ?? 0) + 1;
                }

                // simple sentiment counting via keyword presence
                foreach ($positiveWords as $pw) {
                    if (strpos($clean, $pw) !== false) $sentiments['positive']++;
                }
                foreach ($negativeWords as $nw) {
                    if (strpos($clean, $nw) !== false) $sentiments['negative']++;
                }

                // naive category heuristic mapping by keyword (will later be refined by AI)
                $catMatched = false;
                $catMap = [
                    'tanya_pelayanan_pajak' => ['pelayanan pajak', 'layanan pajak', 'pelayanan samsat'],
                    'tanya_samsat_keliling' => ['samsat keliling', 'samkel', 'keliling'],
                    'tanya_cara_bayar_pajak' => ['cara bayar', 'bagaimana bayar', 'gimana bayar', 'bayar pajak'],
                    'tanya_syarat_bayar_pajak' => ['syarat bayar', 'persyaratan', 'dokumen apa', 'berkas apa'],
                    'keluhan' => ['keluh', 'complain', 'susah', 'lama', 'antri', 'antre', 'error'],
                    'informasi_dokumen' => ['stnk', 'bpkb', 'balik nama', 'mutasi', 'plat'],
                    'pembayaran_online' => ['online', 'e samsat', 'aplikasi', 'website', 'mobile'],
                    'lokasi_jam_operasional' => ['jam buka', 'jam operasional', 'buka jam', 'alamat', 'lokasi'],
                    'denda_keterlambatan' => ['denda', 'terlambat', 'telat'],
                    'balik_nama' => ['balik nama', 'mutasi'],
                ];
                foreach ($catMap as $cat => $phrases) {
                    foreach ($phrases as $p) {
                        if (strpos($clean, $p) !== false) {
                            if (!isset($categoryCounts[$cat])) $categoryCounts[$cat] = 0;
                            $categoryCounts[$cat]++;
                            $catMatched = true;
                            break 2;
                        }
                    }
                }
                if (!$catMatched) {
                    $categoryCounts['lain_lain']++;
                }
            }

            // if neutral not easily computed, estimate
            $sentiments['neutral'] = max(0, intval($count / 5) - ($sentiments['positive'] + $sentiments['negative']));

            // try extract geo from metadata if available on messages
            foreach ($messages as $m) {
                if (is_string($m->metadata) && !empty($m->metadata)) {
                    $md = json_decode($m->metadata, true);
                    if (is_array($md) && !empty($md['city'])) {
                        $city = $md['city'];
                        $geo[$city] = ($geo[$city] ?? 0) + 1;
                    }
                }
            }

            // detect common issues by frequent two-word phrases (bigrams)
            $bigrams = [];
            foreach ($perChat as $item) {
                $text = $item['text'];
                $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text));
                $tokens = array_values(array_filter(array_map('trim', preg_split('/\s+/', $clean)), fn($t) => strlen($t) > 2));
                for ($i = 0; $i < count($tokens) - 1; $i++) {
                    $bg = $tokens[$i] . ' ' . $tokens[$i + 1];
                    $bigrams[$bg] = ($bigrams[$bg] ?? 0) + 1;
                }
            }
            arsort($bigrams);
            $topBigrams = array_slice($bigrams, 0, 10, true);
            foreach ($topBigrams as $k => $v) {
                $commonIssues[$k] = $v;
            }

            // Prepare a concise summary prompt for AI to produce final JSON (single call)
            $topWords = array_slice($wordCounts, 0, 50, true);
            arsort($wordCounts);
            $topWords = array_slice($wordCounts, 0, 50, true);

            $aggText = "SUMMARY_AGGREGATES:\n";
            $aggText .= "total_messages: {$count}\n";
            $aggText .= "top_words:\n";
            foreach ($topWords as $w => $c) {
                $aggText .= "- {$w}: {$c}\n";
            }
            $aggText .= "sentiments:\n";
            foreach ($sentiments as $k => $v) {
                $aggText .= "- {$k}: {$v}\n";
            }
            $aggText .= "geo:\n";
            foreach ($geo as $city => $c) {
                $aggText .= "- {$city}: {$c}\n";
            }
            $aggText .= "common_bigrams:\n";
            foreach ($commonIssues as $bg => $c) {
                $aggText .= "- {$bg}: {$c}\n";
            }
            $aggText .= "categories:\n";
            foreach ($categoryCounts as $cat => $c) {
                $aggText .= "- {$cat}: {$c}\n";
            }

            // Provide explicit instructions to AI for improved structured insight & recommendations
            $instruction = "You will receive aggregate analytics from Indonesian tax service chats. Return ONLY strict JSON with keys: topics (array {label,count}), sentiments {positive,neutral,negative}, geo_counts (object), common_issues (array {text,count}), categories (array {name,count}), recommendations (array of actionable Indonesian sentences giving prioritized actions e.g. sosialisasi samsat keliling malam). If data sparse, still produce reasonable recommendations based on categories & issues. Use existing counts, do not invent unrealistic numbers. Maintain original category names provided.\n";

            // First: perform batched per-transcript AI classification to get accurate categories & sentiments
            $categoriesList = array_values($categoriesConfig ?: []);
            $aiClassifications = [];
            $batchSize = 50;
            $total = count($perChat);
            for ($offset = 0; $offset < $total; $offset += $batchSize) {
                $slice = array_slice($perChat, $offset, $batchSize, true);
                $payload = "Provide a JSON array of objects mapping chatIndex -> category -> confidence -> sentiment.\n";
                $payload .= "Rules:\n";
                $payload .= "- category MUST be one of: [" . implode(', ', $categoriesList) . "]. If none match, use 'lain_lain'.\n";
                $payload .= "- sentiment MUST be one of: positive, neutral, negative.\n";
                $payload .= "- confidence MUST be a number between 0 and 1.\n";
                $payload .= "- Return ONLY valid JSON (an array). No extra text.\n\n";
                $payload .= "TRANSCRIPTS:\n";
                $i = 0;
                $indexMap = [];
                foreach ($slice as $sidx => $item) {
                    $globalIndex = $offset + $i;
                    $indexMap[$globalIndex] = $item['chat_id'];
                    // truncate transcript to avoid token explosion
                    $txt = mb_substr($item['text'], 0, 1200);
                    $payload .= "INDEX: {$globalIndex}\n";
                    $payload .= "TEXT: " . str_replace("\n", " ", $txt) . "\n---\n";
                    $i++;
                }

                $res = $ai->analyzeConversations([$payload]);
                if (!empty($res['success']) && !empty($res['message'])) {
                    $txt = $res['message'];
                    $startPos = strpos($txt, '[');
                    $endPos = strrpos($txt, ']');
                    if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                        $maybe = substr($txt, $startPos, $endPos - $startPos + 1);
                        $j = json_decode($maybe, true);
                        if (is_array($j)) {
                            foreach ($j as $obj) {
                                if (!isset($obj['index']) || !isset($obj['category'])) continue;
                                $idx = intval($obj['index']);
                                $cat = $obj['category'];
                                $conf = isset($obj['confidence']) ? floatval($obj['confidence']) : 0.0;
                                $sent = isset($obj['sentiment']) ? $obj['sentiment'] : 'neutral';
                                $aiClassifications[$idx] = ['chat_id' => ($indexMap[$idx] ?? null), 'category' => $cat, 'confidence' => $conf, 'sentiment' => $sent];
                            }
                        }
                    }
                }
                // small pause to be nice to API (no sleep in job to keep fast; left out)
            }

            // Merge AI classification results into counts (fallback to heuristics if AI empty)
            $aiUsed = !empty($aiClassifications);
            $aiCategoryCounts = array_fill_keys($categoriesConfig, 0);
            if (!isset($aiCategoryCounts['lain_lain'])) $aiCategoryCounts['lain_lain'] = 0;
            $aiSentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            if ($aiUsed) {
                foreach ($aiClassifications as $idx => $info) {
                    $cat = $info['category'] ?? 'lain_lain';
                    if (!isset($aiCategoryCounts[$cat])) $aiCategoryCounts[$cat] = 0;
                    $aiCategoryCounts[$cat]++;
                    $s = $info['sentiment'] ?? 'neutral';
                    if (isset($aiSentiments[$s])) $aiSentiments[$s]++;
                }
                // replace heuristic counts with AI counts
                $categoryCounts = $aiCategoryCounts;
                $sentiments = $aiSentiments;
            }

            // Now call AI once with aggregates + categoryCounts (either heuristic or AI-derived) to get recommendations
            $aggText .= "\n\nFINAL_CATEGORY_COUNTS:\n";
            foreach ($categoryCounts as $cat => $c) {
                $aggText .= "- {$cat}: {$c}\n";
            }

            $res = $ai->analyzeConversations([$instruction . "\n" . $aggText]);

            // ask AI once to map aggregates into structured JSON insights
            $summary = null;
            if (!empty($res['success']) && !empty($res['message'])) {
                $txt = $res['message'];
                $startPos = strpos($txt, '{');
                $endPos = strrpos($txt, '}');
                if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                    $maybe = substr($txt, $startPos, $endPos - $startPos + 1);
                    $j = json_decode($maybe, true);
                    if (is_array($j)) {
                        $summary = $j;
                    }
                }
            }

            // fallback if AI did not return JSON
            if (!$summary) {
                $summary = [
                    'topics' => array_map(fn($k, $v) => ['label' => $k, 'count' => $v], array_keys($topWords), $topWords),
                    'sentiments' => $sentiments,
                    'geo_counts' => $geo,
                    'common_issues' => array_map(fn($k, $v) => ['text' => $k, 'count' => $v], array_keys($commonIssues), $commonIssues),
                    'recommendations' => [],
                ];
            }

            // ensure arrays are sorted and consistent
            usort($summary['topics'], fn($a, $b) => $b['count'] <=> $a['count']);
            if (isset($summary['geo_counts']) && is_array($summary['geo_counts'])) {
                arsort($summary['geo_counts']);
            }

            // include a small per_chat sample when AI classifications exist
            if (!empty($aiClassifications)) {
                $per = [];
                foreach ($aiClassifications as $idx => $info) {
                    $per[] = [
                        'chat_id' => $info['chat_id'] ?? null,
                        'category' => $info['category'] ?? 'lain_lain',
                        'confidence' => $info['confidence'] ?? 0.0,
                        'sentiment' => $info['sentiment'] ?? 'neutral',
                    ];
                }
                // keep sample small
                $summary['per_chat'] = array_slice($per, 0, 200);
            }

            $this->report->update(['summary_json' => $summary, 'status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error('Analytics job failed: ' . $e->getMessage());
            $this->report->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }
}
