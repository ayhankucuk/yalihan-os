#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# CI-GATE-02: Service Locator Guard
# ═══════════════════════════════════════════════════════════════════════════
# Detects new service locator usage: app(), resolve(), App::make()
# Compares against baseline — FAIL if new usage found.
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
BASELINE_FILE="${PROJECT_ROOT}/scripts/guards/baselines/baseline-service-locator.txt"
ALLOWLIST_FILE="${PROJECT_ROOT}/scripts/guards/baselines/allowlist-service-locator.txt"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd "${PROJECT_ROOT}"

# ─── Scan Paths ───
SCAN_PATHS=(
    "app/Http/Controllers"
    "app/Services"
    "app/Domain"
    "app/Domains"
    "app/Modules"
    "app/Actions"
    "app/UseCases"
    "resources"
)

# ─── Excluded Paths (legitimate service locator usage) ───
EXCLUDE_PATTERNS=(
    "app/Providers/"
    "bootstrap/"
    "vendor/"
    "tests/"
    "storage/"
    "node_modules/"
)

build_exclude_args() {
    local args=""
    for pattern in "${EXCLUDE_PATTERNS[@]}"; do
        args="${args} --exclude-dir=$(basename "${pattern%/}")"
    done
    echo "${args}"
}

# ─── Pattern: app(SomeClass::class), resolve(SomeClass::class), App::make() ───
# Excludes: app()->..., app('config'), app('log'), config(), env()
GREP_PATTERN='app\([A-Z][A-Za-z\\]*::class\)|resolve\([A-Z][A-Za-z\\]*::class\)|App::make\('

echo "═══════════════════════════════════════════════════════════════"
echo "🔍 CI-GATE-02: Service Locator Guard"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# ─── Build current violations list ───
CURRENT_FILE=$(mktemp)
trap "rm -f '${CURRENT_FILE}'" EXIT

for scan_path in "${SCAN_PATHS[@]}"; do
    if [[ -d "${scan_path}" ]]; then
        grep -rn --include="*.php" --include="*.blade.php" \
            ${EXCLUDE_PATTERNS[@]/#/--exclude-dir=} \
            -E "${GREP_PATTERN}" "${scan_path}" 2>/dev/null || true
    fi
done | sort > "${CURRENT_FILE}"

# ─── Apply allowlist if exists ───
if [[ -f "${ALLOWLIST_FILE}" ]]; then
    FILTERED_FILE=$(mktemp)
    trap "rm -f '${CURRENT_FILE}' '${FILTERED_FILE}'" EXIT
    while IFS= read -r line; do
        file_path=$(echo "${line}" | cut -d: -f1)
        allowed=false
        while IFS= read -r allow_pattern; do
            [[ -z "${allow_pattern}" ]] && continue
            [[ "${allow_pattern}" == \#* ]] && continue
            if [[ "${file_path}" == *"${allow_pattern}"* ]]; then
                allowed=true
                break
            fi
        done < "${ALLOWLIST_FILE}"
        if [[ "${allowed}" == "false" ]]; then
            echo "${line}"
        fi
    done < "${CURRENT_FILE}" > "${FILTERED_FILE}"
    mv "${FILTERED_FILE}" "${CURRENT_FILE}"
fi

CURRENT_COUNT=$(wc -l < "${CURRENT_FILE}" | tr -d ' ')

echo "📊 Current service locator usages: ${CURRENT_COUNT}"

# ─── Generate baseline if requested ───
if [[ "${1:-}" == "--generate-baseline" ]]; then
    mkdir -p "$(dirname "${BASELINE_FILE}")"
    cp "${CURRENT_FILE}" "${BASELINE_FILE}"
    echo -e "${GREEN}✅ Baseline generated: ${CURRENT_COUNT} entries${NC}"
    exit 0
fi

# ─── Compare against baseline ───
if [[ ! -f "${BASELINE_FILE}" ]]; then
    echo -e "${YELLOW}⚠️  No baseline found at ${BASELINE_FILE}${NC}"
    echo "   Run with --generate-baseline to create initial baseline"
    echo "   Treating current state as baseline (PASS)"
    exit 0
fi

BASELINE_COUNT=$(wc -l < "${BASELINE_FILE}" | tr -d ' ')
echo "📊 Baseline service locator usages: ${BASELINE_COUNT}"

# ─── Find NEW violations (in current but not in baseline) ───
NEW_VIOLATIONS=$(mktemp)
trap "rm -f '${CURRENT_FILE}' '${NEW_VIOLATIONS}'" EXIT

# Compare normalized (file:line patterns, ignoring line numbers shifts)
# Extract file:pattern for comparison
awk -F: '{print $1 ":" $NF}' "${CURRENT_FILE}" | sort > "${CURRENT_FILE}.norm"
awk -F: '{print $1 ":" $NF}' "${BASELINE_FILE}" | sort > "${BASELINE_FILE}.norm"

comm -23 "${CURRENT_FILE}.norm" "${BASELINE_FILE}.norm" > "${NEW_VIOLATIONS}"
rm -f "${CURRENT_FILE}.norm" "${BASELINE_FILE}.norm"

NEW_COUNT=$(wc -l < "${NEW_VIOLATIONS}" | tr -d ' ')

if [[ ${NEW_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${RED}❌ FAIL: ${NEW_COUNT} NEW service locator usage(s) detected!${NC}"
    echo ""
    echo "New violations:"
    head -20 "${NEW_VIOLATIONS}" | while IFS= read -r line; do
        echo "  ⛔ ${line}"
    done
    if [[ ${NEW_COUNT} -gt 20 ]]; then
        echo "  ... and $((NEW_COUNT - 20)) more"
    fi
    echo ""
    echo "Fix: Use constructor injection instead of app()/resolve()/App::make()"
    exit 1
fi

# ─── Check if violations DECREASED (good!) ───
if [[ ${CURRENT_COUNT} -lt ${BASELINE_COUNT} ]]; then
    echo -e "${GREEN}✅ PASS — Violations decreased: ${BASELINE_COUNT} → ${CURRENT_COUNT} (improvement!)${NC}"
    echo "   Consider updating baseline: ./scripts/check-service-locator.sh --generate-baseline"
else
    echo -e "${GREEN}✅ PASS — No new service locator usage${NC}"
fi

exit 0
