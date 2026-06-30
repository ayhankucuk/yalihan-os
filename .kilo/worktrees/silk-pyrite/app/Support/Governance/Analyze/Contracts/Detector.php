<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Contracts;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Finding;

/**
 * Read-only detector. MUST NOT mutate files or run destructive commands.
 */
interface Detector
{
    /** Short unique slug: routes, context7, orphans, deprecated, env. */
    public function slug(): string;

    public function title(): string;

    /** @return list<Finding> */
    public function detect(AnalysisContext $context): array;
}
