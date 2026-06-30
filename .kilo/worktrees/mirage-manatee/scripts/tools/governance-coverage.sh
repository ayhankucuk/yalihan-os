#!/usr/bin/env bash
#
# H7 Problem Analyzer v1 — Coverage Launcher
#
# Deterministic coverage report generator for app/Support/Governance/Analyze
# and the governance:analyze artisan command. Uses phpunit.governance.xml
# override config so coverage payda is scoped to H7 sources only.
#
# Usage:
#   bash scripts/governance-coverage.sh                 # generate report
#   bash scripts/governance-coverage.sh --save-baseline # also overwrite baseline.txt
#
# Exit codes:
#   0 — success
#   1 — phpunit run failed
#   2 — no coverage driver installed (pcov, xdebug, or phpdbg)
#   3 — override config or required paths missing

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

PHP_BIN="${PHP_BIN:-/opt/homebrew/bin/php}"
PHPDBG_BIN="${PHPDBG_BIN:-/opt/homebrew/bin/phpdbg}"
OVERRIDE_CONFIG="phpunit.governance.xml"
REPORT_DIR="reports/coverage/governance"
TEXT_REPORT="$REPORT_DIR/text.txt"
BASELINE_FILE="$REPORT_DIR/baseline.txt"
SUMMARY_FILE="$REPORT_DIR/summary.txt"
RUNNER_KIND=""

SAVE_BASELINE=0
for arg in "$@"; do
    case "$arg" in
        --save-baseline) SAVE_BASELINE=1 ;;
        *) echo "Unknown arg: $arg" >&2; exit 3 ;;
    esac
done

# ---- Preflight checks --------------------------------------------------------

if [ ! -f "$OVERRIDE_CONFIG" ]; then
    echo "ERROR: Override config not found: $OVERRIDE_CONFIG" >&2
    exit 3
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "ERROR: PHP binary not executable: $PHP_BIN" >&2
    echo "Hint: export PHP_BIN=/path/to/php" >&2
    exit 3
fi

if "$PHP_BIN" -m | grep -qiE '^(pcov|xdebug)$'; then
    RUNNER_KIND="php"
elif [ -x "$PHPDBG_BIN" ]; then
    RUNNER_KIND="phpdbg"
else
    echo "ERROR: No coverage driver installed (pcov, xdebug, or phpdbg required)." >&2
    echo "  Install pcov:   pecl install pcov" >&2
    echo "  Install xdebug: pecl install xdebug" >&2
    echo "  Or use phpdbg:  export PHPDBG_BIN=/path/to/phpdbg" >&2
    exit 2
fi

mkdir -p "$REPORT_DIR"

# ---- Run phpunit with coverage -----------------------------------------------

echo "==> Running H7 governance coverage suite..."
set +e
if [ "$RUNNER_KIND" = "phpdbg" ]; then
    "$PHPDBG_BIN" -qrr vendor/bin/phpunit \
        -c "$OVERRIDE_CONFIG" \
        --testsuite=Governance \
        --coverage-text="$TEXT_REPORT" \
        --coverage-clover="$REPORT_DIR/clover.xml" \
        --coverage-html="$REPORT_DIR/html" \
        > "$REPORT_DIR/phpunit.stdout.txt" 2>&1
    PHPUNIT_EXIT=$?
else
    "$PHP_BIN" -d pcov.enabled=1 -d pcov.directory="$REPO_ROOT/app/Support/Governance/Analyze" \
        vendor/bin/phpunit \
        -c "$OVERRIDE_CONFIG" \
        --testsuite=Governance \
        --coverage-text="$TEXT_REPORT" \
        --coverage-clover="$REPORT_DIR/clover.xml" \
        --coverage-html="$REPORT_DIR/html" \
        > "$REPORT_DIR/phpunit.stdout.txt" 2>&1
    PHPUNIT_EXIT=$?
fi
set -e

if [ $PHPUNIT_EXIT -ne 0 ]; then
    echo "ERROR: phpunit exited with code $PHPUNIT_EXIT" >&2
    tail -30 "$REPORT_DIR/phpunit.stdout.txt" >&2
    exit 1
fi

# ---- Generate deterministic summary ------------------------------------------

if [ ! -f "$TEXT_REPORT" ]; then
    echo "ERROR: Expected text report not produced: $TEXT_REPORT" >&2
    exit 1
fi

GENERATED_AT="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
GIT_SHA="$(git rev-parse --short HEAD 2>/dev/null || echo 'nogit')"
TESTS_LINE="$(tail -60 "$REPORT_DIR/phpunit.stdout.txt" | grep -E '^(OK|Tests:)' | head -2 || true)"

{
    echo "# H7 Problem Analyzer — Coverage Summary"
    echo "# Generated: $GENERATED_AT"
    echo "# Git:       $GIT_SHA"
    echo "# Config:    $OVERRIDE_CONFIG"
    echo "# Scope:     app/Support/Governance/Analyze + GovernanceAnalyzeCommand.php"
    echo ""
    echo "## Test run"
    echo "$TESTS_LINE"
    echo ""
    echo "## Coverage (from $TEXT_REPORT)"
    # Extract the "Summary:" block from phpunit text report
    awk '/Summary:/,/^$/' "$TEXT_REPORT" | head -20
    echo ""
    echo "## Per-class coverage"
    # Extract class-level lines like: "  Foo\Bar\Baz"
    grep -E '^\s+Methods:|^\s+Lines:|^[A-Z].*\\.*$' "$TEXT_REPORT" | head -80 || true
} > "$SUMMARY_FILE"

echo "==> Summary written to: $SUMMARY_FILE"

if [ $SAVE_BASELINE -eq 1 ]; then
    cp "$SUMMARY_FILE" "$BASELINE_FILE"
    echo "==> Baseline updated: $BASELINE_FILE"
fi

# ---- Print summary + next steps ----------------------------------------------

echo ""
echo "================================================================"
cat "$SUMMARY_FILE"
echo "================================================================"
echo ""
echo "HTML report: $REPORT_DIR/html/index.html"
echo "Clover XML:  $REPORT_DIR/clover.xml"
[ $SAVE_BASELINE -eq 0 ] && echo "Tip: run with --save-baseline to lock current numbers."

exit 0
