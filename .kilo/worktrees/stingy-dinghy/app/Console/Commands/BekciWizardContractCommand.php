<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Yalıhan Bekçi: Wizard Contract Validator - SIMPLIFIED FINAL
 *
 * WFC-002: WARN mode (no violations)
 * WFC-013: Migration Rename Guard (simple str_contains)
 * WFC-014: Test DB Validation (NEW)
 */
class BekciWizardContractCommand extends Command
{
    protected $signature = 'bekci:wizard-contract';
    protected $description = '🛡️ Yalıhan Bekçi: Wizard Contract Validator';

    private array $violations = [];
    private array $report = [];

    public function handle(): int
    {
        $this->info('🛡️ Yalıhan Bekçi: Wizard Contract Validation Starting...');
        $this->newLine();

        // WFC-001: DB Schema Sync
        $this->checkDbSchemaSync();

        // WFC-002: Yayin Tipi Naming (WARN mode)
        $this->checkYayinTipiNaming();

        // WFC-013: Migration Rename Guard (P0)
        $this->checkMigrationRenameGuard();

        // WFC-014: Test DB Validation (P0)
        $this->checkTestDatabaseConnection();

        // WFC-015: Hallucination Protection (P1)
        $this->checkHallucinationProtection();

        // Generate report
        $this->generateIntegrityReport();

        // Summary
        $this->newLine();
        if (empty($this->violations)) {
            $this->info('✅ All contract checks passed!');
            return self::SUCCESS;
        } else {
            $this->error('❌ ' . count($this->violations) . ' violation(s) found!');
            foreach ($this->violations as $violation) {
                $this->warn("  • [{$violation['rule']}] {$violation['message']}");
            }
            return self::FAILURE;
        }
    }

    private function checkDbSchemaSync(): void
    {
        $this->info('[WFC-001] DB Schema Sync...');

        $tables = [
            'yayin_tipi_sablonlari',
            'yayin_tipleri',
            'ilan_kategorileri',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->addViolation('WFC-001', "Table '{$table}' missing!");
            }
        }

        // Check specific pivottable column
        if (Schema::hasTable('yayin_tipi_sablonlari')) {
            $columns = Schema::getColumnListing('yayin_tipi_sablonlari');

            if (in_array('kategori_id', $columns)) {
                $this->info("  ✅ kategori_id column exists");
            } else {
                $this->warn("  ⚠️  kategori_id column missing");
            }
        }
    }

    private function checkYayinTipiNaming(): void
    {
        $this->info('[WFC-002] Yayin Tipi Naming (P0)...');

        // This command currently checks documentation and existence of ID in DB
        // Validation of code parameters is handled by Context7 MCP
        // Here we just verify the ResolverTrait is available in the project
        $resolverTrait = app_path('Traits/YayinTipiResolverTrait.php');
        if (!File::exists($resolverTrait)) {
            $this->addViolation('WFC-002', "Resolver Trait missing: {$resolverTrait}");
        } else {
            $this->info('  ✅ YayinTipiResolverTrait exists');
        }
    }

    /**
     * WFC-015: Hallucination Protection Guard (P1)
     * Verifies that AI content generation has safety guards
     */
    private function checkHallucinationProtection(): void
    {
        $this->info('[WFC-015] Hallucination Protection Guard (P1)...');

        $aiService = app_path('Services/AI/SmartFieldGenerationService.php');
        if (File::exists($aiService)) {
            $content = File::get($aiService);
            if (str_contains($content, 'hallucination_protection') || str_contains($content, 'NoHallucinationGuard')) {
                $this->info('  ✅ AI Safety guards detected in SmartFieldGenerationService');
            } else {
                $this->warn('  ⚠️  AI Safety guards MISSING in SmartFieldGenerationService');
            }
        }
    }

    /**
     * WFC-013: Migration Rename Guard (P0)
     * Blocks NEW renameColumn usage, exempts legacy migrations
     */
    private function checkMigrationRenameGuard(): void
    {
        $this->info('[WFC-013] Migration Rename Guard (P0)...');

        // ✅ Legacy migrations (already executed in production)
        $legacyAllowed = [
            '2025_10_12_174311_rename_site_adi_to_name_in_site_apartmanlar_table.php',
            '2026_01_13_065607_rename_aktif_mi_to_aktiflik_durumu_in_features.php',
            '2026_01_13_070930_rename_status_to_aktiflik_durumu_in_ups_feature_packs.php',
            '2026_02_10_093000_rename_status_code_in_ai_logs_table.php',
        ];

        $migrationPath = base_path('database/migrations');
        if (!is_dir($migrationPath)) {
            return;
        }

        $files = glob("{$migrationPath}/*.php");
        $foundViolations = false;

        foreach ($files as $file) {
            $basename = basename($file);

            // Skip legacy migrations (historical, already executed)
            if (in_array($basename, $legacyAllowed, true)) {
                continue;
            }

            $content = file_get_contents($file);

            // Simple filename check
            if (str_contains($basename, 'rename_yayin_tipi')) {
                $this->addViolation('WFC-013', "BLOCKED: {$basename}");
                $foundViolations = true;
            }

            // Simple content check for NEW migrations
            if (str_contains($content, 'renameColumn')) {
                $this->addViolation('WFC-013', "BLOCKED renameColumn in {$basename}");
                $foundViolations = true;
            }
        }

        if (!$foundViolations) {
            $this->info('  ✅ No schema rename operations (legacy whitelist: 3)');
        }
    }

    /**
     * WFC-014: Test DB Connection Validation (P0)
     */
    private function checkTestDatabaseConnection(): void
    {
        $this->info('[WFC-014] Test DB Validation (P0)...');

        if (app()->environment('testing')) {
            $dbName = config('database.connections.mysql.database');

            if (!str_contains($dbName, '_test')) {
                $this->addViolation(
                    'WFC-014',
                    "P0: Test must use *_test DB. Current: {$dbName}"
                );
            } else {
                $this->info("  ✅ Test DB: {$dbName}");
            }
        } else {
            $this->info('  ⚠️  Skipped (not testing env)');
        }
    }

    private function generateIntegrityReport(): void
    {
        $this->info('[REPORT] Generating integrity report...');

        $this->report['timestamp'] = now()->toIso8601String();
        $this->report['violations'] = $this->violations;
        $this->report['total_violations'] = count($this->violations);
        $this->report['durum'] = empty($this->violations) ? 'PASS' : 'FAIL';

        $reportPath = storage_path('logs/wizard_integrity_report.json');
        File::put($reportPath, json_encode($this->report, JSON_PRETTY_PRINT));

        $this->info("  ✅ Report: {$reportPath}");
    }

    private function addViolation(string $rule, string $message): void
    {
        $this->violations[] = [
            'rule' => $rule,
            'message' => $message,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
