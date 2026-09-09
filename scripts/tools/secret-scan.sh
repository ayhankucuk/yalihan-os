#!/usr/bin/env bash
# ============================================================
# YALIHAN OS — Canonical Secret Scanner (SSOT)
# File: scripts/tools/secret-scan.sh
# Version: 2.0.0 | Date: 2026-09-01
# Sole enforcement policy for token fingerprint and credential detection.
# All hooks, CI workflows, and gate scripts MUST delegate here.
# Compatible with: GNU bash 3.2+ (macOS /bin/bash / Linux)
# Zero external dependencies: Uses POSIX grep, sed, awk
# ============================================================

set -o pipefail

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || echo '.')"
PATTERN_FILE="${REPO_ROOT}/scripts/tools/secret-scan-patterns.txt"

# ── Colours ────────────────────────────────────────────────
if command -v tput >/dev/null 2>&1 && tput bold >/dev/null 2>&1; then
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

# ── Output helpers ─────────────────────────────────────────
say() { echo -e "$1"; }
pass() { say "${GREEN}✅  $1${RESET}"; }
fail() { say "${RED}❌  $1${RESET}"; }
warn() { say "${YELLOW}⚠️  $1${RESET}"; }
info() { say "     $1"; }

# ── Redact a matched line (show context, hide token) ────────
sanitise() {
  echo "$1" \
    | sed 's/ghp_[a-zA-Z0-9]\{36\}/ghp_***REDACTED***/g' \
    | sed 's/github_pat_[a-zA-Z0-9_]\{22\}/github_pat_***REDACTED***/g' \
    | sed 's/sk-proj-[a-zA-Z0-9]\{48\}/sk-proj-***REDACTED***/g' \
    | sed 's/sk-[a-zA-Z0-9]\{32\}/sk-***REDACTED***/g' \
    | sed 's/sk-ant-[a-zA-Z0-9]\{48\}/sk-ant-***REDACTED***/g' \
    | sed 's/xox[baprs]-[a-zA-Z0-9]\{10\}/xox***-***REDACTED***/g' \
    | sed 's/AKIA[0-9A-Z]\{16\}/AKIA***REDACTED***/g' \
    | sed 's/Bearer [a-zA-Z0-9_\-\.]\{20\}/Bearer ***REDACTED***/g' \
    | sed 's/-----BEGIN [A-Z ]*PRIVATE KEY-----/-----BEGIN ***REDACTED PRIVATE KEY***-----/g'
}

# ── Phase 1: Path-level check ──────────────────────────────
check_blocked_paths() {
  local files="$1"
  local violations=0

  if [ -z "$files" ]; then
    return 0
  fi

  while IFS= read -r file; do
    [ -z "$file" ] && continue

    # Allow safe templates explicitly (path allowed; content will still be scanned)
    if [[ "$file" =~ \.env\.example$ ]] || [[ "$file" =~ \.env\.testing\.example$ ]] || [[ "$file" =~ \.env\.ci$ ]]; then
      continue
    fi

    # Block sensitive .env variants
    if [[ "$file" =~ ^\.env$ ]] || [[ "$file" =~ ^\.env\. ]] || [[ "$file" =~ /\.env$ ]] || [[ "$file" =~ /\.env\. ]]; then
      fail "SECRET_SCAN_BLOCKED: Blocked credential file path: ${file}"
      violations=$((violations + 1))
      continue
    fi

    # Block private keys and certificates
    if [[ "$file" =~ \.pem$ ]] || [[ "$file" =~ \.key$ ]] || [[ "$file" =~ \.p12$ ]] || [[ "$file" =~ \.pfx$ ]] || [[ "$file" =~ id_rsa ]] || [[ "$file" =~ id_ed25519 ]]; then
      fail "SECRET_SCAN_BLOCKED: Blocked private key/certificate path: ${file}"
      violations=$((violations + 1))
      continue
    fi
  done <<< "$files"

  return $violations
}

# ── Phase 2: Content Diff Scanning ──────────────────────────
do_scan() {
  local _diff="$1"
  local _label="${2:-content}"
  local _violations=0

  local _diff_file
  local _match_file
  _diff_file=$(mktemp /tmp/yalihan-ss.XXXXXX)
  _match_file=$(mktemp /tmp/yalihan-sm.XXXXXX)

  # Only inspect added lines
  printf '%s\n' "$_diff" | grep '^+[^+]' > "$_diff_file" 2>/dev/null || true

  if [ -s "$_diff_file" ] && [ -f "$PATTERN_FILE" ]; then
    # Grep with pattern file (using clean patterns, no leading comments)
    grep -v '^#' "$PATTERN_FILE" | grep -v '^$' > "${PATTERN_FILE}.clean" 2>/dev/null || true
    grep -E -f "${PATTERN_FILE}.clean" "$_diff_file" > "$_match_file" 2>/dev/null || true
    rm -f "${PATTERN_FILE}.clean"
  fi

  if [ -s "$_match_file" ]; then
    local _count
    _count=$(wc -l < "$_match_file" 2>/dev/null | tr -d ' ' || echo 1)
    [ -z "$_count" ] && _count=1
    _violations=$_count

    say ""
    while IFS= read -r _line; do
      [ -z "$_line" ] && continue
      local _sanitised
      _sanitised=$(sanitise "$_line")
      fail "SECRET_SCAN_BLOCKED: Secret pattern detected"
      info "Context : ${_sanitised}"
    done < "$_match_file"
    say ""
    fail "BLOCKED — ${_count} secret pattern(s) detected in ${_label}."
  fi

  rm -f "$_diff_file" "$_match_file"
  if [ "$_violations" -gt 0 ]; then
    return 1
  fi
  return 0
}

