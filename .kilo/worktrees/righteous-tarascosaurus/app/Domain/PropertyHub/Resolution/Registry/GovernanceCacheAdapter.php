<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Registry;

use Illuminate\Support\Facades\Cache;

/**
 * Class GovernanceCacheAdapter
 *
 * Provides signature-aware, tenant-isolated Redis caching for snapshots.
 */
class GovernanceCacheAdapter
{
    private const CACHE_PREFIX = 'gov_v2:';
    private const PROFILE_PREFIX = 'gov_v2:profile:';
    private const TELEMETRY_PREFIX = 'gov_v2:telemetry:';

    /**
     * Get a cached snapshot if it exists and matches the expected signature.
     */
    public function get(string $tenantId, string $versionHash, string $expectedSignature): ?array
    {
        $key = $this->buildKey($tenantId, $versionHash, $expectedSignature, 'registry');
        $cached = Cache::get($key);

        if (!$cached) {
            $legacyKey = $this->buildLegacyKey($tenantId, $versionHash);
            $cached = Cache::get($legacyKey);
        }

        if (!$cached) {
            $this->recordL2Miss($tenantId);
            return null;
        }

        // Verify if the cached data matches the immutable signature
        if (($cached['signature'] ?? '') !== $expectedSignature) {
            // Potential cache corruption or mismatch - bust it
            $this->forget($tenantId, $versionHash);
            $this->recordL2Miss($tenantId);
            return null;
        }

        $this->recordL2Hit($tenantId);
        return $cached['snapshot'];
    }

    /**
     * Store a snapshot in the cache.
     */
    public function put(string $tenantId, string $versionHash, array $snapshot, string $signature): void
    {
        $key = $this->buildKey($tenantId, $versionHash, $signature, 'registry');
        $profil = $this->getTenantProfile($tenantId);
        $ttlDakika = $profil['yogunluk'] === 'YUKSEK' ? 120 : ($profil['yogunluk'] === 'DUSUK' ? 30 : 60);

        Cache::put($key, [
            'snapshot' => $snapshot,
            'signature' => $signature,
            'version_hash' => $versionHash,
            'cached_at' => now()->toIso8601String(),
            'cache_katmani' => 'L2',
        ], now()->addMinutes($ttlDakika));
    }

    /**
     * Evict a specific version from the cache.
     */
    public function forget(string $tenantId, string $versionHash): void
    {
        Cache::forget($this->buildLegacyKey($tenantId, $versionHash));
    }

    public function forgetPinned(string $tenantId, string $versionHash, string $signature, string $source = 'registry'): void
    {
        Cache::forget($this->buildKey($tenantId, $versionHash, $signature, $source));
    }

    /**
     * Dynamic cache orchestration by tenant traffic density.
     */
    public function orchestrateTenantProfile(string $tenantId, int $dakikaBazliIstekAdedi): array
    {
        $yogunluk = 'ORTA';

        if ($dakikaBazliIstekAdedi >= 300) {
            $yogunluk = 'YUKSEK';
        } elseif ($dakikaBazliIstekAdedi <= 30) {
            $yogunluk = 'DUSUK';
        }

        $profil = [
            'tenant_id' => $tenantId,
            'yogunluk' => $yogunluk,
            'lazy_warming' => $yogunluk === 'DUSUK',
            'prefetch_snapshot' => $yogunluk === 'YUKSEK',
        ];

        Cache::put(self::PROFILE_PREFIX . $tenantId, $profil, now()->addHours(6));

        return $profil;
    }

    public function getTenantProfile(string $tenantId): array
    {
        return Cache::get(self::PROFILE_PREFIX . $tenantId, [
            'tenant_id' => $tenantId,
            'yogunluk' => 'ORTA',
            'lazy_warming' => false,
            'prefetch_snapshot' => false,
        ]);
    }

    public function rehydrateAfterInconsistency(string $tenantId, string $versionHash, array $snapshot, string $signature): void
    {
        $this->forget($tenantId, $versionHash);
        $this->put($tenantId, $versionHash, $snapshot, $signature);
        $this->incrementTelemetry($tenantId, 'auto_rehydrate');
    }

    public function recordL1Hit(string $tenantId): void
    {
        $this->incrementTelemetry($tenantId, 'l1_hit');
    }

    public function recordL1Miss(string $tenantId): void
    {
        $this->incrementTelemetry($tenantId, 'l1_miss');
    }

    public function getHitRatios(string $tenantId): array
    {
        $l1Hit = (int) Cache::get($this->telemetryKey($tenantId, 'l1_hit'), 0);
        $l1Miss = (int) Cache::get($this->telemetryKey($tenantId, 'l1_miss'), 0);
        $l2Hit = (int) Cache::get($this->telemetryKey($tenantId, 'l2_hit'), 0);
        $l2Miss = (int) Cache::get($this->telemetryKey($tenantId, 'l2_miss'), 0);

        $l1Toplam = max(1, $l1Hit + $l1Miss);
        $l2Toplam = max(1, $l2Hit + $l2Miss);

        return [
            'l1_hit_ratio' => round($l1Hit / $l1Toplam, 4),
            'l2_hit_ratio' => round($l2Hit / $l2Toplam, 4),
            'l1_hit' => $l1Hit,
            'l1_miss' => $l1Miss,
            'l2_hit' => $l2Hit,
            'l2_miss' => $l2Miss,
        ];
    }

    /**
     * Build a tenant-isolated cache key.
     */
    private function buildKey(string $tenantId, string $versionHash, string $signature, string $source): string
    {
        return self::CACHE_PREFIX . "{$tenantId}:{$versionHash}:{$signature}:{$source}";
    }

    private function buildLegacyKey(string $tenantId, string $versionHash): string
    {
        return self::CACHE_PREFIX . "{$tenantId}:{$versionHash}";
    }

    private function recordL2Hit(string $tenantId): void
    {
        $this->incrementTelemetry($tenantId, 'l2_hit');
    }

    private function recordL2Miss(string $tenantId): void
    {
        $this->incrementTelemetry($tenantId, 'l2_miss');
    }

    private function incrementTelemetry(string $tenantId, string $metric): void
    {
        Cache::increment($this->telemetryKey($tenantId, $metric));
    }

    private function telemetryKey(string $tenantId, string $metric): string
    {
        return self::TELEMETRY_PREFIX . "{$tenantId}:{$metric}";
    }
}
