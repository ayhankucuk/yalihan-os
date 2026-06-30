#!/usr/bin/env bash
# ==============================================================================
# Yalıhan SAB Decision Engine — AI Governance Auto-Decision
# Purpose: Scan authority state, detect drift/violations, generate proposals.
# Version: 1.0.0
# Mode: guarded (suggest + auto-propose for low-risk, log-only for high-risk)
# Dependencies: jq >= 1.6
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# --- Config ---
SAB_DIR=".sab"
AUTHORITY_FILE="$SAB_DIR/authority.json"
AUTHORITY_SOURCE="config/authority.json"
PROPOSALS_DIR="$SAB_DIR/proposals"
AUDIT_LOG="$SAB_DIR/history/audit.log"
DECISION_LOG="$SAB_DIR/history/decisions.log"
LATEST_AUTHORITY="$SAB_DIR/latest/authority.json"

# Risk levels: low → auto-propose, medium → propose + warn, high → log-only
RISK_LEVELS=("low" "medium" "high")

# Counters
FINDINGS=0
PROPOSALS_GENERATED=0
HIGH_RISK_SKIPPED=0

# --- Helpers ---
ts() { date '+%Y-%m-%d %H:%M:%S'; }

log_decision() {
    local level="$1" risk="$2" rule="$3" msg="$4"
    mkdir -p "$(dirname "$DECISION_LOG")"
    echo "[$(ts)][DECIDE][$level][$risk] rule=$rule | $msg" >> "$DECISION_LOG"
}

log_audit() {
    local msg="$1"
    mkdir -p "$(dirname "$AUDIT_LOG")"
    echo "[$(ts)][DECIDE] $msg" >> "$AUDIT_LOG"
}

emit_proposal() {
    local target="$1" action="$2" value="$3" reason="$4" risk="$5" rule="$6"

    if [[ "$risk" == "high" ]]; then
        echo "[DECIDE][HIGH-RISK] $reason → SKIPPED (requires manual approval)"
        log_decision "SKIP" "$risk" "$rule" "$reason"
        HIGH_RISK_SKIPPED=$((HIGH_RISK_SKIPPED + 1))
        return 0
    fi

    local proposal_id="proposal-decide-$(date +%Y%m%d-%H%M%S)-$$-${PROPOSALS_GENERATED}"
    local proposal_file="$PROPOSALS_DIR/${proposal_id}.json"

    jq -n \
        --arg target "$target" \
        --arg action "$action" \
        --argjson value "$value" \
        --arg reason "$reason" \
        --arg risk "$risk" \
        --arg rule "$rule" \
        --arg decided_at "$(ts)" \
        '{
            target: $target,
            action: $action,
            value: $value,
            _meta: {
                reason: $reason,
                risk: $risk,
                rule: $rule,
                decided_at: $decided_at,
                engine: "sab-decide-v1.0.0"
            }
        }' > "$proposal_file"

    echo "[DECIDE][PROPOSE] $proposal_id → $reason (risk=$risk)"
    log_decision "PROPOSE" "$risk" "$rule" "Created $proposal_id: $reason"
    PROPOSALS_GENERATED=$((PROPOSALS_GENERATED + 1))
}

die() { echo "[DECIDE][FATAL] $1" >&2; exit 1; }

# --- Pre-flight ---
command -v jq &>/dev/null || die "jq is required."
[[ -d "$SAB_DIR" ]] || die ".sab directory not found."
mkdir -p "$PROPOSALS_DIR" "$(dirname "$DECISION_LOG")"

echo "[DECIDE] Engine starting... ($(ts))"
log_audit "Decision engine started."

