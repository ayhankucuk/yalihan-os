<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Contracts\Reporter;
use App\Support\Governance\Analyze\Finding;

final class TableReporter implements Reporter
{
    public function render(AnalysisResult $result): string
    {
        $out = [];
        $counts = $result->countsByRisk();

        $out[] = 'Governance Analyze Report';
        $out[] = 'Generated: ' . $result->generatedAt;
        $out[] = sprintf(
            'Findings: total=%d  high=%d  medium=%d  low=%d  env_blockers=%d',
            count($result->findings),
            $counts['high'],
            $counts['medium'],
            $counts['low'],
            $result->envBlockerCount(),
        );
        $out[] = str_repeat('-', 80);

        if ($result->findings === []) {
            $out[] = '(no findings)';

            return implode("\n", $out) . "\n";
        }

        foreach ($result->rankedFindings() as $i => $f) {
            $out[] = sprintf(
                '[%s] %s  (%s / %s confidence)  — %s',
                strtoupper($f->risk->value),
                $f->id,
                $f->tur->value,
                $f->confidence->value,
                $f->title,
            );
            $out[] = '  summary: ' . $f->summary;
            $out = array_merge($out, $this->renderEvidenceLines($f->evidence));
            $out[] = '  safe_action: ' . $f->safeAction;
            $out[] = '  detector: ' . $f->detector;
            $out[] = '';
        }

        return implode("\n", $out) . "\n";
    }

    /**
     * @param list<\App\Support\Governance\Analyze\Evidence> $evidence
     * @return list<string>
     */
    private function renderEvidenceLines(array $evidence): array
    {
        return array_map(
            static fn ($e) => sprintf(
                '  evidence: %s%s',
                $e->file,
                $e->line !== null ? ':' . $e->line : '',
            ),
            $evidence,
        );
    }
}
