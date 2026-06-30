#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# G1 Command Registry Guard - False Positive Test Suite
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

GUARD_SCRIPT="scripts/ci-guard-command-registry.sh"
TEST_SCRIPT="/tmp/test-quality-gate.sh"
PASSED=0
FAILED=0

echo "════════════════════════════════════════════════════════════════"
echo "🧪 G1 Command Registry Guard - False Positive Test Suite"
echo "════════════════════════════════════════════════════════════════"
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 1: Valid commands should pass
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 1: Valid registered commands${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
php artisan guard:schema
php artisan sab:integrity-scan
php artisan bekci:wizard-contract
EOF

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ PASS: Valid commands detected correctly${NC}"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}❌ FAIL: Valid commands incorrectly flagged${NC}"
    FAILED=$((FAILED + 1))
fi
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 2: Comments should be ignored
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 2: Commented commands should be ignored${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
# php artisan guard:nonexistent
php artisan guard:schema
EOF

# Temporarily replace quality-gate.sh
BACKUP_QG="/tmp/quality-gate-backup.sh"
cp scripts/quality-gate.sh "$BACKUP_QG"
cp "$TEST_SCRIPT" scripts/quality-gate.sh

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ PASS: Comments ignored correctly${NC}"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}❌ FAIL: Comments not ignored${NC}"
    FAILED=$((FAILED + 1))
fi

# Restore
cp "$BACKUP_QG" scripts/quality-gate.sh
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 3: Unregistered command should be detected
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 3: Unregistered command detection${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
php artisan guard:schema
php artisan guard:this-command-does-not-exist-xyz
EOF

cp scripts/quality-gate.sh "$BACKUP_QG"
cp "$TEST_SCRIPT" scripts/quality-gate.sh

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${RED}❌ FAIL: Unregistered command not detected${NC}"
    FAILED=$((FAILED + 1))
else
    echo -e "${GREEN}✅ PASS: Unregistered command detected correctly${NC}"
    PASSED=$((PASSED + 1))
fi

cp "$BACKUP_QG" scripts/quality-gate.sh
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 4: Multiple spaces/formatting should not affect detection
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 4: Formatting variations${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
php    artisan    guard:schema
php artisan guard:schema --option
php artisan guard:schema | tee log.txt
EOF

cp scripts/quality-gate.sh "$BACKUP_QG"
cp "$TEST_SCRIPT" scripts/quality-gate.sh

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ PASS: Formatting variations handled correctly${NC}"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}❌ FAIL: Formatting variations caused false positive${NC}"
    FAILED=$((FAILED + 1))
fi

cp "$BACKUP_QG" scripts/quality-gate.sh
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 5: Semicolon termination should be handled
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 5: Semicolon termination${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
php artisan guard:schema;
php artisan sab:integrity-scan; echo "done"
EOF

cp scripts/quality-gate.sh "$BACKUP_QG"
cp "$TEST_SCRIPT" scripts/quality-gate.sh

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ PASS: Semicolons handled correctly${NC}"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}❌ FAIL: Semicolons caused false positive${NC}"
    FAILED=$((FAILED + 1))
fi

cp "$BACKUP_QG" scripts/quality-gate.sh
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Test 6: Conditional execution should be parsed
# ─────────────────────────────────────────────────────────────────────────
echo -e "${BLUE}Test 6: Conditional execution${NC}"
cat > "$TEST_SCRIPT" << 'EOF'
#!/bin/bash
if php artisan guard:schema; then
    echo "passed"
fi
EOF

cp scripts/quality-gate.sh "$BACKUP_QG"
cp "$TEST_SCRIPT" scripts/quality-gate.sh

if bash "$GUARD_SCRIPT" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ PASS: Conditional execution handled correctly${NC}"
    PASSED=$((PASSED + 1))
else
    echo -e "${RED}❌ FAIL: Conditional execution caused false positive${NC}"
    FAILED=$((FAILED + 1))
fi

cp "$BACKUP_QG" scripts/quality-gate.sh
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Cleanup
# ─────────────────────────────────────────────────────────────────────────
rm -f "$TEST_SCRIPT" "$BACKUP_QG"

# ─────────────────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────────────────
echo "════════════════════════════════════════════════════════════════"
echo "📊 TEST SUMMARY"
echo "════════════════════════════════════════════════════════════════"
echo "Total tests: $((PASSED + FAILED))"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed - No false positives detected${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed - Review false positive handling${NC}"
    exit 1
fi
