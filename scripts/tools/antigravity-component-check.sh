#!/bin/bash

# Antigravity AI OS — Component & View Existence Checker
# Path: scripts/tools/antigravity-component-check.sh
# Purpose: Before referencing any Blade component, partial, or view in code,
#          this script validates it actually exists on disk.
# Usage:
#   ./scripts/tools/antigravity-component-check.sh <component-or-view-name> [...]
#   Examples:
#     ./scripts/tools/antigravity-component-check.sh frontend.tag
#     ./scripts/tools/antigravity-component-check.sh layouts.frontend components.icon
#     ./scripts/tools/antigravity-component-check.sh x-yaliihan.property-card

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
BOLD='\033[1m'

VIEWS_DIR="resources/views"

if [ $# -eq 0 ]; then
    echo -e "${RED}❌ Usage: $0 <view-or-component-name> [...]${NC}"
    echo -e "  Example: $0 layouts.frontend components.icon frontend.scripts.ai-search"
    exit 1
fi

echo -e "${BLUE}${BOLD}🧩 Antigravity Component & View Existence Checker${NC}"
echo -e "======================================================"

FAILED=0
CHECKED=0

for NAME in "$@"; do
    ((CHECKED++))
    
    # Detect if it's an x-component reference (e.g. x-yaliihan.property-card)
    if [[ "$NAME" == x-* ]]; then
        # x-yaliihan.property-card -> components/yaliihan/property-card.blade.php
        COMPONENT_PATH=$(echo "${NAME#x-}" | sed 's/\./\//g')
        FULL_PATH="${VIEWS_DIR}/components/${COMPONENT_PATH}.blade.php"
        
        if [ -f "$FULL_PATH" ]; then
            echo -e "  ${GREEN}✅ <${NAME}> → ${FULL_PATH}${NC}"
        else
            echo -e "  ${RED}❌ <${NAME}> → ${FULL_PATH} DOES NOT EXIST!${NC}"
            
            # Suggest closest match
            PARENT_DIR=$(dirname "$FULL_PATH")
            if [ -d "$PARENT_DIR" ]; then
                SUGGESTIONS=$(ls "$PARENT_DIR"/*.blade.php 2>/dev/null | head -5)
                if [ ! -z "$SUGGESTIONS" ]; then
                    echo -e "    ${YELLOW}💡 Available components in $(basename $PARENT_DIR)/:${NC}"
                    echo "$SUGGESTIONS" | while read f; do echo -e "       $(basename $f)"; done
                fi
            fi
            ((FAILED++))
        fi
    else
        # Standard dot-notation view (e.g. layouts.frontend)
        VIEW_PATH=$(echo "$NAME" | sed 's/\./\//g')
        FULL_PATH="${VIEWS_DIR}/${VIEW_PATH}.blade.php"
        
        if [ -f "$FULL_PATH" ]; then
            echo -e "  ${GREEN}✅ ${NAME} → ${FULL_PATH}${NC}"
        else
            echo -e "  ${RED}❌ ${NAME} → ${FULL_PATH} DOES NOT EXIST!${NC}"
            
            # Check if it's a directory (partial include scenario)
            DIR_PATH="${VIEWS_DIR}/${VIEW_PATH}"
            if [ -d "$DIR_PATH" ]; then
                echo -e "    ${YELLOW}💡 '${NAME}' is a DIRECTORY, not a file. Available views inside:${NC}"
                ls "$DIR_PATH"/*.blade.php 2>/dev/null | head -5 | while read f; do echo -e "       $(basename $f)"; done
            else
                # Suggest closest parent
                PARENT_DIR=$(dirname "$FULL_PATH")
                if [ -d "$PARENT_DIR" ]; then
                    SUGGESTIONS=$(ls "$PARENT_DIR"/*.blade.php 2>/dev/null | head -5)
                    if [ ! -z "$SUGGESTIONS" ]; then
                        echo -e "    ${YELLOW}💡 Available views in $(basename $PARENT_DIR)/:${NC}"
                        echo "$SUGGESTIONS" | while read f; do echo -e "       $(basename $f)"; done
                    fi
                fi
            fi
            ((FAILED++))
        fi
    fi
done

echo ""
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}${BOLD}❌ FAIL: ${FAILED}/${CHECKED} views/components DO NOT EXIST! DO NOT reference them in code.${NC}"
    exit 1
else
    echo -e "${GREEN}${BOLD}✅ PASS: All ${CHECKED} views/components verified on disk.${NC}"
    exit 0
fi
