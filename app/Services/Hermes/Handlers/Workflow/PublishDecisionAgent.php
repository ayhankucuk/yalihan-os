<?php

namespace App\Services\Hermes\Handlers\Workflow;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Events\Workforce\PublishingDecisionReady;
use App\Events\Workforce\PropertyScoreCalculated;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Hermes\HermesService;
use Illuminate\Support\Facades\Log;

/**
 * PublishDecisionAgent — AI Workforce Sprint 4.5
 *
 * Subscribes to:
 * - workforce.property_score.calculated
 *
 * Emits:
 * - workforce.publishing.decision_ready (after making decision)
 *
 * Role: Makes an automated publishing decision based on property score.
 * Decision: approved | needs_review | rejected
 *
 * State: Advances workspace lifecycle to READY_FOR_PUBLISH on approval.
 */
class PublishDecisionAgent implements HermesHandlerContract
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
            'workforce.property_score.calculated',
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $startTime = microtime(true);

        if (!$event instanceof PropertyScoreCalculated) {
            return [
                'handler' => self::class,
                'error' => 'Invalid event type',
                'duration_ms' => $this->elapsed($startTime),
            ];
        }

        $payload = $event->toPayload();
        $ilanId = $payload['ilan_id'] ?? null;
        $tenantId = $event->tenantId();
        $workspace = $event->workspace;
        $scoreResult = $event->scoreResult;

        // Record execution
        $execLog = $this->recordExecution($ilanId, $tenantId, $payload);

        try {
            // Make publishing decision
            $decision = $this->makeDecision($workspace, $scoreResult);

            // Mark agent complete on workspace
            $workspace->markAiAgentComplete('publish_decision_agent', $decision);

            $execLog->markCompleted($decision);

            // Emit PublishingDecisionReady event
            $this->emitPublishingDecisionReady($workspace, $decision, [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace->getKey(),
                'ilan_baslik' => $workspace->root_folder_name,
                'tier' => $decision['quality_tier'],
            ]);

            Log::info('[PublishDecisionAgent] Publishing decision made', [
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace->getKey(),
                'decision' => $decision['decision'],
                'confidence' => $decision['confidence'],
            ]);

            return [
                'handler' => self::class,
                'ilan_id' => $ilanId,
                'workspace_id' => $workspace->getKey(),
                'decision' => $decision['decision'],
                'confidence' => $decision['confidence'],
                'property_score' => $decision['property_score'],
                'publish_targets' => $decision['publish_targets'],
                'blocking_issues' => $decision['blocking_issues'],
                'duration_ms' => $this->elapsed($startTime),
            ];
        } catch (\Throwable $e) {
            $execLog->markFailed($e->getMessage());
            Log::error('[PublishDecisionAgent] Failed', ['ilan_id' => $ilanId, 'error' => $e->getMessage()]);
            return ['handler' => self::class, 'ilan_id' => $ilanId, 'error' => $e->getMessage(), 'duration_ms' => $this->elapsed($startTime)];
        }
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return false;
    }

    // ─── Decision Logic ─────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function makeDecision(PortfolioDriveWorkspace $workspace, array $scoreResult): array
    {
        $overallScore = $scoreResult['overall_score'] ?? 0;
        $photoScore = $scoreResult['component_scores']['photo_quality'] ?? 0;
        $descriptionScore = $scoreResult['component_scores']['description_quality'] ?? 0;
        $qualityTier = $scoreResult['quality_tier'] ?? 'standard';

        $blockingIssues = [];
        $publishTargets = [];

        // Check blocking issues
        if ($photoScore < 0.3) {
            $blockingIssues[] = [
                'type' => 'photo_critical',
                'message' => 'Fotoğraf kalitesi çok düşük — minimum standartların altında.',
                'severity' => 'critical',
            ];
        }
        if ($descriptionScore < 0.3) {
            $blockingIssues[] = [
                'type' => 'description_critical',
                'message' => 'Açıklama kalitesi çok düşük — içerik yeniden üretilmeli.',
                'severity' => 'critical',
            ];
        }

        // Determine publish targets based on quality tier
        $publishTargets = match ($qualityTier) {
            'premium_plus' => ['airbnb', 'sahibinden', 'hepsiemlak', 'custom'],
            'premium' => ['airbnb', 'sahibinden', 'hepsiemlak'],
            'standard' => ['sahibinden', 'hepsiemlak'],
            default => ['hepsiemlak'],
        };

        // Determine decision
        $decision = match (true) {
            // Critical blocking issues → reject
            count(array_filter($blockingIssues, fn ($i) => ($i['severity'] ?? '') === 'critical')) > 0
                => 'rejected',

            // High score, no issues → approved
            $overallScore >= 0.75 && empty($blockingIssues)
                => 'approved',

            // Medium score → needs review
            $overallScore >= 0.50
                => 'needs_review',

            // Low score → needs review (manual intervention needed)
            default => 'needs_review',
        };

        // Calculate confidence
        $confidence = $this->calculateConfidence($overallScore, $blockingIssues);

        // Generate message
        $message = match ($decision) {
            'approved' => 'Gayrimenkul yayınlama için onaylandı. Otomatik yayınlama başlatılabilir.',
            'needs_review' => 'Gayrimenkul manuel değerlendirme gerektiriyor. Kalite: ' . ucfirst($qualityTier) . '.',
            'rejected' => 'Gayrimenkul yayınlama için uygun değil. Kritik kalite sorunları var.',
        };

        return [
            'decision' => $decision,
            'property_score' => $overallScore,
            'quality_tier' => $qualityTier,
            'confidence' => $confidence,
            'publish_targets' => $publishTargets,
            'blocking_issues' => $blockingIssues,
            'message' => $message,
            'decided_at' => now()->toIso8601String(),
        ];
    }

    private function calculateConfidence(float $score, array $blockingIssues): float
    {
        $base = 0.5;
        $base += ($score * 0.35); // Score contribution

        if (empty($blockingIssues)) {
            $base += 0.15; // Bonus for no issues
        }

        return round(min($base, 1.0), 2);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function recordExecution(?int $ilanId, ?int $tenantId, array $inputPayload): WorkforceExecutionLog
    {
        return WorkforceExecutionLog::create([
            'ilan_id' => $ilanId,
            'tenant_id' => $tenantId,
            'chain_id' => $inputPayload['chain_id'] ?? null,
            'agent_name' => 'publish_decision_agent',
            'agent_class' => self::class,
            'event_received' => 'workforce.property_score.calculated',
            'event_chain_step' => 4,
            'input_payload' => $inputPayload,
            'output_payload' => [],
            'status' => WorkforceExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    private function emitPublishingDecisionReady(
        PortfolioDriveWorkspace $workspace,
        array $decision,
        array $metadata,
    ): void {
        $event = new PublishingDecisionReady($workspace, $decision, $metadata);
        $this->hermesService->receive($event);
    }

    private function elapsed(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 2);
    }
}
