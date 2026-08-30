#!/usr/bin/env bash

set -euo pipefail

# Read-only local gate. This script never connects to VPS and never mutates data.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

STRICT_MODE=false
if [[ "${1:-}" == "--strict" ]]; then
  STRICT_MODE=true
fi

required_files=(
  AGENTS.md
  .project-brain/PROJECT_STATE.md
  .project-brain/FEATURE_MATRIX.md
  .project-brain/EVIDENCE_INDEX.md
  .project-brain/KNOWN_ISSUES.md
  .project-brain/RELEASE_CHECKLIST.md
  .project-brain/SECURITY_PROTOCOL.md
  .project-brain/IMPACT_ANALYSIS.md
  .project-brain/OBSERVABILITY_PLAN.md
  .project-brain/ROLLBACK_SIMULATOR.md
)

echo "PROJECT BRAIN GATE (LOCAL / READ-ONLY)"
echo "Repository: $repo_root"

missing=0
for file in "${required_files[@]}"; do
  if [[ -s "$file" ]]; then
    echo "PASS  $file"
  else
    echo "FAIL  missing or empty: $file"
    missing=1
  fi
done

# Engineering Protocol Header Linter
echo ""
echo "ENGINEERING PROTOCOL HEADER LINTER"
header_warnings=0

check_engineering_header() {
  local target_file="$1"
  local is_strict="${2:-false}"
  local missing_fields=()

  if [[ ! -f "$target_file" ]]; then
    return 0
  fi

  # Check 5 mandatory header elements
  grep -qi "Repository Commit" "$target_file" || missing_fields+=("Repository Commit")
  grep -qi "Working Tree" "$target_file" || missing_fields+=("Working Tree")
  grep -qi "Evidence Date" "$target_file" || missing_fields+=("Evidence Date")
  grep -qi "Evidence Level" "$target_file" || missing_fields+=("Evidence Level")
  grep -qi "Production Authorization" "$target_file" || missing_fields+=("Production Authorization")

  if (( ${#missing_fields[@]} == 0 )); then
    echo "PASS  [Header Valid] $target_file"
  else
    echo "WARN  [UNKNOWN / Missing Header Fields] $target_file -> Missing: ${missing_fields[*]}"
    ((header_warnings++))
    if [[ "$is_strict" == "true" ]]; then
      return 1
    fi
  fi
  return 0
}

# Scan core project brain state and audits if present
if [[ -f ".project-brain/PROJECT_STATE.md" ]]; then
  check_engineering_header ".project-brain/PROJECT_STATE.md" "$STRICT_MODE"
fi

for audit_file in audits/*.md audits/golden-thread-evidence/*.md; do
  if [[ -f "$audit_file" && "$(basename "$audit_file")" != "README.md" ]]; then
    check_engineering_header "$audit_file" "$STRICT_MODE"
  fi
done

echo ""
if ! git diff --check; then
  echo "FAIL  git diff --check"
  exit 1
fi
echo "PASS  git diff --check"

if git diff --name-only | rg -q '(^|/)(\.env|.*\.key|.*\.pem)$'; then
  echo "FAIL  possible secret-bearing file in tracked diff"
  exit 1
fi
echo "PASS  no obvious secret-bearing tracked diff"

if (( missing != 0 )); then
  echo "BLOCKED  project-brain prerequisites are incomplete"
  exit 1
fi

if (( header_warnings > 0 )); then
  if [[ "$STRICT_MODE" == "true" ]]; then
    echo "FAIL  $header_warnings report(s) flagged as UNKNOWN due to missing/invalid header schema (--strict mode)"
    exit 1
  else
    echo "INFO  $header_warnings report(s) flagged as UNKNOWN due to legacy/missing header schema (Step 2 migration target)"
  fi
fi

echo "PASS  brain prerequisites present"
echo "STATUS  local review may continue; production remains untouched"
