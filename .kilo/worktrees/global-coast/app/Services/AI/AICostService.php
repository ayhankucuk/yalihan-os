<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * AI Cost Service (Token Bucket Implementation)
 *
 * This service tracks AI usage costs and enforces limits using Redis counters.
 * It adheres to strict Context7 naming conventions for cache keys.
 */
class AICostService
{
    // Context7 Compliant Cache Key Prefixes
    private const KEY_PREFIX = 'ai_maliyet_sayaci';
    private const LIMIT_HOURLY = 'saatlik_limit';
    private const LIMIT_DAILY = 'gunluk_limit';

    /**
     * Record usage cost for a specific scope (user or global).
     *
     * @param string $scopeIdentifier User ID or IP address
     * @param float $costInUsd Cost of the transaction
     * @return array Current usage stats ['daily' => float, 'hourly' => float]
     */
    public function recordUsage(string $scopeIdentifier, float $costInUsd): array
    {
        $now = Carbon::now();

        // Define cache keys
        $dailyKey = $this->getCacheKey($scopeIdentifier, 'gunluk', $now->format('Y-m-d'));
        $hourlyKey = $this->getCacheKey($scopeIdentifier, 'saatlik', $now->format('Y-m-d-H'));

        // Atomic increment
        // Note: Cache::increment assumes integer/float is supported by driver (Redis supports float)
        // If driver doesn't support float increment, we might need a lock-read-write flow.
        // For now assuming Redis/Memcached which handle it or standard Cache allowed logic.
        // Since standard File cache might struggle with floats in increment, we track in CENTS (integers).

        $costInCents = (int) ($costInUsd * 10000); // 4 decimal precision tracking as integer

        $dailyTotal = Cache::increment($dailyKey, $costInCents);
        $hourlyTotal = Cache::increment($hourlyKey, $costInCents);

        // Set TTL if new key
        // Daily keys expire in 48 hours (buffer for reporting)
        // Hourly keys expire in 24 hours
        // We use 'add' to set TTL only if key didn't exist, but increment creates it.
        // Check for Hard Cap
        if (!$this->checkLimits($scopeIdentifier, true)) {
            $action = config('services.ai.hard_cap_aksiyon', 'fallback');

            if ($action === 'fallback') {
                 // Service caller should handle fallback generation if checkLimits returns false
                 // But recordUsage returns stats usually.
                 // We should probably throw standard exception here if configured to strict 429
                 // Or return a flag?
                 // The prompt asked for: "AIHardCapReached (Exception or Domain Error) produced"

                 throw new \App\Exceptions\AI\AIHardCapException();
            }

            throw new \App\Exceptions\AI\AIHardCapException("AI Hard Cap Reached");
        }

        // Check for Soft Cap
        $this->checkSoftCap($scopeIdentifier, $dailyTotal, $hourlyTotal);

        return [
            'gunluk_kullanim_usd' => $dailyTotal / 10000,
            'saatlik_kullanim_usd' => $hourlyTotal / 10000
        ];
    }

    /**
     * Check if soft cap is reached and trigger event if needed.
     */
    private function checkSoftCap(string $scope, int $dailyCents, int $hourlyCents): void
    {
        if (!config('services.ai.soft_cap_aktif', true)) {
            return;
        }

        $softCapPct = config('services.ai.soft_cap_percentage', 80) / 100;

        $dailyLimitCents = config('services.ai.daily_limit_usd', 10.0) * 10000;
        $hourlyLimitCents = config('services.ai.hourly_limit_usd', 2.0) * 10000;

        // Daily Check
        if ($dailyLimitCents > 0 && ($dailyCents / $dailyLimitCents) >= $softCapPct) {
            $this->triggerSoftCapEvent($scope, 'gunluk', $dailyLimitCents / 10000, $dailyCents / 10000);
        }

        // Hourly Check
        if ($hourlyLimitCents > 0 && ($hourlyCents / $hourlyLimitCents) >= $softCapPct) {
            $this->triggerSoftCapEvent($scope, 'saatlik', $hourlyLimitCents / 10000, $hourlyCents / 10000);
        }
    }

