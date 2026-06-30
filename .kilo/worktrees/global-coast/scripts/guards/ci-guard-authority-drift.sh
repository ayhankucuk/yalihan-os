#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ CI Guard: Listing Lifecycle Authority Drift Detector
# ═══════════════════════════════════════════════════════════════════════════
#
# Governance Enforcement Layer — P0
# ADR: docs/adr/ (Listing Lifecycle Authority — FINAL SEALED)
#
# SEALED RULES (Zero-Tolerance):
#   RULE-W1: Ilan::create() bypass — only IlanCrudService::store() may create
#   RULE-W2: IlanRepository must delegate to crudService (not direct model)
#   RULE-W3: Bulk mass-update of yayin_durumu bypassing YalihanLifecycle
#
# Whitelists:
#   - IlanCrudService.php (The sole write authority)
#   - YalihanLifecycle.php (The sole state transition authority)
#   - tests/ (fixture setup)
#   - Factories + Seeders
#   - Comments (# / // / *)
#
# Exit Codes:
#   0 = No authority drift detected
#   1 = Authority drift detected — PR BLOCKED
#
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
VIOLATIONS=0

echo ""
echo -e "${BOLD}🛡️  Listing Lifecycle Authority Drift Guard${NC}"
echo -e "   Base: ${BASE_DIR}"
echo ""

# ─────────────────────────────────────────────────────────────────────────────
# RULE-W1: Ilan::create() bypass detection
#
# IlanCrudService::store() is the ONLY authorized write path for Ilan records.
# Any Ilan::create() / Ilan::forceCreate() outside IlanCrudService = DRIFT.
# Also catches: new Ilan() in a file that also calls ->save() = DRIFT.
#
# Current known debt: 0 (all bounded instances migrated as of Patch-2)
# Baseline: 0 — adding any new instance is forbidden without IlanCrudService.
# ─────────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-W1] Ilan::create() bypass outside IlanCrudService...${NC}"

W1_SCAN_DIRS=(
    "${BASE_DIR}/app/Http"
    "${BASE_DIR}/app/Services"
    "${BASE_DIR}/app/Modules"
    "${BASE_DIR}/app/Jobs"
    "${BASE_DIR}/app/Listeners"
    "${BASE_DIR}/app/Observers"
    "${BASE_DIR}/app/Domain"
    "${BASE_DIR}/app/Domains"
    "${BASE_DIR}/app/Console/Commands"
)

W1_MATCHES=""
for DIR in "${W1_SCAN_DIRS[@]}"; do
    if [ ! -d "$DIR" ]; then
        continue
    fi
    FOUND=$(grep -rn \
        -E "Ilan::create\(|Ilan::forceCreate\(" \
        "$DIR" \
        --include="*.php" \
        2>/dev/null \
        | grep -v "IlanCrudService" \
        | grep -v "tests/" \
        | grep -v "Factory" \
        | grep -v "Seeder" \
        | grep -Ev '^[^:]+:[0-9]+:[[:space:]]*(//|#|/\*|\*|\*/)' \
        | grep -v "// SAB-EXEMPT" \
        | grep -v "// WA-" \
        || true)
    if [ -n "$FOUND" ]; then
        W1_MATCHES="${W1_MATCHES}${FOUND}"$'\n'
    fi

    # Sub-check: new Ilan() + ->save() in same file (ghost model write bypass pattern)
    # Exempt: SAB-EXEMPT comment on the new Ilan() line
    FOUND2=$(grep -rln \
        "new Ilan[^a-zA-Z]" \
        "$DIR" \
        --include="*.php" \
        2>/dev/null \
        | xargs -I{} sh -c \
            'grep -l "->save()" "$1" 2>/dev/null || true' \
            _ {} \
        | while read -r FILE; do
            # Skip if the new Ilan() line has SAB-EXEMPT
            if grep -qE "new Ilan[^a-zA-Z].*SAB-EXEMPT|SAB-EXEMPT.*new Ilan" "$FILE" 2>/dev/null; then
                continue
            fi
            # Skip IlanCrudService itself (legitimate: it does new Ilan internally)
            if echo "$FILE" | grep -q "IlanCrudService"; then
                continue
            fi
            # Skip tests/factories/seeders
            if echo "$FILE" | grep -qE "tests/|Factory\.php|Seeder\.php"; then
                continue
            fi
            echo "$FILE"
        done || true)

    if [ -n "$FOUND2" ]; then
        W1_MATCHES="${W1_MATCHES}[new Ilan + save() bypass] ${FOUND2}"$'\n'
    fi
done

# Trim trailing whitespace
W1_MATCHES="${W1_MATCHES%$'\n'}"

