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
            arsort($wordCounts);
            $aggText = "SUMMARY_AGGREGATES:\n";
            $aggText .= "total_messages: {$count}\n";
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
            // Request richer recommendation objects: rationale, priority, short actions, estimated effort
            $instruction = "You will receive aggregate analytics from Indonesian tax service chats. Return ONLY strict JSON with keys: \n- topics: array of {label,count} \n- sentiments: object {positive,neutral,negative} \n- geo_counts: object \n- common_issues: array of {text,count} \n- categories: array of {name,count} \n- recommendations: array of SHORT Indonesian sentences (summary) \n- recommendations_detailed: array of objects {category, label, count, rationale (Indonesian), actions (array of short actionable steps in Indonesian), priority (low|medium|high), effort_estimate (short text)}\nIf data sparse, still produce reasonable recommendations based on categories & issues. Use existing counts, do not invent unrealistic numbers. Maintain original category names provided. Only return valid JSON.\n";

            // We will rely on batched AI classification for per-chat category & sentiment.
            // Hardcoded keyword maps removed as per request to use AI-only classification.
            $per_chat_sample = [];

            // Human-readable labels for categories
            $categoryLabels = [
                'tanya_pelayanan_pajak' => 'Tanya Pelayanan Pajak',
                'tanya_samsat_keliling' => 'Tanya Samsat Keliling',
                'samsat_keliling_malam' => 'Samsat Keliling Malam',
                'tanya_cara_bayar_pajak' => 'Tanya Cara Bayar Pajak',
                'tanya_syarat_bayar_pajak' => 'Tanya Syarat Bayar Pajak',
                'cek_tagihan_pajak' => 'Cek Tagihan Pajak',
                'denda_keterlambatan' => 'Denda Keterlambatan',
                'informasi_stnk' => 'Informasi STNK',
                'informasi_bpkb' => 'Informasi BPKB',
                'balik_nama_mutasi' => 'Balik Nama / Mutasi',
                'pembayaran_online' => 'Pembayaran Online',
                'e_samsat_aplikasi' => 'E-Samsat / Aplikasi',
                'lokasi_jam_operasional' => 'Lokasi & Jam Operasional',
                'syarat_pengurusan' => 'Syarat Pengurusan',
                'biaya_tarif' => 'Biaya / Tarif',
                'jadwal_pelayanan' => 'Jadwal Pelayanan',
                'verifikasi_dokumen' => 'Verifikasi Dokumen',
                'komplain_pelayanan' => 'Komplain Pelayanan',
                'informasi_pendaftaran' => 'Informasi Pendaftaran',
                'layanan_bantuan_rumah' => 'Layanan Bantuan Rumah',
                'permintaan_sosialisasi' => 'Permintaan Sosialisasi',
                'pertanyaan_umum' => 'Pertanyaan Umum',
                'informasi_pembayaran_bank' => 'Informasi Pembayaran Bank',
                'panduan_online' => 'Panduan Online',
                'lain_lain' => 'Lain-lain',
            ];

            // Extended sentiment lexicon with negation handling
            $positiveWords = array_merge($positiveWords, ['terima kasih banyak', 'trimakasih', 'mantap', 'bagus', 'puas']);
            $negativeWords = array_merge($negativeWords, ['menunggu', 'lama', 'ribet', 'sulit', 'tidak bisa', 'gagal', 'nipun']);
            $negations = ['tidak', 'bukan', 'nggak', 'gk', 'ga', 'tak'];
            $intensifiers = ['sangat', 'sekali', 'banget', 'amat'];

            // Deterministic fallback removed: classification now relies on batched AI.
            // If AI fails for a batch/chat we'll mark it as 'lain_lain' with neutral sentiment and low confidence.

            // Initial AI classification using dedicated classifyChats method
            $initialBatches = array_chunk($perChat, 40);
            foreach ($initialBatches as $ib) {
                $res = $ai->classifyChats($ib, $categoryLabels);
                // capture raw AI output for diagnostics
                $notes = is_array($notes) ? $notes : [];
                $notes['classify_debug'][] = mb_substr($res['message'] ?? '', 0, 1500);
                $this->report->notes = json_encode($notes);
                $this->report->save();
                $data = $res['data'] ?? null;
                if (is_array($data)) {
                    foreach ($data as $row) {
                        if (!isset($row['chat_id'])) continue;
                        $cat = $row['category'] ?? 'lain_lain';
                        if (!isset($categoryLabels[$cat])) $cat = 'lain_lain';
                        $sent = $row['sentiment'] ?? 'neutral';
                        if (!in_array($sent, ['positive', 'neutral', 'negative'])) $sent = 'neutral';
                        $conf = isset($row['confidence']) ? (float)$row['confidence'] : 0.0;
                        $per_chat_sample[] = [
                            'chat_id' => $row['chat_id'],
                            'category' => $cat,
                            'category_label' => $categoryLabels[$cat] ?? $cat,
                            'confidence' => round($conf, 2),
                            'sentiment' => $sent,
                            'snippet' => mb_substr($row['snippet'] ?? ($row['text'] ?? ''), 0, 300),
                        ];
                    }
                } else {
                    // fallback mark all batch chats unknown
                    foreach ($ib as $c) {
                        $per_chat_sample[] = [
                            'chat_id' => $c['chat_id'],
                            'category' => 'lain_lain',
                            'category_label' => $categoryLabels['lain_lain'] ?? 'Lain-lain',
                            'confidence' => 0.1,
                            'sentiment' => 'neutral',
                            'snippet' => mb_substr($c['text'], 0, 300),
                        ];
                    }
                }
            }

            // Run a second-pass reclassification for low-confidence or 'lain_lain' rows to improve accuracy
            if (!empty($per_chat_sample)) {
                // second pass: reclass those with low confidence or 'lain_lain'
                $toRe = array_filter($per_chat_sample, fn($r) => ($r['category'] === 'lain_lain') || ($r['confidence'] < 0.55));
                if (!empty($toRe)) {
                    $chunks2 = array_chunk($toRe, 30);
                    $notes = is_array($notes) ? $notes : [];
                    foreach ($chunks2 as $c2) {
                        $payload = [];
                        foreach ($c2 as $r) {
                            $payload[] = ['chat_id' => $r['chat_id'], 'text' => $r['snippet']];
                        }
                        $rRes = $ai->classifyChats($payload, $categoryLabels);
                        if (is_array($rRes['data'] ?? null)) {
                            foreach ($rRes['data'] as $upd) {
                                if (!isset($upd['chat_id'])) continue;
                                foreach ($per_chat_sample as &$orig) {
                                    if ((string)$orig['chat_id'] === (string)$upd['chat_id']) {
                                        $newCat = $upd['category'] ?? null;
                                        $newConf = isset($upd['confidence']) ? (float)$upd['confidence'] : 0.0;
                                        $newSent = $upd['sentiment'] ?? $orig['sentiment'];
                                        if ($newCat && isset($categoryLabels[$newCat]) && $newConf > $orig['confidence']) {
                                            $orig['category'] = $newCat;
                                            $orig['category_label'] = $categoryLabels[$newCat];
                                            $orig['confidence'] = round($newConf, 2);
                                            if (in_array($newSent, ['positive', 'neutral', 'negative'])) $orig['sentiment'] = $newSent;
                                        }
                                        break;
                                    }
                                }
                                unset($orig);
                            }
                        }
                        $notes['ai_debug'][] = mb_substr($rRes['message'] ?? '', 0, 1000);
                    }
                    $this->report->notes = json_encode($notes);
                    $this->report->save();
                }

                // recompute aggregates
                $categoryCounts = array_fill_keys($categoriesConfig, 0);
                if (!isset($categoryCounts['lain_lain'])) $categoryCounts['lain_lain'] = 0;
                $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
                foreach ($per_chat_sample as $p) {
                    $cat = $p['category'] ?? 'lain_lain';
                    if (!isset($categoryCounts[$cat])) $categoryCounts[$cat] = 0;
                    $categoryCounts[$cat]++;
                    $s = $p['sentiment'] ?? 'neutral';
                    if (isset($sentiments[$s])) $sentiments[$s]++;
                }
            }

            // Now call AI once with aggregates + categoryCounts (either heuristic or AI-derived) to get recommendations
            $aggText .= "\n\nFINAL_CATEGORY_COUNTS:\n";
            foreach ($categoryCounts as $cat => $c) {
                $aggText .= "- {$cat}: {$c}\n";
            }

            // Try aggregate AI call with a strict JSON example and one retry/repair
            $aggInstruction = $instruction . "\nPLEASE RETURN ONLY A VALID JSON OBJECT matching the schema. Example:\n{\n  \"topics\": [{\"label\": \"stnk\", \"count\": 10}],\n  \"sentiments\": {\"positive\":1,\"neutral\":2,\"negative\":3},\n  \"geo_counts\": {},\n  \"common_issues\": [{\"text\":\"antri lama\", \"count\":5}],\n  \"categories\": [{\"name\":\"tanya_cara_bayar_pajak\",\"count\":5}],\n  \"recommendations\": [\"Sosialisasi pembayaran online\"],\n  \"recommendations_detailed\": [{\"category\":\"tanya_cara_bayar_pajak\", \"label\":\"Tanya Cara Bayar Pajak\", \"count\":5, \"rationale\":\"Ringkasan...\", \"actions\": [\"Buat panduan online\"], \"priority\":\"high\", \"effort_estimate\":\"medium\" }]\n}\n";
            $summary = null;
            $aggAttempt = 0;
            while ($aggAttempt < 2 && !$summary) {
                $res = $ai->analyzeConversations([$aggInstruction . "\n" . $aggText]);
                if (!empty($res['success']) && !empty($res['message'])) {
                    $txt = $res['message'];
                    $startPos = strpos($txt, '{');
                    $endPos = strrpos($txt, '}');
                    if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                        $maybe = substr($txt, $startPos, $endPos - $startPos + 1);
                        $j = json_decode($maybe, true);
                        if (is_array($j)) {
                            $summary = $j;
                            break;
                        }
                    }
                }
                // repair attempt: ask model to return only JSON matching schema
                $repair = "You returned non-JSON or invalid JSON. PLEASE return ONLY the JSON object matching the schema with keys: topics, sentiments, geo_counts, common_issues, categories, recommendations. No extra text.";
                $res2 = $ai->analyzeConversations([$repair]);
                if (!empty($res2['success']) && !empty($res2['message'])) {
                    $txt2 = $res2['message'];
                    $s2 = strpos($txt2, '{');
                    $e2 = strrpos($txt2, '}');
                    if ($s2 !== false && $e2 !== false && $e2 > $s2) {
                        $maybe2 = substr($txt2, $s2, $e2 - $s2 + 1);
                        $j2 = json_decode($maybe2, true);
                        if (is_array($j2)) {
                            $summary = $j2;
                            break;
                        }
                    }
                }
                $aggAttempt++;
            }

            // fallback if AI did not return JSON
            if (!$summary) {
                $summary = [
                    'sentiments' => $sentiments,
                    'geo_counts' => $geo,
                    'common_issues' => array_map(fn($k, $v) => ['text' => $k, 'count' => $v], array_keys($commonIssues), $commonIssues),
                    'recommendations' => [],
                ];
            }

            // Generate an AI insight summary (500-600 words in Indonesian) and attach to summary
            try {
                $insight = $ai->generateInsightSummary($aggText, $categoryLabels);
                if ($insight) {
                    $summary['insight_summary'] = $insight;
                }
            } catch (\Throwable $e) {
                // non-fatal
                $notes = is_array($notes) ? $notes : [];
                $notes['insight_error'] = $e->getMessage();
                $this->report->notes = json_encode($notes);
                $this->report->save();
            }

            // Ensure categories array exists in summary (from categoryCounts)
            $catsArr = [];
            foreach ($categoryCounts as $name => $c) {
                // skip zero-count categories to keep chart clean
                if (empty($c) || $c <= 0) continue;
                $catsArr[] = [
                    'name' => $name,
                    'label' => $categoryLabels[$name] ?? $name,
                    'count' => $c
                ];
            }
            usort($catsArr, fn($a, $b) => $b['count'] <=> $a['count']);
            $summary['categories'] = $catsArr;

            // ensure arrays are sorted and consistent
            if (isset($summary['geo_counts']) && is_array($summary['geo_counts'])) {
                arsort($summary['geo_counts']);
            }

            // include a small per_chat sample when AI classifications exist
            // include a small per_chat sample from our deterministic classifier
            if (!empty($per_chat_sample)) {
                $summary['per_chat'] = array_slice($per_chat_sample, 0, 200);
            }

            // If AI did not produce recommendations, use fallback mapping from config
            if (empty($summary['recommendations'])) {
                $recMap = config('analytics_recommendations.recommendations', []);
                $recs = [];
                // pick top 3 categories
                arsort($categoryCounts);
                $topCats = array_slice(array_keys($categoryCounts), 0, 3);
                foreach ($topCats as $tc) {
                    if (isset($recMap[$tc])) {
                        foreach ($recMap[$tc] as $r) {
                            $recs[] = $r;
                        }
                    }
                }
                // ensure unique and keep up to 5
                $summary['recommendations'] = array_slice(array_values(array_unique($recs)), 0, 5);
            }

            // Build detailed recommendations: rationale + action per top categories
            $detailed = [];
            $recMap = config('analytics_recommendations.recommendations', []);
            $topCatsFull = array_slice($catsArr, 0, 5);
            foreach ($topCatsFull as $tc) {
                $name = $tc['name'];
                $label = $tc['label'];
                $count = $tc['count'];
                $rlist = $recMap[$name] ?? [];
                $detailed[] = [
                    'category' => $name,
                    'label' => $label,
                    'count' => $count,
                    'rationale' => "Kategori '{$label}' muncul dengan {$count} percakapan. Tinjau kebutuhan, keluhan, dan peluang perbaikan pada topik ini.",
                    'actions' => $rlist,
                ];
            }
            $summary['recommendations_detailed'] = $detailed;

            $this->report->update(['summary_json' => $summary, 'status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error('Analytics job failed: ' . $e->getMessage());
            $this->report->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }
}
