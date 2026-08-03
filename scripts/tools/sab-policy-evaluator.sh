#!/usr/bin/env bash

# =========================================================================
# 🛡️ SAB Policy Evaluator & Multi-Gate Quality Engine v1.3
# Evaluates certification manifests against declarative policy rules.
# Produces structured multi-gate JSON result (regression, health, governance, merge).
# Enforces: waiver expiry, canonical fingerprint, source commit provenance.
# Classifies fallback fingerprints as PROVISIONAL_WAIVER (HUMAN_REVIEW_REQUIRED).
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

echo "🚀 SAB Policy Engine v1.3: Evaluating $MANIFEST_FILE against $POLICY_FILE..."
echo "========================================================================="

php -r "
\$manifest = json_decode(file_get_contents('$MANIFEST_FILE'), true);
\$policy = json_decode(file_get_contents('$POLICY_FILE'), true);

\$ruleResults = [];
\$gates = ['regression' => true, 'repository_health' => true, 'governance' => true];
\$waiverDiagnostics = [];

// ─── Pre-compute waiver eligibility ───────────────────────────────────
\$waiverConfig = \$policy['baseline_waiver'] ?? [];
\$waiverBlocked = false;
\$waiverBlockReasons = [];
\$isProvisionalWaiver = false;

// (A) Check waiver expiry
if (\$waiverConfig['enforce_expiry'] ?? false) {
    \$expiresAt = \$manifest['waiver']['expires_at'] ?? \$manifest['baseline']['expires_at'] ?? null;
    if (\$expiresAt === null) {
        \$waiverBlocked = true;
        \$waiverBlockReasons[] = 'MISSING_EXPIRY (waiver.expires_at not set)';
    } else {
        \$expiryDate = strtotime(\$expiresAt);
        if (\$expiryDate === false) {
            \$waiverBlocked = true;
            \$waiverBlockReasons[] = 'INVALID_EXPIRY_FORMAT (expires_at: ' . \$expiresAt . ')';
        } elseif (\$expiryDate < strtotime('today')) {
            \$waiverBlocked = true;
            \$waiverBlockReasons[] = 'WAIVER_EXPIRED (expires_at: ' . \$expiresAt . ')';
        }
    }
    \$waiverDiagnostics['expiry_check'] = \$waiverBlocked ? end(\$waiverBlockReasons) : 'VALID';
}

// (B) Check canonical fingerprint & fallback source classification
if ((\$waiverConfig['enforce_fingerprint'] ?? false) && !\$waiverBlocked) {
    \$storedFingerprint = \$manifest['baseline']['fingerprint_hash'] ?? \$manifest['waiver']['fingerprint_hash'] ?? null;
    \$fingerprintSource = \$manifest['baseline']['fingerprint_source'] ?? 'UNKNOWN';

    if (\$storedFingerprint === null) {
        \$waiverBlocked = true;
        \$waiverBlockReasons[] = 'MISSING_FINGERPRINT (baseline.fingerprint_hash not set)';
    } else {
        // Recompute from canonical test identities or fallback debt areas
        \$identities = \$manifest['baseline']['failing_test_identities'] ?? null;
        if (\$identities !== null && !empty(\$identities)) {
            sort(\$identities);
            \$currentFingerprint = hash('sha256', implode('|', \$identities));
        } else {
            \$debtAreas = \$manifest['baseline']['known_debt_areas'] ?? [];
            if (empty(\$debtAreas)) {
                \$waiverBlocked = true;
                \$waiverBlockReasons[] = 'EMPTY_DEBT_REGISTER (no test identities or debt areas to fingerprint)';
            } else {
                sort(\$debtAreas);
                \$currentFingerprint = hash('sha256', implode('|', \$debtAreas));
            }
        }

        if (!\$waiverBlocked && \$storedFingerprint !== \$currentFingerprint) {
            \$waiverBlocked = true;
            \$waiverBlockReasons[] = 'FINGERPRINT_MISMATCH (stored: ' . substr(\$storedFingerprint, 0, 12) . '... vs computed: ' . substr(\$currentFingerprint, 0, 12) . '...)';
        }

        // If source is FALLBACK_DEBT_AREAS, flag as provisional waiver
        if (!\$waiverBlocked && \$fingerprintSource === 'FALLBACK_DEBT_AREAS') {
            \$isProvisionalWaiver = true;
        }
    }
    \$waiverDiagnostics['fingerprint_check'] = \$waiverBlocked ? end(\$waiverBlockReasons) : (\$isProvisionalWaiver ? 'PROVISIONAL (FALLBACK_DEBT_AREAS)' : 'VALID (CANONICAL_IDENTITIES)');
}

