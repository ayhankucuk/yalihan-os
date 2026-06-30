<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resiliency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker for V3 Engine
 *
 * Disables V3 engine if error rates exceed thresholds.
 */
class CircuitBreaker
{
    private const CACHE_KEY = 'propertyhub.v3.circuit_open';
    private const CACHE_TTL = 300; // 5 minutes cool-down

    public function __construct(
        private readonly float $errorThreshold,
        private readonly int $windowSeconds,
        private readonly int $bucketSize = 60,
    ) {}

    /**
     * Check if circuit is open (V3 disabled).
     */
    public function isAvailable(?string $tenantId = null): bool
    {
        return !Cache::get($this->getKey(self::CACHE_KEY, $tenantId), false);
    }

    /**
     * Check thresholds and trip circuit breaker if needed.
     */
    public function check(?string $tenantId = null): void
    {
        if (!$this->isAvailable($tenantId)) {
            return;
        }

        $errorRate = $this->getRate('error', $tenantId);

        if ($errorRate > $this->errorThreshold) {
            $this->trip('error_rate', $errorRate, $tenantId);
        }
    }

    /**
     * Trip the circuit breaker.
     */
    private function trip(string $reason, float $value, ?string $tenantId = null): void
    {
        Cache::put($this->getKey(self::CACHE_KEY, $tenantId), true, self::CACHE_TTL);

        Log::critical('PropertyHub V3 Circuit Breaker TRIPPED', [
            'tenant_id' => $tenantId ?? 'GLOBAL',
            'reason' => $reason,
            'value' => $value,
            'threshold' => $this->errorThreshold,
            'window_seconds' => $this->windowSeconds,
        ]);
    }

    /**
     * Manually reset circuit breaker.
     */
    public function reset(?string $tenantId = null): void
    {
        Cache::forget($this->getKey(self::CACHE_KEY, $tenantId));
        Log::info('PropertyHub V3 Circuit Breaker manually reset', ['tenant_id' => $tenantId]);
    }

    /**
     * Calculate rate over current window using buckets.
     */
    private function getRate(string $type, ?string $tenantId = null): float
    {
        $now = time();
        $total = 0;
        $count = 0;

        for ($i = 0; $i < ($this->windowSeconds / $this->bucketSize); $i++) {
            $bucketTime = floor(($now - ($i * $this->bucketSize)) / $this->bucketSize) * $this->bucketSize;
            $keyPrefix = "propertyhub.v3.bucket.$bucketTime";
            $keyPrefix = $this->getKey($keyPrefix, $tenantId);

            $total += (int) Cache::get("$keyPrefix.total", 0);
            $count += (int) Cache::get("$keyPrefix.$type", 0);
        }

        return $total > 0 ? $count / $total : 0.0;
    }

    /**
     * Report an execution result to the circuit breaker
     */
    public function report(bool $success, ?string $tenantId = null): void
    {
        $bucketTime = floor(time() / $this->bucketSize) * $this->bucketSize;
        $keyPrefix = "propertyhub.v3.bucket.$bucketTime";
        $keyPrefix = $this->getKey($keyPrefix, $tenantId);
        $ttl = $this->windowSeconds * 2; // Keep buckets long enough

        Cache::increment("$keyPrefix.total");
        if (!$success) {
            Cache::increment("$keyPrefix.error");
        }

        // Extend TTL
        Cache::put("$keyPrefix.total", Cache::get("$keyPrefix.total", 0), $ttl);
        Cache::put("$keyPrefix.error", Cache::get("$keyPrefix.error", 0), $ttl);
    }

    private function getKey(string $baseKey, ?string $tenantId): string
    {
        return $tenantId ? "{$baseKey}:{$tenantId}" : $baseKey;
    }
}
