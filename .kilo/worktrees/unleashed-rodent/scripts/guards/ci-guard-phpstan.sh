#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────
# ci-guard-phpstan.sh — PHPStan static analysis guard
#
# Uses baseline to track pre-existing errors.
# Exits 0 if no NEW errors beyond baseline.
# Exits 1 if new violations introduced.
# ──────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "$PROJECT_ROOT"

PHPSTAN_BIN="./vendor/bin/phpstan"
BASELINE_FILE="phpstan-baseline.neon"
NEON_FILE="phpstan.neon"

if [ ! -x "$PHPSTAN_BIN" ]; then
    echo "❌ PHPStan binary not found at $PHPSTAN_BIN"
    exit 1
fi

# If baseline exists, run with baseline (only new errors fail)
if [ -f "$BASELINE_FILE" ]; then
    echo "🔍 Running PHPStan with baseline (only new errors will fail)..."
    $PHPSTAN_BIN analyse app --no-progress --memory-limit=512M 2>&1
    exit $?
fi

# No baseline — run analysis and report
echo "⚠️  No PHPStan baseline found. Running full analysis..."
echo "   Generate baseline with: ./vendor/bin/phpstan analyse app --generate-baseline"

ERRORS=$($PHPSTAN_BIN analyse app --no-progress --memory-limit=512M --error-format=raw 2>&1 | grep -c "^" || true)

if [ "$ERRORS" -eq 0 ]; then
    echo "✅ PHPStan: 0 errors"
    exit 0
else
    echo "📊 PHPStan: $ERRORS pre-existing errors found (no baseline to compare against)"
    echo "   This is pre-existing technical debt. Creating baseline is recommended."
    exit 1
fi
