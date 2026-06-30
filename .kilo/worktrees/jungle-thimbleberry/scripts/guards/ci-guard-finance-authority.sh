#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ CI Guard: Finance Authority & Drift Detector
# ═══════════════════════════════════════════════════════════════════════════
#
# Amaç: Finance domain'inin SEALED statüsünü korumak.
# - app/Services/Finance içerisinde DB::table kullanımını yasaklar.
# - Canonical write-path'in (YalihanTreasury) sağlamlığını doğrular.
# - Kapatılan dead runtime contract'ların (processBonus) geri gelmesini engeller.
#
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

VIOLATIONS=0
BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"

echo "🔍 Finance Authority Guard Scanner"
echo "   Scope: app/Services/Finance"

# 1. Check for DB::table in Finance Services
DB_TABLE_MATCHES=$(grep -rn "DB::table" "${BASE_DIR}/app/Services/Finance" --include="*.php" 2>/dev/null || true)

if [ -n "$DB_TABLE_MATCHES" ]; then
    echo -e "${RED}❌ FORBIDDEN: DB::table bypass detected in Finance domain:${NC}"
    echo "$DB_TABLE_MATCHES"
    echo -e "   Only Canonical Eloquent models are allowed in SEALED domains."
    VIOLATIONS=$((VIOLATIONS + 1))
fi

# 2. Verify Canonical Path (YalihanTreasury::batchCalculateMonthlyBonuses exists)
if ! grep -q "function batchCalculateMonthlyBonuses" "${BASE_DIR}/app/Services/Finance/YalihanTreasury.php" 2>/dev/null; then
    echo -e "${RED}❌ FORBIDDEN: Canonical write path (YalihanTreasury::batchCalculateMonthlyBonuses) is missing or renamed!${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
fi

# 3. Check for processBonus / batchProcessBonuses drift in BonusCalculator
DRIFT_MATCHES=$(grep -rnE "function processBonus|function batchProcessBonuses" "${BASE_DIR}/app/Services/Finance/BonusCalculator.php" 2>/dev/null || true)

if [ -n "$DRIFT_MATCHES" ]; then
    echo -e "${RED}❌ FORBIDDEN: Dead/Drifted methods detected in BonusCalculator:${NC}"
    echo "$DRIFT_MATCHES"
    echo -e "   These methods were removed. Do not restore them."
    VIOLATIONS=$((VIOLATIONS + 1))
fi

# Sonuç
if [ "$VIOLATIONS" -eq 0 ]; then
    echo -e "${GREEN}✅ Finance Authority Guard: PASSED${NC}"
    exit 0
else
    echo -e "${RED}❌ Finance Authority Guard: FAILED (${VIOLATIONS} violations)${NC}"
    exit 1
fi
