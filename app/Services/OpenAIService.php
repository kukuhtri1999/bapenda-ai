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

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 1500);
        $this->temperature = config('services.openai.temperature', 0.7);
        $this->defaultTimeout = (int) config('services.openai.request_timeout', 30); // seconds
    }

    /**
     * Retry wrapper for API calls with simple exponential backoff.
     * Accepts a callable that performs the API call and returns the response.
     */
    private function retryRequest(callable $fn, int $attempts = 3, int $baseDelay = 500)
    {
        $tries = 0;
        do {
            try {
                $tries++;
                return $fn();
            } catch (Exception $e) {
                Log::warning('OpenAIService::retryRequest attempt ' . $tries . ' failed: ' . $e->getMessage());
                if ($tries >= $attempts) throw $e;
                usleep($baseDelay * 1000 * $tries); // backoff
            }
        } while ($tries < $attempts);
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

            // force use of GPT-5-mini for analytics as requested
            $model = 'gpt-5-mini';

            $call = function () use ($model, $payload) {
                return $this->client->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful data analysis assistant.'],
                        ['role' => 'user', 'content' => $payload],
                    ],
                    // reduce token budget to cut cost and latency for analytics
                    'max_completion_tokens' => 512,
                    // pass timeout if SDK supports it
                    'timeout' => $this->defaultTimeout,
                    'connect_timeout' => min(5, $this->defaultTimeout),
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
            $model = 'gpt-5-mini';

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
            $model = 'gpt-5-mini';

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
            $model = 'gpt-5-mini';

            // Prepare compact context
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

            $instruction = "Kembalikan HANYA JSON (tanpa teks lain) dengan struktur: {\n  \"recommendations\": [string],\n  \"recommendations_detailed\": [{\n    \"category\": string (category key),\n    \"label\": string (human label),\n    \"count\": number,\n    \"rationale\": string (Bahasa Indonesia),\n    \"actions\": [string pendek],\n    \"priority\": \"low\"|\"medium\"|\"high\",\n    \"effort_estimate\": string pendek\n  }]\n}. Fokus pada strategi era digital: kanal online, mobile/web, e-payment, chatbot/RAG, otomasi antrean, sosmed, integrasi bank/VA. Jumlahkan 5-10 rekomendasi ringkas dan 5-8 entri rekomendasi_detailed sesuai kategori dengan count tertinggi. Gunakan label manusia yang sesuai. Jangan membuat angka baru; gunakan count yang ada. Bahasa Indonesia. JSON valid saja.";

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
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $json = json_decode(substr($text, $start, $end - $start + 1), true);
                if (is_array($json)) return $json;
            }
            return null;
        } catch (Exception $e) {
            // fallback and log
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
            $model = 'gpt-5-mini';

            $topicsBrief = [];
            foreach ($topTopics as $t) {
                $topicsBrief[] = ['key' => ($t['key'] ?? $t['name'] ?? ''), 'label' => ($t['label'] ?? ''), 'count' => (int)($t['count'] ?? 0)];
            }

            $wordGoal = (int) config('analytics.strategy_word_goal', 800);
            $instruction = "Kembalikan HANYA JSON valid (tidak ada teks tambahan). Kita akan memberikan TOP " . count($topicsBrief) . " topik (maksimum 3). Untuk keseluruhan topik tersebut, buat STRATEGI TERPERINCI dalam Bahasa Indonesia dengan total kira-kira ~" . $wordGoal . " kata (bagi merata ke topik).\n\nOutput harus mengikuti struktur JSON: { \"strategies\": [ {\n  \"topic_key\": string,\n  \"label\": string,\n  \"count\": number,\n  \"short_summary\": string (2-3 kalimat),\n  \"detailed_strategy\": string panjang (sekitar 600-900 kata per topik) menjelaskan masalah, tujuan, pendekatan teknis & operasional, dan strategi peningkatan awareness digital,\n  \"implementation_steps\": [string] (urut, actionable, sertakan setidaknya 8 langkah teknis & operasional, masing-masing 40-160 karakter),\n  \"suggested_owners\": [string],\n  \"timeline\": string (mis. \"0-3 months\", \"3-12 months\"),\n  \"kpis\": [string],\n  \"estimated_cost\": string pendek,\n  \"dependencies\": [string]\n} ] }\n\nUntuk setiap langkah implementasi sertakan detail teknis nyata bila memungkinkan (mis. \"integrasi VA bank X, endpoint /payments/va, webhook pada /api/va/callback, job nightly sync\"). Fokus juga pada ROADMAP ACTION PLAN: prioritas jangka pendek (0-3 bulan), menengah (3-12 bulan), serta rencana komunikasi untuk meningkatkan awareness (channel, materi, kampanye). Jangan menambahkan angka inventif. Gunakan AGGREGATES yang diberikan sebagai konteks. Hanya JSON.\n";

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
                    'max_completion_tokens' => 2600,
                    'timeout' => $this->defaultTimeout,
                    'connect_timeout' => min(5, $this->defaultTimeout),
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
            $model = 'gpt-5-mini';
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
                    'timeout' => $this->defaultTimeout,
                    'connect_timeout' => min(5, $this->defaultTimeout),
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
     * Classify individual chats into categories & sentiments.
     * @param array $chats Each: ['chat_id' => string|int, 'text' => string]
     * @param array $categoryLabels key => human label
     * @return array ['success'=>bool,'message'=>string,'data'=>array|null]
     */
    public function classifyChats(array $chats, array $categoryLabels): array
    {
        try {
            if (empty($chats)) {
                return ['success' => true, 'message' => 'No chats', 'data' => []];
            }

            $model = 'gpt-5-mini';
            // Category meta (descriptions + indicative keywords) to guide the model
            $categoryMeta = [
                'tanya_cara_bayar_pajak' => ['desc' => 'Pertanyaan cara/metode/langkah pembayaran pajak kendaraan.', 'kw' => ['cara bayar', 'bagaimana bayar', 'gimana bayar', 'metode bayar', 'pembayaran pajak', 'bayar pajak online', 'bayar pajak dimana']],
                'tanya_syarat_bayar_pajak' => ['desc' => 'Menanyakan syarat/dokumen untuk membayar atau perpanjang pajak.', 'kw' => ['syarat bayar', 'dokumen', 'berkas', 'persyaratan', 'butuh apa', 'apa saja dibawa']],
                'denda_keterlambatan' => ['desc' => 'Pertanyaan atau keluhan tentang denda karena terlambat bayar.', 'kw' => ['denda', 'terlambat', 'telat', 'keterlambatan', 'dendanya']],
                'informasi_stnk' => ['desc' => 'Terkait STNK: perpanjangan, hilang, ganti.', 'kw' => ['stnk', 'perpanjang stnk', 'stnk hilang', 'stnk baru']],
                'informasi_bpkb' => ['desc' => 'Pertanyaan tentang BPKB.', 'kw' => ['bpkb', 'bpkb hilang', 'bpkb baru']],
                'balik_nama_mutasi' => ['desc' => 'Balik nama atau mutasi kendaraan.', 'kw' => ['balik nama', 'mutasi', 'ganti nama']],
                'pembayaran_online' => ['desc' => 'Masalah atau cara pembayaran pajak via online / channel digital.', 'kw' => ['pembayaran online', 'bayar online', 'mobile', 'aplikasi', 'website', 'e samsat', 'e-samsat']],
                'e_samsat_aplikasi' => ['desc' => 'Fokus pada aplikasi e-samsat khusus.', 'kw' => ['aplikasi e samsat', 'app samsat', 'login aplikasi', 'error aplikasi']],
                'lokasi_jam_operasional' => ['desc' => 'Menanyakan lokasi atau jam buka / operasional layanan.', 'kw' => ['jam buka', 'jam operasional', 'lokasi', 'alamat', 'buka jam', 'tutup jam']],
                'biaya_tarif' => ['desc' => 'Menanyakan biaya atau tarif pajak/proses.', 'kw' => ['biaya', 'tarif', 'harga', 'berapa bayar', 'berapa biaya']],
                'jadwal_pelayanan' => ['desc' => 'Menanyakan jadwal layanan termasuk samsat keliling.', 'kw' => ['jadwal', 'kapan ada', 'hari apa', 'samsat keliling']],
                'tanya_samsat_keliling' => ['desc' => 'Tanya tentang layanan samsat keliling.', 'kw' => ['samsat keliling', 'samkel', 'keliling']],
                'samsat_keliling_malam' => ['desc' => 'Tanya samsat keliling malam hari.', 'kw' => ['samsat keliling malam', 'malam']],
                'verifikasi_dokumen' => ['desc' => 'Verifikasi/pengecekan keaslian dokumen.', 'kw' => ['verifikasi', 'cek dokumen', 'keaslian']],
                'komplain_pelayanan' => ['desc' => 'Keluhan terhadap kualitas layanan / antrian / petugas.', 'kw' => ['keluh', 'komplain', 'susah', 'lama', 'antri', 'antre', 'ribet']],
                'informasi_pendaftaran' => ['desc' => 'Informasi pendaftaran awal / registrasi.', 'kw' => ['pendaftaran', 'daftar pertama', 'registrasi']],
                'cek_tagihan_pajak' => ['desc' => 'Cek jumlah/tagihan pajak.', 'kw' => ['cek pajak', 'cek tagihan', 'jumlah pajak', 'berapa pajak']],
                'syarat_pengurusan' => ['desc' => 'Syarat umum pengurusan dokumen pajak kendaraan.', 'kw' => ['syarat pengurusan', 'dokumen apa', 'berkas apa']],
                'informasi_pembayaran_bank' => ['desc' => 'Pembayaran melalui bank/VA.', 'kw' => ['bank', 'virtual account', 'va', 'atm']],
                'panduan_online' => ['desc' => 'Tanya panduan/tutorial online (bukan sekedar cara bayar).', 'kw' => ['panduan online', 'tutorial', 'cara menggunakan']],
                'permintaan_sosialisasi' => ['desc' => 'Permintaan materi sosialisasi / edukasi.', 'kw' => ['sosialisasi', 'edukasi', 'penyuluhan']],
                'tanya_pelayanan_pajak' => ['desc' => 'Pertanyaan tentang layanan pajak secara umum.', 'kw' => ['layanan pajak', 'pelayanan pajak']],
                'lain_lain' => ['desc' => 'Hanya gunakan jika tidak cocok dengan kategori manapun.', 'kw' => []],
            ];

            // Build list string for prompt
            $categoriesList = [];
            foreach ($categoryLabels as $k => $lbl) {
                $meta = $categoryMeta[$k] ?? ['desc' => 'Pertanyaan terkait ' . $lbl, 'kw' => []];
                $categoriesList[] = $k . ' | ' . $lbl . ' | ' . $meta['desc'] . ' | keywords: ' . implode(', ', array_slice($meta['kw'], 0, 8));
            }
            if (!isset($categoryLabels['lain_lain'])) {
                $categoriesList[] = 'lain_lain | Lain-lain | Gunakan hanya jika tidak ada kategori lain yang relevan | keywords: (none)';
            }

            // Truncate and prepare chats
            $prepared = [];
            $fast = (bool) config('analytics.fast_mode', true);
            $snippetLen = $fast ? (int) config('analytics.fast_snippet_length', 400) : (int) config('analytics.full_snippet_length', 1400);
            foreach ($chats as $c) {
                $txt = (string)$c['text'];
                $norm = preg_replace('/\s+/', ' ', $txt);
                $prepared[] = [
                    'chat_id' => (string)$c['chat_id'],
                    'text' => mb_substr($norm, 0, $snippetLen)
                ];
            }

            // Few-shot examples (cover several categories)
            $fewShots = [
                ['chat_id' => 'ex1', 'text' => 'Bagaimana cara bayar pajak kendaraan online?', 'category' => 'tanya_cara_bayar_pajak', 'sentiment' => 'neutral'],
                ['chat_id' => 'ex2', 'text' => 'Syarat apa saja untuk perpanjang STNK?', 'category' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'],
                ['chat_id' => 'ex3', 'text' => 'Denda saya berapa kalau telat bayar pajak?', 'category' => 'denda_keterlambatan', 'sentiment' => 'neutral'],
                ['chat_id' => 'ex4', 'text' => 'STNK hilang, bagaimana proses buat baru?', 'category' => 'informasi_stnk', 'sentiment' => 'negative'],
                ['chat_id' => 'ex5', 'text' => 'Antrian lama dan pelayanan lambat', 'category' => 'komplain_pelayanan', 'sentiment' => 'negative'],
                ['chat_id' => 'ex6', 'text' => 'Lokasi samsat keliling hari Sabtu di mana?', 'category' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'],
            ];

            $baseInstruction = "Klasifikasikan setiap chat ke salah satu CATEGORY KEY yang paling relevan. Gunakan 'lain_lain' HANYA jika TIDAK ada kecocokan kuat dengan kategori lain. Jika ada kata kunci spesifik yang cocok, pilih kategori terkait (jangan 'lain_lain'). Tentukan sentiment (positive|neutral|negative) secara sederhana berdasarkan nada pengguna. Berikan confidence 0-1 (0.1 sangat ragu, 0.9+ sangat yakin). Balas HANYA JSON array tanpa teks tambahan.";

            $promptPayload = [
                'task' => $baseInstruction,
                'categories' => $categoriesList,
                'few_shot_examples' => $fewShots,
                'schema' => [
                    'chat_id' => 'string',
                    'category' => 'one CATEGORY KEY exactly',
                    'sentiment' => 'positive|neutral|negative',
                    'confidence' => 'float 0-1',
                    'snippet' => '<=200 chars excerpt'
                ],
                'chats' => $prepared
            ];

            $messages = [
                ['role' => 'system', 'content' => 'You are a precise JSON-only classification engine. Respond ONLY with JSON array. Temperature=0.'],
                ['role' => 'user', 'content' => json_encode($promptPayload, JSON_UNESCAPED_UNICODE)]
            ];

            // Resolve cache for repeated texts to reduce API tokens
            $useCache = (bool) config('analytics.enable_classification_cache', true);
            $cacheTTL = (int) config('analytics.classification_cache_ttl_days', 90);
            $now = now();
            $preparedForApi = $prepared;
            $cachedMap = [];
            if ($useCache) {
                try {
                    // build hashes and fetch known ones
                    $hashes = [];
                    foreach ($prepared as $p) {
                        $h = hash('sha256', mb_strtolower(trim($p['text'])));
                        $hashes[$p['chat_id']] = $h;
                    }
                    if (!empty($hashes)) {
                        $rows = DB::table('chat_classification_cache')
                            ->whereIn('text_hash', array_values($hashes))
                            ->get(['text_hash', 'category', 'sentiment', 'confidence', 'snippet', 'updated_at']);
                        $existing = [];
                        foreach ($rows as $r) {
                            $existing[$r->text_hash] = $r;
                        }
                        $preparedForApi = [];
                        foreach ($prepared as $p) {
                            $h = $hashes[$p['chat_id']];
                            $row = $existing[$h] ?? null;
                            if ($row) {
                                // fresh if within TTL
                                if (!$cacheTTL || Carbon::parse($row->updated_at)->gt($now->copy()->subDays($cacheTTL))) {
                                    $cachedMap[$p['chat_id']] = [
                                        'chat_id' => $p['chat_id'],
                                        'category' => $row->category,
                                        'sentiment' => $row->sentiment,
                                        'confidence' => (float)$row->confidence,
                                        'snippet' => $row->snippet,
                                    ];
                                    continue; // skip API for this one
                                }
                            }
                            $preparedForApi[] = $p;
                        }
                    }
                } catch (\Throwable $t) {
                    // silently ignore cache errors
                }
            }

            $callParams = [
                'model' => $model,
                'messages' => [
                    $messages[0],
                    // Replace chats with the ones that still need API classification
                    ['role' => 'user', 'content' => json_encode(array_replace($promptPayload, ['chats' => $preparedForApi]), JSON_UNESCAPED_UNICODE)]
                ],
                'max_completion_tokens' => 1200,
            ];
            // some models don't accept temperature=0; omit when using gpt-5-mini
            if ($model !== 'gpt-5-mini') $callParams['temperature'] = 0.0;
            $response = $this->client->chat()->create($callParams);

            Log::info('OpenAIService::classifyChats - calling model', ['model' => $model, 'count_chats' => count($prepared)]);
            $text = trim($response->choices[0]->message->content ?? '');
            // capture usage if available
            try {
                if (isset($response->usage)) Log::info('OpenAIService::classifyChats - usage', (array)$response->usage);
            } catch (\Throwable $t) {
                Log::warning('OpenAIService::classifyChats - usage log failed', ['err' => $t->getMessage()]);
            }
            $data = $this->tryParseJsonArray($text);
            // Merge cached
            if (is_array($data)) {
                foreach ($cachedMap as $cid => $cached) {
                    $data[] = $cached;
                }
            } elseif (!empty($cachedMap)) {
                $data = array_values($cachedMap);
            }

            // Retry strategy if over-using lain_lain (>80%)
            if (is_array($data) && count($data) > 5) {
                $lainCount = 0;
                foreach ($data as $d) {
                    if (($d['category'] ?? '') === 'lain_lain') $lainCount++;
                }
                if ($lainCount / max(1, count($data)) > 0.8) {
                    Log::warning('AI classification overused lain_lain, retrying with stronger instruction');
                    $promptPayload['task'] .= "\nPENTING: Anda terlalu sering menggunakan 'lain_lain'. Pada percobaan ini, pilih kategori spesifik jika ada kata terkait sedikit saja.";
                    $messages[1]['content'] = json_encode($promptPayload, JSON_UNESCAPED_UNICODE);
                    $callParams['messages'] = $messages;
                    $response2 = $this->client->chat()->create($callParams);
                    $text2 = trim($response2->choices[0]->message->content ?? '');
                    $parsed2 = $this->tryParseJsonArray($text2);
                    if (is_array($parsed2)) {
                        $text .= "\n--- RETRY ---\n" . $text2;
                        $data = $parsed2;
                    }
                }
            }

            // Keyword corrective fallback for items still lain_lain
            if (is_array($data)) {
                foreach ($data as &$row) {
                    if (($row['category'] ?? '') === 'lain_lain') {
                        $txt = mb_strtolower(($row['snippet'] ?? '') . ' ' . ($this->findChatText($prepared, $row['chat_id']) ?? ''));
                        foreach ($categoryMeta as $ck => $meta) {
                            if ($ck === 'lain_lain') continue;
                            $hits = 0;
                            foreach ($meta['kw'] as $kw) {
                                if ($kw && mb_stripos($txt, $kw) !== false) {
                                    $hits++;
                                    if ($hits >= 1) break;
                                }
                            }
                            if ($hits >= 1) {
                                $row['category'] = $ck;
                                $row['confidence'] = max((float)($row['confidence'] ?? 0.3), 0.55);
                                break; // stop after first match
                            }
                        }
                    }
                }
                unset($row);
            }

            // Heuristic emergency fallback if AI failed OR >90% still lain_lain
            if (!is_array($data) || (count($data) > 0 && $this->proportionLainLain($data) > 0.9)) {
                Log::warning('Applying heuristic fallback classification (AI parse failure or excessive lain_lain)');
                $heuristic = [];
                $positive = ['terima kasih', 'bagus', 'puas', 'mantap', 'sukses'];
                $negative = ['lama', 'telat', 'denda', 'gagal', 'hilang', 'susah', 'ribet', 'error', 'komplain'];
                foreach ($prepared as $p) {
                    $txt = mb_strtolower($p['text']);
                    $assigned = 'lain_lain';
                    foreach ($categoryMeta as $ck => $meta) {
                        if ($ck === 'lain_lain') continue;
                        foreach ($meta['kw'] as $kw) {
                            if ($kw && mb_stripos($txt, $kw) !== false) {
                                $assigned = $ck;
                                break 2;
                            }
                        }
                    }
                    $sent = 'neutral';
                    foreach ($positive as $pw) {
                        if (mb_stripos($txt, $pw) !== false) {
                            $sent = 'positive';
                            break;
                        }
                    }
                    if ($sent === 'neutral') {
                        foreach ($negative as $nw) {
                            if (mb_stripos($txt, $nw) !== false) {
                                $sent = 'negative';
                                break;
                            }
                        }
                    }
                    $heuristic[] = [
                        'chat_id' => $p['chat_id'],
                        'category' => $assigned,
                        'sentiment' => $sent,
                        'confidence' => $assigned === 'lain_lain' ? 0.4 : 0.65,
                        'snippet' => mb_substr($p['text'], 0, 200)
                    ];
                }
                $data = $heuristic;
                $text .= "\n[HeuristicFallbackApplied]";
            }

            // Save newly classified to cache
            if ($useCache && is_array($data)) {
                try {
                    foreach ($data as $row) {
                        if (!isset($row['chat_id'])) continue;
                        $txt = $this->findChatText($prepared, (string)$row['chat_id']);
                        if (!$txt) continue;
                        $h = hash('sha256', mb_strtolower(trim($txt)));
                        DB::table('chat_classification_cache')->updateOrInsert(
                            ['text_hash' => $h],
                            [
                                'category' => $row['category'] ?? 'lain_lain',
                                'sentiment' => $row['sentiment'] ?? 'neutral',
                                'confidence' => (float)($row['confidence'] ?? 0.0),
                                'snippet' => mb_substr($txt, 0, 200),
                                'updated_at' => now(),
                                'last_used_at' => now(),
                            ]
                        );
                    }
                } catch (\Throwable $t) {
                }
            }

            return ['success' => true, 'message' => $text, 'data' => $data];
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

    private function proportionLainLain(array $rows): float
    {
        $total = count($rows);
        if ($total === 0) return 0.0;
        $lain = 0;
        foreach ($rows as $r) {
            if (($r['category'] ?? '') === 'lain_lain') $lain++;
        }
        return $lain / $total;
    }

    /**
     * Generate AI response for customer service chat with RAG
     */
    public function generateCustomerServiceResponse(array $messages, ?string $context = null): array
    {
        try {
            // Get relevant knowledge from knowledge base (RAG)
            $relevantKnowledge = $this->getRelevantKnowledge($messages);

            // Sistem prompt untuk AI Customer Service Bapenda Samsat Lamongan
            $systemPrompt = $this->getSystemPrompt($relevantKnowledge, $context);

            // Prepare messages untuk API
            $apiMessages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];

            // Add conversation history
            foreach ($messages as $message) {
                $apiMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $apiMessages,
                'max_completion_tokens' => $this->maxTokens,
                'temperature' => 0.8, // Increase for more variety
                'top_p' => 0.9,
                'frequency_penalty' => 0.3, // Reduce repetition
                'presence_penalty' => 0.2, // Encourage new topics
            ]);

            return [
                'success' => true,
                'message' => trim($response->choices[0]->message->content),
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

        // Extract keywords for search
        $keywords = $this->extractKeywords($lastUserMessage);

        if (empty($keywords)) {
            return [];
        }

        // Search knowledge base using new structure
        $knowledge = KnowledgeBase::active()
            ->published()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('content', 'LIKE', "%{$keyword}%")
                        ->orWhere('excerpt', 'LIKE', "%{$keyword}%")
                        ->orWhere('search_content', 'LIKE', "%{$keyword}%");
                }
            })
            ->orWhereRaw('MATCH(title, search_content) AGAINST(? IN NATURAL LANGUAGE MODE)', [implode(' ', $keywords)])
            ->orderByDesc('priority')
            ->orderByDesc('view_count')
            ->limit(5)
            ->get(['title', 'content', 'excerpt', 'category', 'type', 'tags'])
            ->toArray();

        return $knowledge;
    }

    /**
     * Extract keywords from user message
     */
    private function extractKeywords(string $message): array
    {
        // Convert to lowercase and remove punctuation
        $cleanMessage = strtolower(preg_replace('/[^\w\s]/', ' ', $message));

        // Common keywords untuk Samsat
        $samsatKeywords = [
            'pajak',
            'bayar',
            'pembayaran',
            'denda',
            'terlambat',
            'keterlambatan',
            'stnk',
            'perpanjang',
            'hilang',
            'ganti',
            'pengganti',
            'balik nama',
            'nama',
            'pindah',
            'jual',
            'beli',
            'lokasi',
            'alamat',
            'jam',
            'operasional',
            'buka',
            'tutup',
            'tarif',
            'biaya',
            'harga',
            'mahal',
            'murah',
            'online',
            'internet',
            'website',
            'aplikasi',
            'e-samsat',
            'motor',
            'mobil',
            'roda',
            'pkb',
            'swdkllj',
            'njkb',
            'progresif',
            'dokumen',
            'syarat',
            'berkas',
            'ktp',
            'bpkb',
            'cek',
            'check',
            'lihat',
            'info',
            'informasi'
        ];

        // Find matching keywords
        $foundKeywords = [];
        foreach ($samsatKeywords as $keyword) {
            if (strpos($cleanMessage, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }

        return array_unique($foundKeywords);
    }

    /**
     * Get system prompt for Bapenda Samsat Customer Service with knowledge
     */
    private function getSystemPrompt(array $relevantKnowledge = [], ?string $context = null): string
    {
        $basePrompt = "Anda adalah asisten AI customer service untuk Bapenda (Badan Pendapatan Daerah) Samsat Lamongan, Jawa Timur.

IDENTITAS & PERAN:
- Nama: Asisten Bapenda Samsat Lamongan
- Peran: Customer Service AI yang ramah, profesional, dan membantu
- Lokasi: Samsat Lamongan, Jawa Timur
- Bahasa: Bahasa Indonesia yang sopan dan mudah dipahami

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
            $basePrompt .= "- Selalu berikan sumber informasi yang jelas dan terpercaya\n\n";
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
        $greetings = [
            "Halo! Selamat datang di layanan Customer Service Samsat Lamongan. Ada yang bisa saya bantu terkait pajak kendaraan Anda?",
            "Hai! Saya Asisten AI Bapenda Samsat Lamongan. Silakan tanyakan apa yang ingin Anda ketahui tentang layanan kami.",
            "Selamat datang! Saya siap membantu Anda dengan informasi layanan Samsat Lamongan. Ada yang bisa saya bantu?",
            "Halo! Ada pertanyaan seputar pajak kendaraan, STNK, atau layanan Samsat Lamongan lainnya?"
        ];

        return $greetings[array_rand($greetings)];
    }
}
