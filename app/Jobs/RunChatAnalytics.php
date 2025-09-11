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
            // allow long-running job (avoid PHP max execution time fatal on heavy analytics)
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $this->report->update(['status' => 'running']);

            $start = $this->report->start_date;
            $end = $this->report->end_date;

            // detect sample flag from notes (cast to array via model)
            $notes = is_array($this->report->notes) ? $this->report->notes : [];
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
                $this->report->notes = $notes;
                $this->report->save();
            }

            if ($sample) {
                $chatQuery = ChatMessage::whereBetween('sent_at', [$start, $end]);
                if ($chatLimit) $chatQuery = $chatQuery->distinct()->limit($chatLimit);
                $chatIds = $chatQuery->pluck('chat_id')->toArray();
                $msgQuery = ChatMessage::whereIn('chat_id', $chatIds)
                    ->where('role', 'user')
                    ->whereBetween('sent_at', [$start, $end])
                    ->inRandomOrder();
                if ($msgLimit) $msgQuery = $msgQuery->take($msgLimit);
                $messages = $msgQuery->get(['id', 'chat_id', 'role', 'content', 'answer', 'topic', 'sentiment', 'metadata', 'sent_at']);
            } else {
                $messages = ChatMessage::whereBetween('sent_at', [$start, $end])
                    ->where('role', 'user')
                    ->get(['id', 'chat_id', 'role', 'content', 'answer', 'topic', 'sentiment', 'metadata', 'sent_at']);
            }
            $count = $messages->count();
            $this->report->update(['chat_count' => $count]);

            // If nothing to process
            if ($messages->isEmpty()) {
                $this->report->update(['summary_json' => ['topics' => [], 'sentiments' => [], 'geo' => [], 'recommendations' => []], 'status' => 'completed']);
                return;
            }
            // Aggregate using stored per-message topic & sentiment (fast path)
            $categoriesConfig = config('analytics.categories', []);
            $categoryCounts = array_fill_keys($categoriesConfig, 0);
            if (!isset($categoryCounts['lain_lain'])) $categoryCounts['lain_lain'] = 0;
            $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            $geo = [];
            $commonIssues = [];

            foreach ($messages as $m) {
                // count by topic
                $topic = is_string($m->topic ?? null) ? $m->topic : null;
                if ($topic) {
                    if (!isset($categoryCounts[$topic])) $categoryCounts[$topic] = 0;
                    $categoryCounts[$topic]++;
                } else {
                    $categoryCounts['lain_lain']++;
                }
                // sentiments
                $s = is_string($m->sentiment ?? null) ? $m->sentiment : null;
                if ($s && isset($sentiments[$s])) $sentiments[$s]++;

                // geo hints in metadata
                if (!empty($m->metadata)) {
                    $md = is_array($m->metadata) ? $m->metadata : @json_decode($m->metadata, true);
                    if (is_array($md) && !empty($md['city'])) {
                        $city = $md['city'];
                        $geo[$city] = ($geo[$city] ?? 0) + 1;
                    }
                }

                // simple common issues as frequent phrases from user content
                $text = trim((string)$m->content);
                if ($text !== '') {
                    $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text));
                    $tokens = array_values(array_filter(array_map('trim', preg_split('/\s+/', $clean)), fn($t) => strlen($t) > 2));
                    for ($i = 0; $i < count($tokens) - 1; $i++) {
                        $bg = $tokens[$i] . ' ' . $tokens[$i + 1];
                        $commonIssues[$bg] = ($commonIssues[$bg] ?? 0) + 1;
                    }
                }
            }
            arsort($commonIssues);
            $commonIssues = array_slice($commonIssues, 0, 10, true);

            // Prepare a concise summary prompt for AI to produce final JSON (single call)
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
                'permintaan_sosialisasi' => 'Permintaan Sosialisasi',
                // 'pertanyaan_umum' removed - use more specific categories only
                'informasi_pembayaran_bank' => 'Informasi Pembayaran Bank',
                'panduan_online' => 'Panduan Online',
                'lain_lain' => 'Lain-lain',
            ];

            // Using stored sentiments; extra lexicons not needed here

            // Build a light per_chat sample from existing stored fields (optional)
            $per_chat_sample = [];
            $sampleCap = 200;
            foreach ($messages->take($sampleCap) as $m) {
                $meta = [];
                if (!empty($m->metadata)) {
                    $meta = is_array($m->metadata) ? $m->metadata : @json_decode($m->metadata, true);
                    if (!is_array($meta)) $meta = [];
                }
                $per_chat_sample[] = [
                    'chat_id' => $m->chat_id,
                    'category' => $m->topic ?? 'lain_lain',
                    'category_label' => $m->topic ?? 'lain_lain',
                    'confidence' => $meta['classification_confidence'] ?? null,
                    'sentiment' => $m->sentiment ?? 'neutral',
                    'snippet' => mb_substr($m->content, 0, 300),
                ];
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
                $this->report->notes = $notes;
                $this->report->save();
            }

            // Generate a more detailed analysis (~1000 words) and attach
            try {
                // build local categories array from current counts for derived context
                $catsLocal = [];
                foreach ($categoryCounts as $name => $c) {
                    if (empty($c) || $c <= 0) continue;
                    $catsLocal[] = [
                        'name' => $name,
                        'label' => $categoryLabels[$name] ?? $name,
                        'count' => $c
                    ];
                }
                usort($catsLocal, fn($a, $b) => $b['count'] <=> $a['count']);
                $derived = [
                    'sentiments' => $sentiments,
                    'categories' => $catsLocal,
                    'geo_counts' => $summary['geo_counts'] ?? new \stdClass(),
                    'common_issues' => $summary['common_issues'] ?? [],
                ];
                $detailed = $ai->generateDetailedAnalysis($aggText, $categoryLabels, $derived);
                if ($detailed) {
                    $summary['detailed_analysis'] = $detailed;
                }
            } catch (\Throwable $e) {
                $notes = is_array($notes) ? $notes : [];
                $notes['detailed_error'] = $e->getMessage();
                $this->report->notes = $notes;
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
            if (!empty($per_chat_sample)) {
                $summary['per_chat'] = array_slice($per_chat_sample, 0, 200);
            }

            // Dynamic AI recommendations (JSON) - prefer AI; fallback to config if unavailable
            $dynRecs = $ai->generateRecommendations($categoryCounts, $commonIssues, $sentiments, $categoryLabels);
            if (is_array($dynRecs)) {
                if (!empty($dynRecs['recommendations']) && is_array($dynRecs['recommendations'])) {
                    $summary['recommendations'] = $dynRecs['recommendations'];
                }
                if (!empty($dynRecs['recommendations_detailed']) && is_array($dynRecs['recommendations_detailed'])) {
                    $summary['recommendations_detailed'] = $dynRecs['recommendations_detailed'];
                }
            }
            // Fallback if AI provided none
            if (empty($summary['recommendations'])) {
                $recMap = config('analytics_recommendations.recommendations', []);
                $recs = [];
                arsort($categoryCounts);
                $top_count = (int) config('analytics.top_count', 3);
                $topCats = array_slice(array_keys($categoryCounts), 0, $top_count);
                foreach ($topCats as $tc) {
                    if (isset($recMap[$tc])) {
                        foreach ($recMap[$tc] as $r) {
                            $recs[] = $r;
                        }
                    }
                }
                $summary['recommendations'] = array_slice(array_values(array_unique($recs)), 0, 8);
            }

            // If AI did not provide detailed recs, build a simple fallback from config
            if (empty($summary['recommendations_detailed'])) {
                $detailed = [];
                $recMap = config('analytics_recommendations.recommendations', []);
                $top_count = (int) config('analytics.top_count', 3);
                $topCatsFull = array_slice($catsArr, 0, $top_count);
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
                        'priority' => 'medium',
                        'effort_estimate' => 'sedang',
                    ];
                }
                $summary['recommendations_detailed'] = $detailed;
            }

            // Trim recommendations_detailed to top_count to keep focused on highest topics
            $top_count = (int) config('analytics.top_count', 3);
            if (!empty($summary['recommendations_detailed']) && is_array($summary['recommendations_detailed'])) {
                $summary['recommendations_detailed'] = array_slice($summary['recommendations_detailed'], 0, $top_count);
            }

            // Compute top N topics (from config) and request super-detailed strategies for them
            $cfgTop = (int) config('analytics.top_count', 3);
            $topN = array_slice($catsArr, 0, $cfgTop);
            $topTopics = [];
            foreach ($topN as $t) {
                $topTopics[] = ['key' => $t['name'] ?? ($t['label'] ?? ''), 'label' => $t['label'] ?? ($t['name'] ?? ''), 'count' => $t['count'] ?? 0];
            }
            try {
                $strategies = $ai->generateTopTopicStrategies($topTopics, $aggText, $categoryLabels);
                if (is_array($strategies)) {
                    $summary['top_topics_strategies'] = $strategies;
                }
            } catch (\Throwable $e) {
                $notes = is_array($notes) ? $notes : [];
                $notes['top_strategy_error'] = $e->getMessage();
                $this->report->notes = $notes;
                $this->report->save();
            }

            // Per-topic long AI insights (~1000 words each) for the same top topics
            try {
                $perTopicInsights = [];
                foreach ($topTopics as $topic) {
                    // collect up to N representative snippets for this topic from per_chat_sample
                    $examples = [];
                    $cap = 6;
                    foreach ($per_chat_sample as $row) {
                        if (($row['category'] ?? null) === ($topic['key'] ?? $topic['label'])) {
                            $examples[] = $row['snippet'] ?? '';
                            if (count($examples) >= $cap) break;
                        }
                    }
                    $insightText = $ai->generatePerTopicInsight($topic, $aggText, $categoryLabels, $examples);
                    if ($insightText) {
                        $perTopicInsights[$topic['key']] = $insightText;
                    }
                }
                if (!empty($perTopicInsights)) {
                    $summary['per_topic_insights'] = $perTopicInsights;
                }
            } catch (\Throwable $e) {
                $notes = is_array($notes) ? $notes : [];
                $notes['per_topic_insights_error'] = $e->getMessage();
                $this->report->notes = $notes;
                $this->report->save();
            }

            // Generate a more comprehensive ~1000-word analysis
            try {
                $derivedForDetail = [
                    'categories' => $catsArr,
                    'sentiments' => $summary['sentiments'] ?? $sentiments,
                    'common_issues' => $summary['common_issues'] ?? array_map(fn($k, $v) => ['text' => $k, 'count' => $v], array_keys($commonIssues), $commonIssues),
                ];
                $detailedText = $ai->generateDetailedAnalysis($aggText, $categoryLabels, $derivedForDetail);
                if ($detailedText) {
                    $summary['detailed_analysis'] = $detailedText;
                }
            } catch (\Throwable $e) {
                $notes = is_array($notes) ? $notes : [];
                $notes['detailed_error'] = $e->getMessage();
                $this->report->notes = $notes;
                $this->report->save();
            }

            $this->report->update(['summary_json' => $summary, 'status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error('Analytics job failed: ' . $e->getMessage());
            $this->report->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }
}
