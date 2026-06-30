<?php

namespace App\Services\Resilience;

use App\Contracts\Resilience\CircuitBreakerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache-Based Circuit Breaker Implementation
 *
 * SAB v4.1 - Resilience Pattern
 * Context7: Harici API'ler (TKGM, TürkiyeAPI vb.) için devre kesici
 *
 * States:
 *   - closed:    Normal çalışma, istekler iletilir
 *   - open:      Hata eşiği aşıldı, istekler engellenir (cool-down süresi)
 *   - half-open: Cool-down sonrası tek istek test amaçlı iletilir
 */
class CircuitBreaker implements CircuitBreakerInterface
{
    /**
     * Circuit açık kalma süresi (saniye)
     */
    protected int $cooldownSeconds;

    /**
     * Circuit açılması için gereken ardışık hata sayısı
     */
    protected int $failureThreshold;

    public function __construct()
    {
        // Default fallbacks
    }

    protected function getCooldown(string $serviceName): int
    {
        return (int) config("ai-runtime.circuit_breaker.{$serviceName}.cooldown_seconds", 
            config('ai-runtime.circuit_breaker.cooldown_seconds', 120)
        );
    }

    protected function getThreshold(string $serviceName): int
    {
        return (int) config("ai-runtime.circuit_breaker.{$serviceName}.failure_threshold", 
            config('ai-runtime.circuit_breaker.failure_threshold', 5)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getState(string $serviceName): string
    {
        $openUntil = Cache::get($this->openKey($serviceName));

        if ($openUntil !== null) {
            if (now()->timestamp < $openUntil) {
                return 'open';
            }

            // Cool-down bitti → half-open
            return 'half-open';
        }

        return 'closed';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(string $serviceName): bool
    {
        $state = $this->getState($serviceName);

        // closed ve half-open durumlarında istek gönderilebilir
        return $state !== 'open';
    }

    /**
     * {@inheritdoc}
     */
    public function success(string $serviceName): void
    {
        // Başarılı istek: hata sayacını sıfırla + circuit'i kapat
        Cache::forget($this->failureKey($serviceName));
        Cache::forget($this->openKey($serviceName));
    }

    /**
     * {@inheritdoc}
     */
    public function failure(string $serviceName): void
    {
        $cooldown = $this->getCooldown($serviceName);
        $threshold = $this->getThreshold($serviceName);

        $failureKey = $this->failureKey($serviceName);
        $failures = (int) Cache::get($failureKey, 0) + 1;

        Cache::put($failureKey, $failures, $cooldown * 2);

        if ($failures >= $threshold) {
            $openUntil = now()->addSeconds($cooldown)->timestamp;
            Cache::put($this->openKey($serviceName), $openUntil, $cooldown);

            Log::warning("🛡️ CIRCUIT_BREAKER_OPENED", [
                'service' => $serviceName,
                'failures' => $failures,
                'cooldown_seconds' => $cooldown,
            ]);
        }
    }

    /**
     * Cache key: hata sayacı
     */
    protected function failureKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:failures";
    }

    /**
     * Cache key: circuit açık durumu
     */
    protected function openKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:open_until";
    }
}
