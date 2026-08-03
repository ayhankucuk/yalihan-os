#!/usr/bin/env bash

# =========================================================================
# 🛡️ SAB Policy Evaluator & Multi-Gate Quality Engine
# Evaluates certification manifests against declarative policy rules.
# Produces structured multi-gate JSON result (regression, health, governance, merge).
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
\$gates = ['regression' => true, 'repository_health' => true, 'governance' => true];

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
    \$waived = false;
    if (\$rule['operator'] === 'EQUALS') {
        \$passed = (\$val === \$rule['expected_value']);
    }

    // Check baseline waiver for waivable rules
    if (!\$passed && (\$rule['waivable'] ?? false) && isset(\$policy['baseline_waiver'])) {
        if (\$rule['id'] === 'RULE_ZERO_FAILURES') {
            \$exempted = \$manifest['baseline']['exempted_failures'] ?? null;
            if (\$exempted !== null && \$val === \$exempted) {
                \$waived = true;
                \$passed = true;
            }
        }
        if (\$rule['id'] === 'RULE_ZERO_ERRORS') {
            \$exempted = \$manifest['baseline']['exempted_errors'] ?? null;
            if (\$exempted !== null && \$val === \$exempted) {
                \$waived = true;
                \$passed = true;
            }
        }
    }

    \$gate = \$rule['gate'] ?? 'governance';
    if (!\$passed) {
        \$gates[\$gate] = false;
    }

    \$status = \$passed ? (\$waived ? 'WAIVED' : 'PASS') : 'FAIL';

    \$ruleResults[] = [
        'rule_id' => \$rule['id'],
        'field' => \$rule['field'],
        'operator' => \$rule['operator'],
        'expected' => \$rule['expected_value'],
        'actual' => \$val,
        'gate' => \$gate,
        'status' => \$status,
        'waived' => \$waived,
        'description' => \$rule['description']
    ];
}

\$mergeGate = (\$gates['regression'] && \$gates['repository_health'] && \$gates['governance']);
\$decision = \$mergeGate ? 'READY_FOR_MERGE' : 'HOLD';

\$output = [
    'task_id' => '$TASK_ID',
    'decision' => \$decision,
    'evaluated_at' => date('c'),
    'policy_version' => \$policy['policy_version'] ?? '1.0',
    'gates' => [
        'regression_gate' => \$gates['regression'] ? 'PASS' : 'FAIL',
        'repository_health_gate' => \$gates['repository_health'] ? 'PASS' : 'FAIL',
        'governance_gate' => \$gates['governance'] ? 'PASS' : 'FAIL',
        'merge_gate' => \$mergeGate ? 'PASS' : 'HOLD'
    ],
    'rules_evaluated' => count(\$ruleResults),
    'rules_passed' => count(array_filter(\$ruleResults, fn(\$r) => \$r['status'] !== 'FAIL')),
    'rules' => \$ruleResults
];

file_put_contents('$RESULT_FILE', json_encode(\$output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . \"\n\");

echo \"📋 Task ID:              $TASK_ID\n\";
echo \"=========================================================================\n\";
echo \"  Regression Gate:        \" . \$output['gates']['regression_gate'] . \"\n\";
echo \"  Repository Health Gate: \" . \$output['gates']['repository_health_gate'] . \"\n\";
echo \"  Governance Gate:        \" . \$output['gates']['governance_gate'] . \"\n\";
echo \"  ─────────────────────────────────────────────────────────────────────\n\";
echo \"  Merge Gate:             \" . \$output['gates']['merge_gate'] . \"\n\";
echo \"=========================================================================\n\";

foreach (\$ruleResults as \$r) {
    \$symbol = (\$r['status'] === 'PASS') ? '✅' : ((\$r['status'] === 'WAIVED') ? '🟡' : '❌');
    echo \"\$symbol [\" . \$r['rule_id'] . \"] \" . \$r['description'] . \": \" . \$r['status'] . \" (Actual: \" . json_encode(\$r['actual']) . \")\n\";
}

echo \"=========================================================================\n\";
echo \"📄 Policy Result JSON saved to: $RESULT_FILE\n\";

if (!\$mergeGate) {
    exit(2);
}
"