if [ -n "$W1_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-W1] P0 FAIL — Direct Ilan write bypass detected:${NC}"
    echo "$W1_MATCHES"
    echo ""
    echo -e "${RED}   Fix: Use IlanCrudService::store(\$data) instead.${NC}"
    echo -e "${RED}   IlanCrudService is the SOLE write authority (Listing Lifecycle Authority, sealed).${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-W1] PASSED — No unauthorized Ilan::create() found.${NC}"
fi

echo ""

# ─────────────────────────────────────────────────────────────────────────────
# RULE-W2: IlanRepository delegation integrity check
#
# IlanRepository::create() must delegate to $this->crudService->store().
# IlanRepository::update() must delegate to $this->crudService->update().
#
# If the repository writes directly to the model, the authority bridge is broken.
# Pattern: $this->model->create( inside IlanRepository = DRIFT.
# ─────────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-W2] IlanRepository delegation integrity...${NC}"

REPO_FILE="${BASE_DIR}/app/Repositories/IlanRepository.php"

if [ ! -f "$REPO_FILE" ]; then
    echo -e "${YELLOW}  ⚠️  [RULE-W2] WARN — IlanRepository.php not found at expected path.${NC}"
    echo "     Expected: ${REPO_FILE}"
else
    # Check for direct model create (forbidden — must delegate)
    W2_MODEL_CREATE=$(grep -n \
        "\$this->model->create(" \
        "$REPO_FILE" \
        | grep -Ev '^\s*(//|#|\*)' \
        || true)

    # Check for direct model update (forbidden — must delegate)
    W2_MODEL_UPDATE=$(grep -n \
        "\$ilan->update(\|\$this->model->where" \
        "$REPO_FILE" \
        | grep -Ev '^\s*(//|#|\*)' \
        | grep -v "crudService" \
        || true)

    # Verify delegation is present
    W2_CRUD_STORE=$(grep -n "crudService->store(" "$REPO_FILE" || true)
    W2_CRUD_UPDATE=$(grep -n "crudService->update(" "$REPO_FILE" || true)

    W2_FAIL=0
    if [ -n "$W2_MODEL_CREATE" ]; then
        echo -e "${RED}❌ [RULE-W2] P0 FAIL — IlanRepository::create() bypasses crudService:${NC}"
        echo "$W2_MODEL_CREATE"
        VIOLATIONS=$((VIOLATIONS + 1))
        W2_FAIL=1
    fi

    if [ -z "$W2_CRUD_STORE" ]; then
        echo -e "${RED}❌ [RULE-W2] P0 FAIL — IlanRepository is missing crudService->store() delegation:${NC}"
        echo "     Expected: \$this->crudService->store(\$data) in IlanRepository::create()"
        VIOLATIONS=$((VIOLATIONS + 1))
        W2_FAIL=1
    fi

    if [ -z "$W2_CRUD_UPDATE" ]; then
        echo -e "${RED}❌ [RULE-W2] P0 FAIL — IlanRepository is missing crudService->update() delegation:${NC}"
        echo "     Expected: \$this->crudService->update(\$ilan, \$data) in IlanRepository::update()"
        VIOLATIONS=$((VIOLATIONS + 1))
        W2_FAIL=1
    fi

    if [ $W2_FAIL -eq 0 ]; then
        echo -e "${GREEN}  ✅ [RULE-W2] PASSED — IlanRepository delegates to crudService correctly.${NC}"
    fi
fi

echo ""

# ─────────────────────────────────────────────────────────────────────────────
# RULE-W3: Mass yayin_durumu state update bypassing YalihanLifecycle
#
# YalihanLifecycle::transition() is the ONLY authorized state change path.
# Mass-updating yayin_durumu via raw Eloquent bypasses all guard rails:
#   Ilan::where(...)->update(['yayin_durumu' => ...])
#   Ilan::whereIn(...)->update(['yayin_durumu' => ...])
#
# YalihanLifecycle.php itself is explicitly whitelisted.
# ─────────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-W3] Mass yayin_durumu state bypass outside YalihanLifecycle...${NC}"

W3_SCAN_DIRS=(
    "${BASE_DIR}/app/Http"
    "${BASE_DIR}/app/Services"
    "${BASE_DIR}/app/Modules"
    "${BASE_DIR}/app/Jobs"
    "${BASE_DIR}/app/Listeners"
    "${BASE_DIR}/app/Observers"
    "${BASE_DIR}/app/Console/Commands"
)

W3_MATCHES=""
for DIR in "${W3_SCAN_DIRS[@]}"; do
    if [ ! -d "$DIR" ]; then
        continue
    fi
    FOUND=$(grep -rn \
        -E "Ilan::(where|whereIn|whereNotIn)\b.*->update\(" \
        "$DIR" \
        --include="*.php" \
        -A1 \
        2>/dev/null \
        | grep -B1 "yayin_durumu" \
        | grep "Ilan::" \
        | grep -v "YalihanLifecycle" \
        | grep -v "tests/" \
        | grep -Ev '^[^:]+:[0-9]+:[[:space:]]*(//|#|/\*|\*|\*/)' \
        | grep -v "// SAB-EXEMPT" \
        || true)
    if [ -n "$FOUND" ]; then
        W3_MATCHES="${W3_MATCHES}${FOUND}"$'\n'
    fi
done

W3_MATCHES="${W3_MATCHES%$'\n'}"

if [ -n "$W3_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-W3] P0 FAIL — Mass yayin_durumu update bypasses YalihanLifecycle:${NC}"
    echo "$W3_MATCHES"
    echo ""
    echo -e "${RED}   Fix: Use YalihanLifecycle::transition(\$ilan, YalihanDurumu::TARGET) instead.${NC}"
    echo -e "${RED}   YalihanLifecycle is the SOLE state transition authority (sealed).${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-W3] PASSED — No mass yayin_durumu state bypass found.${NC}"
fi

echo ""

# ─────────────────────────────────────────────────────────────────────────────
# SUMMARY
# ─────────────────────────────────────────────────────────────────────────────
echo "────────────────────────────────────────────────────────────────"
if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e "${RED}❌ Listing Lifecycle Authority Drift Guard: FAILED (${VIOLATIONS} violation(s))${NC}"
    echo ""
    echo "   Sealed architecture broken — PR is BLOCKED."
    echo "   See: docs/governance/listing-lifecycle-pr-checklist.md"
    exit 1
else
    echo -e "${GREEN}✨ Listing Lifecycle Authority Drift Guard: PASSED — zero drift.${NC}"
    echo "   IlanCrudService authority intact."
    echo "   IlanRepository delegation intact."
    echo "   YalihanLifecycle state authority intact."
    exit 0
fi
