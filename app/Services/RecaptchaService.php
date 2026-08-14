<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected string $secretKey;
    protected bool $enabled;
    protected float $minScore;

    public function __construct()
    {
        $this->secretKey = (string) config('services.recaptcha.secret_key', '');
        $this->enabled = (bool) config('services.recaptcha.enabled', true);
        $this->minScore = (float) config('services.recaptcha.min_score', 0.5);
    }

    /**
     * Verify a Google reCAPTCHA v3 response token.
     *
     * @param string|null $token
     * @param string|array|null $expectedAction
     * @param string|null $ip
     * @return array ['success' => bool, 'score' => float, 'action' => string|null, 'error' => string|null]
     */
    public function verify(?string $token, string|array|null $expectedAction = null, ?string $ip = null): array
    {
        // Bypass if reCAPTCHA is explicitly disabled or no secret key configured
        if (!$this->enabled || empty($this->secretKey)) {
            return [
                'success' => true,
                'score' => 1.0,
                'action' => is_array($expectedAction) ? ($expectedAction[0] ?? null) : $expectedAction,
                'bypassed' => true,
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'score' => 0.0,
                'error' => 'reCAPTCHA token tidak ditemukan. Mohon refresh halaman dan coba kembali.',
            ];
        }

        try {
            $payload = [
                'secret' => $this->secretKey,
                'response' => $token,
            ];

            if ($ip) {
                $payload['remoteip'] = $ip;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', $payload);

            if (!$response->successful()) {
                Log::warning('reCAPTCHA siteverify HTTP error: ' . $response->status());
                return [
                    'success' => false,
                    'score' => 0.0,
                    'error' => 'Gagal menghubungi server verifikasi Google reCAPTCHA.',
                ];
            }

            $data = $response->json();
            $success = (bool) ($data['success'] ?? false);
            $score = (float) ($data['score'] ?? 0.0);
            $action = $data['action'] ?? null;

            if (!$success) {
                $errorCodes = implode(', ', $data['error-codes'] ?? ['invalid-input-response']);
                Log::warning("reCAPTCHA validation failed: {$errorCodes}");
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => "Validasi keamanan gagal ({$errorCodes}). Silakan coba kembali.",
                ];
            }

            // Verify action if specified
            if (!empty($expectedAction) && !empty($action)) {
                $allowed = is_array($expectedAction) ? $expectedAction : [$expectedAction];
                if (!in_array($action, $allowed, true)) {
                    Log::warning("reCAPTCHA action mismatch: expected " . json_encode($allowed) . ", got '{$action}'");
                    return [
                        'success' => false,
                        'score' => $score,
                        'error' => 'Aksi verifikasi tidak valid.',
                    ];
                }
            }

            // Check if score meets minimum threshold
            if ($score < $this->minScore) {
                Log::warning("reCAPTCHA low trust score: {$score} (minimum: {$this->minScore})");
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'Aktivitas mencurigakan terdeteksi oleh sistem keamanan.',
                ];
            }

            return [
                'success' => true,
                'score' => $score,
                'action' => $action,
            ];
        } catch (\Throwable $e) {
            Log::error('reCAPTCHA exception: ' . $e->getMessage());
            // Graceful fallback in case of transient Google network timeout
            return [
                'success' => true,
                'score' => 0.7,
                'fallback' => true,
            ];
        }
    }
}
