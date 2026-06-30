<?php

declare(strict_types=1);

namespace App\Console\Commands\Governance;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\AnalysisRunner;
use App\Support\Governance\Analyze\Contracts\Detector;
use App\Support\Governance\Analyze\Detectors\Context7ForbiddenFieldDetector;
use App\Support\Governance\Analyze\Detectors\DeprecatedSurfaceDetector;
use App\Support\Governance\Analyze\Detectors\EnvironmentBlockerDetector;
use App\Support\Governance\Analyze\Detectors\OrphanReferenceDetector;
use App\Support\Governance\Analyze\Detectors\RouteAuthorityDetector;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use App\Support\Governance\Analyze\Reporters\JsonReporter;
use App\Support\Governance\Analyze\Reporters\TableReporter;
use Illuminate\Console\Command;

/**
 * Read-only governance analyzer. Orchestrates registered detectors and
 * produces a normalized advisory report.
 *
 * Guarantees (ADR H7):
 *  - Does not modify files
 *  - Does not run migrations, installs, or git mutations
 *  - Exit code 0 on completed analysis regardless of finding count
 *  - Exit code 1 only on internal analyzer failure
 */
class GovernanceAnalyzeCommand extends Command
{
    protected $signature = 'governance:analyze
        {--format=table : Output format: table, json}
        {--only= : Comma-separated detector slugs: routes,context7}
        {--risk= : Minimum risk: high, medium, low}
        {--include-env : Enable environment probe detectors (v1: reserved)}
        {--baseline : Mark known-baseline findings separately (v1: reserved)}
        {--output= : Optional file path to write the rendered report to}';

    protected $description = 'Run read-only governance analysis and report findings (analysis-only, no autofix).';

    /** @param list<Detector>|null $detectorsOverride for tests */
    public function handle(?array $detectorsOverride = null): int
    {
        try {
            $detectors = $detectorsOverride ?? $this->defaultDetectors();

            $only = $this->parseCsvOption((string) ($this->option('only') ?? ''));
            $risk = $this->parseRiskOption((string) ($this->option('risk') ?? ''));

            $context = new AnalysisContext(
                repoRoot: base_path(),
                detectorsRequested: $only,
                minRisk: $risk,
                includeEnv: (bool) $this->option('include-env'),
                baseline: (bool) $this->option('baseline'),
            );

            $runner = new AnalysisRunner($detectors);
            $result = $runner->run($context);

            $reporter = match ($this->option('format')) {
                'json' => new JsonReporter(),
                default => new TableReporter(),
            };

            $rendered = $reporter->render($result);

            $outputPath = $this->option('output');
            if (is_string($outputPath) && $outputPath !== '') {
                $this->ensureDir(dirname($outputPath));
                file_put_contents($outputPath, $rendered);
                $this->info('Report written to: ' . $outputPath);
            } else {
                $this->output->write($rendered);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('governance:analyze internal failure: ' . $e->getMessage());
            $this->line('trace: ' . $e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /** @return list<Detector> */
    private function defaultDetectors(): array
    {
        return [
            new RouteAuthorityDetector(),
            new Context7ForbiddenFieldDetector(),
            new DeprecatedSurfaceDetector(),
            new OrphanReferenceDetector(),
            new EnvironmentBlockerDetector(),
        ];
    }

    /** @return list<string> */
    private function parseCsvOption(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function parseRiskOption(string $raw): ?RiskLevel
    {
        if ($raw === '') {
            return null;
        }

        return RiskLevel::tryFrom(strtolower($raw));
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}
