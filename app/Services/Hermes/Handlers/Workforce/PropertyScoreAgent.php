<?php

namespace App\Services\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\Workforce\DescriptionCompleted;
use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Events\Workforce\PropertyScoreCalculated;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PropertyScoreAgent — AI Workforce Sprint 4.5
 *
 * Subscribes to:
 * - workforce.photo_analysis.completed
 * - workforce.description.completed
 *
 * Emits:
 * - workforce.property_score.calculated (after both inputs received)
 *
 * Role: Calculates a composite property intelligence score based on
 * photo analysis and description analysis results.
 *
 * State: Advances workspace lifecycle to QUALITY_CHECKED after completion.
 */
class PropertyScoreAgent implements HermesHandlerContract
{
    /** @var array<string, array> In-memory buffer for cross-event results */
    private array $pendingResults = [];

    public function __construct(
        private HermesService $hermesService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function subscribesTo(): array
    {
        return [
            'workforce.photo_analysis.completed',
            'workforce.description.completed',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);
        $payload = $event->toPayload();
        $ilanId = $payload['ilan_id'] ?? null;
        $tenantId = $event->tenantId();
        $workspaceId = $payload['workspace_id'] ?? null;
        $eventName = $event->eventName();

        // Record execution
        $execLog = $this->recordExecution(
            ilanId: $ilanId,
            tenantId: $tenantId,
            chainId: $payload['chain_id'] ?? null,
            eventName: $eventName,
            inputPayload: $payload,
        );

        try {
            // Load workspace
            $workspace = $this->loadWorkspace($workspaceId, $ilanId);
            if (! $workspace) {
                $execLog->markFailed('Workspace not found');

                return ['error' => 'Workspace not found', 'duration_ms' => $this->elapsed($startTime)];
            }

            // Check if workspace already completed score calculation for this cycle
            $flags = $workspace->ai_completion_flags ?? [];
            if (! empty($flags['property_score_agent']['complete']) && ! empty($flags['property_score_agent']['result'])) {
                $execLog->markCompleted($flags['property_score_agent']['result']);

                return [
                    'handler' => self::class,
                    'ilan_id' => $ilanId,
                    'workspace_id' => $workspaceId,
                    'already_completed' => true,
                    'duration_ms' => $this->elapsed($startTime),
                ];
            }

            // Buffer result by event type with persistent Cache backing (H-05)
            $buffer = $this->getPendingBuffer($workspace);

            if ($event instanceof PhotoAnalysisCompleted) {
                $buffer['photo'] = array_merge($payload, [
                    'quality_score' => $payload['quality_score'] ?? ($event->analysisResult['quality_score'] ?? null),
                    'recommendations' => $payload['recommendations'] ?? ($event->analysisResult['recommendations'] ?? []),
                    'chain_id' => $payload['chain_id'] ?? null,
                ]);
            } elseif ($event instanceof DescriptionCompleted) {
                $buffer['description'] = array_merge($payload, [
                    'title_score' => $payload['title_score'] ?? ($event->analysisResult['title_score'] ?? null),
                    'improved_title' => $payload['improved_title'] ?? ($event->analysisResult['improved_title'] ?? null),
                    'suggestions' => $payload['suggestions'] ?? ($event->analysisResult['suggestions'] ?? []),
                    'chain_id' => $payload['chain_id'] ?? null,
                ]);
            }

            $this->savePendingBuffer($workspace->ilan_id, $buffer);

            $photo = $buffer['photo'] ?? null;
            $description = $buffer['description'] ?? null;

            // Need BOTH results to calculate composite score
            if (! $photo || ! $description) {
                $waitingFor = ! $photo ? 'photo' : 'description';
                $execLog->markCompleted([
                    'buffered' => true,
                    'waiting_for' => $waitingFor,
                ]);

                return [
                    'handler' => self::class,
                    'ilan_id' => $ilanId,
                    'workspace_id' => $workspaceId,
                    'buffered' => true,
                    'waiting_for' => $waitingFor,
                    'duration_ms' => $this->elapsed($startTime),
                ];
            }

            // Calculate composite property score
            $scoreResult = $this->calculatePropertyScore($workspace, $photo, $description);

            // Mark agent complete on workspace
            $workspace->markAiAgentComplete('property_score_agent', $scoreResult);

            $execLog->markCompleted($scoreResult);

            // Propagate chain_id across workforce lifecycle
            $chainId = $payload['chain_id']
                ?? ($photo['chain_id'] ?? null)
                ?? ($description['chain_id'] ?? null);

            // Emit PropertyScoreCalculated event
            $this->emitPropertyScoreCalculated($workspace, $scoreResult, [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspaceId,
                'ilan_baslik' => $workspace->root_folder_name,
                'tier' => $scoreResult['quality_tier'],
                'chain_id' => $chainId,
                'triggered_by' => $eventName,
            ]);

            Log::info('[PropertyScoreAgent] Property score calculated', [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspaceId,
                'overall_score' => $scoreResult['overall_score'],
                'quality_tier' => $scoreResult['quality_tier'],
                'chain_id' => $chainId,
            ]);

            // Clear buffer for this workspace (both cache and in-memory)
            $this->clearPendingBuffer($workspace->ilan_id);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'workspace_id' => $workspaceId,
                'overall_score' => $scoreResult['overall_score'],
                'quality_tier' => $scoreResult['quality_tier'],
                'component_scores' => $scoreResult['component_scores'],
                'duration_ms' => $this->elapsed($startTime),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());
            Log::error('[PropertyScoreAgent] Failed', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);

            return ['handler' => self::class, 'ilan_id' => $ilanId, 'error' => $e->getMessage(), 'duration_ms' => $this->elapsed($startTime)];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAsync(): bool
    {
        return false;
    }

    // ─── Score Calculation ────────────────────────────────────────────────

    /**
     * Calculate composite property intelligence score
     *
     * @return array<string, mixed>
     */
    private function calculatePropertyScore(
        PortfolioDriveWorkspace $workspace,
        ?array $photo,
        ?array $description,
    ): array {
        // Component scores (0.0 - 1.0)
        $photoScore = ($photo['quality_score'] ?? 0.5);
        $descriptionScore = ($description['title_score'] ?? 0.5);

        // Weighted composite
        $overallScore = round(
            ($photoScore * 0.45) + ($descriptionScore * 0.55),
            2
        );

        // Determine quality tier
        $qualityTier = match (true) {
            $overallScore >= 0.85 => 'premium_plus',
            $overallScore >= 0.70 => 'premium',
            $overallScore >= 0.50 => 'standard',
            default => 'budget',
        };

        // Market positioning
        $marketPositioning = match ($qualityTier) {
            'premium_plus' => 'ultra_luxury',
            'premium' => 'upper_market',
            'standard' => 'mass_market',
            default => 'value_market',
        };

        // Recommendations based on weak components
        $recommendations = [];
        if ($photoScore < 0.6) {
            $recommendations[] = [
                'type' => 'photo_quality',
                'message' => 'Fotoğraf kalitesi düşük — minimum kalite standartlarını karşılamıyor.',
                'priority' => 'high',
            ];
        }
        if ($descriptionScore < 0.6) {
            $recommendations[] = [
                'type' => 'description_quality',
                'message' => 'Başlık/açıklama kalitesi düşük — SEO performansı etkilenir.',
                'priority' => 'high',
            ];
        }
        if ($overallScore >= 0.70) {
            $recommendations[] = [
                'type' => 'publish_ready',
                'message' => 'Gayrimenkul yayına hazır — doğrudan yayınlanabilir.',
                'priority' => 'info',
            ];
        }

        return [
            'overall_score' => $overallScore,
            'component_scores' => [
                'photo_quality' => $photoScore,
                'description_quality' => $descriptionScore,
            ],
            'quality_tier' => $qualityTier,
            'market_positioning' => $marketPositioning,
            'recommendations' => $recommendations,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function recordExecution(
        ?int $ilanId,
        ?int $tenantId,
        ?string $chainId,
        string $eventName,
        array $inputPayload,
    ): WorkforceExecutionLog {
        return WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $chainId,
            'agent_name' => 'property_score_agent',
            'agent_class' => self::class,
            'event_received' => $eventName,
            'event_chain_step' => 3,
            'input_payload' => $inputPayload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    private function loadWorkspace(?int $workspaceId, ?int $ilanId): ?PortfolioDriveWorkspace
    {
        if ($workspaceId) {
            return PortfolioDriveWorkspace::find($workspaceId);
        }
        if ($ilanId) {
            return PortfolioDriveWorkspace::forPortfolio($ilanId)->orderBy('id')->first();
        }

        return null;
    }

    private function emitPropertyScoreCalculated(
        PortfolioDriveWorkspace $workspace,
        array $scoreResult,
        array $metadata,
    ): void {
        $event = new PropertyScoreCalculated($workspace, $scoreResult, $metadata);
        $this->hermesService->receive($event);
    }

    private function elapsed(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 2);
    }

    // ─── Buffer Cache Management (H-05) ─────────────────────────────────

    /**
     * Retrieve pending buffer from memory, cache, or workspace completion flags.
     *
     * @return array<string, array>
     */
    private function getPendingBuffer(PortfolioDriveWorkspace $workspace): array
    {
        $ilanId = (int) $workspace->ilan_id;
        if (isset($this->pendingResults[$ilanId])) {
            return $this->pendingResults[$ilanId];
        }

        $cached = Cache::get("hermes:property_score:pending:{$ilanId}");
        if (is_array($cached)) {
            $this->pendingResults[$ilanId] = $cached;

            return $cached;
        }

        $buffer = [];
        $flags = $workspace->ai_completion_flags ?? [];

        if (! empty($flags['photo_agent']['result'])) {
            $buffer['photo'] = array_merge($flags['photo_agent']['result'], [
                'chain_id' => $flags['photo_agent']['result']['chain_id'] ?? null,
            ]);
        }

        if (! empty($flags['description_agent']['result'])) {
            $buffer['description'] = array_merge($flags['description_agent']['result'], [
                'chain_id' => $flags['description_agent']['result']['chain_id'] ?? null,
            ]);
        }

        $this->pendingResults[$ilanId] = $buffer;

        return $buffer;
    }

    /**
     * Persist buffer to memory and Cache with 24-hour TTL.
     *
     * @param  array<string, array>  $buffer
     */
    private function savePendingBuffer(int $ilanId, array $buffer): void
    {
        $this->pendingResults[$ilanId] = $buffer;
        Cache::put("hermes:property_score:pending:{$ilanId}", $buffer, now()->addDay());
    }

    /**
     * Clear buffer from memory and Cache after score calculation.
     */
    private function clearPendingBuffer(int $ilanId): void
    {
        unset($this->pendingResults[$ilanId]);
        Cache::forget("hermes:property_score:pending:{$ilanId}");
    }
}
