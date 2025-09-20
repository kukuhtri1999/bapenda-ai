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

    // OPTIMIZATION: Simple in-memory cache for frequent queries
    private static array $queryCache = [];
    private static int $cacheLimit = 50;

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
     * Enhanced Javanese to Indonesian translation using OpenAI for better understanding
     */
    private function translateJavaneseQuery(string $query): array
    {
        $originalQuery = $query;
        $lowerQuery = strtolower($query);

        // Quick check for common Javanese indicators
        $javaneseIndicators = [
            'opo',
            'piro',
            'carone',
            'carane',
            'kepiye',
            'kepriye',
            'nek',
            'nnek',
            'arep',
            'nang',
            'ning',
            'kene',
            'kono',
            'iki',
            'iku',
            'kae',
            'kuwi',
            'gawe',
            'tuku',
            'adol',
            'lunga',
            'mulih',
            'nggon',
            'omah',
            'kene',
            'ngendi',
            'piye',
            'ngono',
            'ngene',
            'tekan',
            'wis',
            'durung',
            'lagi',
            'mengko',
            'saiki',
            'wingi',
            'sesuk',
            'biyen',
            'bengi',
            'awan',
            'esuk',
            'sore',
            'kanca',
            'konco',
            'bocah',
            'wong',
            'bapak',
            'ibu',
            'mbak',
            'mas',
            'dik',
            'de',
            'nduk',
            'le',
            'yo',
            'ta',
            'to',
            'kan',
            'lho',
            'monggo',
            'nggih',
            'inggih',
            'nuwun',
            'maturnuwun',
            'sugeng',
            'sampun',
            'sing',
            'sek',
            'iso',
            'ora',
            'gak',
            'ilang',
            'teles',
            'anyar',
            'lawas'
        ];

        $detectedJavanese = [];
        foreach ($javaneseIndicators as $indicator) {
            if (strpos($lowerQuery, $indicator) !== false) {
                $detectedJavanese[] = $indicator;
            }
        }

        // If no Javanese detected, return original
        if (empty($detectedJavanese)) {
            return [
                'original' => $originalQuery,
                'translated' => $originalQuery,
                'detected_javanese' => [],
                'is_javanese' => false,
                'translation_method' => 'none'
            ];
        }

        // Use OpenAI for translation if Javanese is detected
        try {
            $translatedQuery = $this->translateWithOpenAI($originalQuery);

            return [
                'original' => $originalQuery,
                'translated' => $translatedQuery,
                'detected_javanese' => $detectedJavanese,
                'is_javanese' => true,
                'translation_method' => 'openai'
            ];
        } catch (\Exception $e) {
            // Fallback to static dictionary if OpenAI fails
            Log::warning('OpenAI translation failed, using fallback', [
                'error' => $e->getMessage(),
                'query' => $originalQuery
            ]);

            $translatedQuery = $this->translateWithStaticDictionary($originalQuery, $detectedJavanese);

            return [
                'original' => $originalQuery,
                'translated' => $translatedQuery,
                'detected_javanese' => $detectedJavanese,
                'is_javanese' => true,
                'translation_method' => 'fallback'
            ];
        }
    }

    /**
     * Translate Javanese to Indonesian using OpenAI
     */
    private function translateWithOpenAI(string $query): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah translator ahli bahasa Jawa ke bahasa Indonesia. Tugas Anda adalah menerjemahkan pertanyaan dari bahasa Jawa (dialek Jawa Timur/Lamongan) ke bahasa Indonesia yang baku dan natural.

ATURAN PENTING:
- Terjemahkan HANYA jika ada kata/frasa bahasa Jawa yang terdeteksi
- Jika sudah dalam bahasa Indonesia, kembalikan teks aslinya
- Pertahankan konteks dan makna pertanyaan
- Gunakan bahasa Indonesia yang formal dan jelas
- Fokus pada konteks layanan pajak/samsat

CONTOH:
- "Opo iki pajak anyar?" → "Apa ini pajak baru?"
- "Piro biaya perpanjang STNK?" → "Berapa biaya perpanjang STNK?"
- "Carone ngurus pajak motor piye?" → "Bagaimana cara mengurus pajak motor?"
- "Nek pengen lapor online gimana?" → "Kalau ingin lapor online bagaimana?"

