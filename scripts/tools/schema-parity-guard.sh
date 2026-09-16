#!/bin/bash
# ============================================================
# YALIHAN OS — Schema Parity & Drift Pre-Commit Guard
# Path: scripts/tools/schema-parity-guard.sh
# Purpose: Fails closed when an agent attempts to commit:
#   1. New/modified migration files without staging mysql-schema.sql
#   2. New/modified migration files without updating .sab/schema-checksum.sha256
#   3. Invalid/mismatched .sab/schema-checksum.sha256
#   4. Stray/dummy migrations (e.g., targeting 'none' table or scratch names)
#   5. Forbidden 'is_active' / legacy English fields in modified Models
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${BLUE}${BOLD}🛡️  SAB Schema & Drift Guard: Verifying Staged Changes...${NC}"

# Get staged files
STAGED_FILES=$(git diff --cached --name-only)

if [ -z "$STAGED_FILES" ]; then
    echo -e "${GREEN}✅ No staged files to check.${NC}"
    exit 0
fi

# ------------------------------------------------------------
# CHECK 1: STRAY / DUMMY MIGRATION DETECTION
# ------------------------------------------------------------
DUMMY_MIGRATIONS=$(echo "$STAGED_FILES" | grep -E '^database/migrations/.*(sync_parity|temp_|test_|_none_)' || true)
if [ -n "$DUMMY_MIGRATIONS" ]; then
    echo -e "${RED}❌ [SAB BLOCKED] Dummy or scratch migration detected in staged files:${NC}"
    echo "$DUMMY_MIGRATIONS" | sed 's/^/   /'
    echo -e "${YELLOW}👉 Remove dummy/test migrations before committing!${NC}"
    exit 1
fi

# ------------------------------------------------------------
# CHECK 2: MIGRATION ↔ SSOT SCHEMA & CHECKSUM PARITY
# ------------------------------------------------------------
MIGRATION_CHANGES=$(echo "$STAGED_FILES" | grep -E '^database/migrations/.*\.php$' || true)

if [ -n "$MIGRATION_CHANGES" ]; then
    echo -e "📦 Migration changes detected:"
    echo "$MIGRATION_CHANGES" | sed 's/^/   /'

    HAS_SCHEMA=$(echo "$STAGED_FILES" | grep -E '^database/schema/mysql-schema\.sql$' || true)
    HAS_CHECKSUM=$(echo "$STAGED_FILES" | grep -E '^\.sab/schema-checksum\.sha256$' || true)

    ERRORS=0

    if [ -z "$HAS_SCHEMA" ]; then
        echo -e "${RED}❌ [SAB PARITY GUARD FAILED] database/schema/mysql-schema.sql is NOT staged!${NC}"
        echo -e "   Rule: Whenever database/migrations/ are modified, the SSOT schema (mysql-schema.sql) MUST be updated and staged."
        ERRORS=$((ERRORS + 1))
    fi

    if [ -z "$HAS_CHECKSUM" ]; then
        echo -e "${RED}❌ [SAB PARITY GUARD FAILED] .sab/schema-checksum.sha256 is NOT staged!${NC}"
        echo -e "   Rule: Whenever the database schema changes, the checksum file MUST be locked and staged."
        ERRORS=$((ERRORS + 1))
    fi

    if [ $ERRORS -gt 0 ]; then
        echo -e "${YELLOW}👉 To fix: Update database/schema/mysql-schema.sql, regenerate .sab/schema-checksum.sha256, and stage them.${NC}"
        exit 1
    fi
fi

# ------------------------------------------------------------
# CHECK 3: CHECKSUM INTEGRITY CHECK
# ------------------------------------------------------------
# If either mysql-schema.sql or schema-checksum.sha256 is staged, verify they match!
HAS_SCHEMA_ANY=$(echo "$STAGED_FILES" | grep -E '^database/schema/mysql-schema\.sql$' || true)
HAS_CHECKSUM_ANY=$(echo "$STAGED_FILES" | grep -E '^\.sab/schema-checksum\.sha256$' || true)

if [ -n "$HAS_SCHEMA_ANY" ] || [ -n "$HAS_CHECKSUM_ANY" ]; then
    if [ -f "database/schema/mysql-schema.sql" ] && [ -f ".sab/schema-checksum.sha256" ]; then
        CURRENT_HASH=$(shasum -a 256 database/schema/mysql-schema.sql 2>/dev/null | awk '{print $1}' || sha256sum database/schema/mysql-schema.sql | awk '{print $1}')
        RECORDED_HASH=$(tr -d '[:space:]' < .sab/schema-checksum.sha256)

        if [ "$CURRENT_HASH" != "$RECORDED_HASH" ]; then
            echo -e "${RED}❌ [SAB CHECKSUM MISMATCH] mysql-schema.sql hash does not match .sab/schema-checksum.sha256!${NC}"
            echo -e "   Current SHA256 : $CURRENT_HASH"
            echo -e "   Recorded SHA256: $RECORDED_HASH"
            echo -e "${YELLOW}👉 Run: shasum -a 256 database/schema/mysql-schema.sql | awk '{print \$1}' > .sab/schema-checksum.sha256${NC}"
            exit 1
        fi
    fi
fi

# ------------------------------------------------------------
# CHECK 4: GHOST FIELD CHECK IN STAGED MODELS
# ------------------------------------------------------------
STAGED_MODELS=$(echo "$STAGED_FILES" | grep -E '^app/Models/.*\.php$' || true)
if [ -n "$STAGED_MODELS" ]; then
    for MODEL in $STAGED_MODELS; do
        if [ -f "$MODEL" ]; then
            # Scan for 'is_active' without ignore comment
            IS_ACTIVE_VIOLATIONS=$(grep -n "'is_active'" "$MODEL" | grep -vE '(context7-ignore|sab-ignore)' || true)
            if [ -n "$IS_ACTIVE_VIOLATIONS" ]; then
                echo -e "${RED}❌ [CONTEXT7 GHOST FIELD] Forbidden 'is_active' detected in $MODEL:${NC}"
                echo "$IS_ACTIVE_VIOLATIONS" | sed 's/^/   /'
                echo -e "${YELLOW}👉 Use canonical 'aktiflik_durumu' instead of 'is_active'.${NC}"
                exit 1
            fi
        fi
    done
fi

echo -e "${GREEN}✅ SAB Schema & Drift Guard: All staged checks PASSED.${NC}"
exit 0
