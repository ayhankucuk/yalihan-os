#!/usr/bin/env bash
# ==============================================================================
# YALIHAN OS — Mechanical Pre-Mutation Conflict Guard (SSOT)
# File: scripts/tools/conflict-guard.sh
# Version: 1.0.0 | Date: 2026-09-04
# Owner: Antigravity | Gate: G2 (Conflict Guard) | Task: BACKLOG-2
#
# Prevents uncoordinated agent mutation of critical hot-spot files.
# Requires an active, non-expired protocol lock in PROJECT_STATE.md.
# Compatible with GNU bash 3.2+ (macOS /bin/bash & Linux).
# ==============================================================================

set -o pipefail

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || echo '.')"

# ── Colours ───────────────────────────────────────────────────────────────────
if [ -t 1 ] && command -v tput >/dev/null 2>&1 && tput bold >/dev/null 2>&1; then
  BOLD=$(tput bold)
  RED=$(tput setaf 1)
  GREEN=$(tput setaf 2)
  YELLOW=$(tput setaf 3)
  CYAN=$(tput setaf 6)
  RESET=$(tput sgr0)
else
  BOLD='\033[1m'
  RED='\033[0;31m'
  GREEN='\033[0;32m'
  YELLOW='\033[0;33m'
  CYAN='\033[0;36m'
  RESET='\033[0m'
fi

# ── Hot-spot Definitions ──────────────────────────────────────────────────────
# Any modification to these paths requires an explicit protocol lock.
HOTSPOT_PATTERNS=(
  "database/schema/mysql-schema.sql"
  "database/migrations/*"
  "routes/web.php"
  "routes/api.php"
  "routes/admin.php"
  ".sab/authority.json"
  "config/*.php"
  "app/Services/IlanCrudService.php"
)

# ── Helper: Matches Hot-spot Pattern ─────────────────────────────────────────
is_hotspot_file() {
  local target_file="$1"
  # Normalize relative path (strip leading ./ if any)
  target_file="${target_file#./}"

  for pattern in "${HOTSPOT_PATTERNS[@]}"; do
    # shellcheck disable=SC2254
    case "$target_file" in
      $pattern) return 0 ;;
    esac
  done
  return 1
}

# ── Helper: Parse Timestamp to Epoch Seconds ──────────────────────────────────
to_epoch() {
  local ts="$1"
  # If already numeric epoch seconds
  if [[ "$ts" =~ ^[0-9]+$ ]]; then
    echo "$ts"
    return 0
  fi

  # Try BSD date (macOS) with -u for UTC
  local epoch=""
  epoch=$(date -u -j -f "%Y-%m-%dT%H:%M:%SZ" "$ts" "+%s" 2>/dev/null || true)
  if [ -n "$epoch" ] && [[ "$epoch" =~ ^[0-9]+$ ]]; then
    echo "$epoch"
    return 0
  fi

  # Try GNU date (Linux)
  epoch=$(date -d "$ts" "+%s" 2>/dev/null || true)
  if [ -n "$epoch" ] && [[ "$epoch" =~ ^[0-9]+$ ]]; then
    echo "$epoch"
    return 0
  fi

  # Try node fallback
  if command -v node >/dev/null 2>&1; then
    epoch=$(node -e "const t = new Date('$ts').getTime(); if(!isNaN(t)) console.log(Math.floor(t/1000));" 2>/dev/null || true)
    if [ -n "$epoch" ] && [[ "$epoch" =~ ^[0-9]+$ ]]; then
      echo "$epoch"
      return 0
    fi
  fi

  # Try python3 fallback
  if command -v python3 >/dev/null 2>&1; then
    epoch=$(python3 -c "import datetime, dateutil.parser; print(int(dateutil.parser.parse('$ts').timestamp()))" 2>/dev/null || true)
    if [ -n "$epoch" ] && [[ "$epoch" =~ ^[0-9]+$ ]]; then
      echo "$epoch"
      return 0
    fi
  fi

  echo "0"
  return 1
}

# Current epoch
get_now_epoch() {
  if [ -n "${NOW_EPOCH_OVERRIDE:-}" ]; then
    echo "$NOW_EPOCH_OVERRIDE"
  else
    date "+%s"
  fi
}

