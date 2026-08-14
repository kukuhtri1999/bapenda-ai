<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use App\Services\RichContentProcessor;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;

class OpenAIService
{
    private Client $client;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $defaultTimeout;
    private bool $debug;
    private SecurityGuardrailService $guardrails;
    private AiCircuitBreakerService $circuitBreaker;

    // OPTIMIZATION: Simple in-memory cache for frequent queries
    private static array $queryCache = [];
    private static int $cacheLimit = 50;

    public function __construct()
    {
        $this->client = \OpenAI::client((string) config('services.openai.api_key'));
        $this->model = config('services.openai.model', 'gpt-5-mini');
        $this->maxTokens = config('services.openai.max_tokens', 4000);
        $this->temperature = config('services.openai.temperature', 0.7);
        $this->defaultTimeout = 30;
        $ragDebug = $_ENV['RAG_DEBUG'] ?? $_SERVER['RAG_DEBUG'] ?? false;
        $this->debug = (bool) (config('app.debug', false) || filter_var($ragDebug, FILTER_VALIDATE_BOOLEAN));
        $this->guardrails = app(SecurityGuardrailService::class);
        $this->circuitBreaker = app(AiCircuitBreakerService::class);
    }





    /**
     * Normalize OpenAI usage object/array to a common array shape.
     */
    private function normalizeUsage($usage): ?array
    {
        if (!$usage) return null;
        $get = function ($obj, string $camel, string $snake) {
            if (is_array($obj)) {
                return $obj[$camel] ?? $obj[$snake] ?? null;
            }
            if (is_object($obj)) {
                return $obj->{$camel} ?? $obj->{$snake} ?? null;
            }
            return null;
        };
        $prompt = $get($usage, 'promptTokens', 'prompt_tokens');
        $completion = $get($usage, 'completionTokens', 'completion_tokens');
        $total = $get($usage, 'totalTokens', 'total_tokens');
        return [
            'prompt_tokens' => is_numeric($prompt) ? (int)$prompt : null,
            'completion_tokens' => is_numeric($completion) ? (int)$completion : null,
            'total_tokens' => is_numeric($total) ? (int)$total : null,
        ];
    }

    private function getAnalyticsModel(): string
    {
        $m = config('services.openai.analytics_model');
        if (is_string($m) && strlen($m) > 0) {
            return $m;
        }
        return 'gpt-5-mini';
    }

