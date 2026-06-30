<?php

namespace App\Agents;

use App\Events\Governance\DecisionMade;
use App\Events\Governance\FindingSuppressed;
use App\Services\Intelligence\GuardPolicyService;

/**
 * GovernanceAgent — Decision Layer
 *
 * Wraps GuardPolicyService. Classifies findings into risk buckets
 * (auto_run / needs_review / blocked) with suppression filtering.
 * Does NOT execute — that's ExecutionAgent's job.
 */
class GovernanceAgent extends BaseAgent
{
    public function __construct(
        private readonly GuardPolicyService $policyService,
    ) {}

    public function name(): string
    {
        return 'governance';
    }

    protected function execute(array $context): array
    {
        $findings = $context['findings'] ?? [];

        if (empty($findings)) {
            return [
                'success' => true,
                'classified' => ['auto_run' => [], 'needs_review' => [], 'blocked' => [], 'suppressed' => []],
                'decisions_count' => 0,
                'summary' => ['auto_run' => 0, 'needs_review' => 0, 'blocked' => 0, 'suppressed' => 0],
            ];
        }

        $classified = $this->policyService->classifyBatch($findings);

        // Emit suppression events
        foreach ($classified['suppressed'] ?? [] as $item) {
            $finding = is_array($item) ? ($item['finding'] ?? $item) : $item;
            FindingSuppressed::dispatch($finding, null, $this->currentRun?->id);
        }

        // Emit decision events for each classified finding
        foreach (['auto_run', 'needs_review', 'blocked'] as $bucket) {
            foreach ($classified[$bucket] ?? [] as $finding) {
                DecisionMade::dispatch(
                    $finding,
                    $bucket,
                    $finding->confidence ?? null,
                    $this->currentRun?->id
                );
            }
        }

        $totalDecisions = count($classified['auto_run'] ?? [])
            + count($classified['needs_review'] ?? [])
            + count($classified['blocked'] ?? []);

        return [
            'success' => true,
            'classified' => $classified,
            'decisions_count' => $totalDecisions,
            'summary' => [
                'auto_run' => count($classified['auto_run'] ?? []),
                'needs_review' => count($classified['needs_review'] ?? []),
                'blocked' => count($classified['blocked'] ?? []),
                'suppressed' => count($classified['suppressed'] ?? []),
            ],
        ];
    }
}