get_project_state_file() {
  echo "${PROJECT_STATE_OVERRIDE:-${REPO_ROOT}/.project-brain/PROJECT_STATE.md}"
}

# ── Lock Evaluation ───────────────────────────────────────────────────────────
# Returns:
#   0: Active valid lock found
#   1: No lock found
#   2: Lock found but expired
check_file_lock() {
  local target_file="$1"
  target_file="${target_file#./}"
  local state_file
  state_file=$(get_project_state_file)

  if [ ! -f "$state_file" ]; then
    return 1
  fi

  local now
  now=$(get_now_epoch)
  local found_expired=0
  local EXPIRED_INFO=""

  # Scan PROJECT_STATE.md for lines like: HOTSPOT_LOCK:<pattern>:<agent>:<timestamp>:<ttl>
  # Also handles: <!-- HOTSPOT_LOCK:... -->
  while IFS= read -r line; do
    if [[ "$line" =~ HOTSPOT_LOCK:([^:]+):([^:]+):(.+):([0-9]+) ]]; then
      local lock_pattern="${BASH_REMATCH[1]}"
      local lock_agent="${BASH_REMATCH[2]}"
      local lock_ts="${BASH_REMATCH[3]}"
      local lock_ttl="${BASH_REMATCH[4]}"

      # Check if target file matches this lock pattern
      local matched=0
      # shellcheck disable=SC2254
      case "$target_file" in
        $lock_pattern) matched=1 ;;
      esac

      if [ "$matched" -eq 1 ]; then
        local lock_epoch
        lock_epoch=$(to_epoch "$lock_ts")
        local expiry=$((lock_epoch + lock_ttl))

        if [ "$now" -le "$expiry" ]; then
          # Valid active lock!
          echo "ACTIVE:$lock_pattern:$lock_agent:$lock_ts:$lock_ttl:$((expiry - now))"
          return 0
        else
          found_expired=1
          EXPIRED_INFO="EXPIRED:$lock_pattern:$lock_agent:$lock_ts:$lock_ttl:$((now - expiry))"
        fi
      fi
    fi
  done < "$state_file"

  if [ "$found_expired" -eq 1 ]; then
    echo "$EXPIRED_INFO"
    return 2
  fi

  return 1
}

# ── Command: List Locks ───────────────────────────────────────────────────────
cmd_list_locks() {
  local PROJECT_STATE_FILE
  PROJECT_STATE_FILE=$(get_project_state_file)
  echo -e "${BOLD}${CYAN}=== YALIHAN OS — Active & Expired Protocol Locks ===${RESET}"
  echo -e "Ledger file: ${CYAN}${PROJECT_STATE_FILE}${RESET}\n"

  if [ ! -f "$PROJECT_STATE_FILE" ]; then
    echo -e "${YELLOW}Warning: PROJECT_STATE.md not found at ${PROJECT_STATE_FILE}${RESET}"
    return 0
  fi

  local now
  now=$(get_now_epoch)
  local count=0

  printf "%-35s %-15s %-22s %-8s %s\n" "HOTSPOT PATTERN" "AGENT" "TIMESTAMP" "TTL(s)" "STATUS"
  printf "%-35s %-15s %-22s %-8s %s\n" "-----------------------------------" "---------------" "----------------------" "--------" "-----------------------"

  while IFS= read -r line; do
    if [[ "$line" =~ HOTSPOT_LOCK:([^:]+):([^:]+):(.+):([0-9]+) ]]; then
      local p="${BASH_REMATCH[1]}"
      local a="${BASH_REMATCH[2]}"
      local t="${BASH_REMATCH[3]}"
      local ttl="${BASH_REMATCH[4]}"
      local epoch
      epoch=$(to_epoch "$t")
      local exp=$((epoch + ttl))

      ((count++))
      if [ "$now" -le "$exp" ]; then
        local rem=$((exp - now))
        printf "%-35s %-15s %-22s %-8s ${GREEN}ACTIVE (rem: %ds)${RESET}\n" "$p" "$a" "$t" "$ttl" "$rem"
      else
        local ago=$((now - exp))
        printf "%-35s %-15s %-22s %-8s ${RED}EXPIRED (%ds ago)${RESET}\n" "$p" "$a" "$t" "$ttl" "$ago"
      fi
    fi
  done < "$PROJECT_STATE_FILE"

  if [ "$count" -eq 0 ]; then
    echo -e "${YELLOW}No protocol locks currently recorded in PROJECT_STATE.md.${RESET}"
  fi
  echo ""
}