    /**
     * Retry wrapper for API calls with simple exponential backoff.
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
                $delay = (int) ($baseDelay * pow(2, max(0, $tries - 1)));
                $jitter = function_exists('random_int') ? random_int(0, (int) ($baseDelay * 0.3)) : 0;
                usleep(($delay + $jitter) * 1000);
            }
        }
        if ($lastException) throw $lastException;
        return null;
    }

    // ANALYTICS METHODS (keep existing implementation)
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

    public function generateInsightSummary(string $aggText, array $categoryLabels = []): ?string
    {
        try {
            $model = $this->getAnalyticsModel();
            $prompt = "Anda adalah seorang analis data senior. Buat ringkasan insight profesional berdasarkan data analytics berikut:

{$aggText}

Berikan analisis dalam format profesional dengan:
- Temuan kunci (2-3 poin utama)
- Tren yang teridentifikasi
- Implikasi untuk layanan

Gunakan bahasa Indonesia profesional, maksimal 200 kata.";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah analis data profesional yang membuat insight ringkas dan akurat.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 300,
            ]);

            return trim($response->choices[0]->message->content ?? '');
        } catch (Exception $e) {
            Log::error('Failed to generate insight summary: ' . $e->getMessage());
            return null;
        }
    }

    public function generateDetailedAnalysis(string $aggText, array $categoryLabels = [], ?array $derived = null): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            // Build category context
            $categoryContext = '';
            if (!empty($categoryLabels)) {
                $categoryContext = "\nKategori layanan:\n";
                foreach ($categoryLabels as $key => $label) {
                    $categoryContext .= "- {$key}: {$label}\n";
                }
            }

            $prompt = "Anda adalah seorang Senior Data Analyst di Samsat Lamongan. Buat dokumen analisis profesional lengkap berdasarkan data berikut:

{$aggText}
{$categoryContext}

Buat dokumen analisis dengan struktur berikut dalam format Markdown:

# 📊 Laporan Analisis Pelayanan Samsat Lamongan

## Executive Summary
[Ringkasan eksekutif dalam 2-3 paragraf yang menjelaskan temuan utama]

## 🔍 Analisis Mendalam

### Pola Permintaan Layanan
[Analisis distribusi kategori layanan dan preferensi masyarakat]

### Analisis Sentimen Pelanggan
[Evaluasi kepuasan dan feedback pelanggan]

### Tren Temporal dan Geografis
[Pola waktu dan lokasi permintaan layanan]

## 📈 Temuan Kunci
1. **[Temuan 1]**: [Penjelasan detail]
2. **[Temuan 2]**: [Penjelasan detail]
3. **[Temuan 3]**: [Penjelasan detail]

## ⚡ Insight Strategis
[Implikasi bisnis dan operasional dari data]

## 🎯 Area Prioritas
[Area yang memerlukan perhatian khusus]

---
*Laporan ini dihasilkan oleh SALMA AI Analytics - Samsat Lamongan*

Gunakan data aktual, berikan insight yang mendalam dan profesional.";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah Senior Data Analyst profesional yang membuat laporan analisis mendalam dengan format yang rapi dan insight yang bermakna.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 1500,
            ]);

            return trim($response->choices[0]->message->content ?? '');
        } catch (Exception $e) {
            Log::error('Failed to generate detailed analysis: ' . $e->getMessage());
            return null;
        }
    }

    public function generateRecommendations(array $categoryCounts, array $commonIssues, array $sentiments, array $categoryLabels = []): ?array
    {
        try {
            $model = $this->getAnalyticsModel();

            // Build context from data
            $dataContext = "Data Analytics Samsat Lamongan:\n\n";
            $dataContext .= "Kategori Permintaan:\n";
            foreach ($categoryCounts as $category => $count) {
                $label = $categoryLabels[$category] ?? $category;
                $dataContext .= "- {$label}: {$count} permintaan\n";
            }

            $dataContext .= "\nSentimen Pelanggan:\n";
            foreach ($sentiments as $sentiment => $count) {
                $dataContext .= "- {$sentiment}: {$count}\n";
            }

            if (!empty($commonIssues)) {
                $dataContext .= "\nIsu Umum:\n";
                foreach (array_slice($commonIssues, 0, 5, true) as $issue => $count) {
                    $dataContext .= "- {$issue}: {$count} kali\n";
                }
            }

            $prompt = "Berdasarkan data analytics berikut, berikan rekomendasi strategis untuk Samsat Lamongan:

{$dataContext}

Berikan output dalam format JSON dengan struktur berikut:
{
  \"recommendations\": [
    \"Rekomendasi singkat 1\",
    \"Rekomendasi singkat 2\",
    \"Rekomendasi singkat 3\"
  ],
  \"detailed_recommendations\": [
    {
      \"category\": \"nama_kategori\",
      \"label\": \"Label Kategori\",
      \"priority\": \"high|medium|low\",
      \"description\": \"Deskripsi masalah dan solusi\",
      \"actions\": [
        \"Aksi spesifik 1\",
        \"Aksi spesifik 2\"
      ],
      \"expected_impact\": \"Dampak yang diharapkan\",
      \"timeframe\": \"Jangka waktu implementasi\"
    }
  ]
}

Fokus pada 3-5 rekomendasi paling impactful berdasarkan volume dan prioritas.";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah konsultan manajemen yang membuat rekomendasi strategis berdasarkan data analytics. Selalu berikan output dalam format JSON yang valid.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 1000,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            // Extract JSON from response
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $jsonStr = substr($content, $start, $end - $start + 1);
                $result = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            // Fallback: create basic structure
            return [
                'recommendations' => [
                    'Tingkatkan pelayanan untuk kategori dengan volume tertinggi',
                    'Optimalkan respon time untuk meningkatkan kepuasan pelanggan',
                    'Perluas informasi online untuk mengurangi pertanyaan berulang'
                ],
                'detailed_recommendations' => []
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate recommendations: ' . $e->getMessage());
            return null;
        }
    }

    // ... other analytics methods

    /**
     * Generate comprehensive one-shot analytics with professional formatting
     */
    public function generateOneShotAnalytics(array $rawRows, array $context, array $categoryLabels = []): array
    {
        try {
            $model = $this->getAnalyticsModel();

            // Build data summary
            $dataSummary = "ANALYTICS DATA SUMMARY:\n";
            $dataSummary .= "Total Messages: " . count($rawRows) . "\n";
            $dataSummary .= "Date Range: " . ($context['date_range'][0] ?? 'N/A') . " to " . ($context['date_range'][1] ?? 'N/A') . "\n\n";

            $dataSummary .= "CATEGORY DISTRIBUTION:\n";
            foreach ($context['category_counts'] ?? [] as $cat => $count) {
                $label = $categoryLabels[$cat] ?? $cat;
                $dataSummary .= "- {$label}: {$count}\n";
            }

            $dataSummary .= "\nSENTIMENT ANALYSIS:\n";
            foreach ($context['sentiments'] ?? [] as $sent => $count) {
                $dataSummary .= "- {$sent}: {$count}\n";
            }

            $prompt = "Anda adalah Senior Data Analyst Samsat Lamongan. Berdasarkan data berikut, buat analisis komprehensif:

{$dataSummary}

Berikan output dalam format JSON dengan struktur berikut:
{
  \"combined_top_insight\": \"[Dokumen analisis profesional lengkap dalam format Markdown dengan insight mendalam, tren, dan rekomendasi strategis - minimal 800 kata]\",
  \"insight_summary\": \"[Ringkasan eksekutif singkat 150-200 kata]\",
  \"recommendations\": [\"Rekomendasi 1\", \"Rekomendasi 2\", \"Rekomendasi 3\"],
  \"recommendations_detailed\": [
    {
      \"category\": \"kategori\",
      \"label\": \"Label\",
      \"priority\": \"high\",
      \"description\": \"Deskripsi\",
      \"actions\": [\"Aksi 1\", \"Aksi 2\"]
    }
  ]
}

PENTING:
- combined_top_insight harus berupa dokumen Markdown profesional lengkap
- Gunakan data real untuk insight yang akurat
- Berikan analisis mendalam dengan insight strategis dan rekomendasi actionable";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah Senior Data Analyst yang membuat laporan analitis profesional. Selalu berikan output JSON yang valid dan lengkap.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 2000,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            // Extract and parse JSON
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $jsonStr = substr($content, $start, $end - $start + 1);
                $result = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            return [];
        } catch (Exception $e) {
            Log::error('Failed to generate one-shot analytics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate basic insight and recommendations as fallback
     */
    public function generateBasicInsightAndRecommendations(array $rawRows, array $context): array
    {
        try {
            $model = $this->getAnalyticsModel();

            $dataContext = "Data: " . count($rawRows) . " messages\n";
            $dataContext .= "Sentiments: " . json_encode($context['sentiments'] ?? []) . "\n";
            $dataContext .= "Categories: " . json_encode($context['category_counts'] ?? []) . "\n";

            $prompt = "Buat insight dan rekomendasi berdasarkan data berikut:

{$dataContext}

Format JSON:
{
  \"insight_long\": \"[Analisis mendalam dalam format Markdown - minimal 500 kata]\",
  \"insight_summary\": \"[Ringkasan 100-150 kata]\",
  \"recommendations\": [\"Rec 1\", \"Rec 2\", \"Rec 3\"],
  \"recommendations_detailed\": [
    {
      \"category\": \"cat\",
      \"label\": \"Label\",
      \"priority\": \"high|medium|low\",
      \"description\": \"Desc\",
      \"actions\": [\"Action 1\", \"Action 2\"]
    }
  ]
}";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Buat analisis dan rekomendasi dalam format JSON yang valid.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 1500,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $jsonStr = substr($content, $start, $end - $start + 1);
                $result = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            return [];
        } catch (Exception $e) {
            Log::error('Failed to generate basic insights: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate detailed strategies for top topics with 5 implementation steps
     */
    public function generateTopTopicStrategies(array $topTopics, string $aggText, array $categoryLabels = []): array
    {
        try {
            $model = $this->getAnalyticsModel();
            $strategies = [];

            foreach ($topTopics as $topic) {
                $topicKey = $topic['key'] ?? '';
                $topicLabel = $topic['label'] ?? $topicKey;
                $topicCount = $topic['count'] ?? 0;

                $prompt = "Anda adalah Strategic Business Consultant untuk Samsat Lamongan.

Topik: {$topicLabel} ({$topicCount} permintaan)
Data: {$aggText}

Buat analisis mendalam dan strategi implementasi dengan TEPAT 5 langkah detail untuk menangani topik ini:

Format JSON:
{
  \"topic_name\": \"{$topicLabel}\",
  \"topic_count\": {$topicCount},
  \"topic_overview\": \"Analisis mendalam tentang topik ini (300-400 kata). Harus mencakup: 1) 5 informasi teratas yang paling sering ditanyakan wajib pajak, 2) analisis lebih lanjut tentang pola dan tren, 3) 7 contoh pesan chat wajib pajak yang representatif untuk topik ini\",
  \"top_questions\": [
    \"Pertanyaan 1 yang sering ditanyakan wajib pajak\",
    \"Pertanyaan 2 yang sering ditanyakan wajib pajak\",
    \"Pertanyaan 3 yang sering ditanyakan wajib pajak\",
    \"Pertanyaan 4 yang sering ditanyakan wajib pajak\",
    \"Pertanyaan 5 yang sering ditanyakan wajib pajak\"
  ],
  \"further_analysis\": \"Analisis mendalam tentang pola komunikasi, frekuensi, dan karakteristik pertanyaan wajib pajak untuk topik ini (150-200 kata)\",
  \"example_chat_messages\": [
    \"Contoh 1: Pesan chat wajib pajak yang representatif\",
    \"Contoh 2: Pesan chat wajib pajak yang representatif\",
    \"Contoh 3: Pesan chat wajib pajak yang representatif\",
    \"Contoh 4: Pesan chat wajib pajak yang representatif\",
    \"Contoh 5: Pesan chat wajib pajak yang representatif\",
    \"Contoh 6: Pesan chat wajib pajak yang representatif\",
    \"Contoh 7: Pesan chat wajib pajak yang representatif\"
  ],
  \"implementation_steps\": [
    {
      \"step\": 1,
      \"title\": \"Judul Langkah Singkat\",
      \"description\": \"Deskripsi detail langkah ini (100-150 kata) - jelaskan what, why, how\",
      \"deliverables\": [\"Deliverable 1\", \"Deliverable 2\"],
      \"timeline\": \"2-4 minggu\",
      \"effort_level\": \"high|medium|low\",
      \"success_metrics\": [\"Metrik 1\", \"Metrik 2\"],
      \"resources_needed\": [\"Resource 1\", \"Resource 2\"]
    }
  ],
  \"expected_outcome\": \"Hasil yang diharapkan dari implementasi strategi ini\",
  \"risk_mitigation\": [\"Risk 1 dan mitigasinya\", \"Risk 2 dan mitigasinya\"]
}

REQUIREMENTS:
- HARUS ada tepat 5 implementation_steps
- Setiap step harus detail dan actionable
- Fokus pada solusi praktis untuk Samsat Lamongan
- Berikan timeline dan effort level yang realistis";

                $response = $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Anda adalah Strategic Business Consultant yang membuat roadmap implementasi detail dengan analisis mendalam. Selalu berikan tepat 5 langkah yang actionable, 5 pertanyaan teratas, dan 7 contoh chat messages.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_completion_tokens' => 2000,
                ]);

                $content = trim($response->choices[0]->message->content ?? '');

                $start = strpos($content, '{');
                $end = strrpos($content, '}');
                if ($start !== false && $end !== false && $end > $start) {
                    $jsonStr = substr($content, $start, $end - $start + 1);
                    $result = json_decode($jsonStr, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($result['implementation_steps'])) {
                        // Ensure topic info is included
                        if (!isset($result['topic_name'])) {
                            $result['topic_name'] = $topicLabel;
                        }
                        if (!isset($result['topic_count'])) {
                            $result['topic_count'] = $topicCount;
                        }
                        if (!isset($result['topic_overview'])) {
                            $result['topic_overview'] = "Analisis menunjukkan bahwa {$topicLabel} merupakan topik yang sering ditanyakan dengan {$topicCount} permintaan dari wajib pajak. Berdasarkan data komunikasi, topik ini mencerminkan kebutuhan informasi yang tinggi dari masyarakat terkait layanan Samsat. Pola komunikasi menunjukkan adanya gap informasi yang perlu ditangani melalui strategi komunikasi yang lebih efektif dan sistem informasi yang lebih accessible untuk meningkatkan kepuasan pelayanan publik.";
                        }

                        // Ensure required fields exist
                        if (!isset($result['top_questions'])) {
                            $result['top_questions'] = [
                                "Bagaimana cara mengurus {$topicLabel}?",
                                "Berapa lama proses {$topicLabel}?",
                                "Dokumen apa saja yang diperlukan untuk {$topicLabel}?",
                                "Berapa biaya untuk {$topicLabel}?",
                                "Dimana lokasi mengurus {$topicLabel}?"
                            ];
                        }

                        if (!isset($result['further_analysis'])) {
                            $result['further_analysis'] = "Analisis pola komunikasi menunjukkan bahwa pertanyaan terkait {$topicLabel} cenderung muncul pada jam kerja dengan frekuensi tinggi. Karakteristik pertanyaan menunjukkan kurangnya informasi yang mudah diakses oleh masyarakat, sehingga diperlukan strategi proaktif untuk menyediakan informasi yang lebih komprehensif dan mudah dipahami.";
                        }

                        if (!isset($result['example_chat_messages'])) {
                            $result['example_chat_messages'] = [
                                "Selamat pagi, saya mau tanya tentang {$topicLabel}, bagaimana prosedurnya ya?",
                                "Pak/Bu, untuk mengurus {$topicLabel} perlu dokumen apa saja?",
                                "Maaf mengganggu, berapa lama proses {$topicLabel} selesai?",
                                "Saya mau tanya biaya untuk {$topicLabel} berapa ya?",
                                "Dimana ya tempat mengurus {$topicLabel}? Jam operasionalnya bagaimana?",
                                "Apakah bisa {$topicLabel} diurus online atau harus datang langsung?",
                                "Terima kasih infonya, kalau ada kendala dalam proses {$topicLabel} hubungi kemana ya?"
                            ];
                        }

                        // Ensure exactly 5 steps
                        $steps = array_slice($result['implementation_steps'], 0, 5);
                        if (count($steps) < 5) {
                            // Fill missing steps with placeholder
                            while (count($steps) < 5) {
                                $stepNum = count($steps) + 1;
                                $steps[] = [
                                    'step' => $stepNum,
                                    'title' => "Langkah {$stepNum}: Evaluasi dan Optimisasi",
                                    'description' => "Evaluasi hasil implementasi langkah sebelumnya dan lakukan optimisasi berdasarkan feedback dan data performa.",
                                    'deliverables' => ['Laporan evaluasi', 'Rencana optimisasi'],
                                    'timeline' => '1-2 minggu',
                                    'effort_level' => 'medium',
                                    'success_metrics' => ['Peningkatan performa', 'Feedback positif'],
                                    'resources_needed' => ['Tim evaluasi', 'Data analytics']
                                ];
                            }
                        }
                        $result['implementation_steps'] = $steps;
                        $strategies[$topicKey] = $result;
                    }
                }
            }

            return $strategies;
        } catch (Exception $e) {
            Log::error('Failed to generate top topic strategies: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate single insight document as fallback
     */
    public function generateSingleInsightDocument(array $rawRows, array $context, array $topTopics = []): ?string
    {
        try {
            $model = $this->getAnalyticsModel();

            // Build comprehensive data context
            $totalMessages = count($rawRows);
            $dateRange = isset($context['date_range']) ? $context['date_range'][0] . " sampai " . $context['date_range'][1] : 'N/A';
            $sentiments = $context['sentiments'] ?? [];
            $categories = $context['category_counts'] ?? [];

            // Build category context
            $categoryText = "";
            if (!empty($categories)) {
                $categoryText = "Distribusi Kategori Permintaan:\n";
                foreach ($categories as $cat => $count) {
                    $percentage = $totalMessages > 0 ? round(($count / $totalMessages) * 100, 1) : 0;
                    $categoryText .= "- {$cat}: {$count} permintaan ({$percentage}%)\n";
                }
            }

            // Build sentiment context
            $sentimentText = "";
            if (!empty($sentiments)) {
                $sentimentText = "Distribusi Sentimen:\n";
                foreach ($sentiments as $sentiment => $count) {
                    $percentage = $totalMessages > 0 ? round(($count / $totalMessages) * 100, 1) : 0;
                    $sentimentText .= "- {$sentiment}: {$count} ({$percentage}%)\n";
                }
            }

            // Build top topics context
            $topTopicsText = "";
            if (!empty($topTopics)) {
                $topTopicsText = "Top 3 Topik Terpopuler:\n";
                foreach (array_slice($topTopics, 0, 3) as $i => $topic) {
                    $topTopicsText .= ($i + 1) . ". {$topic['label']}: {$topic['count']} permintaan\n";
                }
            }

            $prompt = "Anda adalah Senior Data Analyst Samsat Lamongan. Buat dokumen analisis profesional berdasarkan data berikut:

DATA ANALYTICS:
- Total Percakapan: {$totalMessages}
- Periode Analisis: {$dateRange}
- Sumber Data: AI Chat Samsat Lamongan

{$categoryText}

{$sentimentText}

{$topTopicsText}

BUAT DOKUMEN PROFESIONAL dengan struktur TEPAT seperti ini:

# 📊 ANALISIS DATA LAYANAN SAMSAT LAMONGAN

## **Bab 1. Pendahuluan Analisis**

Tujuan analisis periode ini.

Ringkasan data yang digunakan (jumlah percakapan, periode waktu, channel/AI chat).

## **Bab 2. Gambaran Umum Data Percakapan**

Volume percakapan (total, rata-rata harian/mingguan).

Distribusi kanal percakapan (misal: website, WA, chatbot aplikasi).

Profil umum interaksi (misal: jam sibuk, durasi percakapan).

## **Bab 3. Analisis Sentimen**

Persentase sentimen positif, netral, negatif.

Perubahan tren sentimen dari waktu ke waktu.

Faktor yang paling sering memunculkan sentimen negatif/positif.

## **Bab 4. Analisis Topik Percakapan**

Top 3 Topik Terpopuler (misalnya: pembayaran, denda keterlambatan, informasi layanan).

Ringkasan tiap topik (jumlah chat, sentimen dominan).

Pola komunikasi dan tren preferensi wajib pajak per topik.

## **Bab 5. Strategi Tindak Lanjut**

Strategi online (peningkatan FAQ chatbot, kampanye media sosial, edukasi video).

Strategi offline (sosialisasi langsung, peningkatan layanan di loket).

Strategi hybrid (integrasi event offline dengan notifikasi online, QR edukasi pajak, reminder otomatis + call center follow up).

## **Bab 6. Insight & Rekomendasi**

Apa yang perlu diprioritaskan dari temuan topik & sentimen.

Rekomendasi jangka pendek (quick win).

Rekomendasi jangka menengah (perbaikan SOP layanan, integrasi data).

Potensi jangka panjang (prediksi tren wajib pajak dengan AI).

## **Bab 7. Kesimpulan**

Ringkasan hasil analisis (top 3 topik, pola sentimen, strategi utama).

Nilai tambah penggunaan AI chat sebagai sumber big data percakapan.

---
*Laporan dihasilkan oleh SALMA AI Analytics - Samsat Lamongan*

REQUIREMENTS:
- Gunakan data real yang diberikan
- Setiap bab MAKSIMAL 300-500 kata untuk efisiensi
- Total dokumen target 2100-3500 kata (7 bab x 300-500 kata)
- Format Markdown professional dengan heading yang jelas
- Fokus pada insight praktis dan actionable
- Hindari pengulangan informasi antar bab";

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah Senior Data Analyst yang membuat dokumen insight profesional dengan format Markdown yang rapi dan insight yang bermakna. Buat dokumen yang efisien dan to-the-point.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_completion_tokens' => 1000,
            ]);

            return trim($response->choices[0]->message->content ?? '');
        } catch (Exception $e) {
            Log::error('Failed to generate single insight document: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate AI response for customer service chat with RAG
     */
    /**
     * Clear all cached AI customer service responses.
     */
    public static function clearResponseCache(): void
    {
        try {
            if (method_exists(\Illuminate\Support\Facades\Cache::getStore(), 'tags')) {
                \Illuminate\Support\Facades\Cache::tags(['ai_responses'])->flush();
            }
            $keys = \Illuminate\Support\Facades\Cache::get('ai_response_cache_keys', []);
            if (is_array($keys)) {
                foreach ($keys as $k) {
                    \Illuminate\Support\Facades\Cache::forget($k);
                }
            }
            \Illuminate\Support\Facades\Cache::forget('ai_response_cache_keys');
            Log::info('OpenAI response cache cleared successfully.');
        } catch (\Throwable $e) {
            Log::warning('Failed to clear OpenAI response cache: ' . $e->getMessage());
        }
    }

    /**
     * Generate AI response for customer service chat with RAG, Caching, and Tiered Model Routing
     */
    public function generateCustomerServiceResponse(array $messages, ?string $context = null): array
    {
        try {
            // Get latest user message
            $latestUser = null;
            for ($i = count($messages) - 1; $i >= 0; $i--) {
                $m = $messages[$i] ?? null;
                if (is_array($m) && (($m['role'] ?? '') === 'user')) {
                    $latestUser = $m;
                    break;
                }
            }

            if (!$latestUser) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada pertanyaan yang ditemukan.',
                    'error' => 'No user message found'
                ];
            }

            $userQuery = (string)($latestUser['content'] ?? '');
            $userQueryClean = trim($userQuery);

            // ── Response Caching Check (Sub-50ms Latency & $0 Cost) ────────────
            $cacheEnabled = config('services.openai.cache_enabled', true);
            $cacheTtl = (int) config('services.openai.cache_ttl', 3600);
            $normalizedQuery = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $userQueryClean));
            $cacheKey = 'ai_response_cache:' . md5($normalizedQuery);

            if ($cacheEnabled && strlen($normalizedQuery) > 3) {
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    $cachedResult = \Illuminate\Support\Facades\Cache::get($cacheKey);
                    if (is_array($cachedResult) && !empty($cachedResult['message'])) {
                        if ($this->debug) {
                            Log::info('AI response served from cache', ['query' => $userQueryClean, 'cache_key' => $cacheKey]);
                        }
                        $cachedResult['cached'] = true;
                        return $cachedResult;
                    }
                }
            }

