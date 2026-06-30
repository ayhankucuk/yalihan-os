#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🔒 CI Guard: SAB Prompt & Governance Drift Enforcer
# ═══════════════════════════════════════════════════════════════════════════
#
# Taranan pattern'ler:
#   RULE-1: Retired "context7:" command references in app/ & scripts/ (archive hariç)
#   RULE-2: Wizard/Ups resolver merge attempts (unauthorized import cross-boundary)
#   RULE-3: sab-master-prompt.md checksum integrity (freeze guard)
#   RULE-4: Direct DB write bypassing IlanCrudService (write authority guard)
#
# Whitelist:
#   - scripts/archive/   (eski/arşiv dosyaları)
#   - tests/             (fixture/mock kullanımı)
#   - vendor/            (3rd party)
#   - .sab/sab-master-prompt.md Section 8.3 referansı (döküman içi açıklama)
#
# Exit Codes:
#   0 = Tüm kurallar sağlam
#   1 = Violation tespit edildi
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VIOLATIONS=0

BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
APP_DIR="${BASE_DIR}/app"
SCRIPTS_DIR="${BASE_DIR}/scripts"
PROMPT_FILE="${BASE_DIR}/.sab/sab-master-prompt.md"
PROMPT_CHECKSUM_FILE="${BASE_DIR}/.sab/sab-master-prompt.sha256"

echo "🔒 SAB Prompt & Governance Drift Enforcer"
echo "════════════════════════════════════════════════════════════════"

# ─────────────────────────────────────────────────────────────────────
# RULE 1: Retired "context7:" command references
# ─────────────────────────────────────────────────────────────────────
echo ""
echo "📋 RULE 1: Retired context7: command references"

# Search in app/ and scripts/ excluding archive/, vendor/, and this guard script
CONTEXT7_HITS=$(grep -rn "context7:integrity-scan\|context7:integrity" \
    --include="*.php" --include="*.sh" --include="*.yaml" --include="*.yml" \
    "${APP_DIR}" "${SCRIPTS_DIR}" \
    --exclude-dir=archive \
    --exclude-dir=vendor \
    --exclude="ci-guard-sab-prompt.sh" \
    2>/dev/null || true)

if [ -n "$CONTEXT7_HITS" ]; then
    echo -e "${RED}❌ FAIL: Retired context7: command references found${NC}"
    echo "$CONTEXT7_HITS"
    echo ""
    echo "  FIX: Replace 'context7:integrity-scan' with 'sab:integrity-scan'"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}✅ PASS: No retired context7: references${NC}"
fi

# ─────────────────────────────────────────────────────────────────────
# RULE 2: Wizard/Ups resolver merge detection
# ─────────────────────────────────────────────────────────────────────
echo ""
echo "📋 RULE 2: Wizard/Ups resolver boundary guard"

# Detect if Ups namespace imports Wizard resolver (reverse dependency = violation)
UPS_IMPORTS_WIZARD=$(grep -rn "use App\\\\Services\\\\Wizard\\\\" \
    "${APP_DIR}/Services/Ups/" \
    --include="*.php" \
    2>/dev/null || true)

if [ -n "$UPS_IMPORTS_WIZARD" ]; then
    echo -e "${RED}❌ FAIL: Ups imports from Wizard namespace (reverse dependency)${NC}"
    echo "$UPS_IMPORTS_WIZARD"
    echo ""
    echo "  FIX: Ups is SSOT authority. It must never depend on Wizard."
    echo "  See: .sab/sab-master-prompt.md Section 6 — Project-Specific Truths"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}✅ PASS: Ups does not import from Wizard${NC}"
fi

# Detect if Wizard resolver directly writes to feature_assignments
WIZARD_WRITES=$(grep -rn "->insert\|->update\|->delete\|->create\|->save\|DB::table.*feature_assignments.*->insert\|DB::table.*feature_assignments.*->update" \
    "${APP_DIR}/Services/Wizard/FeatureTemplateResolver.php" \
    2>/dev/null || true)

