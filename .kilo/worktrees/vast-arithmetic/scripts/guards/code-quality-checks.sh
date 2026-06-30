#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🧹 Code Quality & Code Smell Detection Script (v2.4 - Enterprise Edition)
# ═══════════════════════════════════════════════════════════════════════════
#
# Usage:
#   ./scripts/code-quality-checks.sh
#   ./scripts/code-quality-checks.sh --sarif --fail-on=error
#
# Enterprise Features:
#   - .qualityignore support (glob patterns)
#   - Inline ignore: // quality:ignore RULE_ID
#   - GitHub Job Summary integration
#   - Advanced severity mapping
#
# ═══════════════════════════════════════════════════════════════════════════

set -eu
IFS=$'\n\t'
set +o pipefail

# Configuration
ENABLE_SARIF=false
SARIF_FILE="reports/code-quality.sarif"
SARIF_TMP=$(mktemp)
DEBUG="${DEBUG:-0}"
FAIL_ON="error" # error, warning, note
IGNORE_FILE=".qualityignore"

# Finding Counters
ERROR_COUNT=0
WARNING_COUNT=0
NOTE_COUNT=0
IGNORED_COUNT=0

# Resource Cleanup
trap 'rm -f "$SARIF_TMP"' EXIT

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# Debug Logger
dbg() {
    [[ "$DEBUG" == "1" ]] && echo -e "[DBG] $(date '+%H:%M:%S') - $*" >&2
}

# Parse Arguments
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --sarif) ENABLE_SARIF=true ;;
        --sarif-file) ENABLE_SARIF=true; SARIF_FILE="$2"; shift ;;
        --fail-on=*) FAIL_ON="${1#*=}" ;;
        *) echo "Unknown parameter passed: $1"; exit 1 ;;
    esac
    shift
done

# Validate FAIL_ON
if [[ ! "$FAIL_ON" =~ ^(error|warning|note)$ ]]; then
    echo "Invalid --fail-on value: $FAIL_ON. Must be one of: error, warning, note"
    exit 1
fi

# Setup Logging
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
LOG_DIR="storage/logs"
LOG_FILE="${LOG_DIR}/code-quality-${TIMESTAMP}.log"
mkdir -p "${LOG_DIR}"

if [ "$ENABLE_SARIF" = true ]; then
    mkdir -p "$(dirname "$SARIF_FILE")"
    dbg "SARIF mode enabled."
fi

# -----------------------------------------------------------------------------
# Enterprise Helper Functions
# -----------------------------------------------------------------------------

log_step() {
    echo ""
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "📊 CHECK: $1" | tee -a "${LOG_FILE}"
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    dbg "Started check: $1"
}

log_finding() {
    local level="$1"
    local msg="$2"
    case "$level" in
        error) echo -e "${RED}❌ $msg${NC}" | tee -a "${LOG_FILE}" ;;
        warning) echo -e "${YELLOW}⚠️  $msg${NC}" | tee -a "${LOG_FILE}" ;;
        note) echo -e "${BLUE}ℹ️  $msg${NC}" | tee -a "${LOG_FILE}" ;;
        ignored) echo -e "${MAGENTA}🙈 [IGNORED] $msg${NC}" | tee -a "${LOG_FILE}" ;;
    esac
}

json_escape() {
    printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g; s/$/\\n/g' | tr -d '\n'
}

# Check if a file should be ignored via .qualityignore
is_file_ignored() {
    local file="$1"
    if [[ -f "$IGNORE_FILE" ]]; then
        while read -r pattern; do
            [[ -z "$pattern" || "$pattern" == "#"* ]] && continue
            # shellcheck disable=SC2053
            [[ "$file" == $pattern ]] && return 0
        done < "$IGNORE_FILE"
    fi
    return 1
}

# Check if a specific line has an inline ignore comment
is_line_ignored() {
    local file="$1"
    local line="$2"
    local ruleId="$3"

    # Check if the line exists and contains the ignore tag
    if [[ -f "$file" ]]; then
        local line_content
        line_content=$(sed -n "${line}p" "$file")
        if [[ "$line_content" == *"quality:ignore ${ruleId}"* || "$line_content" == *"quality:ignore all"* ]]; then
            return 0
        fi
    fi
    return 1
}

