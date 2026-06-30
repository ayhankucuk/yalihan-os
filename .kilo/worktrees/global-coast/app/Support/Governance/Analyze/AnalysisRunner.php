<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze;

use App\Support\Governance\Analyze\Contracts\Detector;

/**
 * Orchestrates the detectors and produces a normalized result.
 *
 * Pure composition — no I/O of its own beyond asking detectors to run.
 */
final class AnalysisRunner
{
    /** @param list<Detector> $detectors */
    public function __construct(private readonly array $detectors)
    {
    }

    public function run(AnalysisContext $context): AnalysisResult
    {
        $findings = [];
        foreach ($this->detectors as $detector) {
            if (! $context->detectorRequested($detector->slug())) {
                continue;
            }
            foreach ($detector->detect($context) as $f) {
                if ($context->minRisk !== null && $f->risk->rank() < $context->minRisk->rank()) {
                    continue;
                }
                $findings[] = $f;
            }
        }

        return new AnalysisResult(
            findings: $findings,
            repoState: [
                'repo_root' => $context->repoRoot,
                'detectors_requested' => $context->detectorsRequested,
                'include_env' => $context->includeEnv,
                'baseline' => $context->baseline,
            ],
            generatedAt: date('c'),
        );
    }
}