# =============================================================================
# RULE 1: Authority Sync Drift
# Check: .sab/authority.json should mirror config/authority.json
# Risk: low (auto-fix — just copy the source of truth)
# =============================================================================
rule1_authority_sync() {
    local rule="authority_sync"

    if [[ ! -f "$AUTHORITY_SOURCE" ]]; then
        log_decision "WARN" "medium" "$rule" "Source authority not found: $AUTHORITY_SOURCE"
        return 0
    fi

    local source_size sab_size
    source_size=$(wc -c < "$AUTHORITY_SOURCE" | tr -d ' ')
    sab_size=$(wc -c < "$AUTHORITY_FILE" 2>/dev/null | tr -d ' ' || echo "0")

    if [[ "$sab_size" -eq 0 ]]; then
        echo "[DECIDE][FINDING] .sab/authority.json is EMPTY — source has ${source_size} bytes"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.authority_sync" \
            "update" \
            '"resync_from_source"' \
            ".sab/authority.json is empty, needs resync from config/authority.json" \
            "low" \
            "$rule"
        # Direct fix: copy source to .sab
        cp "$AUTHORITY_SOURCE" "$AUTHORITY_FILE"
        cp "$AUTHORITY_SOURCE" "$LATEST_AUTHORITY"
        echo "[DECIDE][AUTO-FIX] Copied $AUTHORITY_SOURCE → $AUTHORITY_FILE"
        log_decision "FIX" "low" "$rule" "Resynced authority from source."
        return 0
    fi

    # Compare checksums
    local source_hash sab_hash
    source_hash=$(shasum -a 256 "$AUTHORITY_SOURCE" | cut -d' ' -f1)
    sab_hash=$(shasum -a 256 "$AUTHORITY_FILE" | cut -d' ' -f1)

    if [[ "$source_hash" != "$sab_hash" ]]; then
        echo "[DECIDE][FINDING] Authority drift detected (hashes differ)"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.authority_sync" \
            "update" \
            '"resync_from_source"' \
            "Authority drift: .sab/authority.json differs from config/authority.json" \
            "low" \
            "$rule"
        cp "$AUTHORITY_SOURCE" "$AUTHORITY_FILE"
        cp "$AUTHORITY_SOURCE" "$LATEST_AUTHORITY"
        echo "[DECIDE][AUTO-FIX] Resynced authority."
        log_decision "FIX" "low" "$rule" "Resynced: hash mismatch."
    else
        echo "[DECIDE][OK] Authority in sync."
    fi
}

# =============================================================================
# RULE 2: Required Authority Fields
# Check: authority.json must have version, project, context7_standards
# Risk: medium (missing fields = governance gap)
# =============================================================================
rule2_required_fields() {
    local rule="required_fields"
    local authority_data

    authority_data=$(cat "$AUTHORITY_FILE" 2>/dev/null || echo "{}")
    [[ -z "$authority_data" ]] && authority_data="{}"

    local required_fields=("version" "project" "context7_standards" "compliance_metrics")

    for field in "${required_fields[@]}"; do
        local has_field
        has_field=$(echo "$authority_data" | jq -r --arg f "$field" 'has($f) | tostring' 2>/dev/null || echo "false")

        if [[ "$has_field" != "true" ]]; then
            echo "[DECIDE][FINDING] Missing required field: $field"
            FINDINGS=$((FINDINGS + 1))
            emit_proposal \
                "governance.missing_field" \
                "append" \
                "\"$field\"" \
                "Required authority field '$field' is missing" \
                "medium" \
                "$rule"
        fi
    done
}

# =============================================================================
# RULE 3: Context7 Enforcement Level
# Check: enforcement_level must be "strict" for production
# Risk: high (changing enforcement level is dangerous)
# =============================================================================
rule3_enforcement_level() {
    local rule="enforcement_level"
    local level

    level=$(jq -r '.context7_standards.enforcement_level // "unknown"' "$AUTHORITY_FILE" 2>/dev/null || echo "unknown")

    if [[ "$level" == "unknown" || "$level" == "null" ]]; then
        echo "[DECIDE][FINDING] Enforcement level not set"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "context7_standards.enforcement_level" \
            "update" \
            '"strict"' \
            "Enforcement level missing — production requires 'strict'" \
            "high" \
            "$rule"
    elif [[ "$level" != "strict" ]]; then
        echo "[DECIDE][FINDING] Enforcement level is '$level' (expected: strict)"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "context7_standards.enforcement_level" \
            "update" \
            '"strict"' \
            "Enforcement level downgraded to '$level' — production policy violation" \
            "high" \
            "$rule"
    else
        echo "[DECIDE][OK] Enforcement level: strict"
    fi
}

