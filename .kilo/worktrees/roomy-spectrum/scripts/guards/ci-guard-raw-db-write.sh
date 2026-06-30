#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ CI Guard: Raw DB Write Bypass Detector
# ═══════════════════════════════════════════════════════════════════════════
#
# Governance Enforcement Layer — P1-02
# Amaç: feature_assignments tablosuna yapılan raw DB write bypass'larını
#        PR/CI aşamasında tespit et ve fail et.
#
# Kural 2 (Bağlayıcı): Write-path'te Raw DB write yasaktır:
#   DB::table, insert, update, delete, updateOrInsert, upsert
#
# Whitelist:
#   - *_quarantine tabloları (arşiv, onarım izolasyonu)
#   - tests/ dizini (fixture setup)
#   - Bu dosyanın kendisi (scripts/)
#
# Exit Codes:
#   0 = Forbidden pattern yok
#   1 = Forbidden pattern tespit edildi
#
# ADR: docs/adr/2026-02-21-feature-assignments-architectural-freeze.md
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VIOLATIONS=0

# CI_GUARD_BASE_DIR: testlerde geçici dizin belirtmek için kullanılır.
# Üretimde ayarlanmaz → proje kök dizini (script'in bulunduğu yerin üstü) kullanılır.
BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"

echo "🔍 Raw DB Write Bypass Scanner — Governance Guard"
echo "   Table: feature_assignments"
echo "   Base:  ${BASE_DIR}"
echo "   Scope: app/Http/ app/Console/Commands/ app/Services/ app/Modules/ app/Jobs/ app/Listeners/"
echo ""

# ─────────────────────────────────────────────────────────────
# Taranacak dizinler (test/ ve scripts/ HARİÇ)
# BASE_DIR ile birleştirilir.
# ─────────────────────────────────────────────────────────────
SCAN_DIRS=(
    "${BASE_DIR}/app/Http"
    "${BASE_DIR}/app/Console/Commands"
    "${BASE_DIR}/app/Services"
    "${BASE_DIR}/app/Modules"
    "${BASE_DIR}/app/Jobs"
    "${BASE_DIR}/app/Listeners"
    "${BASE_DIR}/app/Observers"
    "${BASE_DIR}/app/Domain"
    "${BASE_DIR}/app/Domains"
)

# ─────────────────────────────────────────────────────────────
# Tehlikeli pattern: feature_assignments + write operation
# ─────────────────────────────────────────────────────────────
WRITE_PATTERN="DB::table\s*\(\s*['\"]feature_assignments['\"]\s*\)->(insert|update|delete|updateOrInsert|upsert)"

for DIR in "${SCAN_DIRS[@]}"; do
    if [ ! -d "$DIR" ]; then
        continue
    fi

    # grep -P: Perl regex (macOS için -E yeterli, Linux'ta -P)
    MATCHES=$(grep -rn -E "DB::table\s*\(\s*['\"]feature_assignments['\"]\s*\)" "$DIR" \
        --include="*.php" 2>/dev/null \
        | grep -E "\->(insert|update|delete|updateOrInsert|upsert)" \
        | grep -v "_quarantine" \
        | grep -v "//.*DB::table" \
        | grep -v "/\*.*DB::table" \
        | grep -v "#" \
        || true)

    if [ -n "$MATCHES" ]; then
        echo -e "${RED}❌ FORBIDDEN PATTERN DETECTED in ${DIR}:${NC}"
        echo "$MATCHES"
        echo ""
        VIOLATIONS=$((VIOLATIONS + 1))
    fi
done

# ─────────────────────────────────────────────────────────────
# updateOrInsert / upsert doğrudan (tablo adı satırda olmasa bile)
# Sadece feature_assignments bağlamında
# ─────────────────────────────────────────────────────────────
for DIR in "${SCAN_DIRS[@]}"; do
    if [ ! -d "$DIR" ]; then
        continue
    fi

    UPSERT_MATCHES=$(grep -rn -l -E "DB::table\s*\(\s*['\"]feature_assignments['\"]" "$DIR" \
        --include="*.php" 2>/dev/null \
        | xargs grep -l -E "updateOrInsert|->upsert\(" 2>/dev/null \
        | grep -v "_quarantine" \
        || true)

    if [ -n "$UPSERT_MATCHES" ]; then
        echo -e "${RED}❌ updateOrInsert/upsert DETECTED on feature_assignments in:${NC}"
        echo "$UPSERT_MATCHES"
        echo ""
        VIOLATIONS=$((VIOLATIONS + 1))
    fi
done

# ─────────────────────────────────────────────────────────────
# Sonuç
# ─────────────────────────────────────────────────────────────
echo "────────────────────────────────────────"
if [ "$VIOLATIONS" -eq 0 ]; then
    echo -e "${GREEN}✅ Raw DB Write Bypass Guard: PASSED (0 violations)${NC}"
    echo "   All feature_assignments writes go through Eloquent + Observer chain."
    exit 0
else
    echo -e "${RED}❌ Raw DB Write Bypass Guard: FAILED (${VIOLATIONS} violation group(s))${NC}"
    echo ""
    echo -e "${YELLOW}  Fix: Replace DB::table('feature_assignments')->write_op()${NC}"
    echo -e "${YELLOW}  With: FeatureAssignment::eloquent_equivalent()${NC}"
    echo -e "${YELLOW}  Reason: Observer chain (cache + changelog) must fire on every write.${NC}"
    echo ""
    echo "  Ref: docs/adr/2026-02-21-governance-enforcement-layer.md"
    exit 1
fi
