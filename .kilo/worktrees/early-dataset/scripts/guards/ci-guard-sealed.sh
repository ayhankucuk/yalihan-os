#!/usr/bin/env bash

# 🛡️ SAB Sealed Cell Guard: Zero-Tolerance Integrity Check
# ────────────────────────────────────────────────────────
# Sealed Domains (Mühürlü Domainler) comply with architectural
# rules WITHOUT baseline tolerance. Any violation = P0 FAIL.

set -euo pipefail

# ─── Configuration ───────────────────────────────────────
AUTHORITY_FILE=".sab/authority.json"
FAILED=0
VIOLATION_COUNT=0

if [ ! -f "$AUTHORITY_FILE" ]; then
    echo "❌ Error: $AUTHORITY_FILE not found."
    exit 1
fi

# ─── Extract Sealed Domains ─────────────────────────────
echo "🔍 Identifying Sealed Domains (Sealed Cells)..."
SEALED_DOMAINS=$(python3 -c "
import json, sys
with open('$AUTHORITY_FILE') as f:
    data = json.load(f)
domains = data.get('governance', {}).get('sealed_domains', [])
for d in domains:
    print(d)
" 2>/dev/null || cat "$AUTHORITY_FILE" | grep -A 50 '"sealed_domains"' | grep '"app/' | sed 's/[",]//g' | sed 's/^[[:space:]]*//')

if [ -z "$SEALED_DOMAINS" ]; then
    echo "✅ No sealed domains found. Skipping."
    exit 0
fi

DOMAIN_COUNT=$(echo "$SEALED_DOMAINS" | wc -l | tr -d ' ')
echo "🛡️ $DOMAIN_COUNT Sealed Cells identified for Zero-Tolerance scan."
echo "────────────────────────────────────────────────────────"

# ─── Scan: Check each sealed file for violations ────────
# Run integrity scan once, capture full output (includes ALL violations, not just new)
SCAN_OUTPUT=$(php artisan sab:integrity-scan 2>&1 || true)

for DOMAIN_PATH in $SEALED_DOMAINS; do
    # Convert path format: app/Models/Ilan -> Models/Ilan.php
    FILE_BASENAME=$(echo "$DOMAIN_PATH" | sed 's|app/||')

    # Check if any violation line mentions this file
    if echo "$SCAN_OUTPUT" | grep -F "$FILE_BASENAME" | grep -qiE "MEDIUM|HIGH|CRITICAL"; then
        echo "❌ VIOLATION in Sealed Cell: $DOMAIN_PATH"
        echo "$SCAN_OUTPUT" | grep -F "$FILE_BASENAME"
        FAILED=1
        VIOLATION_COUNT=$((VIOLATION_COUNT + 1))
    fi
done

# ─── Forbidden Field Check on Sealed Files ───────────────
echo ""
echo "🔬 Forbidden Field Scan (Sealed Cells only)..."

# Exact forbidden field names (as they appear in $fillable arrays).
# Canonical names like aktiflik_durumu, yayin_durumu, one_cikan are ALLOWED.
FORBIDDEN_EXACT=(
    "'status'"
    "'active'"
    "'order'"
    "'featured'"
    "'enabled'"
    "'is_active'"
    "'old_price'"
    "'legacy_status'"
    "'durum'"
    "'aktif'"
    "'aktif_mi'"
)

for DOMAIN_PATH in $SEALED_DOMAINS; do
    FULL_PATH="$DOMAIN_PATH.php"
    if [ -f "$FULL_PATH" ]; then
        # Extract $fillable block and strip comments
        FILLABLE_BLOCK=$(sed -n '/\$fillable/,/\];/p' "$FULL_PATH" 2>/dev/null | grep -v '^\s*//' || true)
        for PATTERN in "${FORBIDDEN_EXACT[@]}"; do
            if echo "$FILLABLE_BLOCK" | grep -qF "$PATTERN"; then
                echo "❌ FORBIDDEN FIELD $PATTERN in Sealed Cell: $FULL_PATH"
                FAILED=1
                VIOLATION_COUNT=$((VIOLATION_COUNT + 1))
            fi
        done
    fi
done

# ─── BaseModel Check ─────────────────────────────────────
echo ""
echo "🔬 BaseModel Inheritance Check (Sealed Cells only)..."

for DOMAIN_PATH in $SEALED_DOMAINS; do
    FULL_PATH="$DOMAIN_PATH.php"
    if [ -f "$FULL_PATH" ]; then
        # Must extend BaseModel, not Model
        if grep -q 'extends Model$' "$FULL_PATH" 2>/dev/null; then
            echo "❌ BaseModel VIOLATION: $FULL_PATH extends Model instead of BaseModel"
            FAILED=1
            VIOLATION_COUNT=$((VIOLATION_COUNT + 1))
        fi
    fi
done

# ─── Result ──────────────────────────────────────────────
echo ""
echo "────────────────────────────────────────────────────────"
if [ $FAILED -eq 1 ]; then
    echo "❌ SAB Sealed Cell Guard FAILED."
    echo "   $VIOLATION_COUNT violation(s) in mühürlü domains."
    echo "   Baseline tolerance is DISABLED for sealed domains."
    exit 1
else
    echo "✨ SAB Sealed Cell Guard PASSED."
    echo "   All $DOMAIN_COUNT Sealed Cells are sterile."
    exit 0
fi