# ── Command: Acquire Lock ─────────────────────────────────────────────────────
cmd_acquire_lock() {
  local PROJECT_STATE_FILE
  PROJECT_STATE_FILE=$(get_project_state_file)
  local target_pattern="$1"
  local agent_name="$2"
  local ttl="${3:-3600}"

  if [ -z "$target_pattern" ] || [ -z "$agent_name" ]; then
    echo -e "${RED}Usage: $0 --acquire <pattern> <agent> [ttl_seconds]${RESET}"
    exit 1
  fi

  if [ ! -f "$PROJECT_STATE_FILE" ]; then
    mkdir -p "$(dirname "$PROJECT_STATE_FILE")"
    echo -e "# YALIHAN OS — Project Brain State\n\n## Active Protocol Locks\n" > "$PROJECT_STATE_FILE"
  fi

  # If section header missing, append it
  if ! grep -q "## Active Protocol Locks" "$PROJECT_STATE_FILE"; then
    echo -e "\n## Active Protocol Locks\n" >> "$PROJECT_STATE_FILE"
  fi

  local now_iso
  if [ -n "${NOW_EPOCH_OVERRIDE:-}" ]; then
    now_iso="$NOW_EPOCH_OVERRIDE"
  else
    now_iso=$(date -u "+%Y-%m-%dT%H:%M:%SZ")
  fi

  # Remove any existing lock for this exact pattern first
  local tmp_file
  tmp_file=$(mktemp "${TMPDIR:-/tmp}/proj_state.XXXXXX")
  grep -F -v "HOTSPOT_LOCK:${target_pattern}:" "$PROJECT_STATE_FILE" > "$tmp_file"
  mv "$tmp_file" "$PROJECT_STATE_FILE"

  # Append new lock entry
  echo "HOTSPOT_LOCK:${target_pattern}:${agent_name}:${now_iso}:${ttl}" >> "$PROJECT_STATE_FILE"

  echo -e "${GREEN}✅ Lock acquired:${RESET} ${BOLD}${target_pattern}${RESET} by ${CYAN}${agent_name}${RESET} (TTL: ${ttl}s until ${now_iso})"
}

# ── Command: Release Lock ─────────────────────────────────────────────────────
cmd_release_lock() {
  local PROJECT_STATE_FILE
  PROJECT_STATE_FILE=$(get_project_state_file)
  local target_pattern="$1"
  if [ -z "$target_pattern" ]; then
    echo -e "${RED}Usage: $0 --release <pattern>${RESET}"
    exit 1
  fi

  if [ ! -f "$PROJECT_STATE_FILE" ]; then
    echo -e "${YELLOW}No PROJECT_STATE.md file found.${RESET}"
    return 0
  fi

  local tmp_file
  tmp_file=$(mktemp "${TMPDIR:-/tmp}/proj_state.XXXXXX")
  grep -F -v "HOTSPOT_LOCK:${target_pattern}:" "$PROJECT_STATE_FILE" > "$tmp_file"
  mv "$tmp_file" "$PROJECT_STATE_FILE"

  echo -e "${GREEN}✅ Lock released:${RESET} ${BOLD}${target_pattern}${RESET}"
}

