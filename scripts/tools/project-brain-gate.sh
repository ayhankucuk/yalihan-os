#!/usr/bin/env bash

set -euo pipefail

# Read-only local gate. This script never connects to VPS and never mutates data.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

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

echo "PASS  brain prerequisites present"
echo "STATUS  local review may continue; production remains untouched"
