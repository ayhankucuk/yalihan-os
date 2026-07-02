#!/bin/bash

# Antigravity AI OS — Schema-First Validator
# Path: scripts/tools/antigravity-schema-check.sh
# Purpose: Before writing any query or model code, validates that referenced
#          tables and columns actually exist in the live database.
# Usage:
#   ./scripts/tools/antigravity-schema-check.sh <table_name> [column1 column2 ...]
#   Examples:
#     ./scripts/tools/antigravity-schema-check.sh ilanlar
#     ./scripts/tools/antigravity-schema-check.sh ilanlar yayin_durumu fiyat il_adi

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
BOLD='\033[1m'

TABLE_NAME="$1"
shift
COLUMNS=("$@")

if [ -z "$TABLE_NAME" ]; then
    echo -e "${RED}❌ Usage: $0 <table_name> [column1 column2 ...]${NC}"
    echo -e "  Example: $0 ilanlar yayin_durumu fiyat"
    exit 1
fi

echo -e "${BLUE}${BOLD}🔍 Antigravity Schema-First Validator${NC}"
echo -e "========================================="
echo -e "Checking table: ${BOLD}${TABLE_NAME}${NC}"
echo ""

# 1. Check if the table exists
TABLE_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('${TABLE_NAME}') ? 'YES' : 'NO';" 2>/dev/null | tail -1)

if [ "$TABLE_EXISTS" != "YES" ]; then
    echo -e "${RED}❌ TABLE '${TABLE_NAME}' DOES NOT EXIST in the database!${NC}"
    echo -e "${YELLOW}💡 Run 'php artisan db:table' to list available tables.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Table '${TABLE_NAME}' exists.${NC}"

# 2. Get full column listing
echo ""
echo -e "${BLUE}📋 Full column listing:${NC}"
php artisan db:table "$TABLE_NAME" 2>/dev/null

# 3. If specific columns were requested, validate each one
if [ ${#COLUMNS[@]} -gt 0 ]; then
    echo ""
    echo -e "${BLUE}🔎 Validating requested columns:${NC}"
    
    FAILED=0
    for COL in "${COLUMNS[@]}"; do
        COL_EXISTS=$(php artisan tinker --execute="echo Schema::hasColumn('${TABLE_NAME}', '${COL}') ? 'YES' : 'NO';" 2>/dev/null | tail -1)
        
        if [ "$COL_EXISTS" = "YES" ]; then
            echo -e "  ${GREEN}✅ ${COL}${NC}"
        else
            echo -e "  ${RED}❌ ${COL} — DOES NOT EXIST!${NC}"
            
            # Check if this is a forbidden field name from Context7
            case "$COL" in
                status)
                    echo -e "    ${YELLOW}💡 Context7: 'status' is FORBIDDEN. Use 'yayin_durumu' instead.${NC}"
                    ;;
                active|is_active)
                    echo -e "    ${YELLOW}💡 Context7: '${COL}' is FORBIDDEN. Use 'aktiflik_durumu' instead.${NC}"
                    ;;
                order|sort_order)
                    echo -e "    ${YELLOW}💡 Context7: '${COL}' is FORBIDDEN. Use 'display_order' instead.${NC}"
                    ;;
                featured)
                    echo -e "    ${YELLOW}💡 Context7: 'featured' is FORBIDDEN. Use 'one_cikan' instead.${NC}"
                    ;;
                type)
                    echo -e "    ${YELLOW}💡 Context7: 'type' is FORBIDDEN. Use 'tip' / 'tur' / 'kategori' instead.${NC}"
                    ;;
                city|sehir)
                    echo -e "    ${YELLOW}💡 Context7: '${COL}' is FORBIDDEN. Use 'il' / 'il_adi' instead.${NC}"
                    ;;
            esac
            
            ((FAILED++))
        fi
    done
    
    echo ""
    if [ $FAILED -gt 0 ]; then
        echo -e "${RED}${BOLD}❌ FAIL: ${FAILED} column(s) do not exist! DO NOT write code referencing them.${NC}"
        exit 1
    else
        echo -e "${GREEN}${BOLD}✅ PASS: All ${#COLUMNS[@]} columns verified in '${TABLE_NAME}'.${NC}"
    fi
fi

exit 0