# ── Command: Check Staged (Pre-Commit Mode) ───────────────────────────────────
cmd_check_staged() {
  local staged_files
  staged_files=$(git diff --cached --name-only --diff-filter=ACMR 2>/dev/null || true)

  if [ -z "$staged_files" ]; then
    # Nothing staged
    return 0
  fi

  local violations=0
  local checked_hotspots=0

  while IFS= read -r file; do
    [ -z "$file" ] && continue

    if is_hotspot_file "$file"; then
      ((checked_hotspots++))
      local lock_output
      lock_output=$(check_file_lock "$file" 2>&1)
      local lock_status=$?

      if [ "$lock_status" -eq 0 ]; then
        # Lock active and valid
        continue
      elif [ "$lock_status" -eq 2 ]; then
        ((violations++))
        echo -e "${RED}${BOLD}❌ CONFLICT GUARD VIOLATION (EXPIRED LOCK):${RESET}"
        echo -e "   Hot-spot file: ${YELLOW}${file}${RESET}"
        echo -e "   Lock info:     ${lock_output}"
        echo -e "   ${RED}The protocol lock for this file has EXPIRED.${RESET}"
        echo -e "   Please refresh your lock in ${CYAN}.project-brain/PROJECT_STATE.md${RESET} before committing."
        echo ""
      else
        ((violations++))
        echo -e "${RED}${BOLD}❌ CONFLICT GUARD VIOLATION (NO PROTOCOL LOCK):${RESET}"
        echo -e "   Hot-spot file: ${YELLOW}${file}${RESET}"
        echo -e "   ${RED}This is a protected hot-spot file. Committing changes without an active lock is blocked.${RESET}"
        echo -e "   Acquire a protocol lock in ${CYAN}.project-brain/PROJECT_STATE.md${RESET} first."
        echo -e "   Required format: ${BOLD}HOTSPOT_LOCK:<file_pattern>:<agent>:<timestamp>:<ttl_seconds>${RESET}"
        echo -e "   Or use CLI:      ${CYAN}./scripts/tools/conflict-guard.sh --acquire \"$file\" \"<agent_name>\" 3600${RESET}"
        echo ""
      fi
    fi
  done <<< "$staged_files"

  if [ "$violations" -gt 0 ]; then
    echo -e "${RED}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    echo -e "${RED}${BOLD}🔴 PRE-COMMIT BLOCKED by Pre-Mutation Conflict Guard (BACKLOG-2)${RESET}"
    echo -e "   ${violations} hot-spot file(s) modified without active protocol lock."
    echo -e "   Coordinate with other agents and register your lock in PROJECT_STATE.md."
    echo -e "${RED}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    return 1
  fi

  if [ "$checked_hotspots" -gt 0 ]; then
    echo -e "${GREEN}✅ Conflict Guard: ${checked_hotspots} hot-spot file(s) verified with active protocol lock.${RESET}"
  fi

  return 0
}

# ── Command: Check Single File ────────────────────────────────────────────────
cmd_check_single() {
  local target_file="$1"
  if [ -z "$target_file" ]; then
    echo -e "${RED}Usage: $0 --check <file_path>${RESET}"
    exit 1
  fi

  if ! is_hotspot_file "$target_file"; then
    echo -e "${GREEN}File '${target_file}' is NOT a protected hot-spot file.${RESET}"
    return 0
  fi

  local lock_output
  lock_output=$(check_file_lock "$target_file" 2>&1)
  local status=$?

  if [ "$status" -eq 0 ]; then
    echo -e "${GREEN}✅ Protected hot-spot file '${target_file}' has an ACTIVE lock:${RESET}"
    echo "   $lock_output"
    return 0
  elif [ "$status" -eq 2 ]; then
    echo -e "${RED}❌ Protected hot-spot file '${target_file}' lock is EXPIRED:${RESET}"
    echo "   $lock_output"
    return 2
  else
    echo -e "${RED}❌ Protected hot-spot file '${target_file}' has NO active lock.${RESET}"
    return 1
  fi
}

