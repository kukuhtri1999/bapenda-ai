<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\RecaptchaService;
use App\Services\SecurityGuardrailService;
use Symfony\Component\HttpFoundation\Response;

class ChatAntiSpamMiddleware
{
    protected RecaptchaService $recaptchaService;
    protected SecurityGuardrailService $guardrailService;

    public function __construct(
        RecaptchaService $recaptchaService,
        SecurityGuardrailService $guardrailService
    ) {
        $this->recaptchaService = $recaptchaService;
        $this->guardrailService = $guardrailService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $sessionId = $request->input('session_id', 'unknown');

        // ── 1. Google reCAPTCHA v3 Verification (if token is provided or required) ──
        $recaptchaToken = $request->input('recaptcha_token') ?? $request->header('X-Recaptcha-Token');
        if (!empty($recaptchaToken)) {
            $recaptchaResult = $this->recaptchaService->verify(
                token: $recaptchaToken,
                expectedAction: ['chat_message', 'start_chat', 'chat_stream', 'feedback'],
                ip: $ip
            );

            if (!$recaptchaResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $recaptchaResult['error'] ?? 'Verifikasi keamanan reCAPTCHA gagal.',
                    'code' => 'RECAPTCHA_FAILED'
                ], 403);
            }
        }

        // ── 2. Multi-tier Sliding Window Rate Limiting ────────────────────────────
        $ipLimit = (int) config('services.circuit_breaker.rate_limit_ip', 30);
        $sessLimit = (int) config('services.circuit_breaker.rate_limit_session', 15);

        $ipKey = 'rate_limit_ip:' . md5($ip);
        $sessKey = 'rate_limit_sess:' . md5($sessionId);

        $ipHits = (int) Cache::get($ipKey, 0);
        $sessHits = (int) Cache::get($sessKey, 0);

        if ($ipHits >= $ipLimit) {
            Log::warning("Rate limit exceeded for IP {$ip}: {$ipHits}/{$ipLimit}");
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan dari alamat IP Anda. Mohon tunggu 1 menit.',
                'code' => 'IP_RATE_LIMITED'
            ], 429)->header('Retry-After', '60');
        }

        if ($sessHits >= $sessLimit && $sessionId !== 'unknown') {
            Log::warning("Rate limit exceeded for Session {$sessionId}: {$sessHits}/{$sessLimit}");
            return response()->json([
                'success' => false,
                'message' => 'Anda mengirim pesan terlalu cepat. Mohon tunggu sebentar sebelum mengirim lagi.',
                'code' => 'SESSION_RATE_LIMITED'
            ], 429)->header('Retry-After', '30');
        }

        // Increment hit counters with 60s TTL
        Cache::put($ipKey, $ipHits + 1, 60);
        if ($sessionId !== 'unknown') {
            Cache::put($sessKey, $sessHits + 1, 60);
        }

        // ── 3. Prompt Injection & Character Flood Scan ───────────────────────────
        $message = $request->input('message');
        if (!empty($message) && is_string($message)) {
            $check = $this->guardrailService->checkPromptInjection($message);
            if (!$check['is_safe']) {
                return response()->json([
                    'success' => false,
                    'message' => $check['reason'] ?? 'Pesan tidak dapat diproses oleh sistem keamanan.',
                    'code' => 'SECURITY_FLAGGED'
                ], 422);
            }
        }

        return $next($request);
    }
}
