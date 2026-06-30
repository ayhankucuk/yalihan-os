<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze;

use App\Support\Governance\Analyze\Enums\RiskLevel;

/**
 * Aggregate output of an analyze run.
 */
final class AnalysisResult
{
    /**
     * @param list<Finding>        $findings
     * @param array<string, mixed> $repoState
     */
    public function __construct(
        public readonly array $findings,
        public readonly array $repoState = [],
        public readonly string $generatedAt = '',
    ) {
    }

    /** @return array<string, int> */
    public function countsByRisk(): array
    {
        $counts = ['high' => 0, 'medium' => 0, 'low' => 0, 'skip' => 0];
        foreach ($this->findings as $f) {
            $counts[$f->risk->value]++;
        }

        return $counts;
    }

    public function envBlockerCount(): int
    {
        $n = 0;
        foreach ($this->findings as $f) {
            if ($f->tur->value === 'environment_blocker') {
                $n++;
            }
        }

        return $n;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $counts = $this->countsByRisk();

        return [
            'tool' => 'governance:analyze',
            'version' => '1.0',
            'generated_at' => $this->generatedAt,
            'summary' => [
                'findings_total' => count($this->findings),
                'high' => $counts['high'],
                'medium' => $counts['medium'],
                'low' => $counts['low'],
                'env_blockers' => $this->envBlockerCount(),
            ],
            'repo_state' => $this->repoState,
            'findings' => array_map(static fn (Finding $f) => $f->toArray(), $this->findings),
        ];
    }

    public function rankedFindings(): array
    {
        $sorted = $this->findings;
        usort(
            $sorted,
            static fn (Finding $a, Finding $b) => $b->risk->rank() <=> $a->risk->rank()
        );

        return $sorted;
    }
}
