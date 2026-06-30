<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Contracts;

use App\Support\Governance\Analyze\AnalysisResult;

interface Reporter
{
    /** Render the result to a string representation. */
    public function render(AnalysisResult $result): string;
}
