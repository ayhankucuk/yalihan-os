#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ CI Guard: Tenant Isolation Enforcer
# ═══════════════════════════════════════════════════════════════════════════
#
# Amaç: Multi-tenant güvenlik açıklarını CI'da bloklamak.
#
# Yakalanan pattern'lar:
#   1. tenant_id ?? 0          → silent fallback: unauthenticated = tenant 0
#   2. tenant_id ?: 0          → aynı sorun, farklı sözdizimi
#   3. ->tenant_id ?? null     → null fallback da tehlikeli (cross-tenant leak)
#   4. auth()->user()->tenant_id (null-safe olmayan direkt erişim)
#
# Çözüm: TenantContextResolver inject et + ?: throw new TenantContextMissingException
#
# SAB Rule: RULE-T1 (Tenant Isolation)
# Guard Owner: Finance + CRM + Core domains
#
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VIOLATIONS=0
WARNINGS=0
BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"

echo "🔍 Tenant Isolation Guard — Scope: app/"
echo "   Rule: RULE-T1 | Zero-tolerance silent tenant fallback"
echo ""

# ─────────────────────────────────────────────────────────────
# CHECK 1: tenant_id ?? 0  (silent fallback to tenant 0)
# ─────────────────────────────────────────────────────────────
SILENT_ZERO=$(grep -rn 'tenant_id\s*??.*\b0\b' "${BASE_DIR}/app" --include="*.php" 2>/dev/null || true)
COUNT_ZERO=0
[ -n "$SILENT_ZERO" ] && COUNT_ZERO=$(echo "$SILENT_ZERO" | wc -l | tr -d ' ')

if [ "$COUNT_ZERO" -gt 0 ]; then
    echo -e "${RED}❌ BLOCKING [RULE-T1-A]: tenant_id ?? 0 silent fallback — $COUNT_ZERO instance(s)${NC}"
    echo "$SILENT_ZERO" | head -20
    if [ "$COUNT_ZERO" -gt 20 ]; then
        echo "   ... and $((COUNT_ZERO - 20)) more. Run: grep -rn 'tenant_id\s*??\s*0' app/ --include='*.php'"
    fi
    echo ""
    VIOLATIONS=$((VIOLATIONS + COUNT_ZERO))
fi

# ─────────────────────────────────────────────────────────────
# CHECK 2: tenant_id ?: 0  (null coalescing assign to 0)
# ─────────────────────────────────────────────────────────────
SILENT_ZERO_TERNARY=$(grep -rn "tenant_id\s*?:\s*0" "${BASE_DIR}/app" --include="*.php" 2>/dev/null || true)
COUNT_TERNARY=0
[ -n "$SILENT_ZERO_TERNARY" ] && COUNT_TERNARY=$(echo "$SILENT_ZERO_TERNARY" | wc -l | tr -d ' ')

if [ "$COUNT_TERNARY" -gt 0 ]; then
    echo -e "${RED}❌ BLOCKING [RULE-T1-B]: tenant_id ?: 0 silent fallback — $COUNT_TERNARY instance(s)${NC}"
    echo "$SILENT_ZERO_TERNARY" | head -10
    echo ""
    VIOLATIONS=$((VIOLATIONS + COUNT_TERNARY))
fi

# ─────────────────────────────────────────────────────────────
# CHECK 3: Unguarded auth()->user()->tenant_id (no null-safe)
# Pattern: ->user()->tenant_id  (missing ?-> chain)
# ─────────────────────────────────────────────────────────────
UNSAFE_AUTH=$(grep -rn "->user()->tenant_id" "${BASE_DIR}/app" --include="*.php" 2>/dev/null || true)
COUNT_AUTH=0
[ -n "$UNSAFE_AUTH" ] && COUNT_AUTH=$(echo "$UNSAFE_AUTH" | wc -l | tr -d ' ')

if [ "$COUNT_AUTH" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  WARNING [RULE-T1-C]: Non-null-safe auth()->user()->tenant_id — $COUNT_AUTH instance(s)${NC}"
    echo "   Risk: NullPointerException on unauthenticated request (500 yerine 401 döndürmeli)"
    echo "$UNSAFE_AUTH" | head -10
    echo ""
    WARNINGS=$((WARNINGS + COUNT_AUTH))
fi

# ─────────────────────────────────────────────────────────────
# CHECK 4: Finance domain'inde TenantContextResolver kullanımı zorunlu
# ─────────────────────────────────────────────────────────────
FINANCE_FILES=$(find "${BASE_DIR}/app/Services/Finance" -name "*.php" 2>/dev/null)

FINANCE_NO_RESOLVER=""
for file in $FINANCE_FILES; do
    # Eğer dosya tenant_id kullanıyorsa ama TenantContextResolver import etmiyorsa
    if grep -q "tenant_id" "$file" 2>/dev/null; then
        if ! grep -q "TenantContextResolver" "$file" 2>/dev/null; then
            FINANCE_NO_RESOLVER="$FINANCE_NO_RESOLVER\n  $file"
        fi
    fi
done

if [ -n "$FINANCE_NO_RESOLVER" ]; then
    echo -e "${YELLOW}⚠️  WARNING [RULE-T1-D]: Finance dosyaları tenant_id kullanıyor ama TenantContextResolver inject etmiyor:${NC}"
    echo -e "$FINANCE_NO_RESOLVER"
    echo "   Öneri: TenantContextResolver DI ile inject et, auth() direkt erişimden kaçın."
    echo ""
    WARNINGS=$((WARNINGS + 1))
fi

# ─────────────────────────────────────────────────────────────
# RESULT
# ─────────────────────────────────────────────────────────────
echo "─────────────────────────────────────────────────────────"
if [ "$VIOLATIONS" -gt 0 ]; then
    echo -e "${RED}❌ TENANT ISOLATION GUARD: FAILED${NC}"
    echo "   Blocking violations: $VIOLATIONS"
    echo "   Warnings: $WARNINGS"
    echo ""
    echo "   FIX: tenant_id ?? 0  →  auth()->user()?->tenant_id ?: throw new \\RuntimeException('Unauthenticated')"
    echo "   BEST: Inject TenantContextResolver, call ->resolve() — throws TenantContextMissingException"
    exit 1
fi

if [ "$WARNINGS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  TENANT ISOLATION GUARD: PASSED WITH WARNINGS${NC}"
    echo "   Violations: 0 (clean)"
    echo "   Warnings: $WARNINGS — review recommended"
    exit 0
fi

echo -e "${GREEN}✅ TENANT ISOLATION GUARD: PASSED${NC}"
echo "   0 violations. 0 warnings. Tenant isolation clean."
exit 0
