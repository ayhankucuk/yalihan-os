<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class AiAbuseDetectionService
 *
 * SAB Phase 14 Sprint 1: AI Abuse & Anomaly Detection Engine
 * Hybrid implementation combining test contract method signatures
 * with user-provided anomaly scoring logic.
 *
 * @package App\Services\AI
 */
class AiAbuseDetectionService
{
    /**
     * Spam detection threshold (identical prompts within 1 hour)
     */
    protected int $spamThreshold = 10;

    /**
     * Cache TTL for spam detection (seconds)
     */
    protected int $spamCacheTtl = 3600; // 1 hour

    /**
     * Get anomaly score for a user (0.0 = normal, 1.0 = high risk)
     *
     * Test Contract: AiSecurityTest.php:92
     * Combines DB-based user behavior analysis with input pattern analysis
     *
     * @param int $userId
     * @return float
     */
    public function getAnomalyScore(int $userId): float
    {
        $factors = $this->calculateAnomalyFactors($userId);

        // Weighted scoring:
        // - Request frequency: 40%
        // - Error rate: 30%
        // - Pattern diversity: 30%
        $score = ($factors['request_frequency'] * 0.4)
               + ($factors['error_rate'] * 0.3)
               + ($factors['pattern_diversity'] * 0.3);

        return min($score, 1.0);
    }

    /**
     * Detect prompt spam (cache-based)
     *
     * Test Contract: AiSecurityTest.php:116
     * Returns true if spam detected, logs warning
     *
     * @param int $userId
     * @param string $promptHash md5 hash of prompt
     * @return bool
     */
    public function detectPromptSpam(int $userId, string $promptHash): bool
    {
        $cacheKey = "ai_prompt_spam:{$userId}:{$promptHash}";
        $count = Cache::get($cacheKey, 0);

        // Increment counter
        $count = $this->incrementSpamCounter($userId, $promptHash);

        // Check threshold
        if ($count >= $this->spamThreshold) {
            Log::warning('AI Prompt Spam Detected', [
                'user_id' => $userId,
                'prompt_hash' => $promptHash,
                'count' => $count,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Calculate input anomaly score (user-provided logic)
     *
     * Analyzes input string for suspicious patterns:
     * - Excessive uppercase (shouting/command injection)
     * - Risky keywords (system manipulation attempts)
     *
     * @param string $girdi
     * @return float
     */
    public function anomaliSkoruHesapla(string $girdi): float
    {
        $anomaliSkoru = 0.0;
        $kucukHarfGirdi = Str::lower($girdi);
        $toplamKarakter = Str::length($girdi);

        if ($toplamKarakter === 0) {
            return $anomaliSkoru;
        }

        // 1. Uppercase manipulation density (prompt injection shouting effect)
        $buyukHarfSayisi = preg_match_all('/[A-Z-ÇĞİÖŞÜ]/u', $girdi);
        if (($buyukHarfSayisi / $toplamKarakter) > 0.5) {
            $anomaliSkoru += 0.30;
        }

        // 2. System keyword density analysis
        $riskliKelimeler = ['ignore', 'instruction', 'bypass', 'system', 'override', 'secret'];
        $eslesmeSayisi = 0;
        foreach ($riskliKelimeler as $kelime) {
            if (Str::contains($kucukHarfGirdi, $kelime)) {
                $eslesmeSayisi++;
            }
        }

        if ($eslesmeSayisi > 0) {
            $anomaliSkoru += min(($eslesmeSayisi * 0.20), 0.70);
        }

        return min($anomaliSkoru, 1.00);
    }

    /**
     * Tenant-based spam control (user-provided logic)
     *
     * @param int $tenantId
     * @param string $girdiIcerigi
     * @return bool
     */
    public function spamKontrolEt(int $tenantId, string $girdiIcerigi): bool
    {
        $girdiOzeti = hash('sha256', $girdiIcerigi);
        $onbellekAnahtari = "maliye_bekci_ai_spam:{$tenantId}:{$girdiOzeti}";

        // Lock identical requests for 5 seconds
        if (Cache::has($onbellekAnahtari)) {
            return true;
        }

        Cache::put($onbellekAnahtari, true, 5);
        return false;
    }

    /**
     * Increment spam counter for user/prompt combination
     *
     * @param int $userId
     * @param string $promptHash
     * @return int Current count
     */
    protected function incrementSpamCounter(int $userId, string $promptHash): int
    {
        $cacheKey = "ai_prompt_spam:{$userId}:{$promptHash}";
        $count = Cache::get($cacheKey, 0);
        $count++;

        Cache::put($cacheKey, $count, $this->spamCacheTtl);

        return $count;
    }

    /**
     * Calculate anomaly factors from user behavior
     *
     * @param int $userId
     * @return array ['request_frequency' => float, 'error_rate' => float, 'pattern_diversity' => float]
     */
    protected function calculateAnomalyFactors(int $userId): array
    {
        // 1. Request Frequency (last 1 hour)
        $requestCount = DB::table('ai_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $frequencyScore = match (true) {
            $requestCount >= 100 => 1.0,
            $requestCount >= 50 => 0.6,
            $requestCount >= 10 => 0.3,
            default => 0.0,
        };

        // 2. Error Rate (last 24 hours)
        $totalRequests = DB::table('ai_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $errorRequests = DB::table('ai_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->where('is_error', true)
            ->count();

        $errorRate = $totalRequests > 0 ? ($errorRequests / $totalRequests) : 0;
        $errorScore = match (true) {
            $errorRate >= 0.5 => 1.0,
            $errorRate >= 0.3 => 0.6,
            $errorRate >= 0.1 => 0.3,
            default => 0.0,
        };

        // 3. Pattern Diversity (unique prompt hashes in last hour)
        $uniquePrompts = DB::table('ai_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->distinct('prompt_hash')
            ->count('prompt_hash');

        $diversityScore = match (true) {
            $uniquePrompts <= 1 => 1.0, // Spam: same prompt repeated
            $uniquePrompts <= 5 => 0.6,
            $uniquePrompts <= 10 => 0.3,
            default => 0.0, // Normal: diverse prompts
        };

        return [
            'request_frequency' => $frequencyScore,
            'error_rate' => $errorScore,
            'pattern_diversity' => $diversityScore,
        ];
    }
}
