#!/usr/bin/env bash
# ==============================================================================
# Yalıhan SAB Propose — Natural Language → Proposal JSON Generator
# Purpose: Create validated governance proposals from command-line arguments.
# Version: 1.0.0
# Dependencies: jq >= 1.6
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# --- Config ---
PROPOSALS_DIR=".sab/proposals"
AUDIT_LOG=".sab/history/audit.log"
VALID_ACTIONS=("append" "update" "merge")

# --- Helpers ---
usage() {
    cat <<'EOF'
Usage: bash scripts/sab-propose.sh --target <path> --action <action> --value <json> [--reason <text>]

Required:
  --target   Dot-separated path in authority.json (e.g. governance.sealed_domains)
  --action   One of: append, update, merge
  --value    JSON value (string, object, array, number, boolean)

Optional:
  --reason   Human-readable reason for the proposal (logged in audit)

Examples:
  bash scripts/sab-propose.sh \
    --target governance.sealed_domains \
    --action append \
    --value '"app/Models/NewModel"'

  bash scripts/sab-propose.sh \
    --target context7_standards.enforcement_level \
    --action update \
    --value '"strict"' \
    --reason "Enforce strict mode for production"

  bash scripts/sab-propose.sh \
    --target project \
    --action merge \
    --value '{"monitoring_enabled": true}'
EOF
    exit 1
}

log_audit() {
    local msg="$1"
    local ts
    ts="$(date '+%Y-%m-%d %H:%M:%S')"
    echo "[$ts][PROPOSE] $msg" >> "$AUDIT_LOG"
}

die() {
    echo "[PROPOSE][ERROR] $1" >&2
    exit 1
}

# --- Parse arguments ---
TARGET=""
ACTION=""
VALUE=""
REASON=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --target)  TARGET="$2";  shift 2 ;;
        --action)  ACTION="$2";  shift 2 ;;
        --value)   VALUE="$2";   shift 2 ;;
        --reason)  REASON="$2";  shift 2 ;;
        -h|--help) usage ;;
        *)         die "Unknown argument: $1" ;;
    esac
done

# --- Validate required fields ---
[[ -z "$TARGET" ]] && die "Missing --target"
[[ -z "$ACTION" ]] && die "Missing --action"
[[ -z "$VALUE" ]]  && die "Missing --value"

# --- Validate action ---
valid=false
for a in "${VALID_ACTIONS[@]}"; do
    [[ "$a" == "$ACTION" ]] && valid=true && break
done
[[ "$valid" == false ]] && die "Invalid action '$ACTION'. Must be one of: ${VALID_ACTIONS[*]}"

# --- Validate target format (dot-separated, no spaces) ---
if [[ ! "$TARGET" =~ ^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*$ ]]; then
    die "Invalid target format '$TARGET'. Use dot-separated alphanumeric path (e.g. governance.sealed_domains)"
fi

# --- Validate value is valid JSON ---
if ! echo "$VALUE" | jq . > /dev/null 2>&1; then
    die "Invalid JSON value: $VALUE"
fi

# --- Pre-flight checks ---
if ! command -v jq &>/dev/null; then
    die "jq is required but not installed."
fi

[[ ! -d ".sab" ]] && die ".sab directory not found. SAB not initialized."
mkdir -p "$PROPOSALS_DIR" "$(dirname "$AUDIT_LOG")"

# --- Generate proposal ---
PROPOSAL_ID="proposal-$(date +%Y%m%d-%H%M%S)-$$"
PROPOSAL_FILE="$PROPOSALS_DIR/${PROPOSAL_ID}.json"

proposal_json=$(jq -n \
    --arg target "$TARGET" \
    --arg action "$ACTION" \
    --argjson value "$VALUE" \
    '{target: $target, action: $action, value: $value}')

# --- Final validation: re-parse generated JSON ---
if ! echo "$proposal_json" | jq -e '.target, .action, .value' > /dev/null 2>&1; then
    die "Generated proposal failed validation."
fi

# --- Write proposal ---
echo "$proposal_json" > "$PROPOSAL_FILE"

# --- Audit log ---
reason_suffix=""
[[ -n "$REASON" ]] && reason_suffix=" | reason: $REASON"
log_audit "Created ${PROPOSAL_ID} → target=$TARGET action=$ACTION${reason_suffix}"

# --- Output ---
echo "[PROPOSE] Proposal created: $PROPOSAL_FILE"
echo "[PROPOSE] ID: $PROPOSAL_ID"
echo "[PROPOSE] Target: $TARGET"
echo "[PROPOSE] Action: $ACTION"
echo "[PROPOSE] Value: $(echo "$VALUE" | jq -c .)"
[[ -n "$REASON" ]] && echo "[PROPOSE] Reason: $REASON"
echo "[PROPOSE] Ready for pipeline execution."