// (C) Check waiver provenance & source_commit_sha matching
if ((\$waiverConfig['enforce_provenance'] ?? false) && !\$waiverBlocked) {
    \$requiredFields = \$waiverConfig['provenance_fields'] ?? [];
    \$missingFields = [];
    foreach (\$requiredFields as \$fieldPath) {
        \$keys = explode('.', \$fieldPath);
        \$current = \$manifest;
        \$found = true;
        foreach (\$keys as \$k) {
            if (isset(\$current[\$k]) && \$current[\$k] !== null && \$current[\$k] !== '') {
                \$current = \$current[\$k];
            } else {
                \$found = false;
                break;
            }
        }
        if (!\$found) {
            \$missingFields[] = \$fieldPath;
        }
    }
    if (!empty(\$missingFields)) {
        \$waiverBlocked = true;
        \$waiverBlockReasons[] = 'MISSING_PROVENANCE (missing: ' . implode(', ', \$missingFields) . ')';
    }

    // Enforce source_commit_sha match between waiver and audit
    if (!\$waiverBlocked && (\$waiverConfig['enforce_commit_match'] ?? false)) {
        \$waiverCommit = \$manifest['waiver']['source_commit_sha'] ?? \$manifest['waiver']['commit_sha'] ?? null;
        \$auditCommit = \$manifest['audit']['source_commit_sha'] ?? \$manifest['audit']['head_sha'] ?? null;
        if (\$waiverCommit !== null && \$auditCommit !== null && \$waiverCommit !== \$auditCommit) {
            \$waiverBlocked = true;
            \$waiverBlockReasons[] = 'PROVENANCE_COMMIT_MISMATCH (waiver: ' . substr(\$waiverCommit, 0, 10) . ' vs audit: ' . substr(\$auditCommit, 0, 10) . ')';
        }
    }

    \$waiverDiagnostics['provenance_check'] = \$waiverBlocked ? end(\$waiverBlockReasons) : 'VALID';
}

// Determine primary block reason for display
\$primaryBlockReason = !empty(\$waiverBlockReasons) ? \$waiverBlockReasons[0] : null;

// Determine block status type for display
\$blockStatusType = null;
if (\$waiverBlocked && \$primaryBlockReason !== null) {
    if (str_contains(\$primaryBlockReason, 'EXPIRED')) \$blockStatusType = 'EXPIRED';
    elseif (str_contains(\$primaryBlockReason, 'FINGERPRINT_MISMATCH')) \$blockStatusType = 'FINGERPRINT_MISMATCH';
    elseif (str_contains(\$primaryBlockReason, 'PROVENANCE_COMMIT_MISMATCH')) \$blockStatusType = 'COMMIT_MISMATCH';
    elseif (str_contains(\$primaryBlockReason, 'MISSING_PROVENANCE')) \$blockStatusType = 'MISSING_PROVENANCE';
    elseif (str_contains(\$primaryBlockReason, 'MISSING_EXPIRY') || str_contains(\$primaryBlockReason, 'INVALID_EXPIRY')) \$blockStatusType = 'INVALID_WAIVER';
    elseif (str_contains(\$primaryBlockReason, 'EMPTY_DEBT') || str_contains(\$primaryBlockReason, 'MISSING_FINGERPRINT')) \$blockStatusType = 'INCOMPLETE_WAIVER';
    else \$blockStatusType = 'WAIVER_BLOCKED';
}

