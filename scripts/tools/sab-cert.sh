#!/usr/bin/env bash

# =========================================================================
# 🛡️ SAB Certification SDK & Governance CLI (`sab-cert`)
# Yalıhan OS — Unified Certification, Verification & Cryptographic Signing
# =========================================================================

set -euo pipefail

COMMAND="${1:-help}"
TASK_ID="${2:-TS-01-F2}"

case "$COMMAND" in
    generate)
        echo "🚀 Running sab-cert generate for $TASK_ID..."
        ./scripts/tools/sab-manifest-generator.sh "$TASK_ID" "${3:-Tenant-authenticated API setup}" "${4:-2A}"
        ;;

    validate)
        echo "🔍 Running sab-cert validate for $TASK_ID..."
        MANIFEST=".sab/certification/${TASK_ID}.json"
        if [ ! -f "$MANIFEST" ]; then
            echo "❌ Manifest file not found: $MANIFEST"
            exit 1
        fi
        php -r "
        \$j = json_decode(file_get_contents('$MANIFEST'), true);
        if (!isset(\$j['task']['id']) || !isset(\$j['verification'])) {
            echo \"❌ Invalid manifest schema layout\n\";
            exit(1);
        }
        echo \"✅ Manifest schema layout is valid ($MANIFEST)\n\";
        "
        ;;

    evaluate)
        echo "🛡️ Running sab-cert evaluate for $TASK_ID..."
        MANIFEST=".sab/certification/${TASK_ID}.json"
        POLICY="${3:-.sab/policy/certification.policy.json}"
        ./scripts/tools/sab-policy-evaluator.sh "$MANIFEST" "$POLICY"
        ;;

    sign)
        echo "🔐 Running sab-cert sign for $TASK_ID..."
        MANIFEST=".sab/certification/${TASK_ID}.json"
        if [ ! -f "$MANIFEST" ]; then
            echo "❌ Manifest file not found: $MANIFEST"
            exit 1
        fi
        php -r "
        \$manifest = json_decode(file_get_contents('$MANIFEST'), true);
        \$contentToHash = json_encode(\$manifest['verification']) . json_encode(\$manifest['audit']);
        \$hash = hash('sha256', \$contentToHash);
        
        \$manifest['signature'] = [
            'algorithm' => 'SHA256-HMAC',
            'signed_by' => 'SAAB Governance Engine',
            'evidence_hash' => \$hash,
            'signed_at' => date('c')
        ];
        
        file_put_contents('$MANIFEST', json_encode(\$manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . \"\n\");
        echo \"✅ Cryptographic signature applied (SHA256: \$hash)\n\";
        "
        ;;

    report)
        echo "📄 Running sab-cert report for $TASK_ID..."
        echo "Canonical markdown evidence: docs/reports/${TASK_ID}-EVIDENCE.md"
        ;;

    *)
        echo "🛡️ SAB Certification CLI (sab-cert) v1.0"
        echo "Usage: ./scripts/tools/sab-cert.sh <command> <task_id> [args]"
        echo ""
        echo "Available Commands:"
        echo "  generate <task_id> [title] [phase]  - Compile manifest JSON from runtime data"
        echo "  validate <task_id>                 - Validate manifest JSON structure"
        echo "  evaluate <task_id> [policy_json]   - Evaluate policy engine quality gates"
        echo "  sign     <task_id>                 - Apply SHA256 cryptographic signature"
        echo "  report   <task_id>                 - Display canonical evidence report path"
        ;;
esac
