<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Events\Workforce\PropertyWorkspaceCreated;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;

/**
 * PhotoAgent — AI Workforce Sprint 4.5
 *
 * Subscribes to:
 * - workforce.workspace.created (triggers after DriveAgent creates workspace)
 *
 * Emits:
 * - workforce.photo_analysis.completed (downstream: DescriptionAgent)
 *
 * Role: Analyzes listing photos and suggests improvements.
 * Updates workspace lifecycle: WORKSPACE_CREATED → MEDIA_READY
 *
 * No external API calls (vertical slice — rule-based).
 * Production: would call VisionAnalysisService.
 */
class PhotoAgent implements HermesHandlerContract
{
    public function __construct(
        private HermesService $hermesService,
    ) {}

    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            'workforce.workspace.created',
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

        // Load workspace
        $workspace = $this->loadWorkspace($payload);

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
            $ilanBaslik = $payload['ilan_baslik'] ?? $workspace?->root_folder_name ?? '';
            $tier = $this->classifyTier($ilanBaslik);

            $analysisResult = [
                'ilan_id' => $ilanId,
                'chain_id' => $chainId,
                'photos_found' => 0,
                'recommendations' => $this->generatePhotoRecommendations($ilanBaslik, $tier),
                'quality_score' => $this->calculateQualityScore($ilanBaslik, $tier),
                'suggested_count' => $this->suggestPhotoCount($tier),
                'priority_photos' => $this->getPriorityPhotoTypes($tier),
                'analyzed_at' => now()->toIso8601String(),
            ];

            // Update workspace: mark agent complete + auto-advance state
            if ($workspace) {
                $workspace->markAiAgentComplete('photo_agent', $analysisResult);
            }

            $execLog->markCompleted($analysisResult);

            Log::info('[PhotoAgent] Photo analysis complete', [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace?->getKey(),
                'quality_score' => $analysisResult['quality_score'],
                'lifecycle_state' => $workspace?->lifecycle_state?->value,
            ]);

            // Emit PhotoAnalysisCompleted event
            if ($workspace) {
                $this->emitPhotoAnalysisCompleted($workspace, $analysisResult, [
                    'ilan_id' => $ilanId,
                    'chain_id' => $chainId,
                ]);
            }

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace?->getKey(),
                'quality_score' => $analysisResult['quality_score'],
                'recommendations' => $analysisResult['recommendations'],
                'suggested_photo_count' => $analysisResult['suggested_count'],
                'lifecycle_state' => $workspace?->lifecycle_state?->value,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());

            Log::error('[PhotoAgent] Photo analysis failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
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

    private function loadWorkspace(array $payload): ?PortfolioDriveWorkspace
    {
        $workspaceId = $payload['workspace_id'] ?? null;
        $ilanId = $payload['ilan_id'] ?? null;

        if ($workspaceId) {
            return PortfolioDriveWorkspace::find($workspaceId);
        }
        if ($ilanId) {
            return PortfolioDriveWorkspace::forPortfolio($ilanId)->first();
        }
        return null;
    }

    private function emitPhotoAnalysisCompleted(
        PortfolioDriveWorkspace $workspace,
        array $analysisResult,
        array $metadata,
    ): void {
        $event = new PhotoAnalysisCompleted($workspace, $analysisResult, $metadata);
        $this->hermesService->receive($event);
    }

    private function classifyTier(string $baslik): string
    {
        $lower = mb_strtolower($baslik);
        if (str_contains($lower, 'lüks') || str_contains($lower, 'luxury') || str_contains($lower, 'prestij')) {
            return 'luxury';
        }
        if (str_contains($lower, 'premium') || str_contains($lower, 'özel')) {
            return 'premium';
        }
        return 'standard';
    }

    private function generatePhotoRecommendations(string $baslik, string $tier): array // @sab-ignore-context7
    {
        $lower = mb_strtolower($baslik);
        $recommendations = []; // @sab-ignore-context7

        $minPhotos = match ($tier) {
            'luxury' => 10,
            'premium' => 7,
            default => 5,
        };

        $recommendations[] = [
            'type' => 'count',
            'message' => "Minimum {$minPhotos} fotoğraf önerilir ({$tier} segment).",
            'priority' => 'high',
        ];

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

        $recommendations[] = [
            'type' => 'lighting',
            'message' => 'Gün ışığında çekim tercih edilmeli.',
            'priority' => 'low',
        ];

        return $recommendations;
    }

    private function calculateQualityScore(string $baslik, string $tier): float
    {
        $score = 0.5;
        $score += match ($tier) {
            'luxury' => 0.3,
            'premium' => 0.2,
            default => 0.1,
        };

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
            default => 5,
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
