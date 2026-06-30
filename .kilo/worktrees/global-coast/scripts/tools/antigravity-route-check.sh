#!/bin/bash

# Antigravity AI OS — Route Duplication & Existence Guard
# Path: scripts/tools/antigravity-route-check.sh
# Purpose: Validates route names and prevents duplicate route definitions.
# Usage:
#   ./scripts/tools/antigravity-route-check.sh --check <route.name>     # Check if a route exists
#   ./scripts/tools/antigravity-route-check.sh --duplicates             # Find duplicate routes
#   ./scripts/tools/antigravity-route-check.sh --list [filter]          # List routes (optional filter)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
BOLD='\033[1m'

ACTION="$1"
PARAM="$2"

echo -e "${BLUE}${BOLD}🛤️ Antigravity Route Guard${NC}"
echo -e "=============================="

case "$ACTION" in
    --check)
        if [ -z "$PARAM" ]; then
            echo -e "${RED}❌ Usage: $0 --check <route.name>${NC}"
            exit 1
        fi
        
        echo -e "Checking route: ${BOLD}${PARAM}${NC}"
        
        ROUTE_EXISTS=$(php artisan route:list --json 2>/dev/null | python3 -c "
import json, sys
routes = json.load(sys.stdin)
found = [r for r in routes if r.get('name') == '${PARAM}']
if found:
    r = found[0]
    print(f\"YES|{r.get('method','?')}|{r.get('uri','?')}|{r.get('action','?')}\")
else:
    print('NO')
" 2>/dev/null)
        
        if [[ "$ROUTE_EXISTS" == YES* ]]; then
            IFS='|' read -r _ METHOD URI ACTION_NAME <<< "$ROUTE_EXISTS"
            echo -e "${GREEN}✅ Route '${PARAM}' exists:${NC}"
            echo -e "   Method: ${METHOD}"
            echo -e "   URI:    ${URI}"
            echo -e "   Action: ${ACTION_NAME}"
        else
            echo -e "${RED}❌ Route '${PARAM}' DOES NOT EXIST!${NC}"
            echo -e "${YELLOW}💡 Run '$0 --list ${PARAM%%.*}' to find similar routes.${NC}"
            exit 1
        fi
        ;;
        
    --duplicates)
        echo -e "Scanning for duplicate route names..."
        echo ""
        
        DUPES=$(php artisan route:list --json 2>/dev/null | python3 -c "
import json, sys
from collections import Counter
routes = json.load(sys.stdin)
names = [r['name'] for r in routes if r.get('name')]
dupes = {name: count for name, count in Counter(names).items() if count > 1}
if dupes:
    for name, count in sorted(dupes.items()):
        print(f'{name}|{count}')
else:
    print('NONE')
" 2>/dev/null)
        
        if [ "$DUPES" = "NONE" ]; then
            echo -e "${GREEN}✅ No duplicate route names found!${NC}"
        else
            echo -e "${RED}❌ Duplicate routes detected:${NC}"
            echo ""
            echo -e "  ${BOLD}Route Name | Count${NC}"
            echo -e "  -------------------------"
            echo "$DUPES" | while IFS='|' read -r NAME COUNT; do
                echo -e "  ${RED}${NAME}${NC} | ${COUNT}x"
            done
            exit 1
        fi
        ;;
        
    --list)
        FILTER="${PARAM:-}"
        if [ -z "$FILTER" ]; then
            echo -e "Listing all named routes..."
            php artisan route:list --columns=method,uri,name,action 2>/dev/null | head -80
        else
            echo -e "Listing routes matching: ${BOLD}${FILTER}${NC}"
            php artisan route:list --columns=method,uri,name,action 2>/dev/null | grep -i "$FILTER" | head -40
        fi
        ;;
        
    *)
        echo -e "${YELLOW}Usage:${NC}"
        echo -e "  $0 --check <route.name>   Check if a named route exists"
        echo -e "  $0 --duplicates           Find duplicate route names"
        echo -e "  $0 --list [filter]        List routes with optional filter"
        exit 1
        ;;
esac

exit 0