            // ── Tiered Model Selection ─────────────────────────────────────────
            // Determine whether to use standard workhorse (gpt-5-mini) or complex reasoning model (gpt-5)
            $complexModel = config('services.openai.complex_model', 'gpt-5');
            $selectedModel = $this->model; // Default: gpt-5-mini / gpt-4o-mini
            
            // Check for complex dispute / multi-year tax penalty signals
            $complexKeywords = ['sengketa', 'hukum', 'sengketa pajak', 'gugatan', 'perhitungan denda 5 tahun', 'mutasi luar provinsi'];
            foreach ($complexKeywords as $ck) {
                if (mb_strpos($normalizedQuery, $ck) !== false) {
                    $selectedModel = $complexModel;
                    break;
                }
            }

            if ($this->debug) Log::info('OpenAI chat start', ['model' => $selectedModel, 'query' => $userQueryClean]);

            // Get dynamic Indonesian calendar and holiday details
            $cal = $this->getCalendarDetails();

            // ── Retrieve relevant knowledge chunks ────────────────────────────
            $relevantKnowledge = $this->getVectorKnowledge($userQuery, $cal);
            if ($this->debug) {
                Log::info('Vector search results', ['count' => count($relevantKnowledge)]);
            }

            // Extract any AI instructions from the retrieved Knowledge Base chunks
            $instructions = [];
            foreach ($relevantKnowledge as $kb) {
                if (!empty($kb['ai_instructions'])) {
                    $instructions[] = trim($kb['ai_instructions']);
                }
            }
            $instructions = array_unique($instructions);

            $calendarContextString = $this->getCalendarContextString($cal);
            $fullContext = ($context ? $context . "\n" : "") . $calendarContextString;

            if (!empty($instructions)) {
                $fullContext .= "\n\nINSTRUKSI & PETUNJUK KHUSUS AI (DARI KNOWLEDGE BASE REFERENSI - HARUS DIIKUTI):\n";
                foreach ($instructions as $idx => $instr) {
                    $fullContext .= ($idx + 1) . ". {$instr}\n";
                }
            }

            // ── Build structured system prompt via SalmaPromptService ─────────
            /** @var \App\Services\SalmaPromptService $salmaPrompt */
            $salmaPrompt  = app(\App\Services\SalmaPromptService::class);
            $kbChunks     = $salmaPrompt->formatKbChunks($relevantKnowledge);
            $detectedIntent = $salmaPrompt->detectIntent($userQuery);

            // ── Fetch always-on correction/override facts (tambahan_sistem) ───
            $corrections = \App\Models\KnowledgeBase::active()->published()
                ->where('type', 'tambahan_sistem')
                ->orderByDesc('priority')
                ->pluck('content')
                ->map(fn($c) => \App\Models\KnowledgeBase::cleanHtml($c))
                ->filter()
                ->values()
                ->toArray();

            $systemPrompt = $salmaPrompt->buildPrompt($kbChunks, $fullContext, $corrections);

            if ($this->debug) {
                Log::info('SalmaPrompt built', [
                    'intent'    => $detectedIntent,
                    'kb_chunks' => count($kbChunks),
                    'prompt_len' => mb_strlen($systemPrompt),
                ]);
            }

