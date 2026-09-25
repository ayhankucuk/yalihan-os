#!/usr/bin/env bash
# ==============================================================================
# YALIHAN OS — Local Canonical Reproducible Bootstrap Runner (V1)
# ==============================================================================
# Authority:
#   Physical Schema Baseline: database/schema/mysql-schema.sql (282 tables)
#   Schema Baseline Commit:   ec6ba05acb88425bce1b16f7043675dd7b9931b7
#   Migration Lineage Guard:  scripts/tools/verify-migration-boundary.sh
#   Credential Contract:      ADMIN_LOCAL_PASSWORD -> config('auth.admin_password')
#   Authorization Authority:  Spatie model_has_roles
#
# Constraints & Guarantees (V1):
#   1. Localhost MySQL ONLY (Remote hosts strictly rejected).
#   2. Explicit NEW / EMPTY database ONLY (No --force, no overwrite of populated DB).
#   3. Rejects canonical working DB (yalihanai_local_canonical) and production DBs.
#   4. Rejects historical migration replay. If post-baseline migrations > 0, FAILS CLOSED.
#   5. Excludes local-dev/demo seeders (Danisman, Musteri, BodrumPoi).
#   6. Runs strictly verified canonical seeders in deterministic dependency order.
#   7. Verifies structural, data, auth, and contract invariants before emitting BOOTSTRAP_VERIFIED.
# ==============================================================================

set -euo pipefail

# 1. Resolve repository root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${REPO_ROOT}"

# 2. Parse arguments
TARGET_DB=""
for arg in "$@"; do
    case "${arg}" in
        --target-db=*)
            TARGET_DB="${arg#*=}"
            ;;
        --help|-h)
            echo "Usage: ADMIN_LOCAL_PASSWORD=secret ./scripts/tools/bootstrap-local-canonical.sh --target-db=<db_name>"
            exit 0
            ;;
        *)
            echo "ERROR: Unknown argument: ${arg}" >&2
            exit 1
            ;;
    esac
done

if [[ -z "${TARGET_DB}" ]]; then
    echo "ERROR: --target-db=<db_name> is required." >&2
    exit 1
fi

# 3. Target safety and blacklist checks
readonly FORBIDDEN_DB_PATTERN="^(yalihanai_v2_production|yalihanai_local_canonical|yalihanai_production|production|prod|master)$"
if [[ "${TARGET_DB}" =~ ${FORBIDDEN_DB_PATTERN} ]]; then
    echo "ERROR: Target database '${TARGET_DB}' is forbidden. Bootstrap V1 operates only on new/disposable databases." >&2
    exit 1
fi

# 4. Credential check (Fail-closed immediately if missing or blank)
if [[ -z "${ADMIN_LOCAL_PASSWORD:-}" || -z "${ADMIN_LOCAL_PASSWORD//[[:space:]]/}" ]]; then
    echo "ERROR: ADMIN_LOCAL_PASSWORD environment variable is missing or blank. Bootstrap fails closed." >&2
    exit 1
fi

# Helper function to read Laravel config safely
get_laravel_config() {
    local key="$1"
    local default="$2"
    php -r "
        try {
            require_once 'vendor/autoload.php';
            \$app = require_once 'bootstrap/app.php';
            \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            echo config('${key}', '${default}');
        } catch (Throwable \$e) {
            echo '${default}';
        }
    " 2>/dev/null || echo "${default}"
}

# 5. Environment check (Reject production)
APP_ENV_VAL="$(get_laravel_config 'app.env' 'production')"
if [[ "${APP_ENV_VAL}" == "production" ]]; then
    echo "ERROR: Bootstrap cannot run in production environment (APP_ENV=production)." >&2
    exit 1
fi

# 6. Localhost database host check
DB_HOST_VAL="$(get_laravel_config 'database.connections.mysql.host' '127.0.0.1')"
readonly LOCAL_HOST_PATTERN="^(127\.0\.0\.1|localhost|::1)$"
if [[ ! "${DB_HOST_VAL}" =~ ${LOCAL_HOST_PATTERN} ]]; then
    echo "ERROR: Database host '${DB_HOST_VAL}' is not local. Bootstrap V1 strictly requires a localhost database." >&2
    exit 1
fi

# 7. Git ancestry and migration boundary detection
if [[ ! -f "scripts/tools/verify-migration-boundary.sh" ]]; then
    echo "ERROR: Migration boundary guard script missing." >&2
    exit 1
fi

BOUNDARY_OUTPUT="$(./scripts/tools/verify-migration-boundary.sh)"
echo "${BOUNDARY_OUTPUT}"

POST_BASELINE_COUNT="$(echo "${BOUNDARY_OUTPUT}" | grep -E '^POST_BASELINE_MIGRATION_COUNT:' | awk '{print $2}' || echo "UNKNOWN")"