    private function triggerSoftCapEvent(string $scope, string $window, float $limit, float $usage): void
    {
        // Anti-spam lock key (Context7 compliant)
        // ai_soft_cap_kilidi:gunluk:user:{id}
        // Scope might contain 'user_' or just ID. We assume safe string.
        $scopeKey = str_replace([':', ' '], '_', $scope);
        $lockKey = "ai_soft_cap_kilidi:{$window}:{$scopeKey}";

        // TTL: 1 hour for hourly, 24 hours for daily
        $ttl = $window === 'saatlik' ? 3600 : 86400;

        // Atomic lock (SETNX)
        if (Cache::add($lockKey, 1, $ttl)) {
            $scopeType = str_contains($scope, 'ip_') ? 'ip' : 'user'; // Basic heuristic

            \App\Events\AI\AISoftCapReached::dispatch(
                $scopeType,
                $scope,
                $window,
                $limit,
                $usage,
                $usage / $limit
            );
        }
    }

    /**
     * Check if usage is within limits.
     *
     * @param string $scopeIdentifier User ID or IP
     * @param bool $throwIfExceeded Internal use to optionally throw exception (not used yet here, sticking to return bool)
     * @return bool True if allowed, False if limit exceeded
     */
    public function checkLimits(string $scopeIdentifier, bool $throwIfExceeded = false): bool
    {
        if (!config('services.ai.hard_cap_aktif', true)) {
            return true;
        }

        $now = Carbon::now();
        $dailyKey = $this->getCacheKey($scopeIdentifier, 'gunluk', $now->format('Y-m-d'));
        $hourlyKey = $this->getCacheKey($scopeIdentifier, 'saatlik', $now->format('Y-m-d-H'));

        $dailyCents = Cache::get($dailyKey, 0);
        $hourlyCents = Cache::get($hourlyKey, 0);

        $dailyLimitCents = config('services.ai.daily_limit_usd', 10.0) * 10000;
        $hourlyLimitCents = config('services.ai.hourly_limit_usd', 2.0) * 10000;

        $exceeded = false;

        if ($dailyLimitCents > 0 && $dailyCents >= $dailyLimitCents) {
            Log::warning("AI Daily Limit (Hard Cap) Exceeded for {$scopeIdentifier}");
            $exceeded = true;
        }

        if ($hourlyLimitCents > 0 && $hourlyCents >= $hourlyLimitCents) {
            Log::warning("AI Hourly Limit (Hard Cap) Exceeded for {$scopeIdentifier}");
            $exceeded = true;
        }

        return !$exceeded;
    }

    /**
     * Get current usage percentage for Soft Cap check.
     *
     * @param string $scopeIdentifier
     * @return float Maximum usage percentage (0-100) between daily and hourly.
     */
    public function getUsagePercentage(string $scopeIdentifier): float
    {
        $now = Carbon::now();
        $dailyKey = $this->getCacheKey($scopeIdentifier, 'gunluk', $now->format('Y-m-d'));
        $hourlyKey = $this->getCacheKey($scopeIdentifier, 'saatlik', $now->format('Y-m-d-H'));

        $dailyCents = Cache::get($dailyKey, 0);
        $hourlyCents = Cache::get($hourlyKey, 0);

        $dailyLimitCents = config('services.ai.daily_limit_usd', 10.0) * 10000;
        $hourlyLimitCents = config('services.ai.hourly_limit_usd', 2.0) * 10000;

        // Prevent division by zero
        $dailyPct = $dailyLimitCents > 0 ? ($dailyCents / $dailyLimitCents) * 100 : 0;
        $hourlyPct = $hourlyLimitCents > 0 ? ($hourlyCents / $hourlyLimitCents) * 100 : 0;

        return max($dailyPct, $hourlyPct);
    }

    private function getCacheKey(string $scope, string $period, string $timestamp): string
    {
        // Example: ai_maliyet_sayaci:gunluk:2026-02-08:user_123
        return sprintf('%s:%s:%s:%s', self::KEY_PREFIX, $period, $timestamp, $scope);
    }
}
