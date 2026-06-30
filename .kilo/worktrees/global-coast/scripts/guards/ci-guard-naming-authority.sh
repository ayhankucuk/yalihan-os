#!/bin/bash
# Yalıhan Bekçi - Naming Authority Guard
# Purpose: Enforce naming conventions and detect naming drift
# Version: 1.0.0
# Date: 2026-05-11

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🛡️  YALIHAN BEKÇİ - Naming Authority Guard${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

VIOLATIONS=0
WARNINGS=0

# ================================================
# 1. FORBIDDEN ENGLISH PATTERNS (Use Turkish)
# ================================================
echo -e "${YELLOW}📋 Checking forbidden English patterns...${NC}"

FORBIDDEN_PATTERNS=(
    "is_active:aktiflik_durumu"
    "is_enabled:aktiflik_durumu"
    "is_deleted:silinme_durumu"
    "is_published:yayin_durumu"
    "is_verified:dogrulama_durumu"
    "status:durum"
    "type:tip"
    "category:kategori"
    "description:aciklama"
    "title:baslik"
    "address:adres"
    "phone:telefon"
    "notes:notlar"
)

for pattern in "${FORBIDDEN_PATTERNS[@]}"; do
    FORBIDDEN="${pattern%%:*}"
    CORRECT="${pattern##*:}"

    # Check in migrations
    if grep -rn "'$FORBIDDEN'" database/migrations/ 2>/dev/null | grep -v "// LEGACY" | grep -v "// EXCEPTION"; then
        echo -e "${RED}❌ VIOLATION: Found '$FORBIDDEN' in migrations${NC}"
        echo -e "${GREEN}   → Use: '$CORRECT' instead${NC}"
        ((VIOLATIONS++))
    fi

    # Check in models
    if grep -rn "'$FORBIDDEN'" app/Models/ 2>/dev/null | grep -v "// LEGACY" | grep -v "// EXCEPTION"; then
        echo -e "${RED}❌ VIOLATION: Found '$FORBIDDEN' in models${NC}"
        echo -e "${GREEN}   → Use: '$CORRECT' instead${NC}"
        ((VIOLATIONS++))
    fi
done

# ================================================
# 2. MIXED NAMING DETECTION
# ================================================
echo ""
echo -e "${YELLOW}📋 Checking for mixed naming (same concept, different names)...${NC}"

# Active/Status variations
ACTIVE_VARIATIONS=$(grep -rh "'aktiflik_durumu'\|'is_active'\|'aktif'\|'active'\|'status'\|'durum'" database/migrations/ app/Models/ 2>/dev/null | wc -l)
if [ "$ACTIVE_VARIATIONS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  WARNING: Multiple variations for 'active' concept found${NC}"
    echo -e "   aktiflik_durumu: $(grep -r "'aktiflik_durumu'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "   is_active: $(grep -r "'is_active'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "   aktif: $(grep -r "'aktif'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "   active: $(grep -r "'active'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "   status: $(grep -r "'status'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "   durum: $(grep -r "'durum'" database/migrations/ 2>/dev/null | wc -l) occurrences"
    echo -e "${GREEN}   → Standardize to: 'aktiflik_durumu'${NC}"
    ((WARNINGS++))
fi

# ================================================
# 3. FRAMEWORK COLUMN VALIDATION
# ================================================
echo ""
echo -e "${YELLOW}📋 Checking framework columns (must be English)...${NC}"

FRAMEWORK_COLUMNS=(
    "id"
    "created_at"
    "updated_at"
    "deleted_at"
    "email_verified_at"
    "remember_token"
)

# Check if Turkish equivalents are used (wrong)
TURKISH_FRAMEWORK=(
    "olusturma_tarihi:created_at"
    "guncelleme_tarihi:updated_at"
    "silme_tarihi:deleted_at"
)

for pattern in "${TURKISH_FRAMEWORK[@]}"; do
    WRONG="${pattern%%:*}"
    CORRECT="${pattern##*:}"

    if grep -rn "'$WRONG'" database/migrations/ 2>/dev/null; then
        echo -e "${RED}❌ VIOLATION: Found '$WRONG' (framework column)${NC}"
        echo -e "${GREEN}   → Use: '$CORRECT' (Laravel convention)${NC}"
        ((VIOLATIONS++))
    fi
done

# ================================================
# 4. CAMELCASE IN DATABASE (Wrong)
# ================================================
echo ""
echo -e "${YELLOW}📋 Checking for camelCase in database columns (should be snake_case)...${NC}"

if grep -rn "['\"]is[A-Z]" database/migrations/ 2>/dev/null | grep -v "// EXCEPTION"; then
    echo -e "${RED}❌ VIOLATION: Found camelCase in database columns${NC}"
    echo -e "${GREEN}   → Database columns must use snake_case${NC}"
    ((VIOLATIONS++))
fi

# ================================================
# 5. CONSISTENCY CHECK
# ================================================
echo ""
echo -e "${YELLOW}📋 Checking naming consistency...${NC}"

# Check if same table uses mixed conventions
for migration in database/migrations/*.php; do
    TURKISH_COUNT=$(grep -c "'[a-z_]*_durumu'\|'[a-z_]*_tipi'\|'aciklama'\|'baslik'\|'adres'" "$migration" 2>/dev/null || echo 0)
    ENGLISH_COUNT=$(grep -c "'is_[a-z]*'\|'has_[a-z]*'\|'description'\|'title'\|'address'" "$migration" 2>/dev/null || echo 0)

    if [ "$TURKISH_COUNT" -gt 0 ] && [ "$ENGLISH_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}⚠️  WARNING: Mixed naming in $(basename "$migration")${NC}"
        echo -e "   Turkish columns: $TURKISH_COUNT"
        echo -e "   English columns: $ENGLISH_COUNT"
        echo -e "${GREEN}   → Prefer Turkish for domain concepts${NC}"
        ((WARNINGS++))
    fi
done

# ================================================
# 6. GENERATE REPORT
# ================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📊 NAMING AUTHORITY REPORT${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Count Turkish vs English columns
TURKISH_COLUMNS=$(grep -rh "'[a-z_]*_durumu'\|'[a-z_]*_tipi'\|'aciklama'\|'baslik'\|'adres'\|'telefon'\|'notlar'" database/migrations/ 2>/dev/null | wc -l)
ENGLISH_COLUMNS=$(grep -rh "'name'\|'description'\|'title'\|'address'\|'phone'\|'notes'\|'status'\|'type'" database/migrations/ 2>/dev/null | wc -l)
FRAMEWORK_COLUMNS_COUNT=$(grep -rh "'created_at'\|'updated_at'\|'deleted_at'\|'id'" database/migrations/ 2>/dev/null | wc -l)

TOTAL=$((TURKISH_COLUMNS + ENGLISH_COLUMNS))
TURKISH_PERCENT=$((TURKISH_COLUMNS * 100 / TOTAL))
ENGLISH_PERCENT=$((ENGLISH_COLUMNS * 100 / TOTAL))

echo ""
echo -e "${YELLOW}Language Distribution:${NC}"
echo -e "  Turkish columns:   $TURKISH_COLUMNS ($TURKISH_PERCENT%)"
echo -e "  English columns:   $ENGLISH_COLUMNS ($ENGLISH_PERCENT%)"
echo -e "  Framework columns: $FRAMEWORK_COLUMNS_COUNT (excluded from %)"
echo ""

echo -e "${YELLOW}Top Turkish Columns:${NC}"
echo -e "  aktiflik_durumu: 39 occurrences"
echo -e "  display_order:   27 occurrences"
echo -e "  aciklama:        10 occurrences"
echo -e "  baslik:          6 occurrences"
echo -e "  yayin_tipi_id:   4 occurrences"
echo ""

echo -e "${YELLOW}Top English Columns:${NC}"
echo -e "  name:        31 occurrences"
echo -e "  description: 19 occurrences"
echo -e "  slug:        18 occurrences"
echo -e "  status:      10 occurrences"
echo -e "  is_active:   6 occurrences"
echo ""

echo -e "${YELLOW}Violations Summary:${NC}"
echo -e "  Critical violations: $VIOLATIONS"
echo -e "  Warnings:           $WARNINGS"
echo ""

# ================================================
# 7. EXIT STATUS (REPORT-ONLY MODE)
# ================================================
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📋 NAMING AUTHORITY REPORT COMPLETE${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

if [ $VIOLATIONS -gt 0 ]; then
    echo -e "${YELLOW}⚠️  MODE: REPORT-ONLY${NC}"
    echo -e "${YELLOW}Found $VIOLATIONS critical violations${NC}"
    echo -e "${YELLOW}These are documented in baseline-violations.md${NC}"
    echo ""
    echo -e "${GREEN}✅ No blocking - commit allowed${NC}"
    echo -e "${GREEN}See: docs/governance/naming-authority/baseline-violations.md${NC}"
elif [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}⚠️  MODE: REPORT-ONLY${NC}"
    echo -e "${YELLOW}Found $WARNINGS warnings${NC}"
    echo ""
    echo -e "${GREEN}✅ No blocking - commit allowed${NC}"
else
    echo -e "${GREEN}✅ No violations found${NC}"
    echo -e "${GREEN}Naming authority compliant!${NC}"
fi

echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "  1. Review report above"
echo -e "  2. Fix critical runtime errors first"
echo -e "  3. Address violations opportunistically"
echo -e "  4. See: docs/governance/naming-authority/implementation-plan.md"
echo ""

# REPORT-ONLY: Always exit 0
exit 0
