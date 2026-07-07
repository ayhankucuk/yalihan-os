#!/bin/bash

# Antigravity AI OS — Layout & Skeleton Validator
# Path: scripts/tools/antigravity-layout-check.sh
# Purpose: Ensures Blade views use the correct layout (@extends) based on
#          the file's location in the project. Prevents Task 2/24 type
#          layout mismatch errors.
# Usage:
#   ./scripts/tools/antigravity-layout-check.sh                    # Scan all modified blade files
#   ./scripts/tools/antigravity-layout-check.sh <file.blade.php>   # Scan specific file

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${BLUE}${BOLD}📐 Antigravity Layout Validator${NC}"
echo -e "=================================="

# Layout rules based on directory location
# frontend/**  → layouts.frontend
# admin/**     → layouts.admin  (or layouts.app for admin panel)
# public/**    → layouts.frontend
# auth/**      → layouts.guest or layouts.app

VIOLATIONS=0
CHECKED=0

check_file() {
    local FILE="$1"
    
    if [ ! -f "$FILE" ]; then
        return
    fi
    
    # Extract @extends directive
    EXTENDS=$(grep -m1 "@extends" "$FILE" | sed "s/.*@extends('\([^']*\)').*/\1/" | sed 's/.*@extends("\([^"]*\)").*/\1/')
    
    if [ -z "$EXTENDS" ]; then
        # File doesn't use @extends (could be a component, partial, or section)
        return
    fi
    
    ((CHECKED++))
    
    # Determine expected layout based on file path
    EXPECTED=""
    REASON=""
    
    if [[ "$FILE" == *resources/views/frontend/dynamic-form/* ]]; then
        # Legacy exception: dynamic-form is an admin designer tool placed in frontend folder
        if [[ "$EXTENDS" == "admin.layouts.admin" ]]; then
            echo -e "  ${GREEN}✅ ${FILE}${NC} → @extends('${EXTENDS}') [legacy exception OK]"
            return
        fi
        EXPECTED="admin.layouts.admin"
        REASON="Legacy exception: Files in frontend/dynamic-form/ must use admin.layouts.admin"
    elif [[ "$FILE" == *resources/views/frontend/* ]]; then
        EXPECTED="layouts.frontend"
        REASON="Files in frontend/ must use layouts.frontend"
    elif [[ "$FILE" == *resources/views/public/* ]]; then
        EXPECTED="layouts.frontend"
        REASON="Files in public/ must use layouts.frontend"
    elif [[ "$FILE" == *resources/views/admin/* ]]; then
        # Admin can use admin.layouts.admin, layouts.admin, or layouts.app
        if [[ "$EXTENDS" == "admin.layouts.admin" ]] || [[ "$EXTENDS" == "layouts.admin" ]] || [[ "$EXTENDS" == "layouts.app" ]]; then
            echo -e "  ${GREEN}✅ ${FILE}${NC} → @extends('${EXTENDS}') [admin OK]"
            return
        fi
        EXPECTED="admin.layouts.admin (or layouts.admin / layouts.app)"
        REASON="Files in admin/ must use admin.layouts.admin, layouts.admin or layouts.app"
    elif [[ "$FILE" == *resources/views/auth/* ]]; then
        # Auth can use layouts.guest or layouts.app
        if [[ "$EXTENDS" == "layouts.guest" ]] || [[ "$EXTENDS" == "layouts.app" ]]; then
            echo -e "  ${GREEN}✅ ${FILE}${NC} → @extends('${EXTENDS}') [auth OK]"
            return
        fi
        EXPECTED="layouts.guest (or layouts.app)"
        REASON="Files in auth/ must use layouts.guest or layouts.app"
    else
        # Unknown directory, just report
        echo -e "  ${YELLOW}ℹ️ ${FILE}${NC} → @extends('${EXTENDS}') [no rule defined for this path]"
        return
    fi
    
    if [ "$EXTENDS" = "$EXPECTED" ]; then
        echo -e "  ${GREEN}✅ ${FILE}${NC} → @extends('${EXTENDS}')"
    else
        echo -e "  ${RED}❌ ${FILE}${NC}"
        echo -e "     Current:  @extends('${EXTENDS}')"
        echo -e "     Expected: @extends('${EXPECTED}')"
        echo -e "     ${YELLOW}Reason: ${REASON}${NC}"
        ((VIOLATIONS++))
    fi
}

if [ $# -gt 0 ]; then
    # Specific file(s) provided
    for f in "$@"; do
        check_file "$f"
    done
else
    # Scan modified blade files
    echo -e "Scanning modified Blade files..."
    echo ""
    
    BLADE_FILES=$(git status --porcelain | awk '{print $2}' | grep '\.blade\.php$')
    
    if [ -z "$BLADE_FILES" ]; then
        echo -e "${YELLOW}ℹ️ No modified blade files. Scanning all frontend views instead...${NC}"
        BLADE_FILES=$(find resources/views/frontend resources/views/public -name "*.blade.php" 2>/dev/null)
    fi
    
    for f in $BLADE_FILES; do
        check_file "$f"
    done
fi

echo ""
echo -e "=================================="
if [ $VIOLATIONS -gt 0 ]; then
    echo -e "${RED}${BOLD}❌ FAIL: ${VIOLATIONS} layout mismatch(es) found in ${CHECKED} checked files!${NC}"
    exit 1
elif [ $CHECKED -eq 0 ]; then
    echo -e "${YELLOW}ℹ️ No @extends directives found to validate.${NC}"
    exit 0
else
    echo -e "${GREEN}${BOLD}✅ PASS: All ${CHECKED} layout references are correct.${NC}"
    exit 0
fi
