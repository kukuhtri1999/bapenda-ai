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

            // Prepare aggregates for context (still passed to the one-shot call)
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

            // One-shot AI call: pass rows + stats just once, expect full JSON result
            $aggText .= "\n\nFINAL_CATEGORY_COUNTS:\n";
            foreach ($categoryCounts as $cat => $c) {
                $aggText .= "- {$cat}: {$c}\n";
            }
            // Build rows payload for one-shot call
            $rawRows = [];
            foreach ($messages as $m) {
                $rawRows[] = [
                    'text' => (string) $m->content,
                    'topic' => (string) ($m->topic ?? ''),
                    'sentiment' => (string) ($m->sentiment ?? ''),
                ];
            }
            $oneShot = $ai->generateOneShotAnalytics($rawRows, [
                'date_range' => [$start, $end],
                'category_counts' => $categoryCounts,
                'sentiments' => $sentiments,
                'geo_counts' => $geo,
                'common_issues' => $commonIssues,
            ], $categoryLabels);
            $summary = is_array($oneShot) ? $oneShot : [
                'sentiments' => $sentiments,
                'geo_counts' => $geo,
                'common_issues' => array_map(fn($k, $v) => ['text' => $k, 'count' => $v], array_keys($commonIssues), $commonIssues),
            ];
            try {
                \Illuminate\Support\Facades\Log::info('RunChatAnalytics one-shot result', [
                    'report_id' => $this->report->id,
                    'has_combined' => !empty($summary['combined_top_insight']),
                    'recs' => is_array($summary['recommendations'] ?? null) ? count($summary['recommendations']) : 0,
                    'recs_detailed' => is_array($summary['recommendations_detailed'] ?? null) ? count($summary['recommendations_detailed']) : 0,
                ]);
            } catch (\Throwable $t) {
            }

            // If one-shot missed key fields, try a basic generator to fill them
            $needsInsight = empty($summary['combined_top_insight']);
            $needsRecs = empty($summary['recommendations']) || !is_array($summary['recommendations']);
            $needsRecsDet = empty($summary['recommendations_detailed']) || !is_array($summary['recommendations_detailed']);
            if ($needsInsight || $needsRecs || $needsRecsDet) {
                try {
                    $rawRowsBasic = [];
                    foreach ($messages as $m) {
                        $rawRowsBasic[] = [
                            'text' => (string) $m->content,
                            'topic' => (string) ($m->topic ?? ''),
                            'sentiment' => (string) ($m->sentiment ?? ''),
                        ];
                    }
                    $basic = $ai->generateBasicInsightAndRecommendations($rawRowsBasic, [
                        'date_range' => [$start, $end],
                        'category_counts' => $categoryCounts,
                        'sentiments' => $sentiments,
                        'geo_counts' => $geo,
                        'common_issues' => $commonIssues,
                    ]);
                    if (is_array($basic)) {
                        if ($needsInsight && !empty($basic['insight_long'])) {
                            $summary['combined_top_insight'] = $basic['insight_long'];
                        }
                        if (empty($summary['insight_summary']) && !empty($basic['insight_summary'])) {
                            $summary['insight_summary'] = $basic['insight_summary'];
                        }
                        if ($needsRecs && !empty($basic['recommendations']) && is_array($basic['recommendations'])) {
                            $summary['recommendations'] = $basic['recommendations'];
                        }
                        if ($needsRecsDet && !empty($basic['recommendations_detailed']) && is_array($basic['recommendations_detailed'])) {
                            $summary['recommendations_detailed'] = $basic['recommendations_detailed'];
                        }
                        try {
                            \Illuminate\Support\Facades\Log::info('Merged basic AI output', [
                                'report_id' => $this->report->id,
                                'has_combined' => !empty($summary['combined_top_insight']),
                                'recs' => is_array($summary['recommendations'] ?? null) ? count($summary['recommendations']) : 0,
                                'recs_detailed' => is_array($summary['recommendations_detailed'] ?? null) ? count($summary['recommendations_detailed']) : 0,
                            ]);
                        } catch (\Throwable $t) {
                        }
                    }

                    // Still missing long insight? Generate a text-only narrative as last resort
                    if (empty($summary['combined_top_insight'])) {
                        // Build quick topTopics from current categoryCounts (top 2)
                        $tmp = [];
                        foreach ($categoryCounts as $name => $c) {
                            if ($c > 0) {
                                $tmp[] = ['key' => $name, 'label' => $categoryLabels[$name] ?? $name, 'count' => $c];
                            }
                        }
                        usort($tmp, fn($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));
                        $topTopicsQuick = array_slice($tmp, 0, 2);
                        try {
                            $long = $ai->generateSingleInsightDocument($rawRowsBasic, [
                                'date_range' => [$start, $end],
                                'category_counts' => $categoryCounts,
                                'sentiments' => $sentiments,
                                'geo_counts' => $geo,
                                'common_issues' => $commonIssues,
                            ], $topTopicsQuick);
                            if (is_string($long) && trim($long) !== '') {
                                $summary['combined_top_insight'] = $long;
                                try {
                                    \Illuminate\Support\Facades\Log::info('Filled combined_top_insight via text-only fallback', ['report_id' => $this->report->id, 'len' => strlen($long)]);
                                } catch (\Throwable $t) {
                                }
                            }
                        } catch (\Throwable $e) {
                            try {
                                \Illuminate\Support\Facades\Log::warning('Text-only insight fallback failed: ' . $e->getMessage());
                            } catch (\Throwable $t) {
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore; we will proceed with whatever we have
                    try {
                        \Illuminate\Support\Facades\Log::warning('Basic generator failed: ' . $e->getMessage());
                    } catch (\Throwable $t) {
                    }
                }
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

            // Recommendations come only from one-shot result (if present)

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

            // Skip separate combined insight generation; rely on one-shot


            // Skip per-topic long insights to keep single-call approach

            // Skip extra detailed analysis call; one-shot already returns main content

            // Nothing further; if combined_top_insight missing, UI will show waiting text

            // Persist both summary_json and dedicated top-level columns for quick access
            $this->report->summary_json = $summary;
            if (isset($summary['combined_top_insight'])) $this->report->combined_top_insight = $summary['combined_top_insight'];
            if (isset($summary['insight_summary'])) $this->report->insight_summary = $summary['insight_summary'];
            if (isset($summary['recommendations']) && is_array($summary['recommendations'])) $this->report->recommendations = $summary['recommendations'];
            if (isset($summary['recommendations_detailed']) && is_array($summary['recommendations_detailed'])) $this->report->recommendations_detailed = $summary['recommendations_detailed'];
            $this->report->status = 'completed';
            $this->report->save();

            try {
                \Illuminate\Support\Facades\Log::info('Report saved with AI fields', [
                    'report_id' => $this->report->id,
                    'combined_len' => strlen((string)$this->report->combined_top_insight),
                    'recs' => is_array($this->report->recommendations ?? null) ? count($this->report->recommendations) : 0,
                    'recs_detailed' => is_array($this->report->recommendations_detailed ?? null) ? count($this->report->recommendations_detailed) : 0,
                ]);
            } catch (\Throwable $t) {
            }
        } catch (\Throwable $e) {
            Log::error('Analytics job failed: ' . $e->getMessage());
            $this->report->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }
}
