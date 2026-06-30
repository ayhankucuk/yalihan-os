<?php

namespace App\Agents;

use App\Events\Governance\FindingDetected;
use App\Services\Intelligence\CortexFindingService;

/**
 * CortexAgent — Detection Layer
 *
 * Wraps CortexFindingService. Scans system for findings and emits
 * FINDING_DETECTED events for each discovery.
 */
class CortexAgent extends BaseAgent
{
    public function __construct(
        private readonly CortexFindingService $findingService,
    ) {}

    public function name(): string
    {
        return 'cortex';
    }

    protected function execute(array $context): array
    {
        $source = $context['source'] ?? null;

        $findings = $source
            ? $this->findingService->collectFrom($source)
            : $this->findingService->collectAll();

        // Emit event for each finding
        foreach ($findings as $finding) {
            FindingDetected::dispatch($finding, $this->currentRun?->id);
        }

        $severityCounts = [];
        foreach ($findings as $f) {
            $sev = $f->severity->value;
            $severityCounts[$sev] = ($severityCounts[$sev] ?? 0) + 1;
        }

        return [
            'success' => true,
            'findings' => $findings,
            'findings_count' => count($findings),
            'summary' => [
                'total' => count($findings),
                'by_severity' => $severityCounts,
                'sources' => array_count_values(array_map(fn ($f) => $f->source, $findings)),
            ],
        ];
    }
}