# ── Command: Run Regression Test Suite ────────────────────────────────────────
cmd_test() {
  echo -e "${BOLD}${CYAN}=== Running Conflict Guard Regression Test Suite ===${RESET}\n"

  local TEST_TMP
  TEST_TMP=$(mktemp -d "${TMPDIR:-/tmp}/conflict_guard_test.XXXXXX")
  trap 'rm -rf "$TEST_TMP"' EXIT

  local TEST_STATE="$TEST_TMP/PROJECT_STATE.md"
  export PROJECT_STATE_OVERRIDE="$TEST_STATE"

  local test_pass=0
  local test_total=0

  run_test() {
    local desc="$1"
    local expected_status="$2"
    shift 2
    test_total=$((test_total + 1))

    local actual_status=0
    local output=""
    output=$("$@" 2>&1) || actual_status=$?

    if [ "$actual_status" -eq "$expected_status" ]; then
      echo -e "  Test ${test_total}: ${GREEN}PASS${RESET} — ${desc}"
      test_pass=$((test_pass + 1))
    else
      echo -e "  Test ${test_total}: ${RED}FAIL${RESET} — ${desc}"
      echo -e "    Expected: $expected_status | Got: $actual_status"
      echo -e "    Output: $output"
    fi
  }

  echo "1. Testing Hot-spot File Matching..."
  run_test "schema file matches hot-spot" 0 is_hotspot_file "database/schema/mysql-schema.sql"
  run_test "migration file matches hot-spot" 0 is_hotspot_file "database/migrations/2026_01_01_create_test.php"
  run_test "routes/web.php matches hot-spot" 0 is_hotspot_file "routes/web.php"
  run_test "routes/api.php matches hot-spot" 0 is_hotspot_file "routes/api.php"
  run_test "authority.json matches hot-spot" 0 is_hotspot_file ".sab/authority.json"
  run_test "config file matches hot-spot" 0 is_hotspot_file "config/app.php"
  run_test "IlanCrudService matches hot-spot" 0 is_hotspot_file "app/Services/IlanCrudService.php"
  run_test "regular controller does NOT match hot-spot" 1 is_hotspot_file "app/Http/Controllers/HomeController.php"
  run_test "regular blade does NOT match hot-spot" 1 is_hotspot_file "resources/views/welcome.blade.php"

  echo -e "\n2. Testing Lock Acquisition & Verification..."
  # Clean test state file
  echo -e "# Test Project State\n\n## Active Protocol Locks\n" > "$TEST_STATE"

  # No lock on config/app.php yet
  run_test "unlocked hot-spot file fails check" 1 cmd_check_single "config/app.php"

  # Acquire lock for config/app.php with 3600 TTL
  export NOW_EPOCH_OVERRIDE=1700000000
  cmd_acquire_lock "config/*.php" "Antigravity" 3600 >/dev/null

  run_test "active lock permits hot-spot file" 0 cmd_check_single "config/app.php"
  run_test "active lock permits another config file" 0 cmd_check_single "config/database.php"

  # Fast forward time by 4000s (past TTL of 3600)
  export NOW_EPOCH_OVERRIDE=1700004000
  run_test "expired lock is detected and blocked" 2 cmd_check_single "config/app.php"

  echo -e "\n3. Testing Lock Release..."
  export NOW_EPOCH_OVERRIDE=1700000000
  cmd_release_lock "config/*.php" >/dev/null
  run_test "released lock is no longer active" 1 cmd_check_single "config/app.php"

  echo -e "\n4. Testing Multi-lock & Specific Patterns..."
  cmd_acquire_lock "database/migrations/*" "Cline" 1800 >/dev/null
  cmd_acquire_lock "app/Services/IlanCrudService.php" "Kilo" 1800 >/dev/null

  run_test "migration lock active" 0 cmd_check_single "database/migrations/2026_09_01_test.php"
  run_test "IlanCrudService lock active" 0 cmd_check_single "app/Services/IlanCrudService.php"
  run_test "routes still unlocked" 1 cmd_check_single "routes/web.php"

  echo -e "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo -e "Test Results: ${BOLD}${test_pass}/${test_total} PASS${RESET}"
  echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"

  unset PROJECT_STATE_OVERRIDE
  unset NOW_EPOCH_OVERRIDE

  if [ "$test_pass" -eq "$test_total" ]; then
    return 0
  else
    return 1
  fi
}

# ── Main Entrypoint ───────────────────────────────────────────────────────────
case "${1:-}" in
  --staged)
    cmd_check_staged
    ;;
  --check)
    cmd_check_single "${2:-}"
    ;;
  --list-locks)
    cmd_list_locks
    ;;
  --acquire)
    cmd_acquire_lock "${2:-}" "${3:-}" "${4:-3600}"
    ;;
  --release)
    cmd_release_lock "${2:-}"
    ;;
  --test)
    cmd_test
    ;;
  --help|-h)
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --staged                       Check staged files (used by Git pre-commit hook)"
    echo "  --check <file>                 Check if a specific file has an active lock"
    echo "  --list-locks                   List all protocol locks from PROJECT_STATE.md"
    echo "  --acquire <pattern> <agent> [TTL]  Acquire/register a lock in PROJECT_STATE.md"
    echo "  --release <pattern>            Release a lock from PROJECT_STATE.md"
    echo "  --test                         Run full regression test suite"
    echo "  --help                         Show this help"
    ;;
  *)
    cmd_check_staged
    ;;
esac