if [ -n "$WIZARD_WRITES" ]; then
    echo -e "${RED}❌ FAIL: Wizard resolver performs DB writes (read-only violation)${NC}"
    echo "$WIZARD_WRITES"
    echo ""
    echo "  FIX: Wizard resolver is read-only projection. Writes go through Ups."
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}✅ PASS: Wizard resolver is read-only${NC}"
fi

# ─────────────────────────────────────────────────────────────────────
# RULE 3: sab-master-prompt.md integrity (freeze guard)
# ─────────────────────────────────────────────────────────────────────
echo ""
echo "📋 RULE 3: SAB Master Prompt freeze integrity"

if [ ! -f "$PROMPT_FILE" ]; then
    echo -e "${RED}❌ FAIL: .sab/sab-master-prompt.md not found${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
elif [ -f "$PROMPT_CHECKSUM_FILE" ]; then
    EXPECTED_SUM=$(cat "$PROMPT_CHECKSUM_FILE" | awk '{print $1}')
    ACTUAL_SUM=$(shasum -a 256 "$PROMPT_FILE" | awk '{print $1}')

    if [ "$EXPECTED_SUM" != "$ACTUAL_SUM" ]; then
        echo -e "${RED}❌ FAIL: sab-master-prompt.md has been modified without approval${NC}"
        echo "  Expected: ${EXPECTED_SUM}"
        echo "  Actual:   ${ACTUAL_SUM}"
        echo ""
        echo "  FIX: If intentional (HIGH RISK change), update checksum:"
        echo "    shasum -a 256 .sab/sab-master-prompt.md > .sab/sab-master-prompt.sha256"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo -e "${GREEN}✅ PASS: Prompt checksum matches (frozen)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  WARN: No checksum file yet. Generating initial freeze...${NC}"
    shasum -a 256 "$PROMPT_FILE" > "$PROMPT_CHECKSUM_FILE"
    echo "  Created: .sab/sab-master-prompt.sha256"
    echo -e "${GREEN}✅ PASS: Initial checksum generated${NC}"
fi

# ─────────────────────────────────────────────────────────────────────
# RULE 4: Write authority guard (IlanCrudService bypass detection)
# ─────────────────────────────────────────────────────────────────────
echo ""
echo "📋 RULE 4: Write authority guard (IlanCrudService)"

# Detect Ilan::create() outside IlanCrudService (unauthorized write)
ILAN_CREATE_BYPASS=$(grep -rn "Ilan::create\|Ilan::forceCreate\|new Ilan(" \
    "${APP_DIR}" \
    --include="*.php" \
    --exclude-dir=vendor \
    2>/dev/null | \
    grep -v "IlanCrudService" | \
    grep -v "tests/" | \
    grep -v "Factory" | \
    grep -v "Seeder" | \
    grep -v "@deprecated" | \
    grep -v "// WA-" | \
    grep -v "// SAB-EXEMPT" || true)

if [ -n "$ILAN_CREATE_BYPASS" ]; then
    echo -e "${YELLOW}⚠️  WARN: Possible write authority bypass detected${NC}"
    echo "$ILAN_CREATE_BYPASS"
    echo ""
    echo "  NOTE: IlanCrudService::store() is the sole write authority."
    echo "  If legitimate, add // SAB-EXEMPT comment with reason."
    # Warning only — not incrementing violations (legacy code exists)
else
    echo -e "${GREEN}✅ PASS: No unauthorized Ilan write detected${NC}"
fi

# ─────────────────────────────────────────────────────────────────────
# SUMMARY
# ─────────────────────────────────────────────────────────────────────
echo ""
echo "════════════════════════════════════════════════════════════════"

if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e "${RED}❌ SAB PROMPT GUARD: ${VIOLATIONS} violation(s) detected${NC}"
    exit 1
else
    echo -e "${GREEN}✅ SAB PROMPT GUARD: All rules passed${NC}"
    exit 0
fi
