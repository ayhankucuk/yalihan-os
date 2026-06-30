<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze;

use App\Support\Governance\Analyze\Enums\RiskLevel;

/**
 * Run-scoped inputs passed to every detector. Read-only.
 */
final class AnalysisContext
{
    /**
     * @param list<string>   $detectorsRequested  empty = all
     * @param RiskLevel|null $minRisk             filter: only findings at this level or higher
     */
    public function __construct(
        public readonly string $repoRoot,
        public readonly array $detectorsRequested = [],
        public readonly ?RiskLevel $minRisk = null,
        public readonly bool $includeEnv = false,
        public readonly bool $baseline = false,
    ) {
    }

    public function detectorRequested(string $slug): bool
    {
        return $this->detectorsRequested === []
            || in_array($slug, $this->detectorsRequested, true);
    }
}