add_finding() {
    local ruleId="$1"
    local level="$2" # error, warning, note
    local file="$3"
    local line="${4:-1}"
    local message="$5"

    [[ -z "$line" || "$line" == "null" || "$line" -le 0 ]] && line=1

    # Check for Ignores
    if is_file_ignored "$file"; then
        dbg "Ignoring $file due to $IGNORE_FILE"
        IGNORED_COUNT=$((IGNORED_COUNT + 1))
        return
    fi

    if is_line_ignored "$file" "$line" "$ruleId"; then
        dbg "Ignoring $ruleId at $file:$line due to inline comment"
        log_finding "ignored" "$ruleId at $file:$line"
        IGNORED_COUNT=$((IGNORED_COUNT + 1))
        return
    fi

    # Increment Counters
    case "$level" in
        error) ERROR_COUNT=$((ERROR_COUNT + 1)) ;;
        warning) WARNING_COUNT=$((WARNING_COUNT + 1)) ;;
        note) NOTE_COUNT=$((NOTE_COUNT + 1)) ;;
    esac

    # Add to SARIF Buffer
    if [ "$ENABLE_SARIF" = true ]; then
        local rel_file="${file#./}"
        rel_file=$(echo "$rel_file" | xargs)

        local result_json="{\"ruleId\":\"${ruleId}\",\"level\":\"${level}\",\"message\":{\"text\":\"$(json_escape "$message")\"},\"locations\":[{\"physicalLocation\":{\"artifactLocation\":{\"uri\":\"${rel_file}\"},\"region\":{\"startLine\":${line}}}}]}"
        echo "${result_json}" >> "$SARIF_TMP"
    fi
}

# ─────────────────────────────────────────────────────────────────────────
# CHECKS PHASE
# ─────────────────────────────────────────────────────────────────────────

# 1. Long Methods
log_step "1 - Long Methods Detection (>50 lines)"
long_methods=$(find app -name "*.php" -exec grep -l "function" {} \; | while read -r file; do
    awk '/function/{start=NR; method=$0} start > 0 && NR-start>50 && /^[[:space:]]*}[[:space:]]*$/{print FILENAME":"start":"method; start=0}' "$file"
done | head -20 || true)

if [ -n "$long_methods" ]; then
    while IFS=':' read -r file line method; do
        add_finding "LONG_METHOD" "warning" "$file" "$line" "Long method detected: ${method}"
    done <<< "$long_methods"
fi

# 8. God Class
log_step "8 - God Class Detection (>1000 lines)"
god_classes=$(find app -name "*.php" -print0 | xargs -0 wc -l | grep -v " total$" | sort -rn | awk '{if ($1 > 1000) print $2 ":" $1}' | head -10 || true)

if [ -n "$god_classes" ]; then
    while IFS=':' read -r file lines; do
        add_finding "GOD_CLASS" "warning" "$file" "1" "God Class detected (${lines} lines)"
    done <<< "$god_classes"
fi

# 9. Debug Leftovers
log_step "9 - Debug Leftovers"
debug_code=$(grep -rnE "\b(dd|dump|var_dump|print_r)\(" app/ 2>/dev/null | head -20 || true)
if [ -n "$debug_code" ]; then
    while IFS=':' read -r file line content; do
        add_finding "DEBUG_LEFTOVER" "error" "$file" "$line" "Debug code found: ${content}"
    done <<< "$debug_code"
fi

# 10. Hardcoded Secrets
log_step "10 - Hardcoded Secrets"
secrets=$(grep -rnE "(API_KEY|SECRET|PASSWORD|TOKEN)\s*=\s*['\"][^'\"]+['\"]" app/ | grep -v "env(" | grep -v "config(" | head -10 || true)
if [ -n "$secrets" ]; then
    while IFS=':' read -r file line content; do
        add_finding "HARDCODED_SECRET" "error" "$file" "$line" "Potential hardcoded secret"
    done <<< "$secrets"
fi

# 11. Exception Swallowing
log_step "11 - Exception Swallowing"
swallowed=$(grep -rnE "catch\s*\(.*\)\s*\{\s*\}" app/ | head -10 || true)
if [ -n "$swallowed" ]; then
    while IFS=':' read -r file line content; do
        add_finding "EXCEPTION_SWALLOWING" "warning" "$file" "$line" "Empty catch block detected"
    done <<< "$swallowed"
fi

# 12. Prompt Sprawl
log_step "12 - Prompt Sprawl"
prompt_sprawl=$(find app/Services/AI -name "*.php" 2>/dev/null | xargs awk 'length($0) > 300 {print FILENAME ":" NR}' 2>/dev/null | head -10 || true)
if [ -n "$prompt_sprawl" ]; then
    while IFS=':' read -r file line; do
        add_finding "PROMPT_SPRAWL" "note" "$file" "$line" "Potential prompt sprawl (line > 300 chars)"
    done <<< "$prompt_sprawl"
