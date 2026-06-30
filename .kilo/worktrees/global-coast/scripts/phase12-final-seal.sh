#!/bin/bash
# Phase 12 Final Seal Script
# SAB §12: Production Deployment Seal

set -e

echo "🔧 Phase 12 Final Seal - Production Deployment"
echo "=============================================="
echo ""

# 1. Schema Drift Onarımı
echo "📋 Step 1: Repairing schema drift..."
php artisan system:repair-schema-drift

echo ""

# 2. Kalan Migration'ları Tamamla
echo "📋 Step 2: Completing remaining migrations..."
php artisan migrate --force

echo ""

# 3. Tenant Kredilerini Transaksiyonel Olarak Bas
echo "📋 Step 3: Seeding tenant credits (transactional)..."
php artisan tinker --execute="
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

# 4. Sistem Sağlığı Kontrolü
echo "📋 Step 4: System health check..."
php artisan bekci:health

echo ""

# 5. SAB Integrity Scan
echo "📋 Step 5: SAB integrity scan..."
php artisan sab:integrity-scan

echo ""
echo "🏆 =============================================="
echo "🏆 PHASE 12 SEALED: PRODUCTION ACTIVE"
echo "🏆 =============================================="
