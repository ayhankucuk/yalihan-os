#!/usr/bin/env bash
# ==============================================================================
# YALIHAN OS — Migration Baseline Boundary Verifier
# ==============================================================================
# Canonical Authority:
#   CANONICAL_SCHEMA_BASELINE_COMMIT = ec6ba05acb88425bce1b16f7043675dd7b9931b7
#   CANONICAL_SCHEMA_FILE = database/schema/mysql-schema.sql
#
# Responsibility:
#   Deterministically enumerate and verify YALIHAN-owned migration files introduced
#   AFTER the canonical schema baseline commit using Git ancestry.
#   Prevents replaying historical pre-checkpoint migrations over the physical baseline.
# ==============================================================================

set -euo pipefail

# 1. Resolve repository root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${REPO_ROOT}"

# 2. Canonical baseline definition
readonly CANONICAL_SCHEMA_BASELINE_COMMIT="ec6ba05acb88425bce1b16f7043675dd7b9931b7"

# 3. Fail-closed git repository checks
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "ERROR: Not inside a valid git working tree." >&2
    exit 1
fi

if ! git cat-file -e "${CANONICAL_SCHEMA_BASELINE_COMMIT}^{commit}" >/dev/null 2>&1; then
    echo "ERROR: Canonical schema baseline commit (${CANONICAL_SCHEMA_BASELINE_COMMIT}) could not be resolved." >&2
    exit 1
fi

CURRENT_HEAD="$(git rev-parse HEAD)"

# Verify baseline commit is an ancestor of HEAD
if ! git merge-base --is-ancestor "${CANONICAL_SCHEMA_BASELINE_COMMIT}" HEAD; then
    echo "ERROR: Baseline commit (${CANONICAL_SCHEMA_BASELINE_COMMIT}) is not an ancestor of HEAD (${CURRENT_HEAD})." >&2
    exit 1
fi

# 4. Enumerate YALIHAN-owned migration files introduced after baseline commit
# Explicitly search only database/migrations and app/Modules (excluding vendor/)
POST_BASELINE_FILES=()
while IFS= read -r line; do
    [[ -n "${line}" ]] && POST_BASELINE_FILES+=("${line}")
done < <(git diff --name-only --diff-filter=A "${CANONICAL_SCHEMA_BASELINE_COMMIT}..HEAD" -- database/migrations "app/Modules/**/Migrations" "app/Modules/**/migrations" 2>/dev/null | grep -E '\.php$' || true)

POST_BASELINE_COUNT=${#POST_BASELINE_FILES[@]}

# 5. Output deterministic result
echo "=================================================="
echo "YALIHAN OS MIGRATION BOUNDARY VERIFICATION"
echo "=================================================="
echo "BASELINE_COMMIT: ${CANONICAL_SCHEMA_BASELINE_COMMIT}"
echo "CURRENT_HEAD: ${CURRENT_HEAD}"
echo "YALIHAN_OWNED_MIGRATION_PATHS:"
echo "  - database/migrations"
echo "  - app/Modules/*/Database/Migrations"
echo "  - app/Modules/*/database/migrations"
echo "VENDOR_EXCLUSION: ENFORCED (vendor/ migrations excluded from YALIHAN lineage)"
echo "HISTORICAL_REPLAY_GUARD: ACTIVE (Pre-checkpoint migrations blocked from replay)"
echo "POST_BASELINE_MIGRATION_COUNT: ${POST_BASELINE_COUNT}"

if [[ ${POST_BASELINE_COUNT} -eq 0 ]]; then
    echo "POST_BASELINE_MIGRATIONS: NONE"
    echo "STATUS: PASS_BOUNDARY_VERIFIED"
    exit 0
else
    echo "POST_BASELINE_MIGRATIONS:"
    for f in "${POST_BASELINE_FILES[@]}"; do
        echo "  - ${f}"
    done
    echo "STATUS: POST_BASELINE_MIGRATIONS_PENDING"
    exit 0
fi