fi

# 13. Dangerous Functions
log_step "13 - Dangerous Functions"
dangerous=$(grep -rnE "\b(eval|exec|passthru|shell_exec|system|proc_open|popen)\(" app/ 2>/dev/null | head -10 || true)
if [ -n "$dangerous" ]; then
    while IFS=':' read -r file line content; do
        add_finding "DANGEROUS_FUNCTION" "error" "$file" "$line" "Dangerous function usage: ${content}"
    done <<< "$dangerous"
fi

# 14. Select * usage
log_step "14 - Select * usage"
selects=$(grep -rnE "select\(['\"]?\*['\"]?\)" app/ | head -10 || true)
if [ -n "$selects" ]; then
    while IFS=':' read -r file line content; do
        add_finding "SELECT_STAR" "warning" "$file" "$line" "Avoid SELECT * in Eloquent/Query Builder"
    done <<< "$selects"
fi

# ─────────────────────────────────────────────────────────────────────────
# PHASE 1: BLADE & FRONTEND CHECKS (v2.5)
# ─────────────────────────────────────────────────────────────────────────

# 15. Inline PHP in Blade
log_step "15 - Inline PHP in Blade Templates"
inline_php=$(find resources/views -name "*.blade.php" -print0 2>/dev/null | xargs -0 grep -nE "^[[:space:]]*<\?php" 2>/dev/null | head -20 || true)
if [ -n "$inline_php" ]; then
    while IFS=':' read -r file line content; do
        add_finding "INLINE_PHP_BLADE" "warning" "$file" "$line" "Avoid inline PHP in Blade templates, use Blade directives"
    done <<< "$inline_php"
fi

# 16. Missing CSRF Protection
log_step "16 - Missing CSRF Protection"
missing_csrf=$(find resources/views -name "*.blade.php" -print0 2>/dev/null | \
    xargs -0 grep -l '<form[^>]*method=["\']POST["\']' 2>/dev/null | \
    while read -r file; do
        if ! grep -q '@csrf' "$file"; then
            echo "$file:1:Missing @csrf in POST form"
        fi
    done | head -10 || true)
if [ -n "$missing_csrf" ]; then
    while IFS=':' read -r file line content; do
        add_finding "MISSING_CSRF" "error" "$file" "$line" "$content"
    done <<< "$missing_csrf"
fi

# 17. Dark Mode Violations (Context7)
log_step "17 - Dark Mode Compliance (Context7)"
dark_mode_violations=$(find resources/views -name "*.blade.php" -print0 2>/dev/null | \
    xargs -0 grep -nE '\bbg-white\b' 2>/dev/null | \
    grep -v 'dark:bg-' | head -20 || true)
if [ -n "$dark_mode_violations" ]; then
    while IFS=':' read -r file line content; do
        add_finding "DARK_MODE_VIOLATION" "warning" "$file" "$line" "bg-white without dark: variant (Context7 compliance)"
    done <<< "$dark_mode_violations"
fi

# 18. Console.log in JavaScript
log_step "18 - Console.log Leftovers"
console_logs=$(find resources/js -name "*.js" -o -name "*.ts" -o -name "*.vue" 2>/dev/null | \
    xargs grep -nE '\bconsole\.(log|debug|info)\(' 2>/dev/null | head -20 || true)
if [ -n "$console_logs" ]; then
    while IFS=':' read -r file line content; do
        add_finding "CONSOLE_LOG" "warning" "$file" "$line" "Console.log leftover in production code"
    done <<< "$console_logs"
fi

# ─────────────────────────────────────────────────────────────────────────
# PHASE 2: SECURITY CHECKS (v2.5)
# ─────────────────────────────────────────────────────────────────────────

# 19. Unprotected Admin Routes
log_step "19 - Unprotected Admin Routes"
unprotected_routes=$(find routes -name "*.php" -print0 2>/dev/null | \
    xargs -0 grep -nE "Route::(get|post|put|patch|delete)\(['\"].*\/admin\/" 2>/dev/null | \
    while IFS=':' read -r file line content; do
        # Check if the line or next 2 lines contain middleware
        context=$(sed -n "${line},$((line+2))p" "$file" | tr '\n' ' ')
        if ! echo "$context" | grep -qE "middleware\(['\"]auth|->middleware\("; then
            echo "$file:$line:Admin route without auth middleware"
        fi
    done | head -10 || true)
