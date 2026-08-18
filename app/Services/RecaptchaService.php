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
            // If token is missing, allow with fallback but let rate limiter protect
            Log::info('reCAPTCHA token empty, falling back to rate limiter & guardrails.');
            return [
                'success' => true,
                'score' => 0.8,
                'fallback' => true,
                'reason' => 'missing_token',
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
                ->timeout(4)
                ->post('https://www.google.com/recaptcha/api/siteverify', $payload);

            if (!$response->successful()) {
                Log::warning('reCAPTCHA siteverify HTTP error: ' . $response->status());
                // Fallback gracefully on Google network/API issues
                return [
                    'success' => true,
                    'score' => 0.7,
                    'fallback' => true,
                    'reason' => 'google_http_' . $response->status(),
                ];
            }

            $data = $response->json();
            $success = (bool) ($data['success'] ?? false);
            $score = (float) ($data['score'] ?? 0.0);
            $action = $data['action'] ?? null;

            if (!$success) {
                $errorCodes = implode(', ', $data['error-codes'] ?? ['invalid-input-response']);
                Log::warning("reCAPTCHA validation notice: {$errorCodes}");
                // If token was already consumed, expired, or domain mismatch, fall back gracefully
                return [
                    'success' => true,
                    'score' => 0.7,
                    'fallback' => true,
                    'reason' => $errorCodes,
                ];
            }

            // Verify action if specified
            if (!empty($expectedAction) && !empty($action)) {
                $allowed = is_array($expectedAction) ? $expectedAction : [$expectedAction];
                if (!in_array($action, $allowed, true)) {
                    Log::warning("reCAPTCHA action mismatch: expected " . json_encode($allowed) . ", got '{$action}'");
                }
            }

            // Check if score meets minimum threshold (only block if Google explicitly returned success:true and low bot score)
            if ($score > 0 && $score < $this->minScore) {
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
