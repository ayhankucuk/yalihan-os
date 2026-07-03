<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesWorkforceEventVocabulary;
use App\Models\Hermes\WorkforceExecutionLog;
use Illuminate\Support\Facades\Log;

/**
 * PhotoAgent — AI Workforce Sprint 4.3
 *
 * Triggered by: workforce.photo_analysis_requested
 * Role: Analyzes listing photos and suggests improvements
 *
 * No external API calls (vertical slice — rule-based).
 * Production: would call VisionAnalysisService.
 */
class PhotoAgent implements HermesHandlerContract
{
    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesWorkforceEventVocabulary::WORKFORCE_PHOTO_ANALYSIS_REQUESTED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);
        $payload = $event->toPayload();
        $ilanId = $payload['ilan_id'] ?? null;
        $tenantId = $event->tenantId();
        $chainId = $payload['chain_id'] ?? null;
        $portfolioAnalysis = $payload['portfolio_analysis'] ?? [];

        // Record execution
        $execLog = WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
            'agent_name' => 'photo_agent',
            'agent_class' => self::class,
            'event_received' => $event->eventName(),
            'event_chain_step' => 1,
            'input_payload' => $payload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            // Rule-based photo analysis (production: VisionAnalysisService)
            $ilanBaslik = $portfolioAnalysis['ilan_baslik'] ?? $payload['ilan_baslik'] ?? '';
            $tier = $portfolioAnalysis['tier'] ?? 'standard';

            $result = [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'photos_found' => 0, // No real photo data in vertical slice
                'recommendations' => $this->generatePhotoRecommendations($ilanBaslik, $tier),
                'quality_score' => $this->calculateQualityScore($ilanBaslik, $tier),
                'suggested_count' => $this->suggestPhotoCount($tier),
                'priority_photos' => $this->getPriorityPhotoTypes($tier),
                'analyzed_at' => now()->toIso8601String(),
            ];

            $execLog->markCompleted($result);

            Log::info('[PhotoAgent] Photo analysis complete', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'quality_score' => $result['quality_score'],
                'recommendations' => count($result['recommendations']),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'quality_score' => $result['quality_score'],
                'recommendations' => $result['recommendations'],
                'suggested_photo_count' => $result['suggested_count'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[PhotoAgent] Photo analysis failed', [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false;
    }

    private function generatePhotoRecommendations(string $baslik, string $tier): array
    {
        $lower = mb_strtolower($baslik);
        $recommendations = [];

        // Always recommend minimum photos
        $minPhotos = match ($tier) {
            'luxury' => 10,
            'premium' => 7,
            'standard' => 5,
            'budget' => 3,
            default => 4,
        };

        $recommendations[] = [
            'type' => 'count',
            'message' => "Minimum {$minPhotos} fotoğraf önerilir ({$tier} segment).",
            'priority' => 'high',
        ];

        // Tier-specific recommendations
        if ($tier === 'luxury' || $tier === 'premium') {
            $recommendations[] = [
                'type' => 'drone',
                'message' => 'Drone çekimi önerilir — lüks segmentlerde %40 daha fazla ilgi.',
                'priority' => 'medium',
            ];
            $recommendations[] = [
                'type' => 'video',
                'message' => 'Sanal tur veya video walkthrough eklenmesi önerilir.',
                'priority' => 'medium',
            ];
        }

        // Feature-based recommendations
        if (str_contains($lower, 'deniz') || str_contains($lower, 'havuz')) {
            $recommendations[] = [
                'type' => 'exterior',
                'message' => 'Manzara ve havuz fotoğrafları ön plana çıkarılmalı.',
                'priority' => 'high',
            ];
        }

        if (str_contains($lower, 'dublex') || str_contains($lower, 'villa')) {
            $recommendations[] = [
                'type' => 'interior',
                'message' => 'İç mekan detayları ve kat planı eklenmeli.',
                'priority' => 'medium',
            ];
        }

        // Lighting recommendation
        $recommendations[] = [
            'type' => 'lighting',
            'message' => 'Gün ışığında çekim tercih edilmeli.',
            'priority' => 'low',
        ];

        return $recommendations;
    }

    private function calculateQualityScore(string $baslik, string $tier): float
    {
        $score = 0.5; // Base score

        // Tier bonus
        $score += match ($tier) {
            'luxury' => 0.3,
            'premium' => 0.2,
            'standard' => 0.1,
            'budget' => 0.0,
            default => 0.0,
        };

        // Feature richness bonus
        $lower = mb_strtolower($baslik);
        $features = ['deniz', 'havuz', 'bahçe', 'garaj', 'teras', 'jakuzi', 'dublex'];
        $foundCount = count(array_filter($features, fn ($f) => str_contains($lower, $f)));
        $score += min($foundCount * 0.05, 0.2);

        return round(min($score, 1.0), 2);
    }

    private function suggestPhotoCount(string $tier): int
    {
        return match ($tier) {
            'luxury' => 10,
            'premium' => 7,
            'standard' => 5,
            'budget' => 3,
            default => 4,
        };
    }

    private function getPriorityPhotoTypes(string $tier): array
    {
        $base = ['cephe', 'iç_oda', 'mutfak', 'banyo'];

        if ($tier === 'luxury' || $tier === 'premium') {
            return array_merge($base, ['havuz', 'bahçe', 'manzara', 'garaj', 'drone']);
        }

        return $base;
    }
}