if [ -n "$unprotected_routes" ]; then
    while IFS=':' read -r file line content; do
        add_finding "UNPROTECTED_ADMIN_ROUTE" "error" "$file" "$line" "$content"
    done <<< "$unprotected_routes"
fi

# 20. Debug Mode in Production Config
log_step "20 - Debug Mode in Production"
debug_enabled=$(grep -nE "^[[:space:]]*['\"]debug['\"][[:space:]]*=>[[:space:]]*true" config/app.php 2>/dev/null || true)
if [ -n "$debug_enabled" ]; then
    while IFS=':' read -r file line content; do
        add_finding "DEBUG_MODE_ENABLED" "error" "$file" "$line" "Debug mode enabled in config (security risk)"
    done <<< "$debug_enabled"
fi

# 21. Context7 Migration Violations
# SSOT: proje.md §2. Yasaklı Alan İsimleri (Core Domain)
# Note: This is a fast-check. Full validation in php artisan sab:integrity-scan
log_step "21 - Context7 Migration Violations"
context7_violations=$(find database/migrations -name "*.php" -print0 2>/dev/null | \
    xargs -0 grep -nE '\$(table->|Schema::table.*->)(status|order|active)\(' 2>/dev/null | \
    head -20 || true)
if [ -n "$context7_violations" ]; then
    while IFS=':' read -r file line content; do
        add_finding "CONTEXT7_MIGRATION_VIOLATION" "error" "$file" "$line" "Forbidden Context7 field in migration: ${content}"
    done <<< "$context7_violations"
fi

# ─────────────────────────────────────────────────────────────────────────
# PHASE 3: PERFORMANCE & BEST PRACTICES (v2.5)
# ─────────────────────────────────────────────────────────────────────────

# 22. Missing Database Indexes
log_step "22 - Missing Database Indexes"
missing_indexes=$(find database/migrations -name "*.php" -print0 2>/dev/null | \
    xargs -0 grep -nE '\$table->foreign(Id)?\(' 2>/dev/null | \
    while IFS=':' read -r file line content; do
        # Check if ->index() appears in the same or next line
        context=$(sed -n "${line},$((line+1))p" "$file" | tr '\n' ' ')
        if ! echo "$context" | grep -qE '->index\('; then
            echo "$file:$line:Foreign key without index"
        fi
    done | head -10 || true)
if [ -n "$missing_indexes" ]; then
    while IFS=':' read -r file line content; do
        add_finding "MISSING_INDEX" "warning" "$file" "$line" "$content"
    done <<< "$missing_indexes"
fi

# 23. Missing Rate Limiting
log_step "23 - Missing Rate Limiting on API Routes"
missing_throttle=$(find routes -name "api.php" -print0 2>/dev/null | \
    xargs -0 grep -nE "Route::(get|post|put|patch|delete)" 2>/dev/null | \
    while IFS=':' read -r file line content; do
        context=$(sed -n "${line},$((line+2))p" "$file" | tr '\n' ' ')
        if ! echo "$context" | grep -qE 'throttle:|->middleware.*throttle'; then
            echo "$file:$line:API route without rate limiting"
        fi
    done | head -10 || true)
if [ -n "$missing_throttle" ]; then
    while IFS=':' read -r file line content; do
        add_finding "MISSING_RATE_LIMIT" "warning" "$file" "$line" "$content"
    done <<< "$missing_throttle"
fi

# 24. Hardcoded URLs in Config
log_step "24 - Hardcoded URLs in Config"
hardcoded_urls=$(find config -name "*.php" -print0 2>/dev/null | \
    xargs -0 grep -nE "(http://|https://)" 2>/dev/null | \
    grep -v "example.com\|localhost\|127.0.0.1\|'url' =>" | head -10 || true)
if [ -n "$hardcoded_urls" ]; then
    while IFS=':' read -r file line content; do
        add_finding "HARDCODED_URL" "warning" "$file" "$line" "Hardcoded URL in config (use .env)"
    done <<< "$hardcoded_urls"
fi

# ─────────────────────────────────────────────────────────────────────────
# PHASE 4: TEST QUALITY (v2.5)
# ─────────────────────────────────────────────────────────────────────────

# 25. Skipped Tests
log_step "25 - Skipped Tests"
skipped_tests=$(find tests -name "*Test.php" -print0 2>/dev/null | \
    xargs -0 grep -nE '->skip\(|@skip|markTestSkipped' 2>/dev/null | head -10 || true)