// ─── Evaluate rules ───────────────────────────────────────────────────
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
    \$statusOverride = null;
    if (\$rule['operator'] === 'EQUALS') {
        \$passed = (\$val === \$rule['expected_value']);
    }

    // Check baseline waiver for waivable rules
    if (!\$passed && (\$rule['waivable'] ?? false) && !empty(\$waiverConfig)) {
        if (\$waiverBlocked) {
            \$statusOverride = \$blockStatusType;
        } else {
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
    }

    \$gate = \$rule['gate'] ?? 'governance';
    if (!\$passed) {
        \$gates[\$gate] = false;
    }

    \$status = \$statusOverride ?? (\$passed ? (\$waived ? (\$isProvisionalWaiver ? 'PROVISIONAL_WAIVER' : 'WAIVED') : 'PASS') : 'FAIL');

    \$ruleResults[] = [
        'rule_id' => \$rule['id'],
        'field' => \$rule['field'],
        'operator' => \$rule['operator'],
        'expected' => \$rule['expected_value'],
        'actual' => \$val,
        'gate' => \$gate,
        'status' => \$status,
        'waived' => \$waived,
        'provisional' => \$isProvisionalWaiver,
        'waiver_block_reason' => (\$statusOverride !== null) ? \$primaryBlockReason : null,
        'description' => \$rule['description']
    ];
}

\$repoHealthStatus = \$gates['repository_health'] ? (\$isProvisionalWaiver ? 'PROVISIONAL_WAIVER' : 'PASS') : 'FAIL';
\$mergeGateStatus = (\$gates['regression'] && \$gates['repository_health'] && \$gates['governance']) ? (\$isProvisionalWaiver ? 'HUMAN_REVIEW_REQUIRED' : 'READY_FOR_MERGE') : 'HOLD';

\$output = [
    'task_id' => '$TASK_ID',
    'decision' => \$mergeGateStatus,
    'evaluated_at' => date('c'),
    'policy_version' => \$policy['policy_version'] ?? '1.3',
    'gates' => [
        'regression_gate' => \$gates['regression'] ? 'PASS' : 'FAIL',
        'repository_health_gate' => \$repoHealthStatus,
        'governance_gate' => \$gates['governance'] ? 'PASS' : 'FAIL',
        'merge_gate' => \$mergeGateStatus
    ],
    'waiver_diagnostics' => \$waiverDiagnostics,
    'rules_evaluated' => count(\$ruleResults),
    'rules_passed' => count(array_filter(\$ruleResults, fn(\$r) => \$r['status'] !== 'FAIL' && !str_contains(\$r['status'], 'MISMATCH') && \$r['status'] !== 'EXPIRED' && !str_contains(\$r['status'], 'MISSING') && !str_contains(\$r['status'], 'INVALID') && !str_contains(\$r['status'], 'INCOMPLETE'))),
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

if (!empty(\$waiverDiagnostics)) {
    echo \"  Waiver Diagnostics:\n\";
    foreach (\$waiverDiagnostics as \$check => \$result) {
        \$diagSymbol = str_contains(\$result, 'VALID') ? '✅' : (str_contains(\$result, 'PROVISIONAL') ? '🟡' : '❌');
        echo \"    \$diagSymbol \$check: \$result\n\";
    }
    echo \"=========================================================================\n\";
}

foreach (\$ruleResults as \$r) {
    \$symbol = match(\$r['status']) {
        'PASS' => '✅',
        'WAIVED' => '🟡',
        'PROVISIONAL_WAIVER' => '🟡',
        'EXPIRED' => '⏰',
        'FINGERPRINT_MISMATCH' => '🔴',
        'COMMIT_MISMATCH' => '🔴',
        'MISSING_PROVENANCE' => '🔒',
        'INVALID_WAIVER' => '⚠️',
        'INCOMPLETE_WAIVER' => '⚠️',
        default => '❌'
    };
    \$extra = (\$r['waiver_block_reason'] ?? null) ? ' [' . \$r['waiver_block_reason'] . ']' : '';
    echo \"\$symbol [\" . \$r['rule_id'] . \"] \" . \$r['description'] . \": \" . \$r['status'] . \" (Actual: \" . json_encode(\$r['actual']) . \")\" . \$extra . \"\n\";
}

echo \"=========================================================================\n\";
echo \"📄 Policy Result JSON saved to: $RESULT_FILE\n\";

if (\$mergeGateStatus === 'HOLD') {
    exit(2);
} elseif (\$mergeGateStatus === 'HUMAN_REVIEW_REQUIRED') {
    exit(1);
}
"
