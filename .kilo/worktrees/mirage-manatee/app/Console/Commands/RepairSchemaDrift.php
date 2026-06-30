<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Schema Drift Onarım Komutu
 *
 * Fiziksel olarak var olan ancak migrations tablosunda kayıtlı olmayan
 * tabloları tespit edip migration kayıtlarını senkronize eder.
 *
 * SAB §12.3: Database Schema Integrity
 */
class RepairSchemaDrift extends Command
{
    protected $signature = 'system:repair-schema-drift';
    protected $description = 'Repair schema drift by registering physically existing tables in migrations table';

    public function handle(): int
    {
        $this->info('🔍 Scanning for schema drift...');

        $driftTables = [
            'owner_report_rows' => '2026_05_16_100001_create_owner_report_rows_table',
            'owner_report_metrics' => '2026_05_16_100002_create_owner_report_metrics_table',
            'owner_report_exports' => '2026_05_16_100003_create_owner_report_exports_table',
        ];

        $batch = 48;
        $repaired = 0;

        foreach ($driftTables as $table => $migrationName) {
            // Tablo fiziksel olarak var mı?
            $exists = DB::select("SHOW TABLES LIKE '{$table}'");

            if (empty($exists)) {
                $this->warn("  ✗ Table '{$table}' does NOT exist physically - skipping");
                continue;
            }

            // Migration kaydı var mı?
            $registered = DB::table('migrations')
                ->where('migration', $migrationName)
                ->exists();

            if ($registered) {
                $this->line("  - Table '{$table}' already registered");
                continue;
            }

            // Migration kaydını ekle
            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $batch
            ]);

            $this->info("  ✓ Registered: {$migrationName}");
            $repaired++;
        }

        if ($repaired > 0) {
            $this->newLine();
            $this->info("✅ Schema drift repaired: {$repaired} table(s) registered");
            return self::SUCCESS;
        }

        $this->info('✅ No schema drift detected');
        return self::SUCCESS;
    }
}
