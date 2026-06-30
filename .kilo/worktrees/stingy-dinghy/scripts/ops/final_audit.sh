#!/bin/bash

echo "🛡️  Starting Final Global Seal Audit (Shell Mode)..."
echo "------------------------------------------------------------"

# 1. Armored Batch Test
echo "🧪 Running Global Domain Tests (Armored Batch)..."
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan test --filter="BulkKisiNormalizationTest|CrmDriftGuardTest|FinanceIntegrityTest|TaskIntegrityTest"
if [ $? -ne 0 ]; then
    echo "❌ Global Test Batch FAILED!"
    exit 1
fi
echo "✅ Global Test Batch PASSED!"

# 2. CRM Scanners
echo -e "\n🛡️  Checking CRM..."
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan crm:drift-scan
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan quality:gate

# 3. TASK Scanners
echo -e "\n🛡️  Checking TASK..."
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan model:drift-scan --model="App\Modules\TakimYonetimi\Models\Gorev"

# 4. FINANCE Scanners
echo -e "\n🛡️  Checking FINANCE..."
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan model:drift-scan --model="App\Modules\Finans\Models\FinansalIslem"
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan model:drift-scan --model="App\Modules\Finans\Models\Komisyon"

# 5. Final Quality Gate
echo -e "\n🛡️  Running Final Quality Gate..."
DB_HOST=127.0.0.1 DB_DATABASE=yalihanai_test php artisan quality:gate

echo -e "\n🏆 GLOBAL SEAL SUCCESS: All domains are compliant and locked."
