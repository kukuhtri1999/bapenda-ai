<?php

namespace App\Services;

use App\Models\KnowledgeBase;
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

    public function __construct()
    {
        $this->client = \OpenAI::client((string) config('services.openai.api_key'));
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 1500);
        $this->temperature = config('services.openai.temperature', 0.7);
        $this->defaultTimeout = 30;
        $ragDebug = $_ENV['RAG_DEBUG'] ?? $_SERVER['RAG_DEBUG'] ?? false;
        $this->debug = (bool) (config('app.debug', false) || filter_var($ragDebug, FILTER_VALIDATE_BOOLEAN));
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
        return 'gpt-4o-mini';
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
        // Keep existing implementation
        return null;
    }

    public function generateDetailedAnalysis(string $aggText, array $categoryLabels = [], ?array $derived = null): ?string
    {
        // Keep existing implementation
        return null;
    }

    public function generateRecommendations(array $categoryCounts, array $commonIssues, array $sentiments, array $categoryLabels = []): ?array
    {
        // Keep existing implementation
        return null;
    }

    // ... other analytics methods

    /**
     * Generate AI response for customer service chat with RAG
     */
    public function generateCustomerServiceResponse(array $messages, ?string $context = null): array
    {
        $debugInfo = [];

        try {
            // Get the latest user message
            $userMessage = $this->getLatestUserMessage($messages);
            if (!$userMessage) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada pesan dari pengguna yang ditemukan.',
                    'usage' => null,
                    'knowledge_used' => 0,
                    'debug' => ['error' => 'No user message found']
                ];
            }

            $debugInfo['user_message'] = $userMessage;

            // Generate embedding for user query
            $embeddingService = app(EmbeddingService::class);
            $pineconeService = app(PineconeService::class);

            $queryEmbedding = $embeddingService->embed($userMessage);
            if (!$queryEmbedding) {
                $debugInfo['embedding_error'] = 'Failed to generate embedding';
                return array_merge($this->generateFallbackResponse($userMessage), ['debug' => $debugInfo]);
            }

            $debugInfo['embedding_generated'] = true;
            $debugInfo['embedding_length'] = count($queryEmbedding);

            // Search relevant knowledge from vector database (only active and published)
            $similarKnowledge = $pineconeService->query(
                vector: $queryEmbedding,
                topK: 5,
                filter: [
                    'is_active' => true,
                    'status' => 'published'
                ]
            );

            $debugInfo['vector_search'] = [
                'results_count' => count($similarKnowledge),
                'results' => array_map(function ($item) {
                    return [
                        'id' => $item['id'] ?? null,
                        'score' => $item['score'] ?? null,
                        'metadata' => $item['metadata'] ?? null
                    ];
                }, $similarKnowledge)
            ];

            // Build context from retrieved knowledge
            $kbContext = $this->buildKnowledgeContext($similarKnowledge);
            $debugInfo['vector_context_built'] = !empty($kbContext);
            $debugInfo['vector_context_length'] = strlen($kbContext);

            // Fallback to traditional keyword search if vector search didn't find enough relevant content
            if (empty($kbContext)) {
                $kbContext = $this->buildTraditionalKnowledgeContext($userMessage);
                $debugInfo['fallback_to_keyword_search'] = true;
                $debugInfo['keyword_context_length'] = strlen($kbContext);
            } else {
                $debugInfo['fallback_to_keyword_search'] = false;
            }

            $debugInfo['final_context_used'] = !empty($kbContext);
            $debugInfo['final_context_length'] = strlen($kbContext);

            // Generate professional customer service response
            $response = $this->generateContextualResponse($userMessage, $kbContext, $context);

            return [
                'success' => true,
                'message' => $response['message'],
                'usage' => $response['usage'],
                'knowledge_used' => count($similarKnowledge),
                'debug' => $debugInfo
            ];
        } catch (Exception $e) {
            Log::error('AI Customer Service Error: ' . $e->getMessage());
            $debugInfo['exception'] = $e->getMessage();
            return array_merge($this->generateFallbackResponse($userMessage ?? 'pertanyaan umum'), ['debug' => $debugInfo]);
        }
    }

    /**
     * Get the latest user message from conversation
     */
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
     * Build knowledge context from vector search results
     */
    private function buildKnowledgeContext(array $similarKnowledge): string
    {
        if (empty($similarKnowledge)) {
            return '';
        }

        $contextParts = [];
        foreach ($similarKnowledge as $index => $match) {
            $metadata = $match['metadata'] ?? [];
            $score = $match['score'] ?? 0;

            // Only include high-relevance matches (score > 0.3 for now)
            if ($score < 0.3) {
                continue;
            }

            $contextParts[] = "Referensi " . ($index + 1) . " (Relevance: " . round($score, 2) . "):\n" .
                "Judul: " . ($metadata['title'] ?? 'Tidak diketahui') . "\n" .
                "Kategori: " . ($metadata['category'] ?? 'umum') . "\n" .
                "Konten: " . ($metadata['chunk_text'] ?? '') . "\n";
        }

        if (empty($contextParts)) {
            return '';
        }

        return "REFERENSI KNOWLEDGE BASE:\n\n" . implode("\n---\n\n", $contextParts);
    }

    /**
     * Build knowledge context using traditional keyword search as fallback
     */
    private function buildTraditionalKnowledgeContext(string $userMessage): string
    {
        // Extract keywords for search
        $keywords = $this->extractKeywords($userMessage);
        if (empty($keywords)) {
            return '';
        }

        // Search using fulltext and LIKE queries
        $knowledge = \App\Models\KnowledgeBase::active()
            ->published()
            ->where(function ($query) use ($keywords, $userMessage) {
                // Try fulltext search first
                $query->whereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$userMessage])
                    ->orWhere(function ($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                ->orWhere('content', 'LIKE', "%{$keyword}%")
                                ->orWhere('answer', 'LIKE', "%{$keyword}%")
                                ->orWhere('search_content', 'LIKE', "%{$keyword}%");
                        }
                    });
            })
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(3)
            ->get(['id', 'title', 'content', 'answer', 'excerpt', 'category', 'search_content'])
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

            // Strip HTML and limit length
            $plainContent = strip_tags($content);
            $plainContent = mb_substr($plainContent, 0, 500);

            $contextParts[] = "Referensi " . ($index + 1) . " (Keyword Match):\n" .
                "Judul: " . ($kb['title'] ?? 'Tidak diketahui') . "\n" .
                "Kategori: " . ($kb['category'] ?? 'umum') . "\n" .
                "Konten: " . $plainContent . "\n";
        }

        return "REFERENSI KNOWLEDGE BASE:\n\n" . implode("\n---\n\n", $contextParts);
    }

    /**
     * Extract keywords from user message for traditional search
     */
    private function extractKeywords(string $message): array
    {
        // Normalize and tokenize
        $cleanMessage = strtolower($message);
        $cleanMessage = preg_replace('/[^a-z0-9_\-\s]/u', ' ', $cleanMessage);
        $tokens = preg_split('/\s+/', $cleanMessage, -1, PREG_SPLIT_NO_EMPTY);

        // Basic Indonesian stopwords
        $stop = ['dan', 'atau', 'yang', 'untuk', 'dengan', 'di', 'ke', 'dari', 'pada', 'ini', 'itu', 'apa', 'bagaimana', 'berapa', 'dimana', 'kapan', 'mengapa', 'saya', 'kami', 'kita', 'anda', 'kamu', 'ya', 'tidak', 'boleh', 'bisa', 'mohon', 'tolong'];

        $terms = [];
        foreach ($tokens as $t) {
            if (strlen($t) < 3) continue;
            if (in_array($t, $stop, true)) continue;
            $terms[] = $t;
        }

        // Add domain-specific hints
        $terms = array_merge($terms, ['samsat', 'lamongan', 'keliling', 'jadwal', 'jam', 'lokasi']);

        return array_unique(array_slice($terms, 0, 10));
    }

    /**
     * Generate contextual response using OpenAI
     */
    private function generateContextualResponse(string $userMessage, string $kbContext, ?string $additionalContext = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($kbContext, $additionalContext);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.3, // Lower temperature for more consistent responses
        ]);

        $content = trim($response->choices[0]->message->content ?? '');
        $usage = $this->normalizeUsage($response->usage ?? null);

        return [
            'message' => $content,
            'usage' => $usage
        ];
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
     * Generate fallback response when AI processing fails
     */
    private function generateFallbackResponse(string $userMessage): array
    {
        // Try simple KB retrieval as fallback
        $simpleKb = $this->simpleRetrieveKb($userMessage);

        if (!empty($simpleKb)) {
            $kbContent = $simpleKb[0]['content'] ?? $simpleKb[0]['answer'] ?? '';
            if (!empty($kbContent)) {
                $response = "Berdasarkan informasi yang tersedia:\n\n" .
                    strip_tags($kbContent) .
                    "\n\nUntuk informasi lebih lengkap, silakan hubungi Samsat Lamongan langsung atau kunjungi kantor kami.";

                return [
                    'success' => true,
                    'message' => $response,
                    'usage' => null,
                    'knowledge_used' => count($simpleKb)
                ];
            }
        }

        // Ultimate fallback
        return [
            'success' => true,
            'message' => 'Maaf, saya mengalami kesulitan memproses pertanyaan Anda saat ini. Untuk mendapatkan informasi yang akurat, silakan hubungi Samsat Lamongan langsung di nomor telepon resmi atau kunjungi kantor kami. Tim customer service kami siap membantu Anda dengan senang hati.',
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

            $model = config('services.openai.model', 'gpt-4o-mini');

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
}
