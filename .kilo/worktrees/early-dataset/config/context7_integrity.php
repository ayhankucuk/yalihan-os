<?php

declare(strict_types=1);

/**
 * Context7 Integrity Scan Configuration
 *
 * Governance rules for architectural compliance:
 * - Forbidden field naming (status, active, order, etc.)
 * - Canonical field enforcement (authority.json SSOT)
 * - Legacy namespace elimination (Phase-based migration)
 */

return [

    /**
     * Legacy Namespace Prefixes
     *
     * Services that have been migrated to new module locations.
     * References to these prefixes are violations (except in allowlist).
     *
     * Template: Old → New
     * - App\Domain\PropertyHub\Governance → App\Modules\GovernanceCore
     */
    'legacy_namespaces' => [
        'App\\Domain\\PropertyHub\\Governance\\',
    ],

    /**
     * Allowlist Patterns
     *
     * These paths are exempt from legacy namespace violations.
     * Rationale: Tests, seeds, and decommissioning stubs reference legacy code during migration.
     */
    'legacy_allowlist' => [
        'tests/',
        'database/seeders/',
        'database/migrations/',
        'app/Domain/PropertyHub/Governance/', // Alias stub files
        'stubs/',
        'docs/',
    ],

    /**
     * Enforcement Mode
     *
     * warning : Violations are logged but exit(0) is returned (non-blocking)
     * strict : Violations cause exit(1) (blocking, CI fails)
     *
     * CI runs in strict mode.
     * Local development can use warning mode for convenience.
     */
    'enforcement_mode' => env('CONTEXT7_ENFORCEMENT', 'strict'),

    /**
     * Violations Configuration
     *
     * Severity levels and reporting thresholds
     */
    'violations' => [
        'report_critical' => true,
        'report_high' => true,
        'max_allowed_critical' => 0,
        'max_allowed_high' => 0,
    ],

    /**
     * Canonical Field Mappings
     *
     * Authority.json defines all canonical field names.
     * This reference ensures consistency between config and code.
     *
     * Loaded from: .sab/authority.json (Canonical SSOT v2.6)
     */
    'authority_file' => base_path('.sab/authority.json'),

    /**
     * Scan Directories
     *
     * Directories to scan for violations
     */
    'scan_paths' => [
        base_path('app'),
        base_path('config'),
        base_path('database/factories'),
        base_path('routes'),
        base_path('resources/views'),
        base_path('tests'),
    ],

];