# =============================================================================
# RULE 4: Forbidden Fields Guard
# Check: context7_guard.php has non-empty forbidden lists per table
# Risk: medium (empty guard = unprotected table)
# =============================================================================
rule4_forbidden_fields() {
    local rule="forbidden_fields_guard"
    local guard_file="config/context7_guard.php"

    if [[ ! -f "$guard_file" ]]; then
        echo "[DECIDE][FINDING] Context7 guard config missing: $guard_file"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.guard_config" \
            "append" \
            '"context7_guard_missing"' \
            "Guard config file missing — tables are unprotected" \
            "medium" \
            "$rule"
        return 0
    fi

    # Check key tables have forbidden entries
    local tables=("ilanlar" "kisiler" "mahalleler")
    for table in "${tables[@]}"; do
        if ! grep -q "'$table'" "$guard_file" 2>/dev/null; then
            echo "[DECIDE][FINDING] Table '$table' not in guard config"
            FINDINGS=$((FINDINGS + 1))
            emit_proposal \
                "governance.unguarded_table" \
                "append" \
                "\"$table\"" \
                "Table '$table' has no forbidden field guards" \
                "medium" \
                "$rule"
        fi
    done

    echo "[DECIDE][OK] Forbidden fields guard checked."
}

# =============================================================================
# RULE 5: Stale History Cleanup
# Check: Applied proposals older than 30 days should be flagged
# Risk: low (housekeeping)
# =============================================================================
rule5_history_hygiene() {
    local rule="history_hygiene"
    local history_dir="$SAB_DIR/history/proposals"

    if [[ ! -d "$history_dir" ]]; then
        echo "[DECIDE][OK] No history directory — skip."
        return 0
    fi

    local total_files old_files
    total_files=$(find "$history_dir" -type f -name "*.json" | wc -l | tr -d ' ')
    old_files=$(find "$history_dir" -type f -name "*.json" -mtime +30 | wc -l | tr -d ' ')

    if [[ "$old_files" -gt 0 ]]; then
        echo "[DECIDE][FINDING] $old_files proposals older than 30 days in history"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.history_cleanup" \
            "update" \
            "{\"stale_count\": $old_files, \"total\": $total_files}" \
            "$old_files stale proposals in history (>30 days), consider archival" \
            "low" \
            "$rule"
    else
        echo "[DECIDE][OK] History hygiene: $total_files proposals, none stale."
    fi
}

# =============================================================================
# RULE 6: Version Freshness
# Check: authority.json last_updated should be within 7 days
# Risk: low (informational)
# =============================================================================
rule6_version_freshness() {
    local rule="version_freshness"
    local last_updated

    last_updated=$(jq -r '.last_updated // "unknown"' "$AUTHORITY_FILE" 2>/dev/null || echo "unknown")

    if [[ "$last_updated" == "unknown" || "$last_updated" == "null" ]]; then
        echo "[DECIDE][FINDING] Authority last_updated field missing"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.version_freshness" \
            "update" \
            '"missing_timestamp"' \
            "Authority has no last_updated timestamp — governance age unknown" \
            "low" \
            "$rule"
        return 0
    fi

    # macOS date parsing for ISO 8601
    local updated_epoch now_epoch age_days
    updated_epoch=$(date -j -f "%Y-%m-%dT%H:%M:%SZ" "$last_updated" +%s 2>/dev/null || echo "0")
    now_epoch=$(date +%s)

    if [[ "$updated_epoch" -eq 0 ]]; then
        echo "[DECIDE][FINDING] Cannot parse last_updated: $last_updated"
        FINDINGS=$((FINDINGS + 1))
        return 0
    fi

    age_days=$(( (now_epoch - updated_epoch) / 86400 ))

    if [[ "$age_days" -gt 7 ]]; then
        echo "[DECIDE][FINDING] Authority is $age_days days old (last: $last_updated)"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "governance.version_freshness" \
            "update" \
            "{\"age_days\": $age_days, \"last_updated\": \"$last_updated\"}" \
            "Authority governance config is $age_days days stale (threshold: 7 days)" \
            "low" \
            "$rule"
    else
        echo "[DECIDE][OK] Authority freshness: $age_days days old."
    fi
}

# =============================================================================
# RULE 7: MCP Server Health Reference
# Check: authority.json declares MCP server on port 4001
# Risk: medium (wrong port = broken AI integration)
# =============================================================================
rule7_mcp_config() {
    local rule="mcp_config"
    local mcp_port

    mcp_port=$(jq -r '.yalihan_bekci_system.mcp_server.port // "missing"' "$AUTHORITY_FILE" 2>/dev/null || echo "missing")

    if [[ "$mcp_port" == "missing" || "$mcp_port" == "null" ]]; then
        echo "[DECIDE][FINDING] MCP server port not configured in authority"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "yalihan_bekci_system.mcp_server.port" \
            "update" \
            '4001' \
            "MCP server port missing from authority — expected 4001" \
            "medium" \
            "$rule"
    elif [[ "$mcp_port" != "4001" ]]; then
        echo "[DECIDE][FINDING] MCP port is $mcp_port (expected: 4001)"
        FINDINGS=$((FINDINGS + 1))
        emit_proposal \
            "yalihan_bekci_system.mcp_server.port" \
            "update" \
            '4001' \
            "MCP port drift: configured=$mcp_port, expected=4001" \
            "high" \
            "$rule"
    else
        echo "[DECIDE][OK] MCP config: port 4001"
    fi
}

# =============================================================================
# EXECUTE ALL RULES
# =============================================================================
echo ""
echo "[DECIDE] ════════════════════════════════════════"
echo "[DECIDE] Running governance rules..."
echo "[DECIDE] ════════════════════════════════════════"
echo ""

rule1_authority_sync
rule2_required_fields
rule3_enforcement_level
rule4_forbidden_fields
rule5_history_hygiene
rule6_version_freshness
rule7_mcp_config

# =============================================================================
# SUMMARY
# =============================================================================
echo ""
echo "[DECIDE] ════════════════════════════════════════"
echo "[DECIDE] DECISION ENGINE SUMMARY"
echo "[DECIDE] ════════════════════════════════════════"
echo "[DECIDE] Rules executed:     7"
echo "[DECIDE] Findings:           $FINDINGS"
echo "[DECIDE] Proposals created:  $PROPOSALS_GENERATED"
echo "[DECIDE] High-risk skipped:  $HIGH_RISK_SKIPPED"
echo "[DECIDE] ════════════════════════════════════════"

if [[ "$PROPOSALS_GENERATED" -gt 0 ]]; then
    echo "[DECIDE] → $PROPOSALS_GENERATED proposal(s) ready for pipeline."
    echo "[DECIDE] → Watcher will auto-apply on next cycle."
fi

if [[ "$HIGH_RISK_SKIPPED" -gt 0 ]]; then
    echo "[DECIDE] → $HIGH_RISK_SKIPPED high-risk finding(s) require manual approval."
    echo "[DECIDE] → Use: bash scripts/sab-propose.sh --target <path> --action <action> --value <val>"
fi

log_audit "Decision engine finished: findings=$FINDINGS proposals=$PROPOSALS_GENERATED skipped=$HIGH_RISK_SKIPPED"
echo ""
echo "[DECIDE] Done. ($(ts))"