            $apiMessages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];

            // Include recent conversation history (last 6 turns for context)
            $historyMessages = array_filter($messages, fn($m) => isset($m['role'], $m['content']));
            $historyMessages = array_slice(array_values($historyMessages), -6);
            foreach ($historyMessages as $msg) {
                $apiMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }

            // Call OpenAI through Circuit Breaker with resilient failover
            $response = $this->circuitBreaker->executeWithFallback(
                primaryCall: function () use ($selectedModel, $apiMessages) {
                    return $this->client->chat()->create([
                        'model'       => $selectedModel,
                        'messages'    => $apiMessages,
                        'max_completion_tokens' => $this->maxTokens,
                    ]);
                },
                fallbackCall: function (\Throwable $e) use ($userQuery) {
                    Log::warning('Circuit breaker fallback triggered in customer service: ' . $e->getMessage());
                    return (object) [
                        'choices' => [
                            (object) [
                                'message' => (object) ['content' => $this->generateFallbackResponse($userQuery)],
                                'finishReason' => 'stop',
                            ]
                        ],
                        'usage' => null,
                    ];
                }
            );

            $choice       = $response->choices[0] ?? null;
            $finishReason = $choice?->finishReason ?? 'unknown';
            $answerText   = $this->cleanAiResponse(trim($choice?->message?->content ?? ''));
            $normUsage    = $this->normalizeUsage($response->usage ?? null);

            // Log finish_reason so we can detect future truncation issues
            if ($this->debug || $finishReason === 'length' || $answerText === '') {
                Log::warning('OpenAI generateCustomerServiceResponse', [
                    'model'         => $selectedModel,
                    'finish_reason' => $finishReason,
                    'content_empty' => $answerText === '',
                    'usage'         => $normUsage,
                    'intent'        => $detectedIntent,
                ]);
            }

            // Guard: empty content with successful API call means truncation or refusal
            if ($answerText === '') {
                Log::error('OpenAI returned empty content', [
                    'finish_reason' => $finishReason,
                    'usage'         => $normUsage,
                    'query_snippet' => mb_substr($userQuery, 0, 100),
                ]);
                return [
                    'success'        => false,
                    'message'        => 'Maaf, SALMA sedang tidak bisa menyusun jawaban saat ini. Silakan coba ajukan pertanyaan kembali, atau hubungi petugas Samsat Lamongan secara langsung.',
                    'usage'          => $normUsage,
                    'knowledge_used' => count($relevantKnowledge),
                    'error'          => 'empty_response:' . $finishReason,
                ];
            }

            $result = [
                'success'        => true,
                'message'        => $answerText,
                'usage'          => $normUsage,
                'knowledge_used' => count($relevantKnowledge),
                'cached'         => false,
                'model_used'     => $selectedModel
            ];

            // ── Cache Result for Zero-Latency & Zero-Cost Repeated Requests ───
            if ($cacheEnabled && strlen($normalizedQuery) > 3) {
                try {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $result, $cacheTtl);
                    $keys = \Illuminate\Support\Facades\Cache::get('ai_response_cache_keys', []);
                    if (is_array($keys) && !in_array($cacheKey, $keys, true)) {
                        $keys[] = $cacheKey;
                        if (count($keys) > 1000) {
                            array_shift($keys);
                        }
                        \Illuminate\Support\Facades\Cache::put('ai_response_cache_keys', $keys, 86400 * 30);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store AI response in cache: ' . $e->getMessage());
                }
            }

            // ── Track Knowledge Gap if retrieved knowledge is weak (score < 0.50 or empty) ──
            $topScore = !empty($relevantKnowledge) ? (float) max(array_column($relevantKnowledge, 'score')) : 0.0;
            if ($topScore < 0.50) {
                preg_match('/Session ID:\s*([^\s,]+)/', (string)$context, $sessMatch);
                $sessionId = $sessMatch[1] ?? null;
                $this->logKnowledgeGap($userQueryClean, 'low_confidence', $topScore, $sessionId);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan sistem. Silakan coba lagi atau hubungi petugas kami.',
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Stream response tokens in real-time via Server-Sent Events (SSE).
     *
     * @param array $messages
     * @param callable $onChunk Callable receiving each token string chunk
     * @param string|null $context
     * @return string Complete synthesized response
     */
    public function generateCustomerServiceStream(array $messages, callable $onChunk, ?string $context = null): string
    {
        $lastUserMsg = '';
        foreach (array_reverse($messages) as $m) {
            if (isset($m['role']) && $m['role'] === 'user') {
                $lastUserMsg = (string)($m['content'] ?? '');
                break;
            }
        }

        $cal = $this->getCalendarDetails();
        $relevantKnowledge = $this->getVectorKnowledge($lastUserMsg, $cal);

        /** @var \App\Services\SalmaPromptService $salmaPrompt */
        $salmaPrompt = app(\App\Services\SalmaPromptService::class);
        $kbChunks = $salmaPrompt->formatKbChunks($relevantKnowledge);
        $fullContext = ($context ? $context . "\n" : "") . $this->getCalendarContextString($cal);

        $systemPrompt = $salmaPrompt->buildPrompt($kbChunks, $fullContext);

        $apiMessages = [['role' => 'system', 'content' => $systemPrompt]];
        $history = array_slice(array_filter($messages, fn($m) => isset($m['role'], $m['content'])), -6);
        foreach ($history as $msg) {
            $apiMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $completeText = '';
        $stream = $this->client->chat()->createStreamed([
            'model' => $this->model,
            'messages' => $apiMessages,
            'max_completion_tokens' => $this->maxTokens,
        ]);

        foreach ($stream as $response) {
            $delta = $response->choices[0]->delta->content ?? '';
            if ($delta !== '') {
                $completeText .= $delta;
                $onChunk($delta);
            }
        }

        return $completeText;
    }

    /**
     * Log an unanswered or low-confidence query to the knowledge_gaps table.
     */
    public function logKnowledgeGap(string $query, string $source = 'low_confidence', ?float $similarityScore = null, ?string $sessionId = null): void
    {
        try {
            $clean = trim($query);
            if (mb_strlen($clean) < 4) return;

            $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean));
            $normalized = trim(preg_replace('/\s+/u', ' ', $normalized));
            if (empty($normalized)) return;

            $gap = \App\Models\KnowledgeGap::where('normalized_query', $normalized)->first();
            if ($gap) {
                $gap->increment('frequency');
                $gap->update([
                    'last_seen_at' => now(),
                    'similarity_score' => $similarityScore !== null ? min((float)$gap->similarity_score, $similarityScore) : $gap->similarity_score,
                ]);
            } else {
                \App\Models\KnowledgeGap::create([
                    'query' => $clean,
                    'normalized_query' => mb_substr($normalized, 0, 255),
                    'source' => $source,
                    'similarity_score' => $similarityScore,
                    'frequency' => 1,
                    'session_id' => $sessionId,
                    'status' => 'pending',
                    'last_seen_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to log knowledge gap: ' . $e->getMessage());
        }
    }

    /**
     * Generate a complete, publication-ready Knowledge Base draft using OpenAI from a user query or gap.
     * Returns: ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function generateKnowledgeBaseDraft(string $query, ?string $context = null): array
    {
        try {
            $systemPrompt = <<<PROMPT
Anda adalah Tenaga Ahli Kurator Konten dan Kebijakan Pelayanan Bapenda (Badan Pendapatan Daerah) Samsat Jawa Timur.
Tugas Anda: Membuat draf artikel Knowledge Base yang lengkap, profesional, akurat, dan ramah pengguna berdasarkan pertanyaan/isu wajib pajak.

Panduan:
- Tuliskan jawaban dalam Bahasa Indonesia yang profesional, jelas, terstruktur (gunakan bullet points atau penomoran markdown).
- Jangan halusinasi tarif/nominal uang sembarangan jika tidak ada kepastian, jelaskan komponen biaya (PKB pokok, SWDKLLJ, biaya administrasi STNK/TNKB sesuai PP PNBP Polri).
- Tentukan kategori yang paling tepat: pajak, jadwal, lokasi, persyaratan, panduan, denda, atau umum.
- Tentukan tipe dokumen: faq, regulation, guide, announcement, atau operational_hour.
- Buat daftar tag yang relevan dipisahkan dengan koma.
- Berikan instruksi khusus AI (ai_instructions) jika relevan untuk memandu AI chatbot saat merujuk artikel ini.

Kembalikan respon HANYA dalam format JSON valid dengan struktur berikut:
{
  "title": "Judul Artikel yang Menarik & Informatif",
  "question": "Pertanyaan Lengkap Wajib Pajak",
  "answer": "Jawaban Ringkas & Jelas (1-2 paragraf)",
  "content": "Konten Lengkap & Detail dengan Panduan Langkah/Syarat (Markdown)",
  "category": "pajak|jadwal|lokasi|persyaratan|panduan|denda|umum",
  "type": "faq|regulation|guide|announcement|operational_hour",
  "tags": "tag1, tag2, tag3",
  "ai_instructions": "Petunjuk taktis untuk chatbot AI saat membaca artikel ini"
}
PROMPT;

            $userContent = "Pertanyaan/Isu Wajib Pajak:\n" . $query;
            if (!empty($context)) {
                $userContent .= "\n\nKonteks Percakapan/Feedback Tambahan:\n" . $context;
            }

            $response = $this->retryRequest(function () use ($systemPrompt, $userContent) {
                return $this->client->chat()->create([
                    'model' => config('services.openai.model', 'gpt-5-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_completion_tokens' => $this->maxTokens,
                ]);
            });

            $text = trim($response->choices[0]->message->content ?? '');
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $parsed = json_decode($text, true);
            if (!is_array($parsed) || empty($parsed['title'])) {
                return [
                    'success' => false,
                    'error' => 'Gagal mengurai respon AI draf Knowledge Base: ' . mb_substr($text, 0, 200)
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'title' => $parsed['title'] ?? $query,
                    'question' => $parsed['question'] ?? $query,
                    'answer' => $parsed['answer'] ?? '',
                    'content' => $parsed['content'] ?? ($parsed['answer'] ?? ''),
                    'category' => $parsed['category'] ?? 'pajak',
                    'type' => $parsed['type'] ?? 'faq',
                    'tags' => $parsed['tags'] ?? '',
                    'ai_instructions' => $parsed['ai_instructions'] ?? '',
                ]
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAIService::generateKnowledgeBaseDraft error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Get relevant knowledge using hybrid retrieval: Pinecone vector search + DB full-text.
     *
     * Strategy:
     *  1. Vector search with topK=15 — wide net across all indexed chunks
     *  2. DB full-text search — always runs to catch documents not well-covered by vectors
     *  3. Merge: vector results are primary; DB results fill in any uncovered documents
     *
     * This ensures queries that span multiple source documents (e.g. "biaya balik nama")
     * receive relevant chunks from EVERY matching document, not just the top-ranked one.
     */
    private function getVectorKnowledge(string $userQuery, ?array $cal = null): array
    {
        $cal = $cal ?? $this->getCalendarDetails();
        $searchQuery = $this->expandQueryForTemporalContext($userQuery, $cal);

        $vectorResults = [];
        $vectorOk      = false;

        // ── Phase 1: Pinecone vector search ──────────────────────────────────
        try {
            if (class_exists(\App\Services\EmbeddingService::class) && class_exists(\App\Services\PineconeService::class)) {
                $embeddingService = app(EmbeddingService::class);
                $pineconeService  = app(PineconeService::class);

                $queryEmbedding = $embeddingService->embed($searchQuery);
                if ($queryEmbedding) {
                    // topK=15 casts a wide net so chunks from ALL relevant documents surface
                    $matches = $pineconeService->query(
                        vector: $queryEmbedding,
                        topK: 15
                    );

                    $seenKbIds = [];
                    foreach ($matches as $match) {
                        $score    = $match['score'] ?? 0;
                        $metadata = $match['metadata'] ?? [];
                        $kbId     = $metadata['kb_id'] ?? null;

                        // Lower threshold (0.30) accepts marginally relevant chunks from secondary docs
                        if ($score < 0.30) continue;

                        // Allow up to 5 chunks from the same document for multi-part answers
                        $countFromDoc = $seenKbIds[$kbId] ?? 0;
                        if ($kbId && $countFromDoc >= 5) continue;
                        $seenKbIds[$kbId] = $countFromDoc + 1;

                        $vectorResults[] = [
                            'id'              => $kbId,
                            'title'           => $metadata['title'] ?? '',
                            'content'         => $metadata['chunk_text'] ?? '',
                            'answer'          => $metadata['chunk_text'] ?? '',
                            'category'        => $metadata['category'] ?? '',
                            'ai_instructions' => $metadata['ai_instructions'] ?? '',
                            'score'           => $score,
                            '_source'         => 'vector',
                        ];
                    }
                    $vectorOk = true;
                }
            }
        } catch (Exception $e) {
            if ($this->debug) Log::warning('Vector search failed, using DB only: ' . $e->getMessage());
        }

        // ── Phase 2: DB full-text search (always runs) ───────────────────────
        // Supplements vector results to guarantee coverage of ALL matching documents,
        // especially when a document is under-represented in the vector index.
        $dbResults = $this->getDatabaseKnowledge($searchQuery);

        if ($vectorOk && !empty($vectorResults)) {
            // IDs already covered by vector results
            $coveredIds = array_unique(array_column($vectorResults, 'id'));

            // Append DB results for documents NOT yet represented in vector results
            foreach ($dbResults as $dbRow) {
                $dbRow['_source'] = 'db';
                if (!in_array($dbRow['id'], $coveredIds, true)) {
                    $vectorResults[] = $dbRow;
                    $coveredIds[]    = $dbRow['id'];
                }
            }

            if ($this->debug) Log::info('Hybrid retrieval complete', [
                'vector_chunks' => count(array_filter($vectorResults, fn($r) => ($r['_source'] ?? '') === 'vector')),
                'db_supplements' => count(array_filter($vectorResults, fn($r) => ($r['_source'] ?? '') === 'db')),
                'total' => count($vectorResults),
                'top_score' => $vectorResults[0]['score'] ?? 0,
            ]);

            $results = $this->injectReferencedKnowledge($vectorResults);
            return $this->injectAIInstructionReferencedKnowledge($results);
        }

        // Pure DB fallback when vector search could not run at all
        $results = $this->injectReferencedKnowledge($dbResults);
        return $this->injectAIInstructionReferencedKnowledge($results);
    }

    private function getDatabaseKnowledge(string $userQuery): array
    {
        $query = strtolower($userQuery);

        // ── Smarter keyword extraction ────────────────────────────────────────
        // Skip common Indonesian function/stop words so we keep semantic terms.
        $stopWords = [
            'saya',
            'anda',
            'kamu',
            'dia',
            'kami',
            'kita',
            'mereka',
            'ini',
            'itu',
            'yang',
            'di',
            'ke',
            'dari',
            'dan',
            'atau',
            'dengan',
            'untuk',
            'pada',
            'bisa',
            'akan',
            'ada',
            'tidak',
            'ya',
            'juga',
            'sudah',
            'mau',
            'maka',
            'apa',
            'bagaimana',
            'kenapa',
            'apakah',
            'kalau',
            'jika',
            'saja',
            'hanya',
            'paling',
            'enaknya',
            'ingin',
            'boleh',
            'bagi',
            'sama',
            'sini',
            'sana',
            'situ',
            'gimana',
            'dong',
            'bantu',
            'tolong',
            'mohon',
            'info',
            'tanya',
            'tentang',
            'soal',
            'mengenai',
            'gimana',
            'gitu',
            'gini',
            'nih',
            'tuh',
            'deh',
            'lah',
            'dong',
            'kak',
            'pak',
            'bu',
            'mas',
            'mbak',
            'bang',
            'om',
            'gan',
            'bro',
            'sis',
            'min',
            'admin',
            'halo',
            'hai',
        ];

        $allWords = preg_split('/[\s,.\-!?:;]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $keywords = array_values(array_filter(
            $allWords,
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords, true)
        ));
        // Take up to 8 meaningful keywords
        $keywords = array_slice($keywords, 0, 8);
        // Final fallback: if entire query was stop-words, use all words up to 5
        if (empty($keywords)) {
            $keywords = array_slice($allWords, 0, 5);
        }

        // ── Query builder ─────────────────────────────────────────────────────
        $knowledge = KnowledgeBase::active()
            ->published()
            ->where(function ($q) use ($keywords, $query) {
                // Primary: MySQL FULLTEXT for relevance ranking
                $q->whereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);
                // Secondary: individual keyword LIKE fallback
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('search_content', 'LIKE', "%{$keyword}%");
                }
            })
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['id', 'title', 'content', 'answer', 'category', 'search_content', 'ai_instructions'])
            ->map(function ($kb) use ($keywords) {
                // Relevance scoring: title matches are weighted 3× over content
                $title   = mb_strtolower($kb->title ?? '');
                // Score against clean text (search_content has entities decoded)
                $content = mb_strtolower($kb->search_content ?? strip_tags($kb->content ?? ''));
                $score   = 0.12; // Base score to pass threshold

                foreach ($keywords as $keyword) {
                    if (mb_strpos($title, $keyword) !== false)   $score += 0.35;
                    if (mb_strpos($content, $keyword) !== false) $score += 0.12;
                }

                return [
                    'id'              => $kb->id,
                    'title'           => $kb->title,
                    'content'         => $kb->content,
                    'answer'          => $kb->answer,
                    'category'        => $kb->category,
                    'ai_instructions' => $kb->ai_instructions,
                    'search_content'  => $kb->search_content,
                    'score'           => min($score, 1.0),
                ];
            })
            ->filter(fn($kb) => $kb['score'] >= 0.15)   // lower threshold = catch more
            ->sortByDesc('score')
            ->values()
            ->toArray();

        if ($this->debug) Log::info('DB search completed', [
            'query'    => $userQuery,
            'keywords' => $keywords,
            'results'  => count($knowledge),
        ]);
        return $knowledge;
    }

    /**
     * Scan knowledge base entries for "referensi: Title A, Title B" and query matching KB entries
     * from the database to inject them dynamically.
     */
    private function injectReferencedKnowledge(array $results): array
    {
        $referencedTitles = [];

        // 1. Scan existing results for "referensi: ..." or "reference: ..."
        foreach ($results as $item) {
            $content = $item['content'] ?? $item['answer'] ?? '';
            if (empty($content)) {
                continue;
            }

            // Clean strip tags first to get clean text for regex matching
            $cleanText = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Match "referensi: ..." or "reference: ..." up to the end of the line or period
            // Case-insensitive, supporting spaces around colon
            if (preg_match('/(?:referensi|reference)\s*:\s*([^\n\r.]+)/i', $cleanText, $matches)) {
                $rawList = $matches[1];
                // Split by comma
                $parts = explode(',', $rawList);
                foreach ($parts as $part) {
                    $trimmed = trim($part);
                    if (!empty($trimmed) && strlen($trimmed) >= 3) {
                        $referencedTitles[] = $trimmed;
                    }
                }
            }
        }

        if (empty($referencedTitles)) {
            return $results;
        }

        $referencedTitles = array_unique($referencedTitles);
        $injectedIds = array_filter(array_column($results, 'id'));
        $extraResults = [];

        // 2. Search for each referenced title in the database
        foreach ($referencedTitles as $refTitle) {
            // Find in database with a smart search:
            // First try exact case-insensitive match on title:
            $exactMatches = KnowledgeBase::active()->published()
                ->where('title', 'like', $refTitle)
                ->select(['id', 'title', 'content', 'category', 'ai_instructions'])
                ->get();

            if ($exactMatches->isNotEmpty()) {
                foreach ($exactMatches as $match) {
                    if (!in_array($match->id, $injectedIds, true)) {
                        $extraResults[] = $this->formatDbRowForKnowledge($match, 'reference_exact', 0.95);
                        $injectedIds[] = $match->id;
                    }
                }
                continue;
            }

            // Next, try a partial match: title containing the referenced term
            $partialMatches = KnowledgeBase::active()->published()
                ->where('title', 'like', "%{$refTitle}%")
                ->select(['id', 'title', 'content', 'category', 'ai_instructions'])
                ->get();

            if ($partialMatches->isNotEmpty()) {
                foreach ($partialMatches as $match) {
                    if (!in_array($match->id, $injectedIds, true)) {
                        $extraResults[] = $this->formatDbRowForKnowledge($match, 'reference_partial', 0.85);
                        $injectedIds[] = $match->id;
                    }
                }
                continue;
            }

            // Finally, if still no match, split the referenced title into words and try to match
            // any active published KB title containing all keywords (excluding small stop words)
            $words = array_filter(explode(' ', $refTitle), function($word) {
                return strlen(trim($word)) > 3; // Keep only substantial words
            });

            if (!empty($words)) {
                $keywordQuery = KnowledgeBase::active()->published()
                    ->select(['id', 'title', 'content', 'category', 'ai_instructions']);
                
                foreach ($words as $word) {
                    $keywordQuery->where('title', 'like', "%{$word}%");
                }
                
                $keywordMatches = $keywordQuery->get();
                foreach ($keywordMatches as $match) {
                    if (!in_array($match->id, $injectedIds, true)) {
                        $extraResults[] = $this->formatDbRowForKnowledge($match, 'reference_keyword', 0.75);
                        $injectedIds[] = $match->id;
                    }
                }
            }
        }

        // 3. Append the referenced results to the end of the collection
        if (!empty($extraResults)) {
            if ($this->debug) {
                Log::info('Injected cross-referenced knowledge base documents', [
                    'referenced_titles' => $referencedTitles,
                    'injected_count'    => count($extraResults),
                ]);
            }
            $results = array_merge($results, $extraResults);
        }

        return $results;
    }

    /**
     * Helper to format a KnowledgeBase Eloquent model into the standard retrieval array format.
     */
    private function formatDbRowForKnowledge($match, string $source, float $score): array
    {
        return [
            'id'              => $match->id,
            'title'           => $match->title,
            'content'         => $match->content,
            'answer'          => $match->content,
            'category'        => $match->category,
            'ai_instructions' => $match->ai_instructions,
            'search_content'  => strip_tags($match->content),
            'score'           => $score,
            '_source'         => $source,
        ];
    }

    /**
     * Cache result with size limit
     */
    private function cacheResult(string $key, array $result): void
    {
        // Limit cache size
        if (count(self::$queryCache) >= self::$cacheLimit) {
            // Remove oldest entries (simple FIFO)
            $keysToRemove = array_slice(array_keys(self::$queryCache), 0, 10);
            foreach ($keysToRemove as $oldKey) {
                unset(self::$queryCache[$oldKey]);
            }
        }

        self::$queryCache[$key] = $result;
    }

    /**
     * Build comprehensive response from single KB entry (IMPROVED WITH JAVANESE)
     */
    private function buildComprehensiveResponse($knowledge, string $userMessage, bool $isJavanese = false): ?string
    {
        $content = $knowledge['content'] ?: $knowledge['answer'] ?: $knowledge['search_content'];
        if (empty($content)) return null;

        // Process rich content more efficiently
        try {
            $richContentProcessor = app(RichContentProcessor::class);
            $richContent = $richContentProcessor->extractRichContent($content);
            $formattedContent = $richContentProcessor->formatForAIResponse($richContent);
        } catch (\Throwable $e) {
            // Fallback to plain text if rich content processing fails
            $formattedContent = strip_tags($content);
        }

        // Build structured response based on category
        $category = strtolower($knowledge['category'] ?? '');
        $title = $knowledge['title'] ?? '';

        $response = "**{$title}**\n\n";

        // Add content with better formatting
        if (strlen($formattedContent) > 100) {
            $response .= $formattedContent;
        } else {
            // If content is too short, add some context
            $response .= $formattedContent . "\n\n";

            // Add helpful context based on category
            switch ($category) {
                case 'jadwal':
                    $response .= "**Informasi Tambahan:**\n";
                    $response .= "- Pastikan datang 30 menit sebelum jam tutup\n";
                    $response .= "- Bawa dokumen yang diperlukan\n";
                    $response .= "- Siapkan uang pas untuk pembayaran\n";
                    break;
                case 'lokasi':
                    $response .= "**Tips Kunjungan:**\n";
                    $response .= "- Gunakan transportasi umum jika memungkinkan\n";
                    $response .= "- Datang pagi untuk menghindari antrian panjang\n";
                    break;
                case 'biaya':
                    $response .= "**Catatan Penting:**\n";
                    $response .= "- Biaya dapat berubah sewaktu-waktu\n";
                    $response .= "- Siapkan uang pas atau exact\n";
                    $response .= "- Tanyakan detail biaya saat di lokasi\n";
                    break;
            }
        }

        // Add Javanese-friendly closing if detected
        if ($isJavanese) {
            $response .= "\n\n**Butuh bantuan lebih lanjut?**\n";
            $response .= "Monggo hubungi Samsat Lamongan langsung atau berkunjung ke kantor kami untuk informasi terkini.";
        } else {
            $response .= "\n\n**Butuh bantuan lebih lanjut?**\n";
            $response .= "Hubungi Samsat Lamongan langsung atau kunjungi kantor kami untuk informasi terkini.";
        }

        return $response;
    }
    /**
     * Build fast response from single KB entry (LEGACY - kept for compatibility)
     */
    private function buildFastResponse($knowledge, string $userMessage): ?string
    {
        // Delegate to comprehensive response for better quality
        if (is_array($knowledge)) {
            return $this->buildComprehensiveResponse($knowledge, $userMessage);
        }

        // Handle Eloquent model
        $kbArray = [
            'title' => $knowledge->title ?? '',
            'content' => $knowledge->content ?? '',
            'answer' => $knowledge->answer ?? '',
            'search_content' => $knowledge->search_content ?? '',
            'category' => $knowledge->category ?? ''
        ];

        return $this->buildComprehensiveResponse($kbArray, $userMessage);
    }

    private function getLatestUserMessage(array $messages): ?string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return $messages[$i]['content'] ?? null;
            }
        }
        return null;
    }

    /**
     * Build knowledge context from vector search results with rich content support
     */
    private function buildKnowledgeContext(array $similarKnowledge): string
    {
        if (empty($similarKnowledge)) {
            return '';
        }

        $contextParts = [];
        $richContentProcessor = app(RichContentProcessor::class);

        foreach ($similarKnowledge as $index => $match) {
            $metadata = $match['metadata'] ?? [];
            $score = $match['score'] ?? 0;

            // Only include high-relevance matches (score > 0.3 for now)
            if ($score < 0.3) {
                continue;
            }

            $chunkText = $metadata['chunk_text'] ?? '';

            // Process rich content if available
            $richContent = $richContentProcessor->extractRichContent($chunkText);
            $formattedContent = $richContentProcessor->formatForAIResponse($richContent);
            $aiInstructions = $richContentProcessor->generateAIInstructions($richContent);

            $contextParts[] = "Referensi " . ($index + 1) . " (Relevance: " . round($score, 2) . "):\n" .
                "Judul: " . ($metadata['title'] ?? 'Tidak diketahui') . "\n" .
                "Kategori: " . ($metadata['category'] ?? 'umum') . "\n" .
                "Konten: " . $formattedContent . "\n" .
                $aiInstructions;
        }

        if (empty($contextParts)) {
            return '';
        }

        return "REFERENSI KNOWLEDGE BASE:\n\n" . implode("\n---\n\n", $contextParts);
    }

    /**
     * Build knowledge context using traditional keyword search as fallback (IMPROVED ACCURACY)
     */
    private function buildTraditionalKnowledgeContext(string $userMessage): string
    {
        // Extract keywords more efficiently
        $keywords = $this->extractKeywordsOptimized($userMessage);

        // IMPROVEMENT: Even if no keywords, try a broader search
        if (empty($keywords)) {
            // Fallback: search with the full message if it's short enough
            if (strlen($userMessage) <= 100) {
                $keywords = [trim(strtolower($userMessage))];
            } else {
                return '';
            }
        }

        // IMPROVEMENT: More comprehensive search strategy
        $knowledge = \App\Models\KnowledgeBase::active()
            ->published()
            ->where(function ($query) use ($keywords, $userMessage) {
                // Try fulltext search first (broader)
                $query->whereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$userMessage]);

                // Add targeted LIKE searches for all keywords (not just 3)
                $query->orWhere(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        if (strlen($keyword) >= 3) { // Only meaningful keywords
                            $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                ->orWhere('search_content', 'LIKE', "%{$keyword}%")
                                ->orWhere('content', 'LIKE', "%{$keyword}%")
                                ->orWhere('answer', 'LIKE', "%{$keyword}%");
                        }
                    }
                });
            })
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(3) // Keep 3 for better context
            ->get(['id', 'title', 'content', 'answer', 'category', 'search_content'])
            ->toArray();

        if (empty($knowledge)) {
            return '';
        }

        $contextParts = [];
        foreach ($knowledge as $index => $kb) {
            $content = $kb['content'] ?? '';
            if (empty($content) && !empty($kb['answer'])) {
                $content = $kb['answer'];
            }
            if (empty($content) && !empty($kb['search_content'])) {
                $content = $kb['search_content'];
            }

            // Strip HTML and limit length - but keep more content for accuracy
            $plainContent = strip_tags($content);
            $plainContent = mb_substr($plainContent, 0, 600); // Increased from 300 for better accuracy

            $contextParts[] = "Referensi " . ($index + 1) . ":\n" .
                "Judul: " . ($kb['title'] ?? 'N/A') . "\n" .
                "Kategori: " . ($kb['category'] ?? 'umum') . "\n" .
                "Konten: " . $plainContent;
        }

        return "REFERENSI KNOWLEDGE BASE:\n\n" . implode("\n---\n\n", $contextParts);
    }
    /**
     * Extract keywords more efficiently
     */
    private function extractKeywordsOptimized(string $message): array
    {
        // Quick normalization
        $cleanMessage = strtolower($message);
        $cleanMessage = preg_replace('/[^a-z0-9_\-\s]/u', ' ', $cleanMessage);
        $tokens = preg_split('/\s+/', $cleanMessage, -1, PREG_SPLIT_NO_EMPTY);

        // Minimal stopwords
        $stop = ['dan', 'atau', 'yang', 'untuk', 'dengan', 'di', 'ke', 'dari', 'pada', 'ini', 'itu', 'apa', 'saya', 'ya', 'tidak'];

        $terms = [];
        foreach ($tokens as $t) {
            if (strlen($t) < 3) continue;
            if (in_array($t, $stop, true)) continue;
            $terms[] = $t;
        }

        // Return only top keywords for speed
        return array_unique(array_slice($terms, 0, 5)); // Reduced from 10
    }



    /**
     * Build system prompt for customer service AI
     */
    private function buildSystemPrompt(string $kbContext, ?string $additionalContext = null): string
    {
        $basePrompt = "Anda adalah SALMA AI - Asisten Customer Service profesional untuk Bapenda (Badan Pendapatan Daerah) Samsat Lamongan, Jawa Timur.

IDENTITAS & PERAN:
- Nama: SALMA AI (Sistem Asisten Layanan Masyarakat AI)
- Institusi: Bapenda Samsat Lamongan, Jawa Timur
- Peran: Customer Service AI yang ramah, profesional, dan membantu

GAYA KOMUNIKASI:
- Gunakan Bahasa Indonesia baku dan semi-formal
- Bersikap ramah, sopan, dan profesional
- Berikan informasi yang akurat dan mudah dipahami
- Jawaban harus informatif, jelas, dan sekitar 200-500 kata
- Gunakan format yang terstruktur dengan paragraf dan poin jika diperlukan

TUGAS UTAMA:
1. Membantu wajib pajak dengan informasi layanan Samsat Lamongan
2. Memberikan informasi seputar pajak kendaraan bermotor
3. Menjelaskan prosedur, syarat, dan tarif layanan
4. Memberikan panduan lokasi, jadwal, dan cara pembayaran
5. Membantu dengan pertanyaan STNK, BPKB, dan dokumen kendaraan

PEDOMAN MENJAWAB:
- PRIORITASKAN informasi dari Knowledge Base yang tersedia
- Jika Knowledge Base tidak lengkap, tambahkan informasi umum yang akurat
- Berikan langkah-langkah yang jelas dan mudah diikuti
- Sertakan informasi kontak atau lokasi jika relevan
- Jika tidak yakin, arahkan untuk menghubungi petugas langsung
- Selalu akhiri dengan penawaran bantuan lebih lanjut

FORMAT RICH CONTENT:
- PENTING: Jika ada gambar dalam referensi, SELALU sertakan menggunakan format markdown: ![Deskripsi Gambar](URL_gambar)
- Jika referensi mengandung link, sertakan dalam format: [Text Link](URL)
- Gunakan struktur heading (##, ###) untuk mengorganisir informasi
- Gunakan daftar berurut (1., 2., 3.) untuk langkah-langkah prosedur
- Gunakan daftar tidak berurut (-) untuk syarat atau poin-poin
- Gunakan **bold** untuk menekankan poin penting
- Pertahankan dan konversi semua gambar/link dari Knowledge Base ke markdown
- Jika ada gambar tutorial atau infografis, pastikan menyertakannya dalam respons

LARANGAN:
- Jangan memberikan informasi yang tidak akurat atau spekulatif
- Jangan memproses transaksi atau pembayaran
- Jangan meminta data pribadi sensitif
- Jangan memberikan janji yang tidak bisa dipenuhi";

        if (!empty($kbContext)) {
            $basePrompt .= "\n\n" . $kbContext . "\n\nGunakan informasi di atas sebagai referensi utama untuk menjawab pertanyaan.";
        } else {
            $basePrompt .= "\n\nTidak ada informasi spesifik dari Knowledge Base untuk pertanyaan ini. Berikan jawaban umum yang akurat atau arahkan pengguna untuk menghubungi Samsat Lamongan langsung.";
        }

        if ($additionalContext) {
            $basePrompt .= "\n\nKONTEKS TAMBAHAN:\n" . $additionalContext;
        }

        return $basePrompt;
    }

    /**
     * Generate fallback response when AI processing fails (IMPROVED)
     */
    private function generateFallbackResponse(string $userMessage): array
    {
        // IMPROVEMENT: Try more aggressive KB retrieval as fallback
        $simpleKb = $this->simpleRetrieveKb($userMessage);

        if (!empty($simpleKb)) {
            $kbContent = $simpleKb[0]['content'] ?? $simpleKb[0]['answer'] ?? $simpleKb[0]['search_content'] ?? '';
            $kbTitle = $simpleKb[0]['title'] ?? '';

            if (!empty($kbContent)) {
                // Build a better formatted response
                $response = "**Informasi yang Ditemukan:**\n\n";
                if (!empty($kbTitle)) {
                    $response .= "**{$kbTitle}**\n\n";
                }
                $response .= strip_tags($kbContent);
                $response .= "\n\n**Butuh informasi lebih lengkap?**\n";
                $response .= "Silakan hubungi Samsat Lamongan langsung atau kunjungi kantor kami untuk detail terkini.";

                return [
                    'success' => true,
                    'message' => $response,
                    'usage' => null,
                    'knowledge_used' => count($simpleKb)
                ];
            }
        }

        // IMPROVEMENT: Categorize the query and provide specific guidance
        $queryLower = strtolower($userMessage);
        $specificGuidance = '';

        if (strpos($queryLower, 'jadwal') !== false || strpos($queryLower, 'jam') !== false) {
            $specificGuidance = "\n\n**Untuk informasi jadwal:**\n" .
                "- Samsat Lamongan: Senin-Jumat 08:00-15:00\n" .
                "- Samsat Keliling: Jadwal bervariasi per lokasi\n" .
                "- Hubungi (0322) 311234 untuk jadwal terkini";
        } elseif (strpos($queryLower, 'biaya') !== false || strpos($queryLower, 'tarif') !== false) {
            $specificGuidance = "\n\n**Untuk informasi biaya:**\n" .
                "- Biaya bervariasi tergantung jenis kendaraan dan tahun\n" .
                "- Hubungi Samsat untuk tarif terkini\n" .
                "- Siapkan STNK/BPKB untuk cek biaya yang tepat";
        } elseif (strpos($queryLower, 'lokasi') !== false || strpos($queryLower, 'alamat') !== false) {
            $specificGuidance = "\n\n**Lokasi Samsat Lamongan:**\n" .
                "Jl. Veteran No. 1, Lamongan, Jawa Timur\n" .
                "Telepon: (0322) 311234";
        } elseif (strpos($queryLower, 'stnk') !== false || strpos($queryLower, 'perpanjang') !== false) {
            $specificGuidance = "\n\n**Untuk perpanjangan STNK:**\n" .
                "- Bawa STNK asli dan fotokopi\n" .
                "- KTP asli dan fotokopi\n" .
                "- Kendaraan untuk cek fisik (jika diperlukan)\n" .
                "- Pelunasan pajak sebelumnya";
        }

        // Ultimate fallback with specific guidance
        return [
            'success' => true,
            'message' => 'Maaf, saya belum dapat menemukan informasi spesifik untuk pertanyaan Anda dalam database kami saat ini.' .
                $specificGuidance .
                "\n\n**Kontak Samsat Lamongan:**\n" .
                "📞 Telepon: (0322) 311234\n" .
                "📍 Alamat: Jl. Veteran No. 1, Lamongan, Jawa Timur\n" .
                "🕒 Jam Operasional: Senin-Jumat 08:00-15:00\n\n" .
                "Tim customer service kami siap membantu Anda dengan informasi terkini dan akurat.",
            'usage' => null,
            'knowledge_used' => 0
        ];
    }

    /**
     * Simple KB retrieval using traditional database search
     */
    private function simpleRetrieveKb(string $query): array
    {
        // Simple MATCH AGAINST with LIKE fallback
        $fulltext = KnowledgeBase::active()
            ->published()
            ->selectRaw('id, title, content, answer, category, type, tags, MATCH(title, search_content) AGAINST (? IN NATURAL LANGUAGE MODE) as score', [$query])
            ->whereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query])
            ->orderByDesc('score')
            ->limit(3)
            ->get()
            ->toArray();

        if (!empty($fulltext)) {
            return $fulltext;
        }

        // Fallback to LIKE search
        $like = KnowledgeBase::active()
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%")
                    ->orWhere('answer', 'LIKE', "%{$query}%")
                    ->orWhere('search_content', 'LIKE', "%{$query}%");
            })
            ->limit(3)
            ->get(['id', 'title', 'content', 'answer', 'category', 'type', 'tags'])
            ->toArray();

        return $like;
    }

    /**
     * Classify chats into categories and determine sentiment
     * @param array $chats Each: ['chat_id' => string|int, 'text' => string]
     * @param array $categoryLabels key => human label
     * @return array ['success'=>bool,'message'=>string,'data'=>array|null]
     */
    public function classifyChats(array $chats, array $categoryLabels): array
    {
        try {
            if (empty($chats)) return ['success' => true, 'message' => 'No chats', 'data' => []];

            $model = config('services.openai.model', 'gpt-5-mini');

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

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 800,
            ]);

            $text = trim($response->choices[0]->message->content ?? '');
            $arr = json_decode($text, true);
            if (!is_array($arr)) return ['success' => false, 'message' => 'Parse failed', 'data' => null];
            return ['success' => true, 'message' => $text, 'data' => $arr];
        } catch (\Exception $e) {
            Log::error('AI classify error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Generate greeting message
     */
    public function generateGreeting(): string
    {
        return "Halo! Saya Salma AI. ada yang bisa saya bantu ?";
    }

    /**
     * Score a KnowledgeBase entry using AI (0.00 – 1.00).
     * Returns: ['success' => bool, 'score' => float, 'breakdown' => [...], 'reasoning' => string]
     */
    public function scoreKnowledgeBase(\App\Models\KnowledgeBase $kb): array
    {
        try {
            $contentSample = mb_substr((string) ($kb->search_content ?: $kb->content ?: $kb->answer ?: ''), 0, 2000);
            $answerSample  = mb_substr((string) ($kb->answer ?: ''), 0, 1500);
            $title         = (string) ($kb->title ?: '');
            $question      = (string) ($kb->question ?: '');

            $systemPrompt = <<<PROMPT
You are an expert knowledge base quality evaluator. Score the entry across 5 dimensions (each 0–20):
1. Content Completeness – Does the answer/content fully address the question?
2. Clarity & Readability – Is the text clear, well-structured, and easy to understand?
3. Accuracy & Depth – Is information accurate, specific, and sufficiently detailed?
4. Practical Usefulness – Can a user immediately act on this information?
5. Keyword Relevance – Are important terms and context well represented?
Sum all scores (0–100), then divide by 100 to produce a float score 0.00–1.00.
Return ONLY valid JSON with this shape:
{"score":0.82,"breakdown":{"completeness":17,"clarity":16,"accuracy":16,"usefulness":17,"keywords":16},"reasoning":"One sentence summary."}
PROMPT;

            $userContent = "Title: {$title}\nQuestion: {$question}\nAnswer (excerpt): {$answerSample}\nContent (excerpt): {$contentSample}";

            $response = $this->retryRequest(function () use ($systemPrompt, $userContent) {
                return $this->client->chat()->create([
                    'model'                  => config('services.openai.model', 'gpt-5-mini'),
                    'messages'               => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userContent],
                    ],
                    'max_completion_tokens'  => 400,
                ]);
            });

            $text = trim($response->choices[0]->message->content ?? '');
            // Strip markdown code fences if any
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $parsed = json_decode($text, true);
            if (!is_array($parsed) || !isset($parsed['score'])) {
                return ['success' => false, 'error' => 'Failed to parse AI scoring response: ' . $text];
            }

            $score = max(0.0, min(1.0, (float) $parsed['score']));

            return [
                'success'   => true,
                'score'     => round($score, 2),
                'breakdown' => $parsed['breakdown'] ?? null,
                'reasoning' => $parsed['reasoning'] ?? null,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OpenAIService::scoreKnowledgeBase error', ['id' => $kb->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enhance a KnowledgeBase entry's content using GPT-4o.
     * Improves quality while preserving all facts. Aims for score 0.85–0.99.
     * Returns: ['success' => bool, 'title' => string, 'question' => string, 'answer' => string, 'content' => string, 'keywords' => array, 'changes_summary' => string]
     */
    public function enhanceKnowledgeBase(\App\Models\KnowledgeBase $kb): array
    {
        try {
            $systemPrompt = <<<PROMPT
You are an expert knowledge base content enhancer for an Indonesian government tax service (Bapenda/Samsat).
Your task: improve the provided KB entry to achieve a quality score of 0.85–0.99.

Rules:
- PRESERVE all factual information, dates, times, locations, prices, schedules
- Improve clarity, structure, completeness, and practical usefulness
- Write in clear, professional Indonesian (Bahasa Indonesia)
- Expand thin or vague answers into comprehensive, actionable responses
- Add useful context where appropriate (without hallucinating facts)
- Return ONLY valid JSON with this exact shape:

{
  "title": "improved title",
  "question": "improved/clarified question",
  "answer": "enhanced answer text",
  "content": "enhanced full content (can be same as answer if redundant)",
  "keywords": ["keyword1", "keyword2"],
  "changes_summary": "Brief description of what was improved (one sentence, in Indonesian)"
}
PROMPT;

            $payload = json_encode([
                'title'    => $kb->title,
                'question' => $kb->question,
                'answer'   => mb_substr((string) ($kb->answer ?: ''), 0, 3000),
                'content'  => mb_substr((string) ($kb->content ?: ''), 0, 3000),
                'category' => $kb->category,
                'type'     => $kb->type,
                'keywords' => $kb->keywords,
            ], JSON_UNESCAPED_UNICODE);

            $response = $this->retryRequest(function () use ($systemPrompt, $payload) {
                return $this->client->chat()->create([
                    'model'                 => config('services.openai.model', 'gpt-5-mini'),
                    'messages'              => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $payload],
                    ],
                    'max_completion_tokens' => 2000,
                ]);
            });

            $text = trim($response->choices[0]->message->content ?? '');
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $parsed = json_decode($text, true);
            if (!is_array($parsed)) {
                return ['success' => false, 'error' => 'Failed to parse AI enhancement response: ' . mb_substr($text, 0, 300)];
            }

            return [
                'success'         => true,
                'title'           => $parsed['title']           ?? null,
                'question'        => $parsed['question']        ?? null,
                'answer'          => $parsed['answer']          ?? null,
                'content'         => $parsed['content']         ?? null,
                'keywords'        => $parsed['keywords']        ?? null,
                'changes_summary' => $parsed['changes_summary'] ?? null,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OpenAIService::enhanceKnowledgeBase error', ['id' => $kb->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get current Indonesian datetime and calendar context information.
     */
    public function getCalendarDetails(): array
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        
        $daysInIndonesian = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $monthsInIndonesian = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];

        $englishDay = $now->format('l');
        $dayName = $daysInIndonesian[$englishDay] ?? $englishDay;
        
        $englishMonth = $now->format('F');
        $monthName = $monthsInIndonesian[$englishMonth] ?? $englishMonth;
        
        $dateFormatted = $now->format('d') . ' ' . $monthName . ' ' . $now->format('Y');
        $timeFormatted = $now->format('H:i');

        $isSunday = ($englishDay === 'Sunday');
        $isSaturday = ($englishDay === 'Saturday');
        $todayYmd = $now->format('Y-m-d');

        return [
            'carbon' => $now,
            'day_name' => $dayName,
            'date_formatted' => $dateFormatted,
            'time_formatted' => $timeFormatted,
            'is_sunday' => $isSunday,
            'is_saturday' => $isSaturday,
            'ymd' => $todayYmd
        ];
    }

    /**
     * Build calendar context block to be injected into the system prompt.
     */
    public function getCalendarContextString(array $cal): string
    {
        $context = "\nINFO WAKTU & KALENDER SAAT INI:\n";
        $context .= "- Hari: {$cal['day_name']}\n";
        $context .= "- Tanggal: {$cal['date_formatted']}\n";
        $context .= "- Jam: {$cal['time_formatted']} WIB\n";
        
        if ($cal['is_sunday']) {
            $context .= "- Status Hari Kerja/Libur: Hari Minggu (Samsat Induk dan sebagian besar layanan TUTUP)\n";
        } elseif ($cal['is_saturday']) {
            $context .= "- Status Hari Kerja/Libur: Hari Sabtu (Layanan setengah hari atau tutup, silakan cocokkan dengan jadwal layanan terkait)\n";
        } else {
            $context .= "- Status Hari Kerja/Libur: Hari Kerja (Senin s.d. Jumat - Silakan cocokkan dengan jadwal layanan terkait)\n";
        }

        return $context;
    }

    /**
     * Expand user query with current day/date/holiday context if temporal keywords are present.
     */
    private function expandQueryForTemporalContext(string $query, array $cal): string
    {
        $queryLower = strtolower($query);
        
        // Define temporal indicators in Indonesian
        $temporalKeywords = [
            'hari ini', 'besok', 'lusa', 'nanti', 'kemarin',
            'jadwal', 'buka', 'tutup', 'jam', 'hari', 'tanggal',
            'operasional', 'pelayanan', 'buka jam', 'tutup jam',
            'samsat keliling', 'samkel', 'keliling', 'unggulan',
            'ramadhan', 'ramadan', 'puasa', 'lebaran', 'libur',
            'cuti', 'sabtu', 'minggu', 'senin', 'selasa', 'rabu',
            'kamis', 'jumat'
        ];
        
        $hasTemporalSignal = false;
        foreach ($temporalKeywords as $keyword) {
            if (strpos($queryLower, $keyword) !== false) {
                $hasTemporalSignal = true;
                break;
            }
        }
        
        if (!$hasTemporalSignal) {
            return $query;
        }
        
        // Expand query by appending day name and global keywords for Ramadan/holidays.
        // This ensures the RAG retrieval fetches holiday calendars and Ramadan schedules
        // for the LLM to inspect in-context.
        return $query . " {$cal['day_name']} ramadhan ramadan libur tutup";
    }

    /**
     * Scan retrieved knowledge base chunks for instructions referencing other categories or types,
     * and dynamically retrieve and inject those matching entries into the RAG context.
     */
    private function injectAIInstructionReferencedKnowledge(array $results): array
    {
        $referencedCategories = [];
        $referencedTypes = [];
        
        $categoriesMap = KnowledgeBase::getCategories();
        $typesMap = KnowledgeBase::getTypes();

        foreach ($results as $item) {
            $instr = $item['ai_instructions'] ?? '';
            if (empty($instr)) continue;

            // Match anything inside single/double quotes, e.g. 'peraturan & kebijakan'
            if (preg_match_all('/[\'"]([^\'"]+)[\'"]/u', $instr, $matches)) {
                foreach ($matches[1] as $quotedText) {
                    $cleaned = strtolower(trim($quotedText));
                    
                    // 1. Check if it matches a category key or label
                    foreach ($categoriesMap as $key => $label) {
                        if ($cleaned === strtolower($key) || $cleaned === strtolower($label)) {
                            $referencedCategories[] = $key;
                        }
                    }
                    
                    // 2. Check if it matches a type key or label
                    foreach ($typesMap as $key => $label) {
                        if ($cleaned === strtolower($key) || $cleaned === strtolower($label)) {
                            $referencedTypes[] = $key;
                        }
                    }
                }
            }
        }

        if (empty($referencedCategories) && empty($referencedTypes)) {
            return $results;
        }

        $referencedCategories = array_unique($referencedCategories);
        $referencedTypes = array_unique($referencedTypes);
        $injectedIds = array_filter(array_column($results, 'id'));
        $extraResults = [];

        // Fetch extra KBs by category
        if (!empty($referencedCategories)) {
            $catKbs = KnowledgeBase::active()->published()
                ->whereIn('category', $referencedCategories)
                ->select(['id', 'title', 'content', 'category', 'ai_instructions'])
                ->get();
            foreach ($catKbs as $match) {
                if (!in_array($match->id, $injectedIds, true)) {
                    $extraResults[] = $this->formatDbRowForKnowledge($match, 'instruction_category', 0.90);
                    $injectedIds[] = $match->id;
                }
            }
        }

        // Fetch extra KBs by type
        if (!empty($referencedTypes)) {
            $typeKbs = KnowledgeBase::active()->published()
                ->whereIn('type', $referencedTypes)
                ->select(['id', 'title', 'content', 'category', 'ai_instructions'])
                ->get();
            foreach ($typeKbs as $match) {
                if (!in_array($match->id, $injectedIds, true)) {
                    $extraResults[] = $this->formatDbRowForKnowledge($match, 'instruction_type', 0.90);
                    $injectedIds[] = $match->id;
                }
            }
        }

        if (!empty($extraResults)) {
            if ($this->debug) {
                Log::info('Injected AI instruction referenced knowledge bases', [
                    'categories' => $referencedCategories,
                    'types' => $referencedTypes,
                    'count' => count($extraResults),
                ]);
            }
            $results = array_merge($results, $extraResults);
        }

        return $results;
    }

    /**
     * Strip any AI-generated lines that leak internal backend references to the user.
     * These include "📚 Sumber:" citations, "KB" mentions, document names, and internal
     * reasoning annotations. Applied as a safety net even when the prompt already
     * instructs the model not to include them.
     */
    private function cleanAiResponse(string $text): string
    {
        if ($text === '') return $text;

        // 1. Remove entire lines that are source citations or KB references
        $lines = explode("\n", $text);
        $filtered = array_filter($lines, function (string $line) {
            $t = trim($line);
            // Source / citation lines
            if (preg_match('/^\s*[\*\-]*\s*📚\s*(Sumber|Source)/ui', $t)) return false;
            if (preg_match('/^\s*[\*\-]*\s*(Sumber|Source)\s*:/ui', $t)) return false;
            // Lines that are purely KB-internal attributions
            if (preg_match('/^\s*[\*\-]*\s*(Knowledge Base|Basis Data Pengetahuan)\s*:/ui', $t)) return false;
            return true;
        });
        $text = implode("\n", $filtered);

        // 2. Remove inline KB/source annotations (parenthetical or bracketed)
        $patterns = [
            // (KB mensyaratkan / KB menyebut / KB memuat / KB tidak memuat...)
            '/\(KB[^)]{0,120}\)/ui',
            // (Fakta wajib...) or (Fakta wajib pelayanan)
            '/\(Fakta wajib[^)]{0,80}\)/ui',
            // (Panduan Samsat... / Standar Pelayanan... as attribution)
            '/\((?:Panduan|Standar Pelayanan)[^)]{0,120}\)/ui',
            // [KB...] bracketed
            '/\[KB[^\]]{0,120}\]/ui',
            // Inline: "berdasarkan KB", "menurut KB", "KB menyebutkan", "KB tidak memuat"
            '/\b(?:berdasarkan|menurut|sesuai)\s+KB\b/ui',
            '/\bKB\s+(?:menyebutkan|mensyaratkan|memuat|tidak memuat|mencantumkan|menyebut|mengharuskan)\b[^.]{0,80}/ui',
            '/\btidak ada di KB\b/ui',
            '/\btidak tercantum di KB\b/ui',
            // "Fakta wajib + ..." or "Fakta wajib pelayanan"
            '/\.?\s*\(Fakta wajib[^)]{0,80}\)\.?/ui',
            // Trailing inline source blocks like "; Fakta wajib pelayanan."
            '/;\s*Fakta wajib[^.\n]{0,80}/ui',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        // 3. Clean up any double-spaces or orphaned punctuation left behind
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        $text = preg_replace('/ ([,\.;])/', '$1', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