Berikan HANYA hasil terjemahan tanpa penjelasan tambahan.'
            ],
            [
                'role' => 'user',
                'content' => $query
            ]
        ];

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini', // Use fast model for translation
            'messages' => $messages,
            'max_tokens' => 150,
            'temperature' => 0.1, // Low temperature for consistent translation
        ]);

        $translatedText = trim($response->choices[0]->message->content ?? '');

        // If translation is empty or too similar to original, return original
        if (empty($translatedText) || $translatedText === $query) {
            return $query;
        }

        return $translatedText;
    }

    /**
     * Fallback translation using static dictionary
     */
    private function translateWithStaticDictionary(string $query, array $detectedJavanese): string
    {
        // Static dictionary as fallback
        $translations = [
            // Questions words
            'carone' => 'bagaimana caranya',
            'carane' => 'bagaimana caranya',
            'kepiye' => 'bagaimana',
            'kepriye' => 'bagaimana',
            'piro' => 'berapa',
            'pinten' => 'berapa',
            'opo' => 'apa',
            'ngendi' => 'dimana',
            'endi' => 'dimana',
            'kapan' => 'kapan',
            'nalika' => 'kapan',
            'sopo' => 'siapa',

            // Conditional and modal
            'nek' => 'kalau',
            'nnek' => 'kalau',
            'yen' => 'kalau',
            'menawa' => 'kalau',
            'arep' => 'akan',
            'pengen' => 'ingin',
            'kudu' => 'harus',
            'mesti' => 'harus',
            'iso' => 'bisa',
            'bisa' => 'bisa',

            // Location and direction
            'nang' => 'di',
            'ning' => 'di',
            'nggon' => 'tempat',
            'omah' => 'rumah',
            'kantor' => 'kantor',
            'kene' => 'sini',
            'kono' => 'sana',

            // Actions
            'gawe' => 'buat',
            'nggawe' => 'membuat',
            'tuku' => 'beli',
            'adol' => 'jual',
            'ngurus' => 'mengurus',
            'lunga' => 'pergi',
            'mulih' => 'pulang',
            'golek' => 'cari',
            'njaluk' => 'minta',
            'njupuk' => 'mengambil',
            'mbayar' => 'membayar',
            'bayar' => 'membayar',
            'tekan' => 'sampai',
            'nggarti' => 'mengganti',
            'ngganti' => 'mengganti',

            // Time expressions
            'saiki' => 'sekarang',
            'mengko' => 'nanti',
            'wingi' => 'kemarin',
            'sesuk' => 'besok',
            'biyen' => 'dulu',
            'bengi' => 'malam',
            'awan' => 'siang',
            'esuk' => 'pagi',
            'sore' => 'sore',

            // Status and condition
            'wis' => 'sudah',
            'durung' => 'belum',
            'lagi' => 'sedang',
            'isih' => 'masih',
            'anyar' => 'baru',
            'lawas' => 'lama',
            'apik' => 'bagus',
            'rusak' => 'rusak',
            'ilang' => 'hilang',
            'teles' => 'rusak',
            'robek' => 'sobek',

            // Money and cost
            'duwit' => 'uang',
            'arto' => 'uang',
            'regane' => 'harganya',
            'ragane' => 'harganya',
            'larang' => 'mahal',
            'murah' => 'murah',

            // Demonstratives
            'iki' => 'ini',
            'iku' => 'itu',
            'kae' => 'itu',
            'kuwi' => 'itu',
            'sing' => 'yang',
            'sek' => 'yang',

            // Negation
            'ora' => 'tidak',
            'gak' => 'tidak',

            // Politeness markers
            'monggo' => 'silakan',
            'nggih' => 'ya',
            'inggih' => 'ya',
            'nuwun' => 'terima kasih',
            'maturnuwun' => 'terima kasih',

            // Vehicle terms
            'montor' => 'motor',
            'kendharaan' => 'kendaraan',

            // Document terms
            'surat' => 'surat',
            'kertas' => 'dokumen'
        ];

        $translatedQuery = $query;
        foreach ($translations as $javanese => $indonesian) {
            $translatedQuery = preg_replace('/\b' . preg_quote($javanese, '/') . '\b/i', $indonesian, $translatedQuery);
        }

        // Clean up extra spaces
        return preg_replace('/\s+/', ' ', trim($translatedQuery));
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
        $startTime = microtime(true);

        try {
            // OPTIMIZATION 1: Get latest user message faster
            $userMessage = $this->getLatestUserMessage($messages);
            if (!$userMessage) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada pesan dari pengguna yang ditemukan.',
                    'usage' => null,
                    'knowledge_used' => 0
                ];
            }

            // ENHANCEMENT: Translate Javanese to Indonesian for better understanding
            $translation = $this->translateJavaneseQuery($userMessage);
            $searchQuery = $translation['translated'];
            $isJavanese = $translation['is_javanese'];

            if ($this->debug && $isJavanese) {
                Log::info('Javanese query detected', [
                    'original' => $translation['original'],
                    'translated' => $translation['translated'],
                    'detected' => $translation['detected_javanese']
                ]);
            }

            // OPTIMIZATION 2: Try fast keyword search FIRST (using translated query)
            $fastKbResult = $this->tryFastKnowledgeRetrieval($searchQuery, $isJavanese);
            if ($fastKbResult) {
                $processingTime = (microtime(true) - $startTime) * 1000;
                if ($this->debug) Log::info('Fast KB path used', ['time_ms' => $processingTime]);
                return $fastKbResult;
            }

            // OPTIMIZATION 3: Use optimized vector search with shorter embeddings
            $kbContext = '';
            $knowledgeUsed = 0;

            try {
                // Try vector search with timeout and simpler processing (use translated query)
                $embeddingService = app(EmbeddingService::class);
                $pineconeService = app(PineconeService::class);

                $queryEmbedding = $embeddingService->embed($searchQuery);
                if ($queryEmbedding) {
                    $similarKnowledge = $pineconeService->query(
                        vector: $queryEmbedding,
                        topK: 3, // Reduced from 5 to 3 for speed
                        filter: [
                            'is_active' => true,
                            'status' => 'published'
                        ]
                    );

                    $kbContext = $this->buildOptimizedKnowledgeContext($similarKnowledge);
                    $knowledgeUsed = count($similarKnowledge);
                }
            } catch (\Throwable $e) {
                // Fall through to keyword search on vector failure
                if ($this->debug) Log::info('Vector search failed, using keyword fallback', ['error' => $e->getMessage()]);
            }

            // OPTIMIZATION 4: Fallback to faster keyword search (use translated query)
            if (empty($kbContext)) {
                $kbContext = $this->buildTraditionalKnowledgeContext($searchQuery);
                if ($this->debug) Log::info('Using keyword search fallback');
            }

            // OPTIMIZATION 5: Generate response with optimized parameters (include Javanese context)
            $response = $this->generateOptimizedResponse($userMessage, $searchQuery, $kbContext, $context, $isJavanese);

            $processingTime = (microtime(true) - $startTime) * 1000;
            if ($this->debug) Log::info('AI chat completed', ['time_ms' => $processingTime, 'kb_used' => $knowledgeUsed]);

            return [
                'success' => true,
                'message' => $response['message'],
                'usage' => $response['usage'],
                'knowledge_used' => $knowledgeUsed
            ];
        } catch (Exception $e) {
            Log::error('AI Customer Service Error: ' . $e->getMessage());
            return $this->generateFallbackResponse($userMessage ?? 'pertanyaan umum');
        }
    }

    /**
     * Try fast knowledge retrieval for common queries without vector search (IMPROVED WITH JAVANESE)
     */
    private function tryFastKnowledgeRetrieval(string $userMessage, bool $isJavanese = false): ?array
    {
        // OPTIMIZATION: Check cache first
        $cacheKey = md5(strtolower(trim($userMessage)));
        if (isset(self::$queryCache[$cacheKey])) {
            if ($this->debug) Log::info('Cache hit for query', ['key' => $cacheKey]);
            return self::$queryCache[$cacheKey];
        }

        // IMPROVEMENT: More comprehensive pattern matching with Indonesian and Javanese terms
        $fastPatterns = [
            'jadwal' => ['jadwal', 'jam', 'buka', 'tutup', 'waktu', 'schedule', 'kapan', 'pukul', 'nalika'],
            'lokasi' => ['lokasi', 'alamat', 'dimana', 'tempat', 'kantor', 'nang', 'ing', 'endi'],
            'biaya' => ['biaya', 'tarif', 'bayar', 'harga', 'cost', 'piro', 'regane', 'pinten', 'duwit', 'arto'],
            'keliling' => ['keliling', 'samsat keliling', 'jadwal keliling', 'malam', 'ndalem', 'bengi'],
            'stnk' => ['stnk', 'perpanjang stnk', 'renewal', 'extend', 'daftar ulang', 'nggarti'],
            'bpkb' => ['bpkb', 'balik nama', 'mutasi', 'ganti nama', 'tukar nama', 'ngganti'],
            'pajak' => ['pajak', 'rusak', 'hilang', 'ilang', 'teles', 'robek', 'anyar', 'lawas']
        ];

        $lowerMessage = strtolower($userMessage);
        $matchedCategory = null;
        $maxMatches = 0;

        foreach ($fastPatterns as $category => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (strpos($lowerMessage, $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $matchedCategory = $category;
            }
        }

        // IMPROVEMENT: Lower threshold for better coverage but require at least 1 match
        if ($maxMatches >= 1 && $matchedCategory) {
            // IMPROVEMENT: Better search with multiple criteria
            $knowledge = \App\Models\KnowledgeBase::active()
                ->published()
                ->where(function ($query) use ($matchedCategory, $lowerMessage) {
                    $query->where('title', 'LIKE', "%{$matchedCategory}%")
                        ->orWhere('category', 'LIKE', "%{$matchedCategory}%")
                        ->orWhere('tags', 'LIKE', "%{$matchedCategory}%")
                        ->orWhere('search_content', 'LIKE', "%{$matchedCategory}%");
                })
                ->orderByDesc('priority')
                ->orderByDesc('view_count')
                ->limit(3) // Get top 3 instead of 1 for better matching
                ->get(['id', 'title', 'content', 'answer', 'category', 'search_content'])
                ->toArray();

            if (!empty($knowledge)) {
                // IMPROVEMENT: Use the first result but check if it has meaningful content
                $bestMatch = null;
                foreach ($knowledge as $kb) {
                    $content = $kb['content'] ?: $kb['answer'] ?: $kb['search_content'];
                    if (!empty($content) && strlen(strip_tags($content)) > 50) {
                        $bestMatch = $kb;
                        break;
                    }
                }

                if ($bestMatch) {
                    // Build comprehensive response with Javanese context
                    $response = $this->buildComprehensiveResponse($bestMatch, $userMessage, $isJavanese);
                    if ($response) {
                        $result = [
                            'success' => true,
                            'message' => $response,
                            'usage' => null,
                            'knowledge_used' => 1
                        ];

                        // Cache the result
                        $this->cacheResult($cacheKey, $result);

                        return $result;
                    }
                }
            }
        }

        return null;
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
    /**
     * Build optimized knowledge context (reduced processing)
     */
    private function buildOptimizedKnowledgeContext(array $similarKnowledge): string
    {
        if (empty($similarKnowledge)) {
            return '';
        }

        $contextParts = [];
        $richContentProcessor = app(RichContentProcessor::class);

        foreach ($similarKnowledge as $index => $match) {
            $metadata = $match['metadata'] ?? [];
            $score = $match['score'] ?? 0;

            // Higher threshold for speed - only very relevant matches
            if ($score < 0.4) {
                continue;
            }

            $chunkText = $metadata['chunk_text'] ?? '';

            // Simplified rich content processing
            $richContent = $richContentProcessor->extractRichContent($chunkText);
            $formattedContent = $richContentProcessor->formatForAIResponse($richContent);

            // Truncate for speed
            $formattedContent = mb_substr($formattedContent, 0, 400);

            $contextParts[] = "Ref " . ($index + 1) . ":\n" .
                "Judul: " . ($metadata['title'] ?? 'N/A') . "\n" .
                "Konten: " . $formattedContent;

            // Limit to 2 references for speed
            if (count($contextParts) >= 2) break;
        }

        if (empty($contextParts)) {
            return '';
        }

        return "REFERENSI:\n\n" . implode("\n---\n\n", $contextParts);
    }

    /**
     * Generate optimized response with balanced speed and accuracy (ENHANCED FOR JAVANESE)
     */
    private function generateOptimizedResponse(string $userMessage, string $searchQuery, string $kbContext, ?string $additionalContext = null, bool $isJavanese = false): array
    {
        $systemPrompt = $this->buildOptimizedSystemPrompt($kbContext, $additionalContext, $isJavanese);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        // Add translation context if Javanese was detected
        if ($isJavanese && $searchQuery !== $userMessage) {
            $messages[] = [
                'role' => 'system',
                'content' => "Catatan: Pengguna bertanya dalam bahasa Jawa. Query diterjemahkan menjadi: \"$searchQuery\". Berikan respons dalam bahasa Indonesia yang ramah dan mudah dipahami."
            ];
        }

        // BALANCE: Allow longer responses when we have KB context for accuracy
        $maxTokens = !empty($kbContext) ? min($this->maxTokens, 800) : min($this->maxTokens, 500);

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.2, // Slightly higher for better response quality
        ]);

        $content = trim($response->choices[0]->message->content ?? '');
        $usage = $this->normalizeUsage($response->usage ?? null);

        return [
            'message' => $content,
            'usage' => $usage
        ];
    }

    /**
     * Build optimized system prompt (ENHANCED FOR JAVANESE SUPPORT)
     */
    private function buildOptimizedSystemPrompt(string $kbContext, ?string $additionalContext = null, bool $isJavanese = false): string
    {
        $basePrompt = "Anda adalah SALMA AI - Asisten Customer Service profesional Samsat Lamongan.

IDENTITAS: SALMA AI (Sistem Asisten Layanan Masyarakat AI) - Bapenda Samsat Lamongan, Jawa Timur

GAYA KOMUNIKASI:
- Bahasa Indonesia baku, ramah, dan profesional
- Berikan informasi lengkap dan terstruktur
- Jawaban 300-600 kata dengan format yang jelas
- Gunakan **bold** untuk poin penting";

        // Add Javanese understanding note if detected
        if ($isJavanese) {
            $basePrompt .= "\n- PENTING: Pengguna menggunakan bahasa Jawa. Respon dengan bahasa Indonesia yang ramah dan mudah dipahami untuk penutur Jawa";
        }

        $basePrompt .= "\n\nTUGAS UTAMA:
- Bantu dengan info pajak kendaraan, STNK, BPKB, jadwal, lokasi, biaya
- Berikan prosedur lengkap dengan syarat-syarat
- Jelaskan tarif dan komponen biaya yang berlaku
- Informasi jadwal dan lokasi samsat keliling";

        if ($isJavanese) {
            $basePrompt .= "\n- Pahami istilah Jawa seperti: opo (apa), piro (berapa), nek (kalau), carone (caranya), dll";
        }

        $basePrompt .= "\n\nFORMAT RESPONS:
- Mulai dengan informasi utama yang diminta
- Sertakan langkah-langkah atau prosedur jika relevan
- Daftar syarat-syarat atau dokumen yang diperlukan
- Informasi biaya (jika ada di knowledge base)
- Tips atau catatan penting
- Penutup dengan kontak untuk info lebih lanjut

WAJIB:
- **PRIORITASKAN** informasi dari Knowledge Base yang tersedia
- Jika KB tidak lengkap, jelaskan yang tersedia dan arahkan ke Samsat
- Selalu berikan informasi yang berguna, jangan jawaban generik
- Sertakan gambar: ![desc](url) dan link: [text](url) jika ada dalam referensi";

        if (!empty($kbContext)) {
            $basePrompt .= "\n\n" . $kbContext . "\n\nGunakan informasi di atas sebagai referensi utama. Berikan jawaban yang komprehensif berdasarkan knowledge base.";
        } else {
            $basePrompt .= "\n\nTidak ada informasi spesifik dari Knowledge Base. Berikan informasi umum yang akurat tentang layanan Samsat atau arahkan untuk menghubungi Samsat Lamongan langsung dengan informasi kontak yang tepat.";
        }

        if ($additionalContext) {
            $basePrompt .= "\n\nKONTEKS TAMBAHAN: " . $additionalContext;
        }

        return $basePrompt;
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
     * Generate contextual response using OpenAI (OPTIMIZED - kept for compatibility)
     */
    private function generateContextualResponse(string $userMessage, string $kbContext, ?string $additionalContext = null): array
    {
        // Delegate to optimized version with default parameters for compatibility
        return $this->generateOptimizedResponse($userMessage, $userMessage, $kbContext, $additionalContext, false);
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
