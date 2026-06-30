#!/bin/bash
# Phase 12 Final Seal Script - Simplified (No Artisan Commands)
# SAB §12: Production Deployment Seal

set -e

echo "🔧 Phase 12 Final Seal - Production Deployment"
echo "=============================================="
echo ""

# 1. Schema Drift Onarımı (Direct SQL)
echo "📋 Step 1: Repairing schema drift..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$driftTables = [
    'owner_report_rows' => '2026_05_16_100001_create_owner_report_rows_table',
    'owner_report_metrics' => '2026_05_16_100002_create_owner_report_metrics_table',
    'owner_report_exports' => '2026_05_16_100003_create_owner_report_exports_table',
];

\$batch = 48;
\$repaired = 0;

foreach (\$driftTables as \$table => \$migrationName) {
    \$exists = DB::select(\"SHOW TABLES LIKE '\$table'\");
    if (empty(\$exists)) continue;

    \$registered = DB::table('migrations')->where('migration', \$migrationName)->exists();
    if (\$registered) continue;

    DB::table('migrations')->insert(['migration' => \$migrationName, 'batch' => \$batch]);
    echo \"✓ Registered: \$migrationName\n\";
    \$repaired++;
}

echo \"\n✅ Schema drift repaired: \$repaired table(s)\n\";
"

echo ""

# 2. Kalan Migration'ları Tamamla
echo "📋 Step 2: Completing remaining migrations..."
php artisan migrate --force

echo ""

# 3. ai_credit_balances Tablosu Doğrulama
echo "📋 Step 3: Verifying ai_credit_balances table..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$exists = DB::select(\"SHOW TABLES LIKE 'ai_credit_balances'\");
if (!empty(\$exists)) {
    echo \"✅ Table 'ai_credit_balances' exists\n\";
    \$columns = DB::select('DESCRIBE ai_credit_balances');
    echo \"   Columns: \" . count(\$columns) . \"\n\";
} else {
    echo \"❌ CRITICAL: Table 'ai_credit_balances' NOT FOUND\n\";
    exit(1);
}
"

echo ""

# 4. Tenant Kredilerini Transaksiyonel Olarak Bas
echo "📋 Step 4: Seeding tenant credits (transactional)..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

DB::transaction(function() {
    \$count = 0;
    App\Models\SaaS\Tenant::orderBy('id')->each(function(\$tenant) use (&\$count) {
        \$balance = App\Models\AI\AiCreditBalance::firstOrCreate(
            ['tenant_id' => \$tenant->id],
            [
                'available_credits' => 1000,
                'used_credits' => 0,
                'monthly_limit' => 5000,
                'last_reset_at' => now()
            ]
        );
        \$count++;
        echo \"✓ Tenant #{\$tenant->id}: {\$balance->available_credits} credits\n\";
    });
    echo \"\\n✅ Total tenants seeded: \$count\\n\";
});
"

echo ""
echo "🏆 =============================================="
echo "🏆 PHASE 12 SEALED: PRODUCTION ACTIVE"
echo "🏆 =============================================="
