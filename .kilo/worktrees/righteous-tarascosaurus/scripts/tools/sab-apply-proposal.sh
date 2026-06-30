#!/usr/bin/env bash
# ==============================================================================
# Yalıhan Governance Engine — Apply Proposal (SAB v16.0 - Evolution)
# Purpose: Safely apply a governance proposal to authority.json with rollback.
# Usage: ./scripts/sab-apply-proposal.sh <proposal-id>
# ==============================================================================

set -e

PROPOSAL_ID=$1
PROPOSAL_FILE=".sab/proposals/proposal-$PROPOSAL_ID.json"
AUTHORITY_FILE=".sab/authority.json"
SNAPSHOT_FILE=".sab/snapshots/authority-pre-$PROPOSAL_ID-$(date +%s).json"

# --- 1. Validation ---
if [ -z "$PROPOSAL_ID" ]; then
    echo "[EVOLUTION][ERROR] Proposal ID required."
    exit 1
fi

if [ ! -f "$PROPOSAL_FILE" ]; then
    echo "[EVOLUTION][ERROR] Proposal file not found: $PROPOSAL_FILE"
    exit 1
fi

# --- 2. Snapshot ---
echo "[EVOLUTION][INFO] Creating snapshot: $SNAPSHOT_FILE"
cp "$AUTHORITY_FILE" "$SNAPSHOT_FILE"

# --- 3. Patch ---
echo "[EVOLUTION][INFO] Applying proposal $PROPOSAL_ID..."
php scripts/sab-apply-proposal.php "$PROPOSAL_FILE" "$AUTHORITY_FILE" || {
    echo "[EVOLUTION][ERROR] Patching failed. Rolling back..."
    cp "$SNAPSHOT_FILE" "$AUTHORITY_FILE"
    exit 1
}

# --- 4. Post-patch Validation ---
echo "[EVOLUTION][INFO] Validating new authority state..."

# A. Fast Gate (Heuristic)
echo "[EVOLUTION][CHECK] Running policy-check.sh..."
./scripts/policy-check.sh > /dev/null 2>&1 || {
    echo "[EVOLUTION][CRITICAL] New authority failed heuristic gate. Rolling back..."
    cp "$SNAPSHOT_FILE" "$AUTHORITY_FILE"
    exit 1
}

# B. Deep Gate (Semantic)
echo "[EVOLUTION][CHECK] Running php artisan sab:guard..."
php artisan sab:guard > /dev/null 2>&1 || {
    echo "[EVOLUTION][CRITICAL] New authority failed semantic guard. Rolling back..."
    cp "$SNAPSHOT_FILE" "$AUTHORITY_FILE"
    exit 1
}

echo "[EVOLUTION][SUCCESS] Proposal $PROPOSAL_ID applied and verified."
