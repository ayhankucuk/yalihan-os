#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# SAB Controller Zero-Tolerance Guard
# SAB Core Constitution v2.2 — §2 Controller Zero-Tolerance
#
# Kurallar:
#   RULE-C1: Controller içinde DB::transaction / beginTransaction YASAK (P0)
#   RULE-C2: Controller içinde Cache::put / Cache::forget / Cache::flush YASAK (P0)
#   RULE-C3: Controller içinde Eloquent mutation (save, update, create, delete) YASAK (P0)
#
# Muafiyetler:
#   - Trait dosyaları (Controllers/Traits/) — ayrı izlenir
#   - Test dosyaları
#   - Yorum satırları
#   - YalihanBekciController health-check (Cache::put/forget test amaçlı)
#
# Çıkış kodları:
#   0 = İhlal yok
#   1 = P0 veya P1 ihlal tespit edildi
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

CONTROLLER_DIR="app/Http/Controllers"
VIOLATIONS=0
AUTHORITY_FILE=".sab/authority.json"
BLOCKING="true"

if [ -f "$AUTHORITY_FILE" ]; then
    BLOCKING=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print('true' if d.get('ci_guards', {}).get('ci-guard-sab-controller.sh', {}).get('blocking', True) else 'false')
except Exception:
    print('true')
" 2>/dev/null || echo "true")
fi

echo ""
echo "🛡️  SAB Controller Zero-Tolerance Guard"
echo "   Authority: .sab/authority.json §controller_constraints"
echo ""

# ───────────────────────────────────────────────────────────────────────────
# RULE-C1 (P0): DB::transaction / beginTransaction — CONTROLLER YASAK
# ───────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-C1] DB::transaction / beginTransaction in Controllers...${NC}"

C1_MATCHES=$(grep -rn \
    -E "DB::transaction|DB::beginTransaction|\$[a-zA-Z_]+->beginTransaction\(\)" \
    "${CONTROLLER_DIR}" \
    --include="*.php" \
    | grep -v "Traits/" \
    | grep -Ev '^[^:]+:[0-9]+:[[:space:]]*(//|#|/\*|\*|\*/)' \
    | grep -v "tests/" \
    || true)

if [ -n "$C1_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-C1] P0 FAIL — DB transaction in controller:${NC}"
    echo "$C1_MATCHES"
    echo ""
    echo -e "${RED}   Fix: Move DB::transaction to Orchestrator/Service layer.${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-C1] PASSED — No DB transactions in controllers.${NC}"
fi

echo ""

# ───────────────────────────────────────────────────────────────────────────
# RULE-C2 (P1): Cache::put / Cache::forget / Cache::flush — CONTROLLER YASAK
# ───────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-C2] Cache invalidation in Controllers...${NC}"

C2_MATCHES=$(grep -rn \
    -E "(Cache::|\\\\Illuminate\\\\Support\\\\Facades\\\\Cache::)(put|forget|flush|tags)" \
    "${CONTROLLER_DIR}" \
    --include="*.php" \
    | grep -v "Traits/" \
    | grep -Ev '^[^:]+:[0-9]+:[[:space:]]*(//|#|/\*|\*|\*/)' \
    | grep -v "tests/" \
    | grep -v "YalihanBekciController" \
    || true)

if [ -n "$C2_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-C2] P0 FAIL — Cache mutation in controller:${NC}"
    echo "$C2_MATCHES"
    echo ""
    echo -e "${RED}   Fix: Delegate cache invalidation to Service/CacheService layer.${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-C2] PASSED — No direct cache mutation in controllers.${NC}"
fi

echo ""

# ───────────────────────────────────────────────────────────────────────────
# RULE-C3 (P0): Eloquent Mutation — CONTROLLER YASAK
# ───────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 [RULE-C3] Eloquent Mutation in Controllers...${NC}"

C3_MATCHES=$(grep -rn \
    -E "\->(save|update|create|delete|forceDelete)\(|::(create|update|delete|save)\(" \
    "${CONTROLLER_DIR}" \
    --include="*.php" \
    | grep -v "Traits/" \
    | grep -Ev '^[^:]+:[0-9]+:[[:space:]]*(//|#|/\*|\*|\*/)' \
    | grep -v "tests/" \
    | grep -v "SabComplianceMiddleware" \
    | grep -v "SabGuard" \
    || true)

if [ -n "$C3_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-C3] P0 FAIL — Eloquent mutation in controller:${NC}"
    echo "$C3_MATCHES"
    echo ""
    echo -e "${RED}   Fix: Move all model mutations to Service/Orchestrator layer.${NC}"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-C3] PASSED — No direct mutations in controllers.${NC}"
fi

echo ""

# ───────────────────────────────────────────────────────────────────────────
# SONUÇ
# ───────────────────────────────────────────────────────────────────────────
if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e "${RED}❌ SAB Controller Guard: FAILED (${VIOLATIONS} violation(s))${NC}"
    echo "   P0 = DB::transaction in controller (blocking)"
    echo "   P1 = Cache mutation in controller (blocking)"
    echo "   P2 = Eloquent mutation in controller (blocking)"
    if [ "$BLOCKING" = "true" ]; then
        exit 1
    fi
    echo -e "${YELLOW}⚠️  Controller Zero-Tolerance Guard: violations detected (non-blocking — authority.json: blocking=false)${NC}"
    if [ -f "$AUTHORITY_FILE" ]; then
        ESTIMATED_BLOCKING_DATE=$(python3 -c "
import json
try:
    d = json.load(open('$AUTHORITY_FILE'))
    print(d.get('blocking_transition', {}).get('ci-guard-sab-controller.sh', {}).get('estimated_blocking_date', 'N/A'))
except Exception:
    print('N/A')
" 2>/dev/null || echo "N/A")
        echo -e "${YELLOW}⚠️     Blocking geçiş tarihi: ${ESTIMATED_BLOCKING_DATE}${NC}"
    fi
    exit 0
fi

echo -e "${GREEN}✅ SAB Controller Guard: PASSED (0 violations)${NC}"
exit 0