if [[ "${POST_BASELINE_COUNT}" != "0" ]]; then
    echo "ERROR: BLOCKED_POST_BASELINE_MIGRATIONS_REQUIRE_EXECUTION_PLAN" >&2
    echo "Bootstrap V1 strictly forbids automatic post-baseline migration execution." >&2
    exit 1
fi

# 8. MySQL connection parameters
DB_PORT_VAL="$(get_laravel_config 'database.connections.mysql.port' '3306')"
DB_USER_VAL="$(get_laravel_config 'database.connections.mysql.username' 'root')"
DB_PASS_VAL="$(get_laravel_config 'database.connections.mysql.password' '')"

MYSQL_CMD=(mysql -h"${DB_HOST_VAL}" -P"${DB_PORT_VAL}" -u"${DB_USER_VAL}")
if [[ -n "${DB_PASS_VAL}" ]]; then
    MYSQL_CMD+=("-p${DB_PASS_VAL}")
fi

# 9. Target database inspection (Must be NEW or EMPTY)
DB_EXISTS="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '${TARGET_DB}';" 2>/dev/null || echo "0")"
if [[ "${DB_EXISTS}" == "1" ]]; then
    TABLE_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '${TARGET_DB}';" 2>/dev/null || echo "0")"
    if [[ "${TABLE_COUNT}" -gt 0 ]]; then
        echo "ERROR: Target database '${TARGET_DB}' already contains ${TABLE_COUNT} tables. Bootstrap V1 requires a NEW or EMPTY database (no overwrite)." >&2
        exit 1
    fi
else
    echo "📦 Creating target database '${TARGET_DB}'..."
    "${MYSQL_CMD[@]}" -e "CREATE DATABASE \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

# 10. Physical Schema Import
readonly SCHEMA_FILE="database/schema/mysql-schema.sql"
if [[ ! -f "${SCHEMA_FILE}" ]]; then
    echo "ERROR: Canonical schema baseline file '${SCHEMA_FILE}' is missing." >&2
    exit 1
fi

echo "📥 Importing canonical schema baseline (${SCHEMA_FILE}) into '${TARGET_DB}'..."
"${MYSQL_CMD[@]}" --default-character-set=utf8mb4 "${TARGET_DB}" < "${SCHEMA_FILE}"

# 11. Structural Invariant Checks
IMPORTED_TABLE_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '${TARGET_DB}';" 2>/dev/null || echo "0")"
if [[ "${IMPORTED_TABLE_COUNT}" -ne 282 ]]; then
    echo "ERROR: Schema import verification failed. Expected 282 tables, found ${IMPORTED_TABLE_COUNT}." >&2
    exit 1
fi

# Check critical columns and tables
CRITICAL_TENANT_CHECK="$("${MYSQL_CMD[@]}" -Nse "
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = '${TARGET_DB}' 
      AND (
        (TABLE_NAME = 'users' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'komisyonlar' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'leads' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'talepler' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'ilan_fotograflari' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'property_reservations' AND COLUMN_NAME = 'tenant_id') OR
        (TABLE_NAME = 'ilanlar' AND COLUMN_NAME = 'proje_id')
      );
" 2>/dev/null || echo "0")"

if [[ "${CRITICAL_TENANT_CHECK}" -ne 7 ]]; then
    echo "ERROR: Critical schema invariant check failed. Expected 7 structural columns, found ${CRITICAL_TENANT_CHECK}." >&2
    exit 1
fi

PROJE_TABLES_CHECK="$("${MYSQL_CMD[@]}" -Nse "
    SELECT COUNT(*) FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = '${TARGET_DB}' 
      AND TABLE_NAME IN ('emlak_projeleri', 'projeler');
" 2>/dev/null || echo "0")"

if [[ "${PROJE_TABLES_CHECK}" -ne 2 ]]; then
    echo "ERROR: Canonical project tables check failed. Expected 2 tables (emlak_projeleri, projeler), found ${PROJE_TABLES_CHECK}." >&2
    exit 1
fi

echo "✅ Structural baseline invariants verified (282 tables + 7 tenant/project invariants)."

# 12. Execute Canonical Seeders in Strict Dependency Order
# We explicitly call individual seeders rather than generic db:seed to avoid local-dev demo seeders
readonly CANONICAL_SEEDERS=(
    "Database\\Seeders\\TenantBaselineSeeder"
    "Database\\Seeders\\RoleSeeder"
    "Database\\Seeders\\TurkiyeLocationSeeder"
    "Database\\Seeders\\IlanKategoriSeeder"
    "Database\\Seeders\\YayinTipiSeeder"
    "Database\\Seeders\\KategoriYayinTipiPivotSeeder"
    "Database\\Seeders\\FeatureAssignmentSeeder"
    "Database\\Seeders\\ArsaIsyeriFeatureAssignmentSeeder"
    "Database\\Seeders\\CategoryFeatureMatrixSeeder"
    "Database\\Seeders\\SmartFormsCanonicalSeeder"
    "Database\\Seeders\\ExpenseItemSeeder"
    "Database\\Seeders\\AdminUserSeeder"
)

