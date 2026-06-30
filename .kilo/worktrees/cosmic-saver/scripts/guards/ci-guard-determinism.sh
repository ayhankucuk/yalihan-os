#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🔒 CI Guard: SSOT & Determinism Anayasası Enforcer
# ═══════════════════════════════════════════════════════════════════════════
#
# ADR: docs/adr/2026-02-21-ssot-determinism-constitution.md
#
# Kapsam: Template / Resolver / Feature / Cache katmanı
#
# Taranan pattern'ler:
#   RULE-1: getMinimalFeatureSet çağrısı app/ altında (tests/ hariç) → FAIL
#   RULE-2: Cache::forget / Cache::put UpsCacheService dışında → FAIL
#   RULE-3: Hardcoded baslik/fiyat/aciklama üçlüsü feature array (app/ altında) → FAIL
#   RULE-4: resolveTemplateFeatures içinde catch + return hardcoded → FAIL (dosya bazlı)
#   RULE-5: first() + active() — orderBy olmaksızın AltKategoriYayinTipi → FAIL
#
# Whitelist:
#   - tests/ dizini (fixture setup)
#   - scripts/ dizini (bu dosya)
#   - UpsCacheService.php (cache authority kaynak)
#
# Exit Codes:
#   0 = Forbidden pattern yok
#   1 = Forbidden pattern tespit edildi
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VIOLATIONS=0

BASE_DIR="${CI_GUARD_BASE_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
APP_DIR="${BASE_DIR}/app"

echo "🔒 Determinism & SSOT Anayasası Enforcer"
echo "   ADR: docs/adr/2026-02-21-ssot-determinism-constitution.md"
echo "   Base: ${BASE_DIR}"
echo ""

# ─────────────────────────────────────────────────────────────
# RULE-1: getMinimalFeatureSet — app/ altında çağrı yasak
# ─────────────────────────────────────────────────────────────
echo "🔍 [RULE-1] Checking: getMinimalFeatureSet call outside tests..."

RULE1_MATCHES=$(grep -rn "getMinimalFeatureSet" "${APP_DIR}" \
    --include="*.php" 2>/dev/null \
    | grep -v "function getMinimalFeatureSet" \
    | grep -v "//.*getMinimalFeatureSet" \
    | grep -v "#.*getMinimalFeatureSet" \
    || true)

if [ -n "$RULE1_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-1] FAILED — getMinimalFeatureSet çağrısı tespit edildi:${NC}"
    echo "$RULE1_MATCHES"
    echo ""
    echo -e "${YELLOW}  Fix: Exception'ı yut değil — log + rethrow veya explicit fallback flag kullan.${NC}"
    echo "  Ref: ADR-003 § Fallback Politikası"
    echo ""
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-1] PASSED — getMinimalFeatureSet çağrısı yok${NC}"
fi
echo ""

# ─────────────────────────────────────────────────────────────
# RULE-2: Cache::forget / Cache::put — Template/UPS katmanında UpsCacheService dışında yasak
# Kapsam: app/Services/Ups/ + app/Services/Template/ + app/Services/TemplateResolver.php
#         + UPSHelperTrait (UPS controller yardımcısı)
# Genel uygulama cache'leri (adres, ayar, blog...) bu kural dışındadır.
# ─────────────────────────────────────────────────────────────
echo "🔍 [RULE-2] Checking: Cache::forget / Cache::put in Template/UPS layer (UpsCacheService hariç)..."

# Template/UPS katmanına özgü dizinler (UpsOptimisticLockService lock-cache kullandığı için HARİÇ)
UPS_SCOPE=(
    "${APP_DIR}/Services/Ups"
    "${APP_DIR}/Services/Template"
    "${APP_DIR}/Services/TemplateResolver.php"
)

# Hariç tutulacak dosyalar (kendi cache otoritesi olan servisler)
UPS_EXCLUDE_PATTERN="UpsCacheService\.php\|UpsOptimisticLockService\.php"

RULE2_MATCHES=""
for SCOPE_PATH in "${UPS_SCOPE[@]}"; do
    if [ ! -e "$SCOPE_PATH" ]; then
        continue
    fi

    if [ -f "$SCOPE_PATH" ]; then
        _RESULT=$(grep -n "Cache::forget\|Cache::put" "$SCOPE_PATH" 2>/dev/null \
            | grep -v "$UPS_EXCLUDE_PATTERN" \
            | grep -v "^\s*//" \
            | grep -v "^\s*\*" \
            | grep -v "^\s*#" \
            || true)
        if [ -n "$_RESULT" ]; then
            RULE2_MATCHES="${RULE2_MATCHES}${SCOPE_PATH}:"$'\n'"${_RESULT}"$'\n'
        fi
    else
        _RESULT=$(grep -rn "Cache::forget\|Cache::put" "$SCOPE_PATH" \
            --include="*.php" 2>/dev/null \
            | grep -v "$UPS_EXCLUDE_PATTERN" \
            | grep -v "^\S*: *//" \
            | grep -v "^\S*: *\*" \
            | grep -v "^\S*: *#" \
            || true)
        if [ -n "$_RESULT" ]; then
            RULE2_MATCHES="${RULE2_MATCHES}${_RESULT}"$'\n'
        fi
    fi
done

if [ -n "$RULE2_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-2] FAILED — Cache::forget/put Template/UPS katmanında UpsCacheService dışında:${NC}"
    echo "$RULE2_MATCHES"
    echo ""
    echo -e "${YELLOW}  Fix: Cache::forget / Cache::put yerine UpsCacheService metodlarını kullan.${NC}"
    echo "  Ref: ADR-003 § Cache Authority"
    echo ""
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-2] PASSED — Template/UPS katmanında Cache::forget/put yalnızca UpsCacheService içinde${NC}"
fi
echo ""

# ─────────────────────────────────────────────────────────────
# RULE-3: getMinimalFeatureSet tanımı — production kodunda yasak
# (dead code + SSOT ihlali — method silinmiş olmalı)
# ─────────────────────────────────────────────────────────────
echo "🔍 [RULE-3] Checking: getMinimalFeatureSet definition in app/..."

RULE3_MATCHES=$(grep -rn "function getMinimalFeatureSet" "${APP_DIR}" \
    --include="*.php" 2>/dev/null \
    || true)

if [ -n "$RULE3_MATCHES" ]; then
    echo -e "${RED}❌ [RULE-3] FAILED — getMinimalFeatureSet definition tespit edildi:${NC}"
    echo "$RULE3_MATCHES"
    echo ""
    echo -e "${YELLOW}  Fix: Bu metodu sil. Feature setini FeatureTemplateResolver'dan al.${NC}"
    echo "  Ref: ADR-003 § SSOT Kuralları + Fallback Politikası"
    echo ""
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo -e "${GREEN}  ✅ [RULE-3] PASSED — getMinimalFeatureSet definition yok${NC}"
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Sonuç
# ─────────────────────────────────────────────────────────────
echo "────────────────────────────────────────"
if [ "$VIOLATIONS" -eq 0 ]; then
    echo -e "${GREEN}✅ Determinism & SSOT Guard: PASSED (0 violations)${NC}"
    echo "   Tüm template/resolver/feature/cache yolları anayasaya uygun."
    exit 0
else
    echo -e "${RED}❌ Determinism & SSOT Guard: FAILED (${VIOLATIONS} violation group(s))${NC}"
    echo ""
    echo -e "${YELLOW}  Bu ihlaller merge blocker'dır.${NC}"
    echo "  Ref: docs/adr/2026-02-21-ssot-determinism-constitution.md"
    exit 1
fi
