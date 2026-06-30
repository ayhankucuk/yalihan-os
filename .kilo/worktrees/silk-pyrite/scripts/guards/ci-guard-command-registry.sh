#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ G1: Command Registry Guard (Detect/Report Phase)
# ═══════════════════════════════════════════════════════════════════════════
#
# PURPOSE:
#   Validate that commands called in scripts are registered in artisan
#
# SCOPE:
#   - Phase A: Detect/Report (NO build blocker)
#   - Snapshot: php artisan list → .sab/command-registry-snapshot.json
#   - Canonical: .sab/canonical-command-manifest.json
#   - Validate: quality-gate.sh command calls
#
# EXIT CODES:
#   0 = All commands valid
#   1 = Unregistered commands detected (report only, non-blocking)
#
# GOVERNANCE:
#   - SSOT: command registry
#   - Authority: .sab/authority.json
#   - Blocking: false (Phase A)
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SNAPSHOT_FILE=".sab/command-registry-snapshot.json"
CANONICAL_FILE=".sab/canonical-command-manifest.json"
QUALITY_GATE_SCRIPT="scripts/quality-gate.sh"

echo "════════════════════════════════════════════════════════════════"
echo "🛡️  G1: Command Registry Guard (Detect/Report)"
echo "════════════════════════════════════════════════════════════════"
echo ""

# ─────────────────────────────────────────────────────────────────────────
# STEP 1: Refresh command registry snapshot
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}📸 Refreshing command registry snapshot...${NC}"
if php scripts/extract-command-registry.php > "$SNAPSHOT_FILE" 2>&1; then
    TOTAL_COMMANDS=$(python3 -c "import json; print(json.load(open('$SNAPSHOT_FILE'))['total'])")
    echo -e "${GREEN}✅ Snapshot refreshed: $TOTAL_COMMANDS commands${NC}"
else
    echo -e "${RED}❌ Failed to refresh snapshot${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────────────────
# STEP 2: Extract commands from quality-gate.sh
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔍 Extracting commands from quality-gate.sh...${NC}"

CALLED_COMMANDS=$(grep -E "php artisan [a-z:]+" "$QUALITY_GATE_SCRIPT" | \
    grep -v "^#" | \
    sed 's/.*php artisan //' | \
    sed 's/ .*//' | \
    sed 's/;$//' | \
    sort -u)

CALLED_COUNT=$(echo "$CALLED_COMMANDS" | wc -l | tr -d ' ')
echo -e "${GREEN}✅ Found $CALLED_COUNT unique command calls${NC}"
echo ""

# ─────────────────────────────────────────────────────────────────────────
# STEP 3: Validate each command exists in registry
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}🔎 Validating command registry...${NC}"

UNREGISTERED=()
VALID_COUNT=0

while IFS= read -r cmd; do
    if [ -z "$cmd" ]; then
        continue
    fi

    # Check if command exists in snapshot
    if python3 -c "
import json, sys
data = json.load(open('$SNAPSHOT_FILE'))
exists = any(c['name'] == '$cmd' for c in data['commands'])
sys.exit(0 if exists else 1)
" 2>/dev/null; then
        VALID_COUNT=$((VALID_COUNT + 1))
        echo -e "  ${GREEN}✓${NC} $cmd"
    else
        UNREGISTERED+=("$cmd")
        echo -e "  ${RED}✗${NC} $cmd ${YELLOW}(NOT REGISTERED)${NC}"
    fi
done <<< "$CALLED_COMMANDS"

echo ""

# ─────────────────────────────────────────────────────────────────────────
# STEP 4: Detect added/removed/renamed commands (canonical diff)
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}📊 Comparing with canonical manifest...${NC}"

if [ ! -f "$CANONICAL_FILE" ]; then
    echo -e "${YELLOW}⚠️  Canonical manifest not found - skipping diff${NC}"
else
    # Extract canonical quality_gate_commands
    CANONICAL_COMMANDS=$(python3 -c "
import json
data = json.load(open('$CANONICAL_FILE'))
for cmd in data['quality_gate_commands']:
    print(cmd)
" | sort)

    # Compare
    CURRENT_COMMANDS=$(echo "$CALLED_COMMANDS" | sort)

    # Detect added commands
    ADDED=$(comm -13 <(echo "$CANONICAL_COMMANDS") <(echo "$CURRENT_COMMANDS"))
    if [ -n "$ADDED" ]; then
        echo -e "${YELLOW}➕ Added commands:${NC}"
        echo "$ADDED" | while read -r cmd; do
            echo -e "  ${YELLOW}+${NC} $cmd"
        done
    fi

    # Detect removed commands
    REMOVED=$(comm -23 <(echo "$CANONICAL_COMMANDS") <(echo "$CURRENT_COMMANDS"))
    if [ -n "$REMOVED" ]; then
        echo -e "${YELLOW}➖ Removed commands:${NC}"
        echo "$REMOVED" | while read -r cmd; do
            echo -e "  ${YELLOW}-${NC} $cmd"
        done
    fi

    if [ -z "$ADDED" ] && [ -z "$REMOVED" ]; then
        echo -e "${GREEN}✅ No drift from canonical manifest${NC}"
    fi
fi

echo ""

# ─────────────────────────────────────────────────────────────────────────
# STEP 5: Report summary
# ─────────────────────────────────────────────────────────────────────────
echo "════════════════════════════════════════════════════════════════"
echo "📋 SUMMARY"
echo "════════════════════════════════════════════════════════════════"
echo "Total commands in registry: $TOTAL_COMMANDS"
echo "Commands called in quality-gate.sh: $CALLED_COUNT"
echo "Valid commands: $VALID_COUNT"
echo "Unregistered commands: ${#UNREGISTERED[@]}"
echo ""

if [ ${#UNREGISTERED[@]} -gt 0 ]; then
    echo -e "${RED}❌ UNREGISTERED COMMANDS DETECTED:${NC}"
    for cmd in "${UNREGISTERED[@]}"; do
        echo -e "  ${RED}✗${NC} $cmd"
    done
    echo ""
    echo -e "${YELLOW}⚠️  Phase A: DETECT/REPORT mode (non-blocking)${NC}"
    echo -e "${YELLOW}⚠️  Fix: Register command in app/Console/Kernel.php or remove from script${NC}"
    echo ""
    echo -e "${BLUE}ℹ️  Exit code: 1 (report only, CI continues)${NC}"
    exit 1
else
    echo -e "${GREEN}✅ All commands are registered${NC}"
    echo -e "${GREEN}✅ Command registry guard PASSED${NC}"
    exit 0
fi
