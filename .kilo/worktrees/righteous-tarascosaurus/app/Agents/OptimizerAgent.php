<?php

namespace App\Agents;

use App\Models\AgentMemory;
use App\Services\Intelligence\OptimizerService;

/**
 * OptimizerAgent — Learning & Improvement Layer
 *
 * Analyzes historical decision data to generate improvement suggestions:
 * - Repeated suppressions → rule sensitivity adjustment
 * - Frequent rollbacks → threshold calibration
 * - Always-approved findings → automation upgrade
 * - Pattern recognition across decision history
 *
 * CRITICAL RULE: Optimizer cannot self-apply changes.
 * All suggestions go through SAB approval pipeline.
 */
class OptimizerAgent extends BaseAgent
{
    public function __construct(
        private readonly OptimizerService $optimizerService,
    ) {}

    public function name(): string
    {
        return 'optimizer';
    }

    protected function execute(array $context): array
    {
        $lookbackDays = $context['lookback_days'] ?? 30;

        $suggestions = $this->optimizerService->analyze($lookbackDays);

        // Store analysis timestamp in agent memory
        AgentMemory::remember($this->name(), 'last_analysis', 'metric', [
            'analyzed_at' => now()->toIso8601String(),
            'lookback_days' => $lookbackDays,
            'suggestions_generated' => count($suggestions),
        ]);

        return [
            'success' => true,
            'suggestions' => $suggestions,
            'findings_count' => count($suggestions),
            'summary' => [
                'total_suggestions' => count($suggestions),
                'by_type' => array_count_values(array_column($suggestions, 'type')),
                'avg_confidence' => count($suggestions) > 0
                    ? round(array_sum(array_column($suggestions, 'confidence')) / count($suggestions), 2)
                    : 0,
            ],
        ];
    }
}
