<?php

namespace App\Services;

use OpenAI;
use OpenAI\Client;
use Illuminate\Support\Facades\Log;
use App\Models\KnowledgeBase;
use Exception;

class OpenAIService
{
    private Client $client;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 1500);
        $this->temperature = config('services.openai.temperature', 0.7);
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
                'max_tokens' => $this->maxTokens,
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

        // Search knowledge base
        $knowledge = KnowledgeBase::active()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('question', 'LIKE', "%{$keyword}%")
                        ->orWhere('answer', 'LIKE', "%{$keyword}%")
                        ->orWhereJsonContains('keywords', $keyword);
                }
            })
            ->byPriority()
            ->limit(3)
            ->get(['title', 'question', 'answer', 'category'])
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
            'kendaraan',
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

GAYA KOMUNIKASI:
- Selalu sapa dengan ramah (contoh: 'Halo! Ada yang bisa saya bantu terkait layanan Samsat Lamongan?')
- Gunakan bahasa yang sopan dan mudah dipahami
- Berikan jawaban yang akurat dan faktual
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
            $basePrompt .= "\n\nINFORMASI REFERENSI RESMI:\nGunakan informasi berikut sebagai referensi untuk menjawab pertanyaan:\n\n";

            foreach ($relevantKnowledge as $knowledge) {
                $basePrompt .= "**{$knowledge['title']}**\n";
                $basePrompt .= "Q: {$knowledge['question']}\n";
                $basePrompt .= "A: {$knowledge['answer']}\n\n";
            }

            $basePrompt .= "Pastikan jawaban Anda konsisten dengan informasi referensi di atas dan SPESIFIK untuk pertanyaan yang diajukan.";
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
