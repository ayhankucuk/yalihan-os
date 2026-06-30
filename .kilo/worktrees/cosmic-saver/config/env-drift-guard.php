<?php

/**
 * EnvDriftGuard Contract Configuration
 * ─────────────────────────────────────
 * Production-grade governance contract for system:env-drift-guard.
 *
 * Contract Version: 3.2.0
 * ADR: docs/adr/2026-04-10-env-drift-guard-contract.md
 *
 * MODIFICATION RULES:
 *  - Changing severity from 'warn' to 'fail' = safe (tightening)
 *  - Changing severity from 'fail' to 'warn' = requires ADR update + PR approval
 *  - Adding fix_forbidden entries = safe
 *  - Removing fix_forbidden entries = requires ADR update + PR approval
 *  - Policy lock checksum enforces config integrity
 *  - Emergency bypass requires reason + audit log (max 3/day)
 */

return [

    'contract_version' => '3.2.0',

    /*
    |--------------------------------------------------------------------------
    | Check Definitions (CI Fail Policy)
    |--------------------------------------------------------------------------
    |
    | Each check has:
    |  - severity: 'fail' (blocks CI) or 'warn' (annotation only)
    |  - fixable: whether --fix can attempt recovery
    |  - recoverable: whether the issue can be auto-resolved safely
    |  - description: human-readable check purpose
    |
    | --strict mode overrides ALL severity to 'fail'.
    |
    */

    'checks' => [

        'policy_lock' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'Policy config integrity via SHA-256 checksum',
            'inspects' => ['config/env-drift-guard.php', '.sab/policy-lock.sha256'],
            'fail_reason' => 'Policy config changed without governance review — severity may have been loosened',
        ],

        'env_testing' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => '.env.testing existence and required DB keys',
            'inspects' => ['.env.testing'],
            'fail_reason' => 'Test environment undefined — all tests unreliable',
        ],

        'db_connectivity' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'Test database reachability via PDO',
            'inspects' => ['.env.testing → DB_HOST, DB_PORT, DB_DATABASE'],
            'fail_reason' => 'Test DB unreachable — test suite will fail entirely',
        ],

        'schema_mysql' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'mysql-schema.sql (SSOT) file existence',
            'inspects' => ['database/schema/mysql-schema.sql'],
            'fail_reason' => 'SSOT schema missing — no authority source for column validation',
        ],

        'schema_testing' => [
            'severity' => 'warn',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => true,
            'description' => 'testing-schema.sql existence (recommended but optional)',
            'inspects' => ['database/schema/testing-schema.sql'],
            'fail_reason' => 'Testing schema missing — schema diff check skipped',
        ],

        'schema_diff' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'Column parity between mysql-schema.sql and testing-schema.sql',
            'inspects' => ['database/schema/mysql-schema.sql', 'database/schema/testing-schema.sql'],
            'fail_reason' => 'Schema files diverged — test DB structure unreliable',
        ],

        'fillable_alignment' => [
            'severity' => 'warn',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => true,
            'description' => 'Model $fillable vs DB column alignment (ghost field detection)',
            'inspects' => ['app/Models/Ilan.php', 'database/schema/mysql-schema.sql'],
            'fail_reason' => 'Ghost fields in $fillable may cause silent data loss',
        ],

        'migration_parity' => [
            'severity' => 'warn',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => true,
            'description' => 'Migration-declared columns vs mysql-schema.sql SSOT',
            'inspects' => ['database/migrations/*.php', 'database/schema/mysql-schema.sql'],
            'fail_reason' => 'Migrations declare columns not in SSOT — schema authority unclear',
        ],

        'enum_drift' => [
            'severity' => 'warn',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => true,
            'description' => 'Canonical enum values vs legacy string usage in codebase',
            'inspects' => ['app/Http/Controllers/**', 'app/Services/**', 'app/Modules/**'],
            'fail_reason' => 'Legacy enum values may cause silent filtering/query failures',
        ],

        'schema_checksum' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'SSOT file integrity via SHA-256 checksum',
            'inspects' => ['database/schema/mysql-schema.sql', '.sab/schema-checksum.sha256'],
            'fail_reason' => 'mysql-schema.sql modified without governance review',
        ],

        'relation_integrity' => [
            'severity' => 'fail',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => false,
            'description' => 'FK constraints reference valid tables and columns',
            'inspects' => ['database/schema/mysql-schema.sql'],
            'fail_reason' => 'Broken FK references cause runtime constraint violations',
        ],

        'orphan_tables' => [
            'severity' => 'warn',
            'fixable' => false,
            'recoverable' => false,
            'bypassable' => true,
            'description' => 'Tables in live DB but not declared in SSOT',
            'inspects' => ['database/schema/mysql-schema.sql', 'live DB'],
            'fail_reason' => 'Orphan tables indicate manual drift or abandoned experiments',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Safe Fix Boundaries
    |--------------------------------------------------------------------------
    |
    | --fix ONLY performs operations listed in fix_allowed.
    | Operations in fix_forbidden are NEVER performed, regardless of flags.
    |
    | RULE: --fix is BLOCKED when --strict is active.
    |       CI pipelines MUST NOT auto-fix; they MUST fail-fast.
    |
    */

    'fix_allowed' => [
        'config_cache_clear',    // Artisan config:clear
        'route_cache_clear',     // Artisan route:clear
        'view_cache_clear',      // Artisan view:clear
    ],

    'fix_forbidden' => [
        'db_schema_mutate',          // NEVER ALTER/DROP/CREATE TABLE
        'migration_generate',        // NEVER create migration files
        'env_production_modify',     // NEVER touch production .env
        'env_testing_modify',        // NEVER auto-edit .env.testing
        'service_behavior_modify',   // NEVER modify service layer code
        'model_fillable_modify',     // NEVER auto-edit $fillable arrays
        'enum_value_modify',         // NEVER modify enum class files
        'schema_file_modify',        // NEVER modify schema SQL files
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Tables (Governance Scope)
    |--------------------------------------------------------------------------
    */

    'critical_tables' => ['ilanlar', 'kisiler', 'roles', 'users'],

    'critical_models' => [
        \App\Models\Ilan::class => 'ilanlar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enum Governance
    |--------------------------------------------------------------------------
    */

    'enum_checks' => [
        [
            'enum' => \App\Enums\IlanDurumu::class,
            'field' => 'yayin_durumu',
            'scan_dirs' => ['app/Http/Controllers', 'app/Services', 'app/Modules'],
            'forbidden_values' => ['Aktif', 'Taslak', 'Pasif', 'Beklemede', 'Active', 'Draft', 'Inactive'],
        ],
        [
            'enum' => \App\Enums\AktiflikDurumu::class,
            'field' => 'aktiflik_durumu',
            'scan_dirs' => ['app/Http/Controllers', 'app/Services', 'app/Modules'],
            'forbidden_values' => ['active', 'inactive', 'enabled', 'disabled'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Required .env.testing Keys
    |--------------------------------------------------------------------------
    */

    'required_env_keys' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'],

    /*
    |--------------------------------------------------------------------------
    | Orphan Table Ignore List
    |--------------------------------------------------------------------------
    | Framework/system tables that exist in DB but are NOT in mysql-schema.sql.
    | These are expected and should not trigger orphan warnings.
    */

    'orphan_ignore_tables' => [
        'migrations',
        'failed_jobs',
        'personal_access_tokens',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Lock (v3.2)
    |--------------------------------------------------------------------------
    |
    | SHA-256 checksum of THIS config file is stored in .sab/policy-lock.sha256.
    | If the config changes without updating the lock, guard detects tampering.
    | Prevents: silent severity loosening (fail→warn) without governance review.
    |
    | enabled: true/false — toggle policy lock enforcement
    | lockfile: path to store the policy checksum
    |
    */

    'policy_lock' => [
        'enabled' => true,
        'lockfile' => '.sab/policy-lock.sha256',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-based Override (v3.2)
    |--------------------------------------------------------------------------
    |
    | Different execution contexts enforce different policies.
    | Role is determined by --role=<name> or auto-detected from environment.
    |
    | 'ci':        strict by default, --fix blocked, bypass via token only
    | 'local':     default mode, --fix allowed, bypass via token allowed
    | 'emergency': explicit emergency role, bypass allowed, --fix blocked
    |
    | auto_detect: if true, role is inferred from CI env vars when --role omitted
    |   - CI_* or GITHUB_ACTIONS=true → 'ci'
    |   - otherwise → 'local'
    |
    | Role Matrix (governance roles are for contract creation, not CLI):
    |  developer:        run guard, JSON output, local --fix
    |  maintainer:       tighten severity (WARN→FAIL), propose ignore list
    |  governance_admin: loosen severity, approve bypass, policy unlock
    |  ci_system:        enforce only, cannot modify policy
    |
    */

    'roles' => [
        'auto_detect' => true,

        'ci' => [
            'implicit_strict' => true,     // --strict assumed
            'fix_allowed' => false,        // --fix blocked
            'bypass_allowed' => true,      // bypass via valid token only
        ],

        'local' => [
            'implicit_strict' => false,
            'fix_allowed' => true,
            'bypass_allowed' => true,
        ],

        'emergency' => [
            'implicit_strict' => false,
            'fix_allowed' => false,        // no --fix during emergency
            'bypass_allowed' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Contract System (v3.2)
    |--------------------------------------------------------------------------
    |
    | Token-based bypass with governance controls.
    | Usage: --bypass-token=BYP-2026-04-10-001
    |
    | Bypass contracts are stored in a JSON file and must be pre-approved.
    | Each contract specifies: which checks, who approved, when it expires.
    |
    | Non-bypassable checks ALWAYS fail regardless of any token.
    | Bypassable checks: only governance debt class (WARN severity).
    |
    | Rules:
    |  - Token must exist in contract file
    |  - Contract must not be expired
    |  - Check must be in bypassable list
    |  - Max duration: 7 days
    |  - Full audit trail on every use
    |  - --fix + --bypass-token: REJECTED
    |
    */

    'bypass' => [
        'contract_file' => 'storage/governance/env-drift-bypass.json',
        'audit_file' => 'storage/governance/env-drift-audit.log',
        'log_channel' => 'security',
        'max_duration_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Non-Bypassable Checks (v3.2)
    |--------------------------------------------------------------------------
    | These checks can NEVER be bypassed. They represent critical system
    | integrity — if they fail, no token can override them.
    |
    | Adding to this list = safe (tightening)
    | Removing from this list = requires ADR + governance_admin approval
    */

    'non_bypassable_checks' => [
        'policy_lock',
        'env_testing',
        'db_connectivity',
        'schema_mysql',
        'schema_diff',
        'schema_checksum',
        'relation_integrity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypassable Checks (v3.2)
    |--------------------------------------------------------------------------
    | Only governance debt checks can be bypassed with a valid token.
    | These are checks where the system works but architectural debt exists.
    */

    'bypassable_checks' => [
        'orphan_tables',
        'enum_drift',
        'migration_parity',
        'fillable_alignment',
        'schema_testing',
    ],

];
