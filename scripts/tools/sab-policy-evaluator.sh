#!/usr/bin/env bash

# =========================================================================
# 🛡️ SAB Policy Evaluator & Quality Gate CLI
# Yalıhan OS — Governance Automation
# =========================================================================

set -euo pipefail

MANIFEST_FILE="${1:-.sab/certification/TS-01-F2.json}"

if [ ! -f "$MANIFEST_FILE" ]; then
    echo "❌ ERROR: Manifest file not found: $MANIFEST_FILE"
    exit 1
fi

echo "🚀 SAB Policy Evaluator: Assessing $MANIFEST_FILE..."
echo "========================================================================="

# 1. Read JSON values using php -r
EVALUATION=$(php -r "
\$json = json_decode(file_get_contents('$MANIFEST_FILE'), true);
\$newRegressions = \$json['verification']['new_regressions'] ?? -1;
\$preflightStatus = \$json['audit']['preflight_status'] ?? 'FAIL';
\$approvalStatus = \$json['approval']['status'] ?? 'REJECTED';
\$taskId = \$json['task']['id'] ?? 'UNKNOWN';

echo \"TASK_ID=\$taskId\n\";
echo \"NEW_REGRESSIONS=\$newRegressions\n\";
echo \"PREFLIGHT=\$preflightStatus\n\";
echo \"APPROVAL=\$approvalStatus\n\";
")

eval "$EVALUATION"

echo "📋 Task ID:              $TASK_ID"
echo "🔍 Preflight Status:      $PREFLIGHT"
echo "🛡️ New Regressions:       $NEW_REGRESSIONS"
echo "🟢 Approval Status:       $APPROVAL"
echo "-------------------------------------------------------------------------"

FAILURES=0

if [ "$NEW_REGRESSIONS" -ne 0 ]; then
    echo "❌ POLICY VIOLATION: new_regressions is $NEW_REGRESSIONS (expected 0)"
    FAILURES=$((FAILURES + 1))
else
    echo "✅ PASS: Zero new regressions verified (0)"
fi

if [ "$PREFLIGHT" != "PASS" ]; then
    echo "❌ POLICY VIOLATION: preflight_status is $PREFLIGHT (expected PASS)"
    FAILURES=$((FAILURES + 1))
else
    echo "✅ PASS: Preflight status verified (PASS)"
fi

if [ "$APPROVAL" != "APPROVED_FOR_MERGE" ]; then
    echo "❌ POLICY VIOLATION: approval.status is $APPROVAL (expected APPROVED_FOR_MERGE)"
    FAILURES=$((FAILURES + 1))
else
    echo "✅ PASS: Approval status verified (APPROVED_FOR_MERGE)"
fi

echo "========================================================================="

if [ "$FAILURES" -eq 0 ]; then
    echo "🟢 FINAL SAB QUALITY GATE DECISION: READY FOR MERGE"
    exit 0
else
    echo "🔴 FINAL SAB QUALITY GATE DECISION: REJECTED ($FAILURES violations)"
    exit 2
fi
