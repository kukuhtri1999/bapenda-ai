<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AiCircuitBreakerService
{
    const STATE_CLOSED = 'CLOSED';       // Healthy, normal operation
    const STATE_OPEN = 'OPEN';           // Tripped, routing to fallback
    const STATE_HALF_OPEN = 'HALF_OPEN'; // Probing recovery

    protected bool $enabled;
    protected int $maxFailures;
    protected int $resetTimeout; // seconds

    public function __construct()
    {
        $this->enabled = (bool) config('services.circuit_breaker.enabled', true);
        $this->maxFailures = (int) config('services.circuit_breaker.max_failures', 3);
        $this->resetTimeout = (int) config('services.circuit_breaker.reset_timeout', 60);
    }

    /**
     * Get the current state of the AI Circuit Breaker.
     */
    public function getState(): string
    {
        if (!$this->enabled) {
            return self::STATE_CLOSED;
        }

        $state = Cache::get('ai_cb_state', self::STATE_CLOSED);
        $lastFailureTime = Cache::get('ai_cb_last_failure', 0);

        if ($state === self::STATE_OPEN) {
            if ((time() - $lastFailureTime) >= $this->resetTimeout) {
                Cache::put('ai_cb_state', self::STATE_HALF_OPEN, 300);
                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    /**
     * Check if primary provider is currently available to accept requests.
     */
    public function isAvailable(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $state = $this->getState();
        return $state === self::STATE_CLOSED || $state === self::STATE_HALF_OPEN;
    }

    /**
     * Record a successful execution, resetting failure counters.
     */
    public function recordSuccess(): void
    {
        if (!$this->enabled) return;

        Cache::forget('ai_cb_failures');
        Cache::put('ai_cb_state', self::STATE_CLOSED, 86400);
        Cache::increment('ai_cb_total_successes');
    }

    /**
     * Record a failure or timeout event.
     */
    public function recordFailure(\Throwable $e): void
    {
        if (!$this->enabled) return;

        $failures = (int) Cache::increment('ai_cb_failures');
        Cache::put('ai_cb_last_failure', time(), 86400);
        Cache::increment('ai_cb_total_failures');

        Log::error("AiCircuitBreaker recorded failure ({$failures}/{$this->maxFailures}): " . $e->getMessage());

        if ($failures >= $this->maxFailures) {
            Cache::put('ai_cb_state', self::STATE_OPEN, 86400);
            Log::alert("🚨 AI CIRCUIT BREAKER TRIPPED TO [OPEN]! Traffic will be routed to secondary fallback for {$this->resetTimeout}s.");
        }
    }

    /**
     * Execute a callable through the Circuit Breaker with automatic failover.
     *
     * @param callable $primaryCall
     * @param callable|null $fallbackCall
     * @return mixed
     * @throws Exception
     */
    public function executeWithFallback(callable $primaryCall, ?callable $fallbackCall = null): mixed
    {
        if ($this->isAvailable()) {
            try {
                $result = $primaryCall();
                $this->recordSuccess();
                return $result;
            } catch (\Throwable $e) {
                $this->recordFailure($e);

                if ($fallbackCall) {
                    Log::info("AiCircuitBreaker switching to fallback provider after primary failure.");
                    return $fallbackCall($e);
                }
                throw $e;
            }
        }

        // Circuit is OPEN
        Log::warning("AiCircuitBreaker is OPEN. Bypassing primary call directly to fallback.");
        if ($fallbackCall) {
            return $fallbackCall(new Exception("Circuit breaker is OPEN"));
        }

        throw new Exception("AI Service is temporarily undergoing maintenance (Circuit Breaker OPEN).");
    }

    /**
     * Get health metrics for administrative telemetry.
     */
    public function getHealthStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'state' => $this->getState(),
            'current_consecutive_failures' => (int) Cache::get('ai_cb_failures', 0),
            'max_failures_threshold' => $this->maxFailures,
            'reset_timeout_seconds' => $this->resetTimeout,
            'total_successes' => (int) Cache::get('ai_cb_total_successes', 0),
            'total_failures' => (int) Cache::get('ai_cb_total_failures', 0),
        ];
    }
}
