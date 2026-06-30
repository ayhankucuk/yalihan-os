<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Contracts\Reporter;

final class JsonReporter implements Reporter
{
    public function render(AnalysisResult $result): string
    {
        return json_encode(
            $result->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) . "\n";
    }
}