# ── Mode: Scan Staged Diff ──────────────────────────────────
scan_staged() {
  say ""
  say "${BOLD}${CYAN}━━━ YALIHAN OS Secret Scan (Staged) ━━━${RESET}"
  say "   Scanner: scripts/tools/secret-scan.sh"
  say "   SSOT: YES — pattern policy from secret-scan-patterns.txt"
  say "   Shell: ${BASH_VERSION:-sh}"

  local staged_files
  staged_files=$(git diff --staged --name-only 2>/dev/null || true)
  local file_count=0
  if [ -n "$staged_files" ]; then
    file_count=$(echo "$staged_files" | grep -v '^$' | wc -l | tr -d ' ' || echo 0)
  fi

  if [ "$file_count" -lt 1 ]; then
    pass "No staged files — nothing to scan."
    return 0
  fi

  say "   Scanning ${file_count} staged file(s)..."

  local total_violations=0

  # 1. Path-level check
  check_blocked_paths "$staged_files"
  local path_status=$?
  total_violations=$((total_violations + path_status))

  # 2. Content diff scan (exclude the pattern definitions file itself)
  local _diff
  _diff=$(git diff --staged --no-color -U0 -- . ':(exclude)scripts/tools/secret-scan-patterns.txt' 2>/dev/null || true)
  do_scan "$_diff" "staged diff"
  local content_status=$?
  total_violations=$((total_violations + content_status))

  if [ "$total_violations" -gt 0 ]; then
    say ""
    fail "COMMIT BLOCKED — ${total_violations} security violation(s) detected."
    return 1
  fi

  pass "All ${file_count} staged file(s) clean — no secrets detected."
  return 0
}

# ── Mode: Scan CI / Diff Range ──────────────────────────────
scan_ci() {
  say ""
  say "${BOLD}${CYAN}━━━ YALIHAN OS Secret Scan (CI) ━━━${RESET}"
  say "   Scanner: scripts/tools/secret-scan.sh"
  say "   SSOT: YES"
  say "   Mode: CI"

  local total_violations=0
  local _diff=""
  local _label=""
  local changed_files=""

  if [ $# -gt 0 ] && [ -n "$1" ]; then
    local _range="$1"
    _label="git range (${_range})"
    changed_files=$(git diff --name-only "$_range" 2>/dev/null || true)
    _diff=$(git diff --no-color -U0 "$_range" 2>/dev/null || true)
  else
    _label="staged / working diff"
    changed_files=$(git diff --staged --name-only 2>/dev/null || true)
    _diff=$(git diff --staged --no-color -U0 2>/dev/null || true)
    # If no staged diff, check HEAD commit diff
    if [ -z "$_diff" ]; then
      _diff=$(git diff --no-color -U0 HEAD~1..HEAD 2>/dev/null || true)
      changed_files=$(git diff --name-only HEAD~1..HEAD 2>/dev/null || true)
      _label="HEAD commit diff"
    fi
  fi

  # 1. Path check
  if [ -n "$changed_files" ]; then
    check_blocked_paths "$changed_files"
    local path_status=$?
    total_violations=$((total_violations + path_status))
  fi

  # 2. Content scan
  if [ -n "$_diff" ]; then
    do_scan "$_diff" "$_label"
    local content_status=$?
    total_violations=$((total_violations + content_status))
  else
    pass "No diff content to scan."
  fi

  if [ "$total_violations" -gt 0 ]; then
    say ""
    fail "CI BLOCKED — ${total_violations} security violation(s) detected in ${_label}."
    return 1
  fi

  pass "CI scan clean — no secrets detected."
  return 0
}

# ── Version / Help ───────────────────────────────────────────
show_version() {
  say "secret-scan.sh v2.0.0 | SSOT | 2026-09-01"
  say "Patterns: secret-scan-patterns.txt (ghp_, github_pat_, sk-proj-, sk-, sk-ant-, xox*, AKIA, Bearer, PRIVATE KEY)"
  say "Usage:"
  say "  secret-scan.sh --staged          Scan staged diff (for pre-commit hooks)"
  say "  secret-scan.sh --ci [git-range]  Scan git diff range or staged changes (for CI)"
}

# ── Entry Point ─────────────────────────────────────────────
MODE="${1:-}"
case "$MODE" in
  --staged)
    scan_staged
    exit $?
    ;;
  --ci)
    shift
    scan_ci "$@"
    exit $?
    ;;
  --version)
    show_version
    exit 0
    ;;
  --help|-h|"")
    show_version
    exit 0
    ;;
  *)
    warn "Unknown mode: $MODE"
    show_version
    exit 2
    ;;
esac
