<?php

namespace App\Console\Commands\Sab;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\Governance\DeprecationValidator\ArchiveMetadataReader;
use App\Services\Governance\DeprecationValidator\SectionInventoryBuilder;
use App\Services\Governance\DeprecationValidator\TargetMappingValidator;
use App\Services\Governance\DeprecationValidator\ValidationReportBuilder;
use App\Services\Governance\DeprecationValidator\ValidationReportFormatter;

/**
 * 🛡️ SAB Deprecation Coverage Validation Command
 *
 * Validates that legacy file split/merge/archive migrations were executed correctly.
 * Checks section coverage, archive metadata, target role correctness, and AI context isolation.
 *
 * Exit codes:
 *   0 = PASS (all validations passed)
 *   1 = FAIL or PARTIAL (issues detected)
 *   2 = System error (file not found, parse error, etc.)
 */
class SabDeprecationValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:deprecation:validate
                            {archive : Path to the archived legacy file (relative to project root)}
                            {--report : Generate a markdown report file in docs/reports/}
                            {--json : Output as JSON (for CI pipeline consumption)}
                            {--strict : Zero tolerance mode — any PARTIAL results in FAIL}
                            {--mapping= : Custom mapping file path (default: <archive>.mapping.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ Validate deprecation coverage for a legacy file split/merge/archive migration';

    /**
     * Execute the console command.
     */
    public function handle(
        ArchiveMetadataReader $metadataReader,
        SectionInventoryBuilder $inventoryBuilder,
        TargetMappingValidator $mappingValidator,
        ValidationReportBuilder $reportBuilder
    ): int {
        $archivePath = $this->argument('archive');
        $isJson = $this->option('json');
        $isReport = $this->option('report');
        $isStrict = $this->option('strict');

        $formatter = new ValidationReportFormatter($this);

        try {
            // Step 1: Resolve mapping file path
            $mappingPath = $this->option('mapping')
                ?? $this->resolveMappingPath($archivePath);

            if (!$isJson) {
                $this->info("🛡️  SAB Deprecation Validator");
                $this->line("  Archive: {$archivePath}");
                $this->line("  Mapping: {$mappingPath}");
                $this->newLine();
            }

            // Step 2: Read and validate archive metadata
            if (!$isJson) {
                $this->line('  [1/4] Reading archive metadata...');
            }
            $archiveMetadataResult = $metadataReader->read($archivePath);

            // Show metadata warnings
            if (!$isJson && !empty($archiveMetadataResult['warnings'])) {
                foreach ($archiveMetadataResult['warnings'] as $warning) {
                    $this->warn("    ⚠ {$warning}");
                }
            }

            // Step 3: Build section inventory from archive
            if (!$isJson) {
                $this->line('  [2/4] Building section inventory...');
            }
            $sectionInventory = $inventoryBuilder->build($archivePath);

            if (!$isJson) {
                $this->line("    Found " . count($sectionInventory) . " sections");
            }

            // Step 4: Load mapping and validate targets
            if (!$isJson) {
                $this->line('  [3/4] Validating section-to-target mappings...');
            }
            $mapping = $mappingValidator->loadMapping($mappingPath);
            $mappingValidation = $mappingValidator->validate($mapping);

            // Step 5: Build final report
            if (!$isJson) {
                $this->line('  [4/4] Building validation report...');
                $this->newLine();
            }
            $report = $reportBuilder->build(
                $archiveMetadataResult,
                $sectionInventory,
                $mappingValidation,
                $archivePath,
                $isStrict
            );

            // Output
            if ($isJson) {
                $formatter->renderJson($report);
            } else {
                $formatter->renderConsole($report);
            }

            // Generate markdown report file if requested
            if ($isReport) {
                $this->writeMarkdownReport($report, $formatter, $archivePath);
            }

            // Exit code based on decision
            $decision = $report['final_decision']['decision'];

            return match ($decision) {
                'PASS' => 0,
                'PARTIAL', 'FAIL' => 1,
                default => 2,
            };

        } catch (\Throwable $e) {
            if ($isJson) {
                $this->line(json_encode([
                    'error' => $e->getMessage(),
                    'exit_code' => 2,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("❌ Validation Error: " . $e->getMessage());
            }
            return 2;
        }
    }

    /**
     * Resolve the default mapping file path from the archive path.
     * Convention: archive.md → archive.mapping.json
     *
     * @param string $archivePath
     * @return string
     */
    private function resolveMappingPath(string $archivePath): string
    {
        // Remove .md extension and add .mapping.json
        $basePath = preg_replace('/\.md$/', '', $archivePath);
        return $basePath . '.mapping.json';
    }

    /**
     * Write a Markdown report file to docs/reports/.
     *
     * @param array $report
     * @param ValidationReportFormatter $formatter
     * @param string $archivePath
     * @return void
     */
    private function writeMarkdownReport(array $report, ValidationReportFormatter $formatter, string $archivePath): void
    {
        $markdown = $formatter->generateMarkdown($report);

        // Generate report filename from archive name
        $basename = pathinfo($archivePath, PATHINFO_FILENAME);
        $reportPath = base_path("docs/reports/deprecation-validation-{$basename}.md");

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, $markdown);

        $this->info("📄 Report written to: docs/reports/deprecation-validation-{$basename}.md");
    }
}
