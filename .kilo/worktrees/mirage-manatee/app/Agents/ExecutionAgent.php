<?php

namespace App\Agents;

use App\Events\Governance\ActionApplied;
use App\Events\Governance\ActionFailed;
use App\Services\Intelligence\AutonomyService;
use App\Services\Intelligence\SabDecisionBridgeService;

/**
 * ExecutionAgent — Action Layer
 *
 * Wraps SabDecisionBridgeService. Takes pre-classified findings
 * and creates proposals / queues / blocks accordingly.
 * Emits ACTION_APPLIED or ACTION_FAILED events.
 *
 * SAB6: Integrates AutonomyService for action budget tracking
 * and dry-run simulation before real application.
 */
class ExecutionAgent extends BaseAgent
{
    public function __construct(
        private readonly SabDecisionBridgeService $bridge,
        private readonly AutonomyService $autonomyService,
    ) {}

    public function name(): string
    {
        return 'execution';
    }

    protected function execute(array $context): array
    {
        $classified = $context['classified'] ?? [];

        if (empty($classified)) {
            return [
                'success' => true,
                'summary' => ['auto_run' => 0, 'queued' => 0, 'blocked' => 0, 'suppressed' => 0, 'failed' => 0, 'dry_run' => 0],
            ];
        }

        // SAB6: Check system pause before execution
        if ($this->autonomyService->isSystemPaused()) {
            // Move all auto_run to needs_review when paused
            $classified['needs_review'] = array_merge(
                $classified['needs_review'] ?? [],
                $classified['auto_run'] ?? []
            );
            $classified['auto_run'] = [];
        }

        // SAB6: Dry-run mode — simulate auto_run but don't apply
        $dryRunCount = 0;
        if (config('governance.dry_run', false) && !empty($classified['auto_run'])) {
            foreach ($classified['auto_run'] as $finding) {
                $this->autonomyService->logDryRun(
                    $finding->finding_id,
                    $finding->title,
                    $finding->recommended_action,
                    [
                        'severity' => $finding->severity->value,
                        'domain' => $finding->domain,
                        'target' => $finding->target,
                        'confidence' => $finding->confidence,
                    ]
                );
                $dryRunCount++;
            }

            // In dry-run, move auto_run to needs_review instead of applying
            $classified['needs_review'] = array_merge(
                $classified['needs_review'] ?? [],
                $classified['auto_run']
            );
            $classified['auto_run'] = [];
        }

        $result = $this->bridge->executeClassified($classified);

        // SAB6: Track each auto-run action in the budget
        foreach ($result['auto_run'] ?? [] as $filename) {
            $this->autonomyService->recordAction();

            ActionApplied::dispatch(
                $filename,
                $filename,
                $this->currentRun?->id
            );
        }

        // Emit failed events
        if (($result['failed'] ?? 0) > 0) {
            ActionFailed::dispatch(
                'batch_' . ($this->currentRun?->id ?? 'unknown'),
                "{$result['failed']} proposals failed during execution",
                $this->currentRun?->id
            );
        }

        return [
            'success' => true,
            'findings_count' => count($result['auto_run'] ?? []) + ($result['queued'] ?? 0),
            'decisions_count' => ($result['queued'] ?? 0),
            'summary' => [
                'auto_run' => count($result['auto_run'] ?? []),
                'queued' => $result['queued'] ?? 0,
                'blocked' => $result['blocked'] ?? 0,
                'suppressed' => $result['suppressed'] ?? 0,
                'failed' => $result['failed'] ?? 0,
                'dry_run' => $dryRunCount,
            ],
        ];
    }
}
