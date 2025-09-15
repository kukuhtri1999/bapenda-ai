<?php

namespace App\Services;

use OpenAI;
use OpenAI\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\KnowledgeBase;
use Exception;
use Illuminate\Support\Str;

class OpenAIService
{
    private Client $client;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $defaultTimeout;
    private bool $debug;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->model = config('services.openai.model', 'gpt-5-mini');
        $this->maxTokens = config('services.openai.max_tokens', 1500);
        $this->temperature = config('services.openai.temperature', 0.7);
        // Default timeout (seconds). Keep constant here to avoid config/env coupling at boot time.
        $this->defaultTimeout = 30;
        $this->debug = (bool) (config('app.debug') || env('RAG_DEBUG', false));
    }

    private function getAnalyticsModel(): string
    {
        $m = config('services.openai.analytics_model');
        return is_string($m) && strlen($m) > 0 ? $m : $this->model;
    }

    /**
     * Retry wrapper for API calls with simple exponential backoff.
     * Accepts a callable that performs the API call and returns the response.
     */
    private function retryRequest(callable $fn, int $attempts = 3, int $baseDelay = 500)
    {
        $tries = 0;
        $lastException = null;
        while ($tries < $attempts) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $lastException = $e;
                $tries++;
                Log::warning('OpenAIService::retryRequest attempt ' . $tries . ' failed: ' . $e->getMessage());
                if ($tries >= $attempts) break;
                // exponential backoff with jitter (milliseconds)
                $delay = (int) ($baseDelay * pow(2, max(0, $tries - 1)));
                $jitter = function_exists('random_int') ? random_int(0, (int) ($baseDelay * 0.3)) : 0;
                usleep(($delay + $jitter) * 1000);
            }
        }
        if ($lastException) throw $lastException;
        return null;
    }
    /**
     * Analyze an array of conversation transcripts using GPT-5-mini with a strict JSON schema.
     * Input: array of strings (transcripts)
     * Output: ['success' => bool, 'message' => string, 'json' => array|null]
     */
    public function analyzeConversations(array $transcripts): array
    {
        try {
            $payload = "You are an analytics assistant. Given these conversation transcripts, return ONLY a JSON object (no surrounding text) with the following keys:\n- topics: array of {label: string, count: int}\n- sentiments: {positive:int, neutral:int, negative:int}\n- geo_counts: object mapping city->count (if city info unavailable return empty object)\n- common_issues: array of {text:string, count:int}\n- recommendations: array of strings\n\nTranscripts:\n" . implode("\n---\n", array_slice($transcripts, 0, 100));

            $model = $this->getAnalyticsModel();

            $call = function () use ($model, $payload) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful data analysis assistant.'],
                        ['role' => 'user', 'content' => $payload],
                    ],
                    // reduce token budget to cut cost and latency for analytics
                    'max_completion_tokens' => 512,
                ]);
            };
            $response = $this->retryRequest($call);

            $text = trim($response->choices[0]->message->content ?? '');
            $json = null;
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
            }

            return ['success' => true, 'message' => $text, 'json' => $json];
        } catch (Exception $e) {
            Log::error('AI analytics error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'json' => null];
        }
    }

    /**
     * Generate a 500-600 word Indonesian insight summary and strategic recommendations
     * using the aggregated analytics text provided.
     * Returns plain text (string) or null on failure.
     */
    public function generateInsightSummary(string $aggText, array $categoryLabels = []): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            $labelsText = "Categories and labels:\n";
            foreach ($categoryLabels as $k => $lbl) {
                $labelsText .= "- {$k}: {$lbl}\n";
            }

            $instruction = "Anda adalah analis transformasi digital untuk layanan publik (Samsat/Bapenda). Berikan sebuah INSIGHT SUMMARY dalam Bahasa Indonesia sepanjang sekitar 500 sampai 600 kata yang merangkum temuan dari data agregat berikut, menyoroti prioritas strategis untuk era digital modern, langkah aksi konkrit (short-term dan mid-term), metrik keberhasilan yang disarankan, dan rekomendasi kanal/teknologi untuk implementasi (mis. mobile apps, pembayaran elektronik, RAG/knowledge base, social media, automation). Jangan sertakan JSON atau meta; balas hanya teks naratif dalam Bahasa Indonesia.";

            $userContent = $instruction . "\n\n" . $labelsText . "\nAGGREGATES:\n" . $aggText;

            $callParams = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior public sector digital transformation analyst, concise and practical.'],
                    ['role' => 'user', 'content' => $userContent]
                ],
                'max_completion_tokens' => min(1600, $this->maxTokens),
            ];
            // omit temperature for gpt-5-mini
            $response = $this->client->chat()->create($callParams);
            $text = trim($response->choices[0]->message->content ?? '');
            // log usage if present
            try {
                if (isset($response->usage)) Log::info('OpenAIService::generateInsightSummary - usage', (array)$response->usage);
            } catch (\Throwable $t) {
            }
            return $text ?: null;
        } catch (Exception $e) {
            Log::error('AI generateInsightSummary error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a detailed Indonesian analysis (~1000 words) with actions & strategy.
     * Includes prioritized roadmap, risks/mitigations, channels/technologies, and KPIs.
     * Returns plain text or null on failure.
     */
    public function generateDetailedAnalysis(string $aggText, array $categoryLabels = [], ?array $derived = null): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            $labelsText = "Categories and labels:\n";
            foreach ($categoryLabels as $k => $lbl) {
                $labelsText .= "- {$k}: {$lbl}\n";
            }

            $derivedJson = $derived ? json_encode($derived, JSON_UNESCAPED_UNICODE) : '{}';

            $instruction = "Anda bertindak sebagai konsultan strategi layanan publik digital untuk Samsat/Bapenda. Buat ANALISIS TERPERINCI sekitar 900-1100 kata (target ~1000 kata) dalam Bahasa Indonesia berdasarkan data agregat dan ringkasan berikut. Sertakan: (1) temuan kunci yang didukung data (kategori, tren, sentimen, isu umum, wilayah jika ada), (2) rekomendasi aksi prioritas jangka pendek (0-3 bulan) dan menengah (3-12 bulan) yang spesifik dan dapat dieksekusi, (3) strategi kanal/teknologi (mobile/web, pembayaran elektronik, chatbot/RAG, media sosial, loket digital, integrasi bank/VA), (4) metrik/KPI yang dapat dipantau, (5) risiko & mitigasi, (6) dampak yang diharapkan pada kepuasan wajib pajak dan efisiensi operasional. Tulis naratif yang mengalir, tanpa poin numerik berlebihan, namun boleh menggunakan bullet seperlunya. Hindari JSON atau markup; balas hanya teks naratif.";

            $userContent = $instruction
                . "\n\n" . $labelsText
                . "\nAGGREGATES (ringkas):\n" . $aggText
                . "\n\nDERIVED_JSON (opsional):\n" . $derivedJson . "\n";

            $callParams = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior digital strategy consultant for public services.'],
                    ['role' => 'user', 'content' => $userContent]
                ],
                'max_completion_tokens' => min(2000, max(1400, $this->maxTokens)),
            ];
            $response = $this->client->chat()->create($callParams);
            $text = trim($response->choices[0]->message->content ?? '');
            try {
                if (isset($response->usage)) Log::info('OpenAIService::generateDetailedAnalysis - usage', (array)$response->usage);
            } catch (\Throwable $t) {
            }
            return $text ?: null;
        } catch (Exception $e) {
            Log::error('AI generateDetailedAnalysis error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate dynamic AI recommendations based on aggregates and category counts.
     * Returns ['recommendations'=>array, 'recommendations_detailed'=>array] or null on failure.
     */
    public function generateRecommendations(array $categoryCounts, array $commonIssues, array $sentiments, array $categoryLabels = []): ?array
    {
        try {
            $model = $this->getAnalyticsModel();

            $cats = [];
            foreach ($categoryCounts as $k => $v) {
                $cats[] = $k . ':' . (int)$v;
            }
            $issues = [];
            foreach ($commonIssues as $k => $v) {
                $issues[] = $k . ':' . (int)$v;
            }
            $sent = [];
            foreach ($sentiments as $k => $v) {
                $sent[] = $k . ':' . (int)$v;
            }

            $labelsText = [];
            foreach ($categoryLabels as $k => $lbl) {
                $labelsText[] = $k . ' = ' . $lbl;
            }

            $instruction = "Kembalikan HANYA JSON valid (tanpa teks lain) dalam Bahasa Indonesia dengan struktur: {\n  \"recommendations\": [string],\n  \"recommendations_detailed\": [{\n    \"category\": string,\n    \"label\": string,\n    \"count\": number,\n    \"rationale\": string,\n    \"actions\": [string],\n    \"priority\": \"low\"|\"medium\"|\"high\",\n    \"effort_estimate\": string\n  }]\n}";

            $payload = [
                'labels' => implode("; ", $labelsText),
                'category_counts' => implode(", ", $cats),
                'common_issues' => implode(", ", $issues),
                'sentiments' => implode(", ", $sent),
            ];

            $messages = [
                ['role' => 'system', 'content' => 'You are an analytics recommender that outputs strict JSON only.'],
                ['role' => 'user', 'content' => $instruction . "\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)]
            ];

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 900,
            ]);
            $text = trim($response->choices[0]->message->content ?? '');

            // Extract JSON object from response
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
                if (is_array($json)) return $json;
            }

            // Repair attempt: ask model to return JSON only
            try {
                $repair = [
                    ['role' => 'system', 'content' => 'You are a strict JSON-only assistant.'],
                    ['role' => 'user', 'content' => 'Model returned non-JSON. PLEASE RETURN ONLY a VALID JSON object matching the previously requested schema (no explanatory text). Output must be in Bahasa Indonesia.']
                ];
                $resp2 = $this->client->chat()->create([
                    'model' => $model,
                    'messages' => $repair,
                    'max_completion_tokens' => 600,
                ]);
                $text2 = trim($resp2->choices[0]->message->content ?? '');
                $s2 = strpos($text2, '{');
                $e2 = strrpos($text2, '}');
                if ($s2 !== false && $e2 !== false && $e2 > $s2) {
                    $maybe2 = substr($text2, $s2, $e2 - $s2 + 1);
                    $json2 = json_decode($maybe2, true);
                    if (is_array($json2)) return $json2;
                }
            } catch (\Throwable $t) {
                Log::warning('generateRecommendations repair call failed: ' . $t->getMessage());
            }

            return null;
        } catch (Exception $e) {
            Log::error('generateRecommendations error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate super-detailed strategy actions for the top topics.
     * Expects $topTopics = array of ['key'=>..., 'label'=>..., 'count'=>int]
     * Returns array keyed by topic key with detailed strategy objects.
     */
    public function generateTopTopicStrategies(array $topTopics, string $aggText, array $categoryLabels = []): ?array
    {
        try {
            if (empty($topTopics)) return null;
            $model = $this->getAnalyticsModel();

            $topicsBrief = [];
            foreach ($topTopics as $t) {
                $topicsBrief[] = ['key' => ($t['key'] ?? $t['name'] ?? ''), 'label' => ($t['label'] ?? ''), 'count' => (int)($t['count'] ?? 0)];
            }

            $wordGoal = (int) config('analytics.strategy_word_goal', 950);
            $instruction = "Kembalikan HANYA JSON valid (tanpa teks tambahan). Anda akan menerima hingga TOP " . count($topicsBrief) . " topik (maks. 3). Untuk setiap topik, buat ANALISIS STRATEGIS MENDALAM dalam Bahasa Indonesia. Fokus pada langkah yang dapat dieksekusi oleh instansi publik (Bapenda/Samsat) dan jelaskan teknisnya.\n\nStruktur JSON WAJIB persis:\n{ \"strategies\": [ {\n  \"topic_key\": string,\n  \"label\": string,\n  \"count\": number,\n  \"short_summary\": string (2-3 kalimat yang sangat ringkas),\n  \"detailed_strategy\": string (~" . $wordGoal . " kata, ±10%) yang menjelaskan: latar masalah, tujuan/indikator keberhasilan, rancangan solusi digital & operasional (alur sistem, integrasi, SOP, SDM), rencana komunikasi/edukasi (online & offline), risiko & mitigasi, serta tata kelola (governance/ownership). Tulis dalam paragraf-paragraf pendek 3-6 kalimat per paragraf agar mudah dibaca manusia.\n  \"implementation_steps\": [ {\n    \"title\": string (aksi konkrit, imperative),\n    \"description\": string (60-120 kata, paparkan detail teknis: API/endpoint, DB/log, otomasi job, SOP front-office, materi sosialisasi),\n    \"example\": string (contoh nyata yang relevan; bisa sebut format dokumen, pesan notifikasi, atau contoh konten),\n    \"estimated_time\": string (mis. '1-2 minggu', '2-4 minggu', '1-2 bulan'),\n    \"effort\": \"low\"|\"medium\"|\"high\"\n  } ],\n  \"suggested_owners\": [string],\n  \"timeline\": string (mis. \"0-3 bulan\", \"3-12 bulan\"),\n  \"kpis\": [string],\n  \"estimated_cost\": string pendek,\n  \"dependencies\": [string]\n} ] }\n\nKetentuan penting:\n- Untuk setiap topik, buat MINIMAL 12 langkah pada implementation_steps, usahakan 12–16 langkah jika relevan.\n- Gunakan konteks AGGREGATES untuk rasional & prioritas.\n- Hindari angka fiktif; gunakan rentang waktu umum.\n- Balas HANYA JSON.\n";

            $payload = [
                'top_topics' => $topicsBrief,
                'aggregates' => $aggText
            ];

            $messages = [
                ['role' => 'system', 'content' => 'You are a senior public sector digital strategy consultant. Output strict JSON only.'],
                ['role' => 'user', 'content' => $instruction . "\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)]
            ];

            $call = function () use ($model, $messages) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => $messages,
                    'max_completion_tokens' => 5200,
                    'response_format' => ['type' => 'json_object'],
                ]);
            };

            $response = $this->retryRequest($call);
            $text = trim($response->choices[0]->message->content ?? '');
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
                if (is_array($json) && isset($json['strategies'])) {
                    // key by topic_key
                    $out = [];
                    foreach ($json['strategies'] as $s) {
                        if (isset($s['topic_key'])) $out[$s['topic_key']] = $s;
                    }
                    return $out;
                }
            }
            // Repair: ask to resend valid JSON only
            try {
                $resp2 = $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Output JSON only.'],
                        ['role' => 'user', 'content' => 'Ulangi dan balas HANYA JSON valid sesuai skema strategi yang diminta.'],
                    ],
                    'max_completion_tokens' => 3600,
                    'response_format' => ['type' => 'json_object'],
                ]);
                $txt2 = trim($resp2->choices[0]->message->content ?? '');
                $s2 = strpos($txt2, '{');
                $e2 = strrpos($txt2, '}');
                if ($s2 !== false && $e2 !== false && $e2 > $s2) {
                    $maybe2 = substr($txt2, $s2, $e2 - $s2 + 1);
                    $json2 = json_decode($maybe2, true);
                    if (is_array($json2) && isset($json2['strategies'])) {
                        $out = [];
                        foreach ($json2['strategies'] as $s) {
                            if (isset($s['topic_key'])) $out[$s['topic_key']] = $s;
                        }
                        return $out;
                    }
                }
            } catch (\Throwable $t) {
            }
            return null;
        } catch (Exception $e) {
            Log::error('generateTopTopicStrategies error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate ~1000-word Indonesian insight for a single topic (problem, digital solution, roadmap, KPIs, risks, comms plan).
     * $topic = ['key'=>..., 'label'=>..., 'count'=>int]
     * $examples = array of representative snippets for this topic
     */
    public function generatePerTopicInsight(array $topic, string $aggText, array $categoryLabels = [], array $examples = []): ?string
    {
        try {
            $model = $this->getAnalyticsModel();
            $wordGoal = (int) config('analytics.per_topic_word_goal', 800);

            $topicBrief = json_encode([
                'key' => $topic['key'] ?? ($topic['name'] ?? ''),
                'label' => $topic['label'] ?? ($topic['name'] ?? ''),
                'count' => (int)($topic['count'] ?? 0),
            ], JSON_UNESCAPED_UNICODE);

            $examplesText = '';
            if (!empty($examples)) {
                $cap = min(8, count($examples));
                $sel = array_slice($examples, 0, $cap);
                $lines = [];
                foreach ($sel as $sn) {
                    $lines[] = '- ' . mb_substr($sn, 0, 280);
                }
                $examplesText = "\nREPRESENTATIVE_SNIPPETS (truncated):\n" . implode("\n", $lines) . "\n";
            }

            $instruction = "Tulis INSIGHT PER TOPIK sekitar ~" . $wordGoal . " kata (±10%) dalam Bahasa Indonesia untuk topik di bawah. Gunakan subjudul tegas berikut (dengan urutan yang sama) agar mudah dibaca dan dieksekusi:\n\n" .
                "1. Latar Permasalahan\n" .
                "2. Akar Penyebab & Analisis Pola Percakapan\n" .
                "3. Solusi Digital yang Disarankan (teknis & operasional)\n" .
                "4. Roadmap Aksi 0–3 Bulan (langkah teknis rinci)\n" .
                "5. Roadmap Aksi 3–12 Bulan (langkah teknis rinci)\n" .
                "6. Strategi Komunikasi & Edukasi (channel, pesan, kampanye)\n" .
                "7. Metrik/KPI yang Dipantau\n" .
                "8. Risiko Utama & Mitigasi\n" .
                "9. Dampak yang Diharapkan\n\n" .
                "Catatan penulisan: konkret, kaya detail, sebut contoh integrasi (mis. VA bank, webhook, endpoint), automasi (job/scheduler), dan tata kelola. Balas HANYA teks naratif dengan subjudul di atas; jangan gunakan JSON atau markup lain.";

            $content = $instruction
                . "\nTOPIC:\n" . $topicBrief
                . "\nAGGREGATES (ringkas):\n" . $aggText
                . $examplesText;

            $call = function () use ($model, $content) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a senior public-sector digital strategist writing long-form insights (~800 words).'],
                        ['role' => 'user', 'content' => $content],
                    ],
                    'max_completion_tokens' => 1800,
                ]);
            };
            $response = $this->retryRequest($call);
            $text = trim($response->choices[0]->message->content ?? '');
            return $text ?: null;
        } catch (Exception $e) {
            Log::error('generatePerTopicInsight error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simple, single-call generator: given raw rows [ {text, topic, sentiment} ],
     * return strict JSON with long insight and recommendations.
     * Output shape:
     * {
     *   "insight_long": string (~1400-1600 words, Indonesian),
     *   "insight_summary": string (~300-500 words),
     *   "recommendations": [string],
     *   "recommendations_detailed": [
     *     { "category": string, "label": string, "count": number|null,
     *       "rationale": string, "actions": [string],
     *       "priority": "low"|"medium"|"high", "effort_estimate": string }
     *   ]
     * }
     */
    public function generateBasicInsightAndRecommendations(array $rows, array $stats = []): ?array
    {
        try {
            $model = $this->getAnalyticsModel();

            // Light clipping to avoid token explosion
            $maxRows = 150; // adjustable via env/config if needed
            if (count($rows) > $maxRows) {
                $rows = array_slice($rows, 0, $maxRows);
            }
            // Ensure fields are compact
            $rows = array_values(array_map(function ($r) {
                return [
                    'text' => isset($r['text']) ? mb_substr((string)$r['text'], 0, 220) : '',
                    'topic' => (string)($r['topic'] ?? ''),
                    'sentiment' => (string)($r['sentiment'] ?? ''),
                ];
            }, $rows));

            $payload = [
                'date_range' => $stats['date_range'] ?? null,
                'category_counts' => $stats['category_counts'] ?? new \stdClass(),
                'sentiments' => $stats['sentiments'] ?? new \stdClass(),
                'geo_counts' => $stats['geo_counts'] ?? new \stdClass(),
                'common_issues' => $stats['common_issues'] ?? [],
                'messages' => $rows,
            ];

            $schema = "Kembalikan HANYA JSON valid (tanpa teks lain) dalam Bahasa Indonesia dengan struktur persis: {\n  \"insight_long\": string (~1400-1600 kata),\n  \"insight_summary\": string (300-500 kata),\n  \"recommendations\": [string],\n  \"recommendations_detailed\": [{\n    \"category\": string,\n    \"label\": string,\n    \"count\": number|null,\n    \"rationale\": string,\n    \"actions\": [string],\n    \"priority\": \"low\"|\"medium\"|\"high\",\n    \"effort_estimate\": string\n  }]\n}.\n\nInstruksi tambahan:\n- Analisislah berdasarkan \"messages\" (tiap baris mengandung topik & sentimen).\n- Gunakan ringkasan statistik pada category_counts, sentiments, geo_counts, dan common_issues untuk menyusun narasi.\n- Tulis semua konten dalam Bahasa Indonesia yang operasional dan actionable untuk layanan publik (Bapenda/Samsat).\n- Jangan gunakan angka palsu. Jika count tidak diketahui, biarkan null.\n- Balas HANYA JSON sesuai skema di atas.";

            $messages = [
                ['role' => 'system', 'content' => 'You are a senior Indonesian public-service analytics writer. Output JSON only.'],
                ['role' => 'user', 'content' => $schema . "\n\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)],
            ];

            $call = function () use ($model, $messages) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => $messages,
                    'max_completion_tokens' => 3000,
                    'response_format' => ['type' => 'json_object'],
                ]);
            };
            $response = $this->retryRequest($call);
            $text = trim($response->choices[0]->message->content ?? '');

            // Extract JSON object from response
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
                if (is_array($json) && isset($json['insight_long'])) {
                    return $json;
                }
            }

            // Repair attempt: ask to return JSON only
            $repair = "Balas ulang dengan HANYA JSON valid sesuai skema (tanpa kata pengantar).";
            $resp2 = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Output JSON only.'],
                    ['role' => 'user', 'content' => $repair],
                ],
                'max_completion_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);
            $txt2 = trim($resp2->choices[0]->message->content ?? '');
            $s2 = strpos($txt2, '{');
            $e2 = strrpos($txt2, '}');
            if ($s2 !== false && $e2 !== false && $e2 > $s2) {
                $maybe2 = substr($txt2, $s2, $e2 - $s2 + 1);
                $json2 = json_decode($maybe2, true);
                if (is_array($json2) && isset($json2['insight_long'])) {
                    return $json2;
                }
            }
            return null;
        } catch (Exception $e) {
            Log::error('generateBasicInsightAndRecommendations error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate ONE long Indonesian insight document (~1200–1600 words), plain text only.
     * Inputs:
     *  - $rows: [{text, topic, sentiment}] (clipped internally)
     *  - $stats: ['date_range', 'category_counts', 'sentiments', 'geo_counts', 'common_issues']
     *  - $topTopics: [{key,label,count}] — top-2 preferably
     */
    public function generateSingleInsightDocument(array $rows, array $stats, array $topTopics): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            $maxRows = 150;
            if (count($rows) > $maxRows) $rows = array_slice($rows, 0, $maxRows);
            $rows = array_values(array_map(function ($r) {
                return [
                    'text' => isset($r['text']) ? mb_substr((string)$r['text'], 0, 240) : '',
                    'topic' => (string)($r['topic'] ?? ''),
                    'sentiment' => (string)($r['sentiment'] ?? ''),
                ];
            }, $rows));

            $payload = [
                'top_topics' => array_values(array_map(fn($t) => [
                    'key' => $t['key'] ?? ($t['label'] ?? ''),
                    'label' => $t['label'] ?? ($t['key'] ?? ''),
                    'count' => (int)($t['count'] ?? 0),
                ], array_slice($topTopics, 0, 2))),
                'stats' => [
                    'category_counts' => $stats['category_counts'] ?? new \stdClass(),
                    'sentiments' => $stats['sentiments'] ?? new \stdClass(),
                    'geo_counts' => $stats['geo_counts'] ?? new \stdClass(),
                    'common_issues' => $stats['common_issues'] ?? [],
                    'date_range' => $stats['date_range'] ?? null,
                ],
                'messages' => $rows,
            ];

            $topicPair = implode(' & ', array_values(array_map(fn($t) => ($t['label'] ?? $t['key'] ?? ''), array_slice($topTopics, 0, 2))));

            $instruction = "Tulis SATU dokumen insight panjang (~1200–1600 kata) dalam Bahasa Indonesia tentang layanan Bapenda/Samsat. Gaya seperti dokumen perencanaan (rapi, to the point, actionable). Gunakan subjudul berikut, urut, tanpa nomor: \n\n" .
                "Ringkasan Eksekutif\n\n" .
                "Konteks & Data Singkat\n\n" .
                "Dua Topik Utama (" . $topicPair . ")\n\n" .
                "Analisis Dampak terhadap Layanan\n\n" .
                "Strategi Terpadu\n\n" .
                "Rencana 0–3 Bulan\n\n" .
                "Rencana 3–12 Bulan\n\n" .
                "Komunikasi & Edukasi (Digital + Offline)\n\n" .
                "KPI Kunci\n\n" .
                "Risiko & Mitigasi\n\n" .
                "Penutup\n\n" .
                "Instruksi: sebut dua topik di setiap bagian yang relevan; berikan langkah-langkah teknis/operasional yang realistis; hindari angka fiktif; balas HANYA teks naratif (tanpa JSON/markdown).";

            $messages = [
                ['role' => 'system', 'content' => 'You are a senior Indonesian public-sector strategist. Output plain text only.'],
                ['role' => 'user', 'content' => $instruction . "\n\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)],
            ];

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 2600,
            ]);
            $text = trim($response->choices[0]->message->content ?? '');
            return $text ?: null;
        } catch (Exception $e) {
            Log::error('generateSingleInsightDocument error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ultra-simple path: build a bullet list like
     *  - pertanyaan … — sentiment NEGATIVE — tanya_pajak
     * then append a main analysis prompt and request a single long document.
     * Returns plain Indonesian text.
     */
    public function generateBulletListInsight(array $rows, ?string $customMainPrompt = null): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            // Cap to avoid token overflow
            $maxRows = 300;
            $snippetLen = 160;
            if (count($rows) > $maxRows) $rows = array_slice($rows, 0, $maxRows);

            $lines = [];
            foreach ($rows as $idx => $r) {
                $text = trim((string)($r['text'] ?? ''));
                if ($text !== '') $text = mb_substr(preg_replace('/\s+/u', ' ', $text), 0, $snippetLen);
                $sent = strtoupper((string)($r['sentiment'] ?? ''));
                $topic = (string)($r['topic'] ?? '');
                $label = $topic !== '' ? $topic : 'lain_lain';
                $lines[] = '- ' . ($text !== '' ? $text : 'pertanyaan ' . ($idx + 1)) . ' — sentiment ' . ($sent ?: 'NEUTRAL') . ' — ' . $label;
            }
            $header = "ini adalah data wajib pajak yang akan saya analisis :\n" . implode("\n", $lines);

            $defaultPrompt = "Tulis SATU dokumen insight panjang (~1200–1600 kata) dalam Bahasa Indonesia untuk layanan Bapenda/Samsat. Gaya ringkas namun operasional, seperti dokumen perencanaan. Gunakan subjudul tanpa penomoran: Ringkasan Eksekutif; Konteks & Data Singkat; Temuan & Pola; Dampak pada Layanan; Strategi Terpadu; Rencana 0–3 Bulan; Rencana 3–12 Bulan; Komunikasi & Edukasi (Digital + Offline); KPI Kunci; Risiko & Mitigasi; Penutup. Berikan langkah-langkah nyata dan hindari angka fiktif. Balas HANYA teks naratif (tanpa JSON/markdown).";
            $mainPrompt = $customMainPrompt && trim($customMainPrompt) !== '' ? $customMainPrompt : $defaultPrompt;

            $content = $header . "\n\n" . $mainPrompt;

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior Indonesian public-sector strategist. Output plain text only.'],
                    ['role' => 'user', 'content' => $content],
                ],
                'max_completion_tokens' => 2600,
            ]);
            $text = trim($response->choices[0]->message->content ?? '');
            try {
                Log::info('OpenAIService::generateBulletListInsight done', [
                    'len' => strlen($text),
                    'rows' => count($rows ?? []),
                ]);
            } catch (\Throwable $t) {
                // ignore logging issues
            }
            return $text ?: null;
        } catch (Exception $e) {
            Log::error('generateBulletListInsight error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * One-shot analytics: single AI call returns the full report JSON we need for UI and storage.
     * Required JSON keys: combined_top_insight, insight_summary, recommendations, recommendations_detailed,
     *                     categories, sentiments, geo_counts, common_issues
     */
    public function generateOneShotAnalytics(array $rows, array $stats = [], array $categoryLabels = []): ?array
    {
        try {
            $model = $this->getAnalyticsModel();

            // Clip and normalize rows
            $maxRows = 220;
            $snippetLen = 220;
            if (count($rows) > $maxRows) $rows = array_slice($rows, 0, $maxRows);
            $rows = array_values(array_map(function ($r) use ($snippetLen) {
                $txt = isset($r['text']) ? preg_replace('/\s+/u', ' ', (string)$r['text']) : '';
                return [
                    'text' => mb_substr($txt, 0, $snippetLen),
                    'topic' => (string)($r['topic'] ?? ''),
                    'sentiment' => (string)($r['sentiment'] ?? ''),
                ];
            }, $rows));

            $payload = [
                'date_range' => $stats['date_range'] ?? null,
                'category_counts' => $stats['category_counts'] ?? new \stdClass(),
                'sentiments' => $stats['sentiments'] ?? new \stdClass(),
                'geo_counts' => $stats['geo_counts'] ?? new \stdClass(),
                'common_issues' => $stats['common_issues'] ?? [],
                'category_labels' => $categoryLabels,
                'messages' => $rows,
            ];

            $schema = "Kembalikan HANYA JSON valid (tanpa teks lain) dengan struktur tepat berikut dalam Bahasa Indonesia:\n{\n  \"combined_top_insight\": string (~1200-1600 kata),\n  \"insight_summary\": string (300-500 kata),\n  \"recommendations\": [string],\n  \"recommendations_detailed\": [{\n    \"category\": string,\n    \"label\": string,\n    \"count\": number|null,\n    \"rationale\": string,\n    \"actions\": [string],\n    \"priority\": \"low\"|\"medium\"|\"high\",\n    \"effort_estimate\": string\n  }],\n  \"categories\": [{ \"name\": string, \"label\": string, \"count\": number }],\n  \"sentiments\": { \"positive\": number, \"neutral\": number, \"negative\": number },\n  \"geo_counts\": { [kota: string]: number },\n  \"common_issues\": [{ \"text\": string, \"count\": number }]\n}\nInstruksi:\n- Analisis berasal dari \"messages\" dan ringkasan statistik.\n- Gunakan label manusia dari category_labels jika tersedia.\n- Tuliskan narasi panjang operasional untuk layanan Bapenda/Samsat.\n- Balas HANYA JSON tanpa teks tambahan.";

            $messages = [
                ['role' => 'system', 'content' => 'You are a JSON-only Indonesian analytics writer for public services.'],
                ['role' => 'user', 'content' => $schema . "\n\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)],
            ];

            $call = function () use ($model, $messages) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => $messages,
                    'max_completion_tokens' => 2600,
                    'response_format' => ['type' => 'json_object'],
                ]);
            };
            $response = $this->retryRequest($call);
            $text = trim($response->choices[0]->message->content ?? '');
            try {
                Log::info('OneShot raw response', [
                    'len' => strlen($text),
                    'head' => mb_substr($text, 0, 200),
                ]);
            } catch (\Throwable $t) {
                // ignore log failures
            }

            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
                if (is_array($json)) {
                    $json = $this->normalizeOneShotReport($json);
                    if (!empty($json['combined_top_insight'])) return $json;
                }
            }

            // Repair: ask model to return JSON only
            $resp2 = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Output JSON only.'],
                    ['role' => 'user', 'content' => 'Ulangi dan balas HANYA JSON valid sesuai skema.'],
                ],
                'max_completion_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);
            $txt2 = trim($resp2->choices[0]->message->content ?? '');
            $s2 = strpos($txt2, '{');
            $e2 = strrpos($txt2, '}');
            if ($s2 !== false && $e2 !== false && $e2 > $s2) {
                $maybe2 = substr($txt2, $s2, $e2 - $s2 + 1);
                $json2 = json_decode($maybe2, true);
                if (is_array($json2)) {
                    $json2 = $this->normalizeOneShotReport($json2);
                    if (!empty($json2['combined_top_insight'])) return $json2;
                }
            }

            // As a last attempt, request recommendations only to ensure arrays are filled
            try {
                $recSchema = "Balas HANYA JSON valid: {\n  \"recommendations\": [string],\n  \"recommendations_detailed\": [{\n    \"category\": string, \"label\": string, \"count\": number|null, \"rationale\": string, \"actions\": [string], \"priority\": \"low\"|\"medium\"|\"high\", \"effort_estimate\": string\n  }]\n}\nGunakan DATA berikut untuk menyusun rekomendasi yang realistis dalam Bahasa Indonesia.";
                $resp3 = $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Output JSON only.'],
                        ['role' => 'user', 'content' => $recSchema . "\n\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)],
                    ],
                    'max_completion_tokens' => 1200,
                    'response_format' => ['type' => 'json_object'],
                ]);
                $txt3 = trim($resp3->choices[0]->message->content ?? '');
                $s3 = strpos($txt3, '{');
                $e3 = strrpos($txt3, '}');
                if ($s3 !== false && $e3 !== false && $e3 > $s3) {
                    $maybe3 = substr($txt3, $s3, $e3 - $s3 + 1);
                    $json3 = json_decode($maybe3, true);
                    if (is_array($json3)) {
                        $json3 = $this->normalizeOneShotReport(['recommendations' => $json3['recommendations'] ?? [], 'recommendations_detailed' => $json3['recommendations_detailed'] ?? []]);
                        return $json3;
                    }
                }
            } catch (\Throwable $t) {
                // ignore
            }

            return null;
        } catch (Exception $e) {
            Log::error('generateOneShotAnalytics error: ' . $e->getMessage());
            return null;
        }
    }

    private function normalizeOneShotReport(array $r): array
    {
        // Coerce types and ensure required keys exist
        $r['combined_top_insight'] = is_string($r['combined_top_insight'] ?? null)
            ? $r['combined_top_insight']
            : (is_string($r['insight_summary'] ?? null) ? $r['insight_summary'] : '');
        $r['insight_summary'] = is_string($r['insight_summary'] ?? null) ? $r['insight_summary'] : '';

        // Simple list recommendations
        if (!isset($r['recommendations']) || !is_array($r['recommendations'])) $r['recommendations'] = [];
        $r['recommendations'] = array_values(array_filter(array_map(function ($x) {
            return is_string($x) ? trim($x) : '';
        }, $r['recommendations']), function ($x) {
            return $x !== '';
        }));

        // Detailed recommendations normalization
        if (!isset($r['recommendations_detailed']) || !is_array($r['recommendations_detailed'])) {
            $r['recommendations_detailed'] = [];
        }
        $allowedPriorities = ['low', 'medium', 'high'];
        $r['recommendations_detailed'] = array_values(array_map(function ($it) use ($allowedPriorities) {
            $category = isset($it['category']) && is_string($it['category']) ? $it['category'] : '';
            $label = isset($it['label']) && is_string($it['label']) ? $it['label'] : $category;
            $count = isset($it['count']) && is_numeric($it['count']) ? (int)$it['count'] : null;
            $rationale = isset($it['rationale']) && is_string($it['rationale']) ? $it['rationale'] : '';
            $actions = isset($it['actions']) && is_array($it['actions']) ? array_values(array_filter(array_map(function ($a) {
                return is_string($a) ? trim($a) : '';
            }, $it['actions']))) : [];
            $priority = isset($it['priority']) && in_array($it['priority'], $allowedPriorities, true) ? $it['priority'] : 'medium';
            $effort = isset($it['effort_estimate']) && is_string($it['effort_estimate']) ? $it['effort_estimate'] : '';
            return [
                'category' => $category,
                'label' => $label,
                'count' => $count,
                'rationale' => $rationale,
                'actions' => $actions,
                'priority' => $priority,
                'effort_estimate' => $effort,
            ];
        }, $r['recommendations_detailed']));

        // Stats containers
        if (!isset($r['categories']) || !is_array($r['categories'])) $r['categories'] = [];
        if (!isset($r['sentiments']) || !is_array($r['sentiments'])) $r['sentiments'] = [];
        foreach (['positive', 'neutral', 'negative'] as $k) {
            if (!isset($r['sentiments'][$k]) || !is_numeric($r['sentiments'][$k])) $r['sentiments'][$k] = 0;
        }
        if (!isset($r['geo_counts']) || !is_array($r['geo_counts'])) $r['geo_counts'] = [];
        if (!isset($r['common_issues']) || !is_array($r['common_issues'])) $r['common_issues'] = [];

        try {
            Log::info('OneShot normalized', [
                'has_insight' => !empty($r['combined_top_insight']),
                'recs' => count($r['recommendations'] ?? []),
                'recs_detailed' => count($r['recommendations_detailed'] ?? []),
            ]);
        } catch (\Throwable $t) {
            // ignore
        }

        return $r;
    }

    /**
     * Generate a single combined Indonesian insight (~1500 words) that discusses the top 2 topics together.
     * Returns plain text or null on failure.
     */
    public function generateCombinedTopInsight(array $topTopics, string $aggText, array $categoryLabels = []): ?string
    {
        try {
            if (empty($topTopics)) return null;
            $model = $this->getAnalyticsModel();

            $topicsBrief = [];
            foreach ($topTopics as $t) {
                $topicsBrief[] = [
                    'key' => $t['key'] ?? ($t['name'] ?? ''),
                    'label' => $t['label'] ?? ($t['name'] ?? ''),
                    'count' => (int)($t['count'] ?? 0),
                ];
            }

            $topicsJson = json_encode($topicsBrief, JSON_UNESCAPED_UNICODE);
            $topicLabels = array_values(array_filter(array_map(fn($t) => ($t['label'] ?? $t['key'] ?? ''), $topicsBrief)));
            $topicPair = implode(' & ', array_slice($topicLabels, 0, 2));

            // Simple but effective instruction for a long combined narrative
            $instruction = "Susun narasi komprehensif (~1400–1600 kata) dalam Bahasa Indonesia yang menggabungkan dua topik teratas berikut secara terpadu: " . $topicPair . ". Gunakan subjudul: Ringkasan Eksekutif, Mengapa Dua Topik Ini Penting, Temuan Kunci, Strategi Terpadu, Rencana Aksi 0–3 Bulan, Rencana Aksi 3–12 Bulan, Rencana Komunikasi & Edukasi, KPI yang Dipantau, Risiko & Mitigasi, Dampak yang Diharapkan. Tulis mengalir, operasional, dan sebutkan kedua topik tersebut di setiap bagian. Hindari JSON; balas hanya teks naratif.";

            $messages = [
                ['role' => 'system', 'content' => 'You are a senior public-sector digital strategy consultant. Write in Indonesian.'],
                ['role' => 'user', 'content' => $instruction . "\n\nTOPICS:\n" . $topicsJson . "\n\nAGGREGATES:\n" . $aggText]
            ];

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 2200,
            ]);

            $text = trim($response->choices[0]->message->content ?? '');

            // enforce ~1600-word hard cap as safety
            try {
                $words = preg_split('/\s+/u', trim($text));
                if (is_array($words) && count($words) > 1600) {
                    $text = implode(' ', array_slice($words, 0, 1600)) . '\n\n...';
                }
            } catch (\Throwable $t) {
                // ignore
            }

            if ($text) return $text;

            // Second attempt with an even lighter prompt if the first returned empty
            $simpleInstruction = "Tulis analisis panjang (~1500 kata) Bahasa Indonesia tentang dua topik: " . $topicPair . ". Sertakan: Ringkasan Eksekutif; Mengapa Penting; Temuan Kunci; Strategi Terpadu; Aksi 0–3 Bulan; Aksi 3–12 Bulan; Komunikasi & Edukasi; KPI; Risiko & Mitigasi; Dampak. Sebut dua topik di setiap bagian. Jawab dengan teks naratif saja.";
            $resp2 = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful Indonesian public-service strategist.'],
                    ['role' => 'user', 'content' => $simpleInstruction . "\n\nCONTEXT:\n" . mb_substr($aggText, 0, 6000)]
                ],
                'max_completion_tokens' => 2000,
            ]);
            $text2 = trim($resp2->choices[0]->message->content ?? '');
            return $text2 ?: null;
        } catch (Exception $e) {
            Log::error('generateCombinedTopInsight error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * V2: Generate a structured JSON outline for a comprehensive insight report.
     * Returns associative array or null.
     */
    public function generateInsightOutlineJson(array $topTopics, array $catsArr, array $sentiments, array $commonIssues, array $geoCounts, string $aggText): ?array
    {
        try {
            $model = 'gpt-5-mini';

            $payload = [
                'top_topics' => array_values(array_map(function ($t) {
                    return [
                        'key' => $t['key'] ?? ($t['name'] ?? ''),
                        'label' => $t['label'] ?? ($t['name'] ?? ''),
                        'count' => (int)($t['count'] ?? 0),
                    ];
                }, $topTopics)),
                'categories' => $catsArr,
                'sentiments' => $sentiments,
                'common_issues' => $commonIssues,
                'geo_counts' => $geoCounts,
                'aggregates' => $aggText,
            ];

            $schemaInstruction = "Balas HANYA JSON valid (tanpa penjelasan) dengan struktur berikut dalam Bahasa Indonesia: {\n  \"exec_summary\": string (150-250 kata),\n  \"why_these_topics\": string (100-180 kata),\n  \"findings\": [string],\n  \"short_term_actions\": [string],\n  \"mid_term_actions\": [string],\n  \"strategies\": [string],\n  \"kpis\": [string],\n  \"risks\": [string],\n  \"comms_plan\": [string]\n}. Butir tindakan harus spesifik dan dapat dieksekusi.";

            $messages = [
                ['role' => 'system', 'content' => 'You are a JSON-only public sector analytics planner.'],
                ['role' => 'user', 'content' => $schemaInstruction . "\nDATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE)]
            ];

            $resp = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 1400,
            ]);
            $text = trim($resp->choices[0]->message->content ?? '');
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $maybe = substr($text, $start, $end - $start + 1);
                $json = json_decode($maybe, true);
                if (is_array($json)) return $json;
            }
            return null;
        } catch (Exception $e) {
            Log::error('generateInsightOutlineJson error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Turn the outline JSON into a long Indonesian narrative (~1400–1600 words).
     * AI-only: returns null if generation fails.
     */
    public function generateNarrativeFromOutline(array $outline): ?string
    {
        // Basic validate keys
        $keys = ['exec_summary', 'why_these_topics', 'findings', 'short_term_actions', 'mid_term_actions', 'strategies', 'kpis', 'risks', 'comms_plan'];
        $ok = true;
        foreach ($keys as $k) {
            if (!array_key_exists($k, $outline)) {
                $ok = false;
                break;
            }
        }
        $jsonStr = json_encode($outline, JSON_UNESCAPED_UNICODE);
        try {
            $model = 'gpt-5-mini';
            $instruction = "Susun NARASI KOMPREHENSIF (~1400–1600 kata) dalam Bahasa Indonesia dari OUTLINE JSON berikut. Gunakan subjudul tegas:\n\n"
                . "Ringkasan Eksekutif\n\n"
                . "Mengapa Dua Topik Ini Penting\n\n"
                . "Temuan Kunci\n\n"
                . "Strategi Terpadu\n\n"
                . "Rencana Aksi 0–3 Bulan\n\n"
                . "Rencana Aksi 3–12 Bulan\n\n"
                . "Rencana Komunikasi & Edukasi\n\n"
                . "KPI yang Dipantau\n\n"
                . "Risiko & Mitigasi\n\n"
                . "Dampak yang Diharapkan\n\n"
                . "Instruksi:\n\n"
                . "Tulis narasi mengalir, praktis, dan operasional.\n\n"
                . "Pastikan strategi dan rencana aksi tidak hanya fokus pada digitalisasi (media sosial, aplikasi, notifikasi), tetapi juga mencakup strategi sosialisasi offline seperti baliho, radio lokal, kerjasama dengan komunitas/ormas, pasar malam, masjid, sekolah, dan event daerah.\n\n"
                . "Sertakan contoh konkret dalam setiap bagian rencana aksi, misalnya:\n\n"
                . "Digital: konten reels edukatif, push notification aplikasi.\n\n"
                . "Offline: sosialisasi melalui pengeras suara masjid, banner di pasar, kerja sama dengan karang taruna.\n\n"
                . "Fokus pada konteks pelayanan publik Bapenda/Samsat dengan tujuan utama meningkatkan kepatuhan pajak dan kepuasan wajib pajak.\n\n"
                . "Pastikan dua topik teratas disebutkan secara eksplisit di setiap bagian penjelasan.\n\n"
                . "Balas hanya dalam bentuk teks naratif panjang sesuai struktur, jangan tampilkan JSON.";
            $resp = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior Indonesian public-sector strategist writing long-form reports.'],
                    ['role' => 'user', 'content' => $instruction . "\n\nOUTLINE_JSON:\n" . $jsonStr]
                ],
                'max_completion_tokens' => 2300,
            ]);
            $text = trim($resp->choices[0]->message->content ?? '');
            if ($text) return $text;
        } catch (Exception $e) {
            Log::warning('generateNarrativeFromOutline AI expansion failed: ' . $e->getMessage());
        }
        return null;
    }

    // Removed local non-AI narrative generator to enforce AI-only outputs

    /**
     * Classify individual chats into categories & sentiments.
     * @param array $chats Each: ['chat_id' => string|int, 'text' => string]
     * @param array $categoryLabels key => human label
     * @return array ['success'=>bool,'message'=>string,'data'=>array|null]
     */
    public function classifyChats(array $chats, array $categoryLabels): array
    {
        try {
            if (empty($chats)) return ['success' => true, 'message' => 'No chats', 'data' => []];

            $model = 'gpt-5-mini';

            $labels = [];
            foreach ($categoryLabels as $k => $lbl) {
                $labels[] = $k . '|' . $lbl;
            }

            $prepared = [];
            foreach ($chats as $c) {
                $txt = (string)($c['text'] ?? '');
                $prepared[] = [
                    'chat_id' => (string)($c['chat_id'] ?? ''),
                    'text' => mb_substr(preg_replace('/\s+/', ' ', $txt), 0, 400)
                ];
            }

            $schema = ['chat_id' => 'string', 'category' => 'key', 'sentiment' => 'positive|neutral|negative', 'confidence' => '0..1', 'snippet' => '<=200 chars'];
            $payload = ['labels' => $labels, 'schema' => $schema, 'chats' => $prepared];

            $messages = [
                ['role' => 'system', 'content' => 'Return ONLY a JSON array. Classify into given labels; choose best match (avoid other). Provide simple sentiment and 0-1 confidence.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]
            ];

            $resp = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 800,
            ]);
            $text = trim($resp->choices[0]->message->content ?? '');
            $arr = json_decode($text, true);
            if (!is_array($arr)) return ['success' => false, 'message' => 'Parse failed', 'data' => null];
            return ['success' => true, 'message' => $text, 'data' => $arr];
        } catch (Exception $e) {
            Log::error('AI classify error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Attempt to parse a JSON array (primary) from raw model output
     */
    private function tryParseJsonArray(string $text): ?array
    {
        $trim = trim($text);
        $j = json_decode($trim, true);
        if (is_array($j)) return $j;
        $s = strpos($trim, '[');
        $e = strrpos($trim, ']');
        if ($s !== false && $e !== false && $e > $s) {
            $maybe = substr($trim, $s, $e - $s + 1);
            $j2 = json_decode($maybe, true);
            if (is_array($j2)) return $j2;
        }
        return null;
    }

    /**
     * Find the full original (prepared) chat text by id.
     */
    private function findChatText(array $prepared, string $chatId): ?string
    {
        foreach ($prepared as $p) {
            if ($p['chat_id'] === $chatId) return $p['text'];
        }
        return null;
    }

    // Removed: proportionLainLain (no longer used; heuristic fallback eliminated)

    /**
     * Generate AI response for customer service chat with RAG
     */
    public function generateCustomerServiceResponse(array $messages, ?string $context = null): array
    {
        try {
            // Get relevant knowledge from knowledge base (RAG)
            $relevantKnowledge = $this->getRelevantKnowledge($messages);
            if ($this->debug) {
                $lk = $relevantKnowledge[0]['title'] ?? null;
                Log::info('RAG debug: relevantKnowledge count', ['count' => count($relevantKnowledge), 'top' => $lk]);
            }

            // Initialize chat assembly variables
            $apiMessages = [];
            $recentSystem = [];
            $latestUser = null;
            for ($i = count($messages) - 1; $i >= 0; $i--) {
                $m = $messages[$i] ?? null;
                if (is_array($m) && (($m['role'] ?? '') === 'user')) {
                    $latestUser = $m;
                    break;
                }
            }

            // Base style + policy instruction (concise, KB-first, safe improvisation allowed)
            $apiMessages[] = [
                'role' => 'system',
                'content' => 'Anda adalah SALMA AI — Asisten Samsat Lamongan. Selalu jawab dalam Bahasa Indonesia (baku, semi-formal). Jangan gunakan bahasa daerah (mis. Jawa/Jawa Timuran), Inggris, atau bahasa lain. Gunakan format yang paling sesuai untuk pertanyaan: paragraf singkat, atau kombinasi paragraf dan poin. Jawaban harus ringkas, jelas, dan mudah dibaca. Prioritaskan Knowledge Base; jika informasi tidak lengkap, boleh beri panduan umum yang aman tanpa mencantumkan angka pasti.'
            ];

            // Provide explicit KB_CONTEXT and estimation policy for flexible, KB-prioritized answers
            if (!empty($relevantKnowledge)) {
                $contextBlocks = [];
                $maxRefs = 3;
                $i = 0;
                foreach ($relevantKnowledge as $rk) {
                    if ($i >= $maxRefs) break;
                    $title = (string)($rk['title'] ?? '');
                    $cat = (string)($rk['category'] ?? '');
                    $type = (string)($rk['type'] ?? '');
                    $body = (string)($rk['content'] ?? '');
                    if ($body === '' && !empty($rk['answer'])) $body = (string)$rk['answer'];
                    $plain = trim(strip_tags($body));
                    if ($plain === '') {
                        $i++;
                        continue;
                    }
                    $snippet = mb_substr($plain, 0, 1200);
                    $contextBlocks[] = '[' . ($i + 1) . "] {$title} ({$type}/{$cat})\n{$snippet}";
                    $i++;
                }
                if (!empty($contextBlocks)) {
                    $apiMessages[] = [
                        'role' => 'system',
                        'content' => "KB_CONTEXT (PRIORITASKAN informasi ini saat menjawab. Jika konteks tidak lengkap, Anda BOLEH menambahkan penjelasan umum yang aman dan prosedural berdasarkan pengetahuan publik, namun JANGAN membuat angka pasti yang tidak ada di konteks):\n" . implode("\n\n---\n\n", $contextBlocks)
                    ];
                    $apiMessages[] = [
                        'role' => 'system',
                        'content' => "ESTIMATION_POLICY:\n- Jika pertanyaan menyinggung BIAYA/PAJAK dan KB tidak mencantumkan angka pasti, jelaskan bahwa besaran pajak/biaya dapat berbeda tergantung tahun, merk, tipe/model, status pajak, dan PNBP.\n- Tetap BERIKAN daftar komponen biaya yang tersedia di KB (mis. balik nama: PNBP BPKB/STNK, cek fisik, admin), serta dokumen-persyaratan terkait.\n- Hindari angka fiktif; gunakan bahasa estimatif (mis. 'dapat berbeda', 'perkiraan', 'mengikuti ketentuan yang berlaku').\n- Akhiri dengan saran tindakan: hubungi/kunjungi Samsat Lamongan untuk angka pasti dan verifikasi dokumen.\n- Gunakan format yang paling sesuai: paragraf singkat atau kombinasi paragraf dan poin. Jawaban harus ringkas, jelas, dan mudah dibaca."
                    ];
                }
            }
            // Determine top KB and evaluate image-only fast path with stricter relevance
            $kbTop = $relevantKnowledge[0] ?? null;
            $kbHtml = is_array($kbTop) ? (string)($kbTop['content'] ?? '') : '';
            $kbHasImages = false;
            if (!empty($kbHtml) && preg_match('/<img\s/i', $kbHtml)) {
                $kbHasImages = true;
            } elseif (is_array($kbTop) && !empty($kbTop['images']) && is_array($kbTop['images'])) {
                $kbHasImages = count($kbTop['images']) > 0;
            }

            $useImageFastPath = false;
            if ($kbHasImages && !empty($kbHtml)) {
                $userText = (string)($latestUser['content'] ?? '');
                $imageIntent = $this->hasImageIntent($userText);
                $scoreTop = (float)($kbTop['score'] ?? 0);
                $scoreSecond = (float)($relevantKnowledge[1]['score'] ?? 0);
                $margin = $scoreTop - $scoreSecond;
                $overlap = $this->keywordOverlapCountForKb(is_array($kbTop) ? $kbTop : [], $userText);
                // Gate to avoid irrelevant repetition
                if ($imageIntent || ($scoreTop >= 1.0 && $margin >= 0.3) || ($overlap >= 2 && $scoreTop >= 0.8)) {
                    $useImageFastPath = true;
                }
            }

            if ($useImageFastPath) {
                if ($this->debug) Log::info('RAG path: image-only', ['kb_id' => $kbTop['id'] ?? null, 'title' => $kbTop['title'] ?? null]);
                $htmlWithLightbox = $this->wrapImagesWithLightbox($kbHtml);
                return [
                    'success' => true,
                    'message' => '<div class="kb-raw-content">' . $htmlWithLightbox . '</div>',
                    'usage' => null,
                    'knowledge_used' => count($relevantKnowledge)
                ];
            }

            // For non-image KBs, continue to model path with explicit KB_CONTEXT below

            // Add pruned system context (if any) and latest user question only
            foreach ($recentSystem as $s) {
                $apiMessages[] = [
                    'role' => 'system',
                    'content' => (string) $s['content']
                ];
            }
            if ($latestUser) {
                $apiMessages[] = [
                    'role' => 'user',
                    'content' => (string) $latestUser['content']
                ];
            }

            $params = [
                'model' => $this->model,
                'messages' => $apiMessages,
                'max_completion_tokens' => $this->maxTokens,
            ];
            // Some models (e.g., gpt-5-mini) only support default sampling; omit overrides
            if ($this->model !== 'gpt-5-mini') {
                $params['temperature'] = 0.2;
                $params['top_p'] = 0.9;
                $params['frequency_penalty'] = 0.2;
                $params['presence_penalty'] = 0.1;
            }

            $response = $this->client->chat()->create($params);

            $answerText = trim($response->choices[0]->message->content);
            if ($this->debug) Log::info('RAG path: model', ['tokens' => (array)($response->usage ?? [])]);

            return [
                'success' => true,
                'message' => $answerText,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->totalTokens,
                ],
                'knowledge_used' => count($relevantKnowledge)
            ];
        } catch (Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan sistem. Silakan coba lagi atau hubungi petugas kami.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get relevant knowledge from knowledge base using simple search
     */
    private function getRelevantKnowledge(array $messages): array
    {
        // Get last user message for search
        $userMessages = array_filter($messages, fn($msg) => $msg['role'] === 'user');
        if (empty($userMessages)) {
            return [];
        }

        $lastUserMessage = end($userMessages)['content'];
        if ($this->debug) Log::info('RAG debug: lastUserMessage', ['text' => mb_substr((string)$lastUserMessage, 0, 180)]);

        // Extract keywords for search
        $keywords = $this->extractKeywords($lastUserMessage);
        if ($this->debug) Log::info('RAG debug: keywords', ['kw' => $keywords]);

        if (empty($keywords)) {
            return [];
        }

        // Prefer full-text search on the actual question first; include a computed score
        $fulltext = KnowledgeBase::active()
            ->published()
            ->selectRaw('id, title, question, answer, content, excerpt, category, type, tags, images, source_type, search_content, view_count, MATCH(title, search_content) AGAINST (? IN NATURAL LANGUAGE MODE) as score', [$lastUserMessage])
            ->whereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$lastUserMessage])
            ->orderByDesc('score')
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(5)
            ->get()
            ->toArray();

        if (!empty($fulltext)) {
            if ($this->debug) Log::info('RAG debug: fulltext hit', ['count' => count($fulltext)]);
            return $fulltext;
        }

        // Fallback to keyword-based LIKE queries
        $knowledge = KnowledgeBase::active()
            ->published()
            ->where(function ($query) use ($keywords) {
                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('title', 'LIKE', "%{$keyword}%")
                            ->orWhere('question', 'LIKE', "%{$keyword}%")
                            ->orWhere('content', 'LIKE', "%{$keyword}%")
                            ->orWhere('answer', 'LIKE', "%{$keyword}%")
                            ->orWhere('excerpt', 'LIKE', "%{$keyword}%")
                            ->orWhere('search_content', 'LIKE', "%{$keyword}%");
                    }
                });
            })
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(8)
            ->get(['id', 'title', 'question', 'answer', 'content', 'excerpt', 'category', 'type', 'tags', 'images', 'source_type', 'search_content', 'view_count'])
            ->toArray();
        if ($this->debug) Log::info('RAG debug: like fallback', ['raw_count' => count($knowledge)]);

        // De-duplicate by id and prefer entries with more keyword overlap
        $seen = [];
        $scored = [];
        foreach ($knowledge as $row) {
            $id = $row['id'];
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $hay = strtolower(($row['title'] ?? '') . ' ' . ($row['search_content'] ?? '') . ' ' . ($row['answer'] ?? ''));
            $overlap = 0;
            foreach ($keywords as $kw) {
                if ($kw && strpos($hay, strtolower($kw)) !== false) $overlap++;
            }
            $row['score'] = $overlap;
            $scored[] = $row;
        }
        usort($scored, function ($a, $b) {
            if (($b['score'] ?? 0) === ($a['score'] ?? 0)) return ($b['view_count'] ?? 0) <=> ($a['view_count'] ?? 0);
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });
        $scored = array_slice($scored, 0, 5);
        if (!empty($scored)) {
            if ($this->debug) Log::info('RAG debug: like-scored return', ['count' => count($scored)]);
            return $scored;
        }

        // Emergency fallback: broaden search ignoring published & active scope
        try {
            $emergency = KnowledgeBase::query()
                ->where(function ($q) use ($lastUserMessage) {
                    $term = '%' . $lastUserMessage . '%';
                    $q->where('title', 'LIKE', $term)
                        ->orWhere('question', 'LIKE', $term)
                        ->orWhere('answer', 'LIKE', $term)
                        ->orWhere('content', 'LIKE', $term)
                        ->orWhere('search_content', 'LIKE', $term);
                })
                ->orderByDesc('view_count')
                ->limit(5)
                ->get(['id', 'title', 'question', 'answer', 'content', 'excerpt', 'category', 'type', 'tags', 'images', 'source_type', 'search_content', 'view_count'])
                ->toArray();

            foreach ($emergency as &$row) {
                $row['score'] = 0.5; // minimal score
            }
            if (!empty($emergency)) {
                if ($this->debug) Log::info('RAG debug: emergency return', ['count' => count($emergency)]);
                return $emergency;
            }
        } catch (\Throwable $t) {
            if ($this->debug) Log::warning('RAG debug: emergency error ' . $t->getMessage());
        }

        return [];
    }

    /**
     * Build a concise KB-grounded answer (3–6 short bullets or sentences)
     */
    private function buildConciseAnswerFromKb(array $kb, string $question = ''): string
    {
        $html = (string)($kb['content'] ?? '');
        if ($html === '' && !empty($kb['answer'])) $html = (string)$kb['answer'];
        $text = $this->normalizeHtmlToText($html);
        if ($text === '') return '';
        $keywords = $this->extractKeywords($question);
        $bullets = $this->extractBullets($html);
        if (!empty($bullets)) {
            $picked = $this->pickRelevantLines($bullets, $keywords, 6);
            if (!empty($picked)) return '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $picked)) . '</li></ul>';
        }
        $sentences = $this->splitSentences($text);
        $picked = $this->pickRelevantLines($sentences, $keywords, 5);
        if (empty($picked)) $picked = array_slice($sentences, 0, 5);
        return implode(' ', array_map('htmlspecialchars', $picked));
    }

    private function normalizeHtmlToText(string $html): string
    {
        if ($html === '') return '';
        $repl = [
            '/<\/(p|div|h[1-6]|li)>/i' => "$0\n",
            '/<br\s*\/?\s*>/i' => "\n",
        ];
        $tmp = preg_replace(array_keys($repl), array_values($repl), $html);
        $txt = trim(strip_tags($tmp));
        $txt = preg_replace('/[\r\n]+/', "\n", $txt);
        $txt = preg_replace('/\s{2,}/', ' ', $txt);
        return trim($txt);
    }

    private function extractBullets(string $html): array
    {
        $items = [];
        if ($html === '') return $items;
        try {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            if ($dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
                $lis = $dom->getElementsByTagName('li');
                foreach ($lis as $li) {
                    $text = trim(strip_tags($dom->saveHTML($li)));
                    if ($text !== '') $items[] = $text;
                }
            }
            libxml_clear_errors();
        } catch (\Throwable $t) {
        }
        return $items;
    }

    private function splitSentences(string $text): array
    {
        if ($text === '') return [];
        $parts = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }

    private function pickRelevantLines(array $lines, array $keywords, int $limit): array
    {
        if (empty($lines)) return [];
        $kw = array_unique(array_map('strtolower', $keywords));
        $scored = [];
        foreach ($lines as $i => $line) {
            $hay = mb_strtolower($line);
            $score = 0;
            foreach ($kw as $k) {
                if ($k !== '' && mb_strpos($hay, $k) !== false) $score++;
            }
            $scored[] = ['i' => $i, 's' => $score, 't' => $line];
        }
        usort($scored, function ($a, $b) {
            return $b['s'] <=> $a['s'];
        });
        $picked = array_slice(array_map(fn($r) => $r['t'], $scored), 0, $limit);
        // ensure at least first lines if all scores zero
        if (implode('', $picked) === '' && !empty($lines)) {
            $picked = array_slice($lines, 0, $limit);
        }
        return $picked;
    }

    /**
     * Extract keywords from user message
     */
    private function extractKeywords(string $message): array
    {
        // Normalize and tokenize
        $cleanMessage = strtolower($message);
        $cleanMessage = preg_replace('/[^a-z0-9_\-\s]/u', ' ', $cleanMessage);
        $tokens = preg_split('/\s+/', $cleanMessage, -1, PREG_SPLIT_NO_EMPTY);

        // Basic Indonesian stopwords
        $stop = [
            'dan',
            'atau',
            'yang',
            'untuk',
            'dengan',
            'di',
            'ke',
            'dari',
            'pada',
            'ini',
            'itu',
            'apa',
            'bagaimana',
            'berapa',
            'dimana',
            'kapan',
            'mengapa',
            'saya',
            'kami',
            'kita',
            'anda',
            'kamu',
            'ya',
            'tidak',
            'boleh',
            'bisa',
            'mohon',
            'tolong',
            'agar',
            'dapat',
            'jadi',
            'ada',
            'adalah',
            'sebagai',
            'tentang',
            'karena',
            'maka',
            'jika',
            'kalau',
            'hingga',
            'sampai',
            'serta',
            'selain',
            'juga',
            'lebih',
            'kurang',
            'mohon',
            'terima',
            'kasih'
        ];

        $terms = [];
        foreach ($tokens as $t) {
            if (strlen($t) < 3) continue;
            if (in_array($t, $stop, true)) continue;
            $terms[] = $t;
        }

        // Add Samsat domain hints to bias search without forcing same KB
        $domainHints = $this->domainHints();
        $terms = array_unique(array_merge($terms, $domainHints));

        // Cap number of terms to keep SQL concise
        return array_slice($terms, 0, 12);
    }

    /**
     * Domain-specific hint terms to bias search.
     */
    private function domainHints(): array
    {
        return ['samsat', 'lamongan', 'pajak', 'kendaraan', 'stnk', 'pkb', 'swdkllj', 'bpkb', 'tarif', 'biaya', 'lokasi', 'jam', 'operasional', 'online', 'esamsat', 'balik', 'nama', 'progresif'];
    }

    /**
     * Detect if the user's query likely requests images/posters/brochures.
     */
    private function hasImageIntent(string $text): bool
    {
        $t = mb_strtolower($text);
        $keys = ['gambar', 'foto', 'poster', 'brosur', 'ilustrasi', 'contoh', 'tampilan', 'lihat', 'infografis', 'galeri'];
        foreach ($keys as $k) {
            if (mb_strpos($t, $k) !== false) return true;
        }
        return false;
    }

    /**
     * Compute overlap count between user keywords and a single KB entry fields
     */
    private function keywordOverlapCountForKb(array $kb, string $userText): int
    {
        $kw = $this->extractKeywords($userText);
        if (empty($kw)) return 0;
        $hay = strtolower(
            ($kb['title'] ?? '') . ' ' .
                ($kb['question'] ?? '') . ' ' .
                ($kb['search_content'] ?? '') . ' ' .
                ($kb['answer'] ?? '')
        );
        $count = 0;
        foreach ($kw as $k) {
            if ($k && strpos($hay, strtolower($k)) !== false) $count++;
        }
        return $count;
    }

    /**
     * Wrap <img> tags with anchor for lightbox behavior, keeping src intact.
     */
    private function wrapImagesWithLightbox(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if (!$loaded) return $html;

        $imgs = $dom->getElementsByTagName('img');
        $wrapTargets = [];
        foreach ($imgs as $img) {
            if ($img instanceof \DOMElement) $wrapTargets[] = $img;
        }

        foreach ($wrapTargets as $img) {
            $src = $img->getAttribute('src');
            if (!$src) continue;
            $parent = $img->parentNode;
            $isAlreadyLink = ($parent instanceof \DOMElement) && strtolower($parent->nodeName) === 'a';
            if ($isAlreadyLink) continue;

            $a = $dom->createElement('a');
            $a->setAttribute('href', $src);
            $a->setAttribute('class', 'kb-lightbox');
            $a->setAttribute('data-src', $src);

            $parent->replaceChild($a, $img);
            $a->appendChild($img);
        }

        $out = $dom->saveHTML();
        $out = preg_replace('/^<\?xml.*?\?>/i', '', $out);
        return $out;
    }

    /**
     * Get system prompt for Bapenda Samsat Customer Service with knowledge
     */
    private function getSystemPrompt(array $relevantKnowledge = [], ?string $context = null): string
    {
        $basePrompt = "Anda adalah asisten AI customer service untuk Bapenda (Badan Pendapatan Daerah) Samsat Lamongan, Jawa Timur.

IDENTITAS & PERAN:
- Nama: SALMA AI — Asisten Samsat Lamongan
- Peran: Customer Service AI yang ramah, profesional, dan membantu
- Lokasi: Samsat Lamongan, Jawa Timur
- Bahasa: WAJIB Bahasa Indonesia (baku, semi-formal), jangan gunakan bahasa daerah (mis. Jawa/Jawa Timuran), Inggris, atau bahasa lain

TUGAS UTAMA:
1. Membantu masyarakat dengan informasi layanan Samsat Lamongan
2. Menjawab pertanyaan seputar pajak kendaraan bermotor
3. Memberikan informasi jadwal, lokasi, dan syarat-syarat layanan
4. Membantu dengan prosedur pembayaran pajak kendaraan
5. Memberikan informasi umum tentang STNK, BPKB, dan dokumen kendaraan
- Jika tidak tahu jawaban pasti, arahkan untuk menghubungi petugas langsung
- Selalu tutup dengan menawarkan bantuan lebih lanjut

PENTING - PERHATIKAN SETIAP PERTANYAAN:
- Baca dan pahami setiap pertanyaan dengan teliti
- Berikan jawaban yang SPESIFIK untuk setiap pertanyaan yang berbeda
- JANGAN memberikan jawaban yang sama untuk pertanyaan yang berbeda
- Sesuaikan respons dengan topik yang ditanyakan
- Jika pertanyaan berbeda, berikan informasi yang relevan dengan pertanyaan tersebut

INFORMASI YANG BISA DIBANTU:
- Cara bayar pajak kendaraan
- Syarat perpanjangan STNK
- Lokasi dan jam operasional Samsat Lamongan
- Tarif pajak kendaraan
- Prosedur balik nama kendaraan
- Syarat dan cara membuat STNK baru
- Informasi denda keterlambatan
- Cara cek pajak online

LARANGAN:
- Jangan memberikan informasi yang tidak akurat
- Jangan memproses pembayaran atau transaksi apapun
- Jangan meminta data pribadi sensitif (NIK, nomor rekening, dll)
- Jangan memberikan janji yang tidak bisa dipenuhi sistem
- JANGAN copy-paste jawaban yang sama untuk pertanyaan berbeda

GUNAKAN INFORMASI RESMI:
Jawab berdasarkan pengetahuan yang akurat dan terkini tentang layanan Samsat. Jika ada pertanyaan di luar scope layanan Samsat, arahkan dengan sopan ke layanan yang tepat.";

        // Add relevant knowledge if available
        if (!empty($relevantKnowledge)) {
            $basePrompt .= "\n\nINFORMASI REFERENSI RESMI DARI KNOWLEDGE BASE:\nGunakan informasi berikut sebagai referensi utama untuk menjawab pertanyaan:\n\n";

            foreach ($relevantKnowledge as $index => $knowledge) {
                $basePrompt .= "**Referensi " . ($index + 1) . ": {$knowledge['title']}**\n";
                $basePrompt .= "Kategori: {$knowledge['category']}\n";
                $basePrompt .= "Tipe: {$knowledge['type']}\n";

                if (!empty($knowledge['excerpt'])) {
                    $excerpt = strlen($knowledge['excerpt']) > 400 ? substr($knowledge['excerpt'], 0, 400) . '...' : $knowledge['excerpt'];
                    $basePrompt .= "Ringkasan: {$excerpt}\n";
                }

                if (!empty($knowledge['tags'])) {
                    $tags = is_array($knowledge['tags']) ? implode(', ', $knowledge['tags']) : $knowledge['tags'];
                    $basePrompt .= "Tags: {$tags}\n";
                }

                $basePrompt .= "\n";
            }

            $basePrompt .= "PENTING: \n";
            $basePrompt .= "- Prioritaskan informasi dari Knowledge Base di atas untuk menjawab pertanyaan\n";
            $basePrompt .= "- Jika informasi tidak ada di Knowledge Base, berikan jawaban umum yang akurat\n";
            $basePrompt .= "- Jika tidak yakin dengan jawaban, arahkan user untuk bertanya langsung ke petugas Samsat atau social media resmi kami\n";
            $basePrompt .= "- Selalu berikan sumber informasi yang jelas dan terpercaya\n";
            $basePrompt .= "- Gunakan format yang paling sesuai untuk kejelasan: paragraf singkat, atau kombinasi paragraf + poin jika perlu. Jawaban harus ringkas, jelas, dan mudah dibaca.\n";
            $basePrompt .= "- Gunakan HANYA informasi dari referensi; jika tidak ada di referensi, jawab bahwa belum tersedia di Knowledge Base kami.\n\n";
        } else {
            $basePrompt .= "\n\nCATATAN: Tidak ada informasi spesifik di Knowledge Base untuk pertanyaan ini.\n";
            $basePrompt .= "Berikan jawaban umum yang akurat, atau arahkan user untuk menghubungi:\n";
            $basePrompt .= "- Petugas Samsat Lamongan langsung\n";
            $basePrompt .= "- Social media resmi Bapenda Lamongan\n";
            $basePrompt .= "- Call center resmi Samsat\n\n";
        }

        if ($context) {
            $basePrompt .= "\n\nKONTEKS TAMBAHAN:\n" . $context . "\n\nPerhatikan: Jawab sesuai dengan konteks percakapan dan pertanyaan spesifik yang diajukan.";
        }

        return $basePrompt;
    }

    /**
     * Generate greeting message
     */
    public function generateGreeting(): string
    {
        return "Halo! Saya Salma AI. ada yang bisa saya bantu ?";
    }
}