if [ -n "$skipped_tests" ]; then
    while IFS=':' read -r file line content; do
        add_finding "SKIPPED_TEST" "note" "$file" "$line" "Test is skipped (technical debt)"
    done <<< "$skipped_tests"
fi

# 26. Missing Assertions
log_step "26 - Tests Without Assertions"
missing_assertions=$(find tests -name "*Test.php" -print0 2>/dev/null | \
    xargs -0 grep -l "function test" 2>/dev/null | \
    while read -r file; do
        # Find test methods without assert
        awk '/function test.*\(/ {start=NR; method=$0} start > 0 && /^[[:space:]]*}/ {
            if (NR-start > 3) {
                cmd="sed -n " start "," NR "p \"" FILENAME "\" | grep -q \"assert\\|expect\""
                if (system(cmd) != 0) {
                    print FILENAME ":" start ":Test without assertions: " method
                }
            }
            start=0
        }' "$file"
    done | head -5 || true)
if [ -n "$missing_assertions" ]; then
    while IFS=':' read -r file line content; do
        add_finding "MISSING_ASSERTION" "warning" "$file" "$line" "$content"
    done <<< "$missing_assertions"
fi

# ─────────────────────────────────────────────────────────────────────────
# REPORTING PHASE
# ─────────────────────────────────────────────────────────────────────────
echo ""
echo "════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
echo "📊 ENTERPRISE QUALITY SUMMARY" | tee -a "${LOG_FILE}"
echo "════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"

# Print Stats to console
echo -e "Critical Errors: ${RED}${ERROR_COUNT}${NC}"
echo -e "Warnings:        ${YELLOW}${WARNING_COUNT}${NC}"
echo -e "Notes:           ${BLUE}${NOTE_COUNT}${NC}"
echo -e "Ignored:         ${MAGENTA}${IGNORED_COUNT}${NC}"

# GitHub Job Summary (Markdown)
if [[ -v GITHUB_STEP_SUMMARY ]]; then
    cat <<EOF >> "$GITHUB_STEP_SUMMARY"
## 🛡️ Yalihan Code Quality Report
| Severity | Count |
| :--- | :---: |
| ❌ Errors | $ERROR_COUNT |
| ⚠️ Warnings | $WARNING_COUNT |
| ℹ️ Notes | $NOTE_COUNT |
| 🙈 Ignored | $IGNORED_COUNT |

**Threshold:** \`fail-on=$FAIL_ON\`
EOF
    if [[ $ERROR_COUNT -gt 0 ]]; then
        echo "### ❌ Critical Failures Detected" >> "$GITHUB_STEP_SUMMARY"
    fi
fi

# SARIF assembly
if [ "$ENABLE_SARIF" = true ]; then
    if [ -s "$SARIF_TMP" ]; then
        SORTED_TMP=$(mktemp)
        sort -u "$SARIF_TMP" > "$SORTED_TMP"
        SARIF_RESULTS=$(awk 'ORS=","' "$SORTED_TMP" | sed 's/,$//')
        rm -f "$SORTED_TMP"
    else
        SARIF_RESULTS=""
    fi

    cat <<EOF > "$SARIF_FILE"
{
  "\$schema": "https://json.schemastore.org/sarif-2.1.0-rtm.5.json",
  "version": "2.1.0",
  "runs": [
    {
      "tool": {
        "driver": {
          "name": "Yalihan Code Quality",
          "informationUri": "https://yalihan.com",
          "rules": []
        }
      },
      "results": [ $SARIF_RESULTS ]
    }
  ]
}
EOF
    log_success "SARIF Report generated: $SARIF_FILE"
fi

# Exit Logic
SHOULD_FAIL=0
case "$FAIL_ON" in
    error)   [[ $ERROR_COUNT -gt 0 ]] && SHOULD_FAIL=1 ;;
    warning) [[ $((ERROR_COUNT + WARNING_COUNT)) -gt 0 ]] && SHOULD_FAIL=1 ;;
    note)    [[ $((ERROR_COUNT + WARNING_COUNT + NOTE_COUNT)) -gt 0 ]] && SHOULD_FAIL=1 ;;
esac

if [ $SHOULD_FAIL -eq 1 ]; then
    log_error "Quality Gate Failed (Threshold: ${FAIL_ON})"
    exit 1
else
    log_success "Quality Gate Passed (Threshold: ${FAIL_ON})"
    exit 0
fi
