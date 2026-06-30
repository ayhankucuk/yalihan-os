<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Reporters;

use App\Support\Governance\Analyze\AnalysisResult;
use App\Support\Governance\Analyze\Contracts\Reporter;
use App\Support\Governance\Analyze\Finding;

/**
 * Markdown Reporter for Governance Analysis
 *
 * Produces human-readable, machine-diff-friendly markdown output.
 * Designed for baseline comparison and documentation.
 *
 * @package App\Support\Governance\Analyze\Reporters
 */
final class MarkdownReporter implements Reporter
{
    public function render(AnalysisResult $result): string
    {
        $output = [];

        // Header
        $output[] = '# Governance Analysis Report';
        $output[] = '';
        $output[] = sprintf('**Generated:** %s', date('Y-m-d H:i:s T'));
        $output[] = sprintf('**Total Findings:** %d', count($result->findings));
        $output[] = '';

        // Summary by risk level
        $output[] = '## Summary';
        $output[] = '';
        $output[] = $this->buildSummaryTable($result);
        $output[] = '';

        // Findings by detector
        if (count($result->findings) > 0) {
            $output[] = '## Findings';
            $output[] = '';

            $groupedFindings = $this->groupFindingsByDetector($result);

            foreach ($groupedFindings as $detector => $findings) {
                $output[] = sprintf('### %s', $this->formatDetectorName($detector));
                $output[] = '';

                foreach ($findings as $finding) {
                    $output[] = $this->formatFinding($finding);
                }

                $output[] = '';
            }
        } else {
            $output[] = '## Findings';
            $output[] = '';
            $output[] = '✅ No issues detected.';
            $output[] = '';
        }

        return implode("\n", $output);
    }

    /**
     * Build summary table with risk level counts
     */
    private function buildSummaryTable(AnalysisResult $result): string
    {
        $counts = $result->countsByRisk();

        $table = [];
        $table[] = '| Risk Level | Count |';
        $table[] = '|------------|-------|';

        foreach (['high', 'medium', 'low'] as $level) {
            $count = $counts[$level] ?? 0;
            $icon = $this->getRiskIcon($level);
            $table[] = sprintf('| %s %s | %d |', $icon, strtoupper($level), $count);
        }

        return implode("\n", $table);
    }

    /**
     * Group findings by detector class
     *
     * @return array<string, Finding[]>
     */
    private function groupFindingsByDetector(AnalysisResult $result): array
    {
        $grouped = [];

        foreach ($result->findings as $finding) {
            $detector = $finding->detector;
            $grouped[$detector][] = $finding;
        }

        // Sort by detector name for deterministic output
        ksort($grouped);

        return $grouped;
    }

    /**
     * Format detector class name for display
     */
    private function formatDetectorName(string $detectorClass): string
    {
        // Extract class name from FQN
        $parts = explode('\\', $detectorClass);
        $className = end($parts);

        // Convert CamelCase to Title Case with spaces
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
    }

    /**
     * Format a single finding
     */
    private function formatFinding(Finding $finding): string
    {
        $lines = [];

        $icon = $this->getRiskIcon($finding->risk->value);
        $lines[] = sprintf('#### %s %s', $icon, $finding->title);
        $lines[] = '';
        $lines[] = sprintf('- **Risk:** %s', strtoupper($finding->risk->value));
        $lines[] = sprintf('- **Type:** %s', $finding->tur->value);
        $lines[] = sprintf('- **Layer:** %s', $finding->layer);

        if (count($finding->evidence) > 0) {
            $firstEvidence = $finding->evidence[0];
            $location = $firstEvidence->file;
            if ($firstEvidence->line !== null) {
                $location .= ':' . $firstEvidence->line;
            }
            $lines[] = sprintf('- **Location:** `%s`', $location);

            if ($firstEvidence->snippet) {
                $lines[] = '';
                $lines[] = '```php';
                $lines[] = $firstEvidence->snippet;
                $lines[] = '```';
            }
        }

        $lines[] = '';
        $lines[] = $finding->summary;
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Get icon for risk level
     */
    private function getRiskIcon(string $riskLevel): string
    {
        return match ($riskLevel) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            'skip' => 'ℹ️',
            default => '⚪',
        };
    }
}