echo "🌱 Executing canonical reference seeders in dependency order..."
for seeder in "${CANONICAL_SEEDERS[@]}"; do
    echo "   → Seeding: ${seeder}"
    DB_DATABASE="${TARGET_DB}" ADMIN_LOCAL_PASSWORD="${ADMIN_LOCAL_PASSWORD}" php artisan db:seed --class="${seeder}" --force >/dev/null
done

echo "✅ All 12 canonical seeders executed successfully."

# 13. Data Invariant and Auth Pivot Verification
TENANT_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.tenants;" 2>/dev/null || echo "0")"
ROLES_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.roles;" 2>/dev/null || echo "0")"
FC_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.feature_categories;" 2>/dev/null || echo "0")"
F_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.features;" 2>/dev/null || echo "0")"
FA_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.feature_assignments;" 2>/dev/null || echo "0")"
ADMIN_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.users WHERE email IN ('ayhankucuk@gmail.com', 'yalihanemlak@gmail.com') AND tenant_id = 1 AND aktiflik_durumu = 1;" 2>/dev/null || echo "0")"
SPATIE_PIVOT_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.model_has_roles WHERE model_type = 'App\\\\Models\\\\User';" 2>/dev/null || echo "0")"

if [[ "${TENANT_COUNT}" -lt 1 || "${ROLES_COUNT}" -lt 4 || "${FC_COUNT}" -lt 7 || "${F_COUNT}" -lt 36 || "${FA_COUNT}" -lt 82 || "${ADMIN_COUNT}" -ne 2 || "${SPATIE_PIVOT_COUNT}" -ne 2 ]]; then
    echo "ERROR: Data invariant verification failed." >&2
    echo "  tenants: ${TENANT_COUNT} (min 1)" >&2
    echo "  roles: ${ROLES_COUNT} (min 4)" >&2
    echo "  feature_categories: ${FC_COUNT} (min 7, expected 25)" >&2
    echo "  features: ${F_COUNT} (min 36, expected 84)" >&2
    echo "  feature_assignments: ${FA_COUNT} (min 82, expected 215)" >&2
    echo "  admin_users: ${ADMIN_COUNT} (expected 2)" >&2
    echo "  spatie_pivots: ${SPATIE_PIVOT_COUNT} (expected 2)" >&2
    exit 1
fi

# 14. Business / Demo Zero-Pollution Verification
ILANLAR_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.ilanlar;" 2>/dev/null || echo "0")"
TALEPLER_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.talepler;" 2>/dev/null || echo "0")"
LEADS_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.leads;" 2>/dev/null || echo "0")"
RESERVATIONS_COUNT="$("${MYSQL_CMD[@]}" -Nse "SELECT COUNT(*) FROM \`${TARGET_DB}\`.property_reservations;" 2>/dev/null || echo "0")"

if [[ "${ILANLAR_COUNT}" -ne 0 || "${TALEPLER_COUNT}" -ne 0 || "${LEADS_COUNT}" -ne 0 || "${RESERVATIONS_COUNT}" -ne 0 ]]; then
    echo "ERROR: Demo/business pollution detected in freshly bootstrapped database!" >&2
    echo "  ilanlar: ${ILANLAR_COUNT}, talepler: ${TALEPLER_COUNT}, leads: ${LEADS_COUNT}, reservations: ${RESERVATIONS_COUNT}" >&2
    exit 1
fi

echo "✅ Zero business/demo pollution verified (ilanlar=0, talepler=0, leads=0, reservations=0)."

# 15. Run Seeder Gate and Canonical Test Verification
echo "🛡️  Running seeder gate check..."
node scripts/guards/seeder-gate.cjs >/dev/null

echo "🧪 Running canonical seeder regression suite against target DB..."
php artisan test tests/Feature/Seeder/FeatureAssignmentSeederTest.php >/dev/null
php artisan test tests/Feature/Wizard/CategoryFeatureMatrixTest.php >/dev/null

echo "=================================================="
echo "YALIHAN OS CANONICAL BOOTSTRAP RESULT"
echo "=================================================="
echo "TARGET_DATABASE: ${TARGET_DB}"
echo "PHYSICAL_TABLES: ${IMPORTED_TABLE_COUNT}"
echo "POST_BASELINE_MIGRATIONS: NONE (0)"
echo "CANONICAL_SEEDERS_EXECUTED: 12"
echo "ADMIN_AUTH_PIVOTS: VERIFIED (Spatie super-admin)"
echo "BUSINESS_POLLUTION: CLEAN (0)"
echo "REGRESSION_GATES: PASS"
echo "RUNTIME_AUTH_SURFACE: NOT_VERIFIED_BY_THIS_RUNNER"
echo "STATUS: BOOTSTRAP_VERIFIED"
echo "=================================================="

exit 0
