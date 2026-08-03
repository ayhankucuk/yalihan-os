#!/usr/bin/env bash

# =========================================================================
# 🛡️ SAB Policy Evaluator & Quality Gate CLI
# Evaluates certification manifests and emits machine-readable evaluation results.
# =========================================================================

set -euo pipefail

MANIFEST_FILE="${1:-.sab/certification/TS-01-F2.json}"
POLICY_FILE="${2:-.sab/policy/certification.policy.json}"

if [ ! -f "$MANIFEST_FILE" ]; then
    echo "❌ ERROR: Manifest file not found: $MANIFEST_FILE"
    exit 1
fi

if [ ! -f "$POLICY_FILE" ]; then
    echo "❌ ERROR: Policy file not found: $POLICY_FILE"
    exit 1
fi

TASK_ID=$(php -r "\$j = json_decode(file_get_contents('$MANIFEST_FILE'), true); echo \$j['task']['id'] ?? 'UNKNOWN';")
RESULT_FILE=".sab/certification/${TASK_ID}.policy-result.json"

echo "🚀 SAB Policy Engine: Evaluating $MANIFEST_FILE against $POLICY_FILE..."
echo "========================================================================="

php -r "
\$manifest = json_decode(file_get_contents('$MANIFEST_FILE'), true);
\$policy = json_decode(file_get_contents('$POLICY_FILE'), true);

\$ruleResults = [];
\$allPassed = true;

foreach (\$policy['rules'] as \$rule) {
    \$keys = explode('.', \$rule['field']);
    \$current = \$manifest;
    \$val = null;
    
    foreach (\$keys as \$k) {
        if (isset(\$current[\$k])) {
            \$current = \$current[\$k];
            \$val = \$current;
        } else {
            \$val = null;
            break;
        }
    }

    \$passed = false;
    if (\$rule['operator'] === 'EQUALS') {
        \$passed = (\$val === \$rule['expected_value']);
    }

    if (!\$passed) {
        \$allPassed = false;
    }

    \$ruleResults[] = [
        'rule_id' => \$rule['id'],
        'field' => \$rule['field'],
        'operator' => \$rule['operator'],
        'expected' => \$rule['expected_value'],
        'actual' => \$val,
        'status' => \$passed ? 'PASS' : 'FAIL',
        'description' => \$rule['description']
    ];
}

\$decision = \$allPassed ? 'READY_FOR_MERGE' : 'REJECTED';

\$output = [
    'task_id' => '$TASK_ID',
    'decision' => \$decision,
    'evaluated_at' => date('c'),
    'policy_version' => \$policy['policy_version'] ?? '1.0',
    'rules_evaluated' => count(\$ruleResults),
    'rules_passed' => count(array_filter(\$ruleResults, fn(\$r) => \$r['status'] === 'PASS')),
    'rules' => \$ruleResults
];

file_put_contents('$RESULT_FILE', json_encode(\$output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . \"\n\");

echo \"📋 Task ID:              $TASK_ID\n\";
echo \"🟢 Quality Gate Result:  \" . \$decision . \"\n\";
echo \"-------------------------------------------------------------------------\n\";

foreach (\$ruleResults as \$r) {
    \$symbol = (\$r['status'] === 'PASS') ? '✅' : '❌';
    echo \"\$symbol [\" . \$r['rule_id'] . \"] \" . \$r['description'] . \": \" . \$r['status'] . \" (Actual: \" . json_encode(\$r['actual']) . \")\n\";
}

echo \"=========================================================================\n\";
echo \"📄 Policy Result JSON saved to: $RESULT_FILE\n\";

if (!\$allPassed) {
    exit(2);
}
"
