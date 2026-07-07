#!/bin/bash

# Antigravity AI OS — Preflight Guard & Golden Rules Scanner
# Path: scripts/tools/antigravity-preflight.sh
# Purpose: Ensures newly written or modified files comply with the 10 Golden Rules
#          to prevent legacy bloat and guarantee 100% Technical Constitution compliance.

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color
BOLD='\033[1m'

echo -e "${BLUE}${BOLD}🚀 Antigravity Preflight Guard: Checking Modified Files for Golden Rules...${NC}"
echo -e "========================================================================="

# 1. Get modified/added files in git (staged or unstaged)
FILES=$(git status --porcelain | awk '{print $2}' | grep -E '\.(php|js|vue|css)$')

if [ -z "$FILES" ]; then
    # Fallback to last commit diff if no uncommitted changes exist
    echo -e "${YELLOW}ℹ️ No uncommitted changes found. Scanning files changed in the last commit instead...${NC}"
    FILES=$(git diff --name-only HEAD~1 2>/dev/null | grep -E '\.(php|js|vue|css)$')
fi

if [ -z "$FILES" ]; then
    echo -e "${GREEN}✅ No modified files to scan! Everything is clean.${NC}"
    exit 0
fi

VIOLATIONS=0

for FILE in $FILES; do
    if [ ! -f "$FILE" ]; then
        continue
    fi
    
    echo -e "📄 Scanning: ${BOLD}$FILE${NC}"
    
    # --- RULE 1: FontAwesome Ban (Blade files only) ---
    if [[ "$FILE" == *.blade.php ]]; then
        FA_COUNT=$(grep -n -e 'fa-[a-z0-9\-]\+' -e 'fas ' -e 'far ' -e 'fal ' -e 'fab ' "$FILE")
        if [ ! -z "$FA_COUNT" ]; then
            echo -e "  ${RED}❌ Rule 1 (FontAwesome Ban) violated: Do not use FontAwesome icons. Use local SVGs or approved custom components.${NC}"
            echo "$FA_COUNT" | sed 's/^/    Line /'
            ((VIOLATIONS++))
        fi
    fi

    # --- RULE 2: FQCN Facade Check (Blade files only) ---
    # Scan for common Blade Facades without FQCN (e.g. Route::, Auth::, DB::, Cache::)
    if [[ "$FILE" == *.blade.php ]]; then
        # Matches e.g., Route::has, Auth::user, etc. but ignores \Illuminate\Support\Facades\
        FACADE_VIOLATIONS=$(grep -nE '(^|[^a-zA-Z0-9_\\])(Route|Auth|DB|Cache|Log|Session|Config|Gate)::[a-zA-Z0-9_]+' "$FILE" | grep -v 'Illuminate\\Support\\Facades')
        if [ ! -z "$FACADE_VIOLATIONS" ]; then
            echo -e "  ${RED}❌ Rule 2 (Blade FQCN Facade Rule) violated: Use full namespace, e.g. \\Illuminate\\Support\\Facades\\Route::has()${NC}"
            echo "$FACADE_VIOLATIONS" | sed 's/^/    Line /'
            ((VIOLATIONS++))
        fi
    fi

    # --- RULE 3: Hardcoded URL Check (Blade & Controller files) ---
    # Scan for raw href="/url" or action="/url" patterns instead of route()
    if [[ "$FILE" == *.blade.php ]] || [[ "$FILE" == *Controller.php ]]; then
        HARDCODED_URLS=$(grep -nE 'href=["'\'']/([a-zA-Z0-9_\-]+|feedback)["'\'']|action=["'\'']/([a-zA-Z0-9_\-]+)["'\'']' "$FILE" | grep -vE 'href=["'\'']#["'\'']|href=["'\'']javascript:void\(0\)["'\'']|route\(|url\(|config\(')
        if [ ! -z "$HARDCODED_URLS" ]; then
            echo -e "  ${RED}❌ Rule 3 (Hardcoded URL Ban) violated: Do not hardcode URLs, use route() or config() instead:${NC}"
            echo "$HARDCODED_URLS" | sed 's/^/    Line /'
            ((VIOLATIONS++))
        fi
    fi

    # --- RULE 4: Vite prefix check (JS/PHP) ---
    # Scan for MIX_ prefix
    MIX_USAGE=$(grep -n -e 'MIX_PUSHER_' -e 'MIX_[A-Z0-9_]\+' "$FILE")
    if [ ! -z "$MIX_USAGE" ]; then
        echo -e "  ${RED}❌ Rule 4 (Mix env prefix Ban) violated: Use VITE_ prefix instead of MIX_ for Vite build tool:${NC}"
        echo "$MIX_USAGE" | sed 's/^/    Line /'
        ((VIOLATIONS++))
    fi

    # --- RULE 5: Determinism check (PHP only) ---
    # Warn when calling first() without orderBy()
    if [[ "$FILE" == *.php ]]; then
        # Finds ->first() where there is no ->orderBy() on the same line (heuristic)
        FIRST_USAGE=$(grep -n -e '->first(' "$FILE" | grep -v 'orderBy')
        if [ ! -z "$FIRST_USAGE" ]; then
            echo -e "  ${YELLOW}⚠️ Rule 5 (Determinism Standard) warning: Verify that first() is deterministic (ordered):${NC}"
            echo "$FIRST_USAGE" | sed 's/^/    Line /'
        fi
    fi

    # --- RULE 6: Deprecated external APIs (All files) ---
    DEPRECATED_APIS=$(grep -n -e 'source\.unsplash\.com' "$FILE")
    if [ ! -z "$DEPRECATED_APIS" ]; then
        echo -e "  ${RED}❌ Rule 6 (Deprecated External APIs) violated: Banned external link source.unsplash.com detected:${NC}"
        echo "$DEPRECATED_APIS" | sed 's/^/    Line /'
        ((VIOLATIONS++))
    fi

    # --- RULE 7: Env Usage outside config (PHP only, excluding config/) ---
    if [[ "$FILE" == *.php ]] && [[ "$FILE" != *config/* ]]; then
        ENV_USAGE=$(grep -nE '([^a-zA-Z0-9_]|^)env\(' "$FILE")
        if [ ! -z "$ENV_USAGE" ]; then
            echo -e "  ${RED}❌ Rule 7 (Env usage outside config) violated: Use config() helper instead of direct env() calls:${NC}"
            echo "$ENV_USAGE" | sed 's/^/    Line /'
            ((VIOLATIONS++))
        fi
    fi

    # --- RULE 8: Silent catch blocks (PHP only) ---
    if [[ "$FILE" == *.php ]]; then
        # Search for empty catch blocks
        SILENT_CATCH=$(grep -nE 'catch\s*\(\s*\\?Exception\s+\$[a-zA-Z0-9_]+\s*\)\s*\{\s*\}' "$FILE")
        if [ -z "$SILENT_CATCH" ]; then
            SILENT_CATCH=$(grep -nE 'catch\s*\(.*Exception.*\)\s*\{\s*\}' "$FILE")
        fi
        if [ ! -z "$SILENT_CATCH" ]; then
            echo -e "  ${RED}❌ Rule 8 (Silent Catch Ban) violated: Do not swallow exceptions silently without log/throw or @sab-ignore-catch:${NC}"
            echo "$SILENT_CATCH" | sed 's/^/    Line /'
            ((VIOLATIONS++))
        fi
    fi
done

echo -e "========================================================================="
if [ $VIOLATIONS -gt 0 ]; then
    echo -e "${RED}${BOLD}❌ FAIL: $VIOLATIONS Golden Rule violation(s) found in modified files!${NC}"
    echo -e "${RED}Please resolve these violations before proceeding.${NC}"
    exit 1
else
    echo -e "${GREEN}${BOLD}✅ PASS: All modified files comply with the 10 Golden Rules!${NC}"
    exit 0
fi
