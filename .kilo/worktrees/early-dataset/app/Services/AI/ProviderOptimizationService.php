<?php

namespace App\Services\AI;

use App\Models\AiProviderDecision;
use App\Models\AiProviderProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProviderOptimizationService
{
    protected ProviderScoringEngine $scoringEngine;
    protected AiCostGuardService $costGuard;
    protected AiAlertService $alertService;

    public function __construct(
        ProviderScoringEngine $scoringEngine, 
        AiCostGuardService $costGuard,
        AiAlertService $alertService
    ) {
        $this->scoringEngine = $scoringEngine;
        $this->costGuard = $costGuard;
        $this->alertService = $alertService;
    }

    /**
     * Choose the best provider dynamically
     */
    public function chooseProvider(?int $categoryId, ?int $yayinTipiId, array $context = []): string
    {
        $correlationId = $context['correlation_id'] ?? null;

        // 1. Feature Flag Check
        if (!config('provider-optimization.enabled')) {
            return $this->getStaticFallback('Optimization disabled');
        }

        // 2. Cost Guard Pressure Check (>= 95% budget -> Force cheapest)
        $budget = $this->costGuard->checkBudget(null);
        if (isset($budget['level']) && $budget['level'] >= 0.95) {
            return $this->recordDecision($categoryId, $yayinTipiId, 'gemini', [], [
                'trigger' => 'cost_guard_downgrade',
                'reason' => "Budget at " . ($budget['level'] * 100) . "%"
            ], $correlationId);
        }

        // 3. Dynamic Scoring
        $weights = $this->scoringEngine->getWeights($categoryId);
        
        // Fetch both 7d and 30d profiles
        $allProfiles = AiProviderProfile::whereIn('window', ['7d', '30d'])
            ->where(function($q) use ($categoryId) {
                $q->where('kategori_id', $categoryId)->orWhereNull('kategori_id');
            })
            ->get()
            ->groupBy('provider');

        $scores = [];
        $debugInfo = [];

        $minSamples7d = config('provider-optimization.sample_size.min_7d', 50);

        foreach ($allProfiles as $provider => $providerProfiles) {
            // Apply cooldown if exists
            if ($this->isInCooldown($provider)) {
                $scores[$provider] = -1;
                $debugInfo[$provider] = 'cooldown_active';
                continue;
            }

            // Strategy: 
            // 1. Try Category-Specific 7d
            // 2. Fallback to Category-Specific 30d if 7d samples < min
            // 3. Fallback to Global 7d
            // 4. Fallback to Global 30d
            
            $profile = null;
            $source = 'none';

            // Check Cat-Specific 7d
            $p7d = $providerProfiles->first(fn($p) => $p->kategori_id == $categoryId && $p->window == '7d');
            if ($p7d && $p7d->sample_size >= $minSamples7d) {
                $profile = $p7d;
                $source = 'cat_7d';
            } else {
                // Check Cat-Specific 30d
                $p30d = $providerProfiles->first(fn($p) => $p->kategori_id == $categoryId && $p->window == '30d');
                if ($p30d && $p30d->sample_size >= config('provider-optimization.sample_size.min_30d', 100)) {
                    $profile = $p30d;
                    $source = 'cat_30d';
                } else {
                    // Check Global 7d
                    $g7d = $providerProfiles->first(fn($p) => $p->kategori_id == null && $p->window == '7d');
                    if ($g7d && $g7d->sample_size >= $minSamples7d) {
                        $profile = $g7d;
                        $source = 'global_7d';
                    } else {
                        // Check Global 30d
                        $g30d = $providerProfiles->first(fn($p) => $p->kategori_id == null && $p->window == '30d');
                        if ($g30d) {
                            $profile = $g30d;
                            $source = 'global_30d';
                        }
                    }
                }
            }

            if ($profile) {
                $scores[$provider] = $this->scoringEngine->calculateScore($profile->toArray(), $weights);
                $debugInfo[$provider] = ['source' => $source, 'samples' => $profile->sample_size];
            }
        }

        if (empty($scores)) {
            return $this->getStaticFallback('No performance data found');
        }

        arsort($scores);
        $chosen = array_key_first($scores);

        return $this->recordDecision($categoryId, $yayinTipiId, $chosen, $scores, [
            'trigger' => 'dynamic_scoring',
            'weights' => $weights,
            'debug' => $debugInfo
        ], $correlationId);
    }

    /**
     * Static fallback from config
     */
    protected function getStaticFallback(string $reason): string
    {
        $list = config('provider-optimization.static_priority', ['openai', 'vertex', 'gemini']);
        return $list[0];
    }

    /**
     * Check if a provider is in cooldown due to errors
     */
    protected function isInCooldown(string $provider): bool
    {
        return Cache::has("ai_provider_cooldown:{$provider}");
    }

    /**
     * Record the decision to DB for audit
     */
    protected function recordDecision(?int $catId, ?int $pubId, string $chosen, array $scores, array $reason, ?string $correlationId): string
    {
        // Phase 12.2: Check provider health and trigger alerts
        $providerHealth = 'healthy';
        $errorRate = 0.0;
        
        if (isset($reason['debug'][$chosen])) {
            $profile = AiProviderProfile::where('provider', $chosen)
                ->where('window', '7d')
                ->first();
                
            if ($profile) {
                $errorRate = $profile->error_rate ?? 0.0;
                
                // Trigger provider health alerts
                if ($errorRate > 0) {
                    $this->alertService->providerErrorAlert($chosen, $errorRate, $profile->sample_size);
                }
                
                if ($errorRate >= 0.20) {
                    $providerHealth = 'critical';
                    // Activate cooldown
                    Cache::put("ai_provider_cooldown:{$chosen}", true, now()->addMinutes(60));
                } elseif ($errorRate >= 0.10) {
                    $providerHealth = 'degraded';
                }
            }
        }

        // Phase 12.1 & 12.2: Build debug metadata with health info
        $debugMetadata = [
            'window_used' => $reason['debug'][$chosen]['source'] ?? 'unknown',
            'sample_size' => $reason['debug'][$chosen]['samples'] ?? 0,
            'all_scores' => $scores,
            'error_rate' => $errorRate,
            'provider_health' => $providerHealth,
            'last_check_at' => now()->toIso8601String(),
            'timestamp' => now()->toIso8601String()
        ];

        AiProviderDecision::create([
            'correlation_id' => $correlationId,
            'kategori_id' => $catId,
            'yayin_tipi_id' => $pubId,
            'chosen_provider' => $chosen,
            'scores_json' => $scores,
            'reason_json' => $reason,
            'debug_metadata' => $debugMetadata
        ]);

        // Phase 12.1: Structured Logging
        Log::info('AI Provider Selected', [
            'correlation_id' => $correlationId,
            'chosen_provider' => $chosen,
            'kategori_id' => $catId,
            'yayin_tipi_id' => $pubId,
            'scores' => $scores,
            'trigger' => $reason['trigger'],
            'window_used' => $debugMetadata['window_used'],
            'sample_size' => $debugMetadata['sample_size'],
            'provider_health' => $providerHealth,
            'error_rate' => $errorRate
        ]);

        return $chosen;
    }
}
