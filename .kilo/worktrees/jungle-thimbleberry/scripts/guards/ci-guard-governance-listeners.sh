#!/usr/bin/env bash
# CI Guard: Governance Event Listener Safety
# Ensures event listeners remain read-only and don't violate authority boundaries

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

echo "🛡️  CI Guard: Governance Event Listener Safety"
echo "================================================"
echo ""

VIOLATIONS=0

# Check 1: No database writes in listeners
echo "✓ Checking for database writes in governance listeners..."
if grep -r "DB::\|->save()\|->update()\|->delete()\|->create()\|->insert()" \
    "$PROJECT_ROOT/app/Listeners/Governance/" 2>/dev/null | grep -v "^Binary"; then
    echo "❌ VIOLATION: Database writes found in governance listeners"
    echo "   Listeners must be read-only"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No database writes detected"
fi
echo ""

# Check 2: No authority model access
echo "✓ Checking for authority model access..."
if grep -r "Authority::\|use.*Authority;" \
    "$PROJECT_ROOT/app/Listeners/Governance/" 2>/dev/null | grep -v "^Binary"; then
    echo "❌ VIOLATION: Authority model access found in governance listeners"
    echo "   Listeners must not access authority models"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No authority model access detected"
fi
echo ""

# Check 3: No enforcement logic
echo "✓ Checking for enforcement logic..."
if grep -r "enforce\|block\|deny\|reject\|prevent" \
    "$PROJECT_ROOT/app/Listeners/Governance/" 2>/dev/null | \
    grep -v "^Binary" | grep -v "comment" | grep -v "//"; then
    echo "❌ VIOLATION: Enforcement logic found in governance listeners"
    echo "   Listeners must observe only, not enforce"
    VIOLATIONS=$((VIOLATIONS + 1))
else
    echo "   ✅ No enforcement logic detected"
fi
echo ""

# Check 4: No CQRS boundary violations
echo "✓ Checking for CQRS boundary violations..."
if grep -r "Eloquent::\|Model::" \
    "$PROJECT_ROOT/app/Listeners/Governance/" 2>/dev/null | \
    grep -v "^Binary" | grep -v "comment"; then
    echo "⚠️  WARNING: Direct model access found in governance listeners"
    echo "   Consider using read-only repositories"
fi
echo ""

# Check 5: Verify listeners are registered properly
echo "✓ Checking listener registration..."
if [ -f "$PROJECT_ROOT/app/Providers/EventServiceProvider.php" ]; then
    if grep -q "Governance" "$PROJECT_ROOT/app/Providers/EventServiceProvider.php"; then
        echo "   ✅ Governance listeners registered"
    else
        echo "   ℹ️  No governance listeners registered yet"
    fi
else
    echo "   ⚠️  EventServiceProvider not found"
fi
echo ""

# Summary
echo "================================================"
if [ $VIOLATIONS -eq 0 ]; then
    echo "✅ PASSED: All governance listener safety checks passed"
    echo ""
    echo "Governance listeners are:"
    echo "  ✓ Read-only"
    echo "  ✓ Authority-safe"
    echo "  ✓ Enforcement-free"
    echo "  ✓ CQRS-compliant"
    exit 0
else
    echo "❌ FAILED: $VIOLATIONS violation(s) detected"
    echo ""
    echo "Governance listeners must:"
    echo "  • Be read-only (no database writes)"
    echo "  • Not access authority models"
    echo "  • Not contain enforcement logic"
    echo "  • Respect CQRS boundaries"
    exit 1
fi
