<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SecurityGuardrailService
{
    /**
     * Known adversarial prompt injection and jailbreak patterns.
     */
    protected array $injectionPatterns = [
        '/(?:ignore|forget|disregard|override|abaikan|lupakan|hiraukan)\s+(?:all\s+|semua\s+)?(?:previous|prior|above|instruksi|perintah|aturan)\s+(?:instructions|prompts|rules|sebelumnya|awal)/iu' => 'system_override_attempt',
        '/(?:print|reveal|display|output|leak|show|bocorkan|tampilkan|cetak|berikan)\s+(?:your\s+|saya\s+)?(?:system\s+prompt|developer\s+instructions|hidden\s+rules|api\s+key|instruksi\s+sistem)/iu' => 'prompt_leak_attempt',
        '/(?:act\s+as|pretend\s+to\s+be|berperilaku\s+sebagai)\s+(?:dan|unfiltered|jailbreak|anarchy|root|admin\s+mode)/iu' => 'jailbreak_persona_attempt',
        '/(?:format|erase|delete|drop)\s+(?:all\s+)?(?:database|tables|files)/iu' => 'destructive_command_attempt',
        '/<script\b[^>]*>(.*?)<\/script>/is' => 'xss_payload_detected',
    ];

    /**
     * Scan user input for adversarial prompt injection or abusive payload.
     *
     * @param string $input
     * @return array ['is_safe' => bool, 'reason' => string|null, 'flagged_pattern' => string|null]
     */
    public function checkPromptInjection(string $input): array
    {
        $clean = trim($input);

        // Check for character flood attack (e.g., repeating the same character > 60 times)
        if (preg_match('/(.)\1{60,}/u', $clean)) {
            return [
                'is_safe' => false,
                'reason' => 'Pesan terdeteksi mengandung spam karakter berulang.',
                'flagged_pattern' => 'character_flood',
            ];
        }

        // Check against known injection patterns
        foreach ($this->injectionPatterns as $pattern => $flag) {
            if (preg_match($pattern, $clean)) {
                Log::warning("SecurityGuardrail flagged suspicious prompt [{$flag}]: " . mb_substr($clean, 0, 100));
                return [
                    'is_safe' => false,
                    'reason' => 'Permintaan mengandung instruksi yang tidak diizinkan oleh sistem keamanan.',
                    'flagged_pattern' => $flag,
                ];
            }
        }

        return [
            'is_safe' => true,
            'reason' => null,
            'flagged_pattern' => null,
        ];
    }

    /**
     * Mask citizen Personally Identifiable Information (PII) before sending to external LLM.
     * Adheres to Indonesian Personal Data Protection (UU PDP) standards.
     *
     * @param string $text
     * @return string
     */
    public function maskPii(string $text): string
    {
        // 1. Mask credit/debit card or bank account patterns explicitly
        $masked = preg_replace('/(?:rekening|rek|kartu|atm|cc|debit)\s*[:#-]?\s*(\d{10,16})/iu', 'rekening [DISAMARKAN]', $text);

        // 2. Mask 16-digit Indonesian NIK (Nomor Induk Kependudukan)
        // Keep first 6 digits (district code) and mask remaining 10 digits
        $masked = preg_replace_callback('/\b(\d{6})(\d{10})\b/', function ($m) {
            return $m[1] . 'xxxxxxxxxx';
        }, $masked);

        // 3. Mask email addresses
        $masked = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL DISAMARKAN]', $masked);

        return $masked;
    }
}
