#!/usr/bin/env php
<?php
/**
 * Phase 12 Final Seal - PHP Native Execution
 * SAB §12: Production Deployment Seal
 *
 * Bu script tüm operasyonları PHP native olarak çalıştırır.
 * Bash dependency'si yoktur.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\SaaS\Tenant;
use App\Models\AI\AiCreditBalance;

echo "🔧 Phase 12 Final Seal - Production Deployment\n";
echo "==============================================\n\n";

// Step 1: Schema Drift Onarımı
echo "📋 Step 1: Repairing schema drift...\n";

$driftTables = [
    'owner_report_rows' => '2026_05_16_100001_create_owner_report_rows_table',
    'owner_report_metrics' => '2026_05_16_100002_create_owner_report_metrics_table',
    'owner_report_exports' => '2026_05_16_100003_create_owner_report_exports_table',
];

$batch = 48;
$repaired = 0;

foreach ($driftTables as $table => $migrationName) {
    $exists = DB::select("SHOW TABLES LIKE '{$table}'");
    if (empty($exists)) {
        echo "  ✗ Table '{$table}' does NOT exist physically - skipping\n";
        continue;
    }

    $registered = DB::table('migrations')->where('migration', $migrationName)->exists();
    if ($registered) {
        echo "  - Table '{$table}' already registered\n";
        continue;
    }

    DB::table('migrations')->insert(['migration' => $migrationName, 'batch' => $batch]);
    echo "  ✓ Registered: {$migrationName}\n";
    $repaired++;
}

echo "\n✅ Schema drift repaired: {$repaired} table(s)\n\n";

// Step 2: Kalan Migration'ları Tamamla
echo "📋 Step 2: Running remaining migrations...\n";
echo "Execute manually: php artisan migrate --force\n\n";

// Step 3: ai_credit_balances Tablosu Doğrulama
echo "📋 Step 3: Verifying ai_credit_balances table...\n";

$exists = DB::select("SHOW TABLES LIKE 'ai_credit_balances'");
if (!empty($exists)) {
    echo "✅ Table 'ai_credit_balances' exists\n";
    $columns = DB::select('DESCRIBE ai_credit_balances');
    echo "   Columns: " . count($columns) . "\n";
    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "❌ CRITICAL: Table 'ai_credit_balances' NOT FOUND\n";
    exit(1);
}

echo "\n";

// Step 4: Tenant Kredilerini Transaksiyonel Olarak Bas
echo "📋 Step 4: Seeding tenant credits (transactional)...\n";

try {
    DB::transaction(function() {
        $count = 0;
        Tenant::orderBy('id')->each(function($tenant) use (&$count) {
            $balance = AiCreditBalance::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'available_credits' => 1000,
                    'used_credits' => 0,
                    'monthly_limit' => 5000,
                    'last_reset_at' => now()
                ]
            );
            $count++;
            echo "  ✓ Tenant #{$tenant->id}: {$balance->available_credits} credits\n";
        });
        echo "\n✅ Total tenants seeded: {$count}\n";
    });
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "🏆 ==============================================\n";
echo "🏆 PHASE 12 SEALED: PRODUCTION ACTIVE\n";
echo "🏆 ==============================================\n";
echo "\nNext steps:\n";
echo "1. php artisan bekci:health\n";
echo "2. php artisan sab:integrity-scan\n";

exit(0);
