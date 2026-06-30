<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resiliency;

use App\Domain\PropertyHub\Observability\GovernanceIncidentService;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Modules\GovernanceCore\Core\VersionActivationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registry Bypass Detector
 *
 * Monitors DB queries for unauthorized direct access to governed tables.
 * Part of the Sprint 13 Zero-Trust protocol.
 */
class RegistryBypassDetector
{
    private const GOVERNED_TABLES = [
        'property_config_versions',
        'property_config_audit_logs',
        'ups_templates',
        'governed_rule_definitions',
        'governed_rule_assignments',
    ];

    /**
     * Canonical authorized namespace list.
     * Legacy decommission sonrasi whitelist'e eklenmis servisler.
     * Yeni yetkili erisim eklendikce buraya eklenir.
     */
    private const AUTHORIZED_NAMESPACES = [
        // Registry & Activation
        'ActiveConfigRegistry',
        'VersionActivationService',
        'VersionRollbackService',
        'ConfigSnapshotService',
        // Drift & Governance
        'DriftDetectionService',
        'AutonomousDriftResponder',
        'GovernanceRiskScorer',
        'ActivationLockService',
        // Observability (Read-Only)
        'GovernanceObservabilityController',
        // Health & Recovery
        'HealthAutoRecoveryService',
    ];

    private bool $isEnabled = true;

    public function __construct(
        private readonly GovernanceIncidentService $incidentService
    ) {
        $this->registerListener();
    }

    /**
     * Start/Stop monitoring.
     */
    public function enable(): void { $this->isEnabled = true; }
    public function disable(): void { $this->isEnabled = false; }

    /**
     * Internal listener registration.
     */
    private function registerListener(): void
    {
        DB::listen(function ($query) {
            if (!$this->isEnabled) {
                return;
            }

            $sql = strtolower($query->sql);

            foreach (self::GOVERNED_TABLES as $table) {
                if (str_contains($sql, $table)) {
                    $this->checkBypass($table, $sql);
                    break;
                }
            }
        });
    }

    /**
     * Check if the query is authorized.
     */
    private function checkBypass(string $table, string $sql): void
    {
        // Whitelisted Contexts:
        // 1. Registry (Read)
        // 2. Activation Service (Read/Write)
        // 3. Rollback Service (Read/Write)
        // 4. Migrations/Seeds (In CLI)

        if (app()->runningInConsole() && !app()->environment('testing')) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        $authorized = false;

        foreach ($backtrace as $step) {
            $class = $step['class'] ?? '';
            foreach (self::AUTHORIZED_NAMESPACES as $ns) {
                if (str_contains($class, $ns)) {
                    $authorized = true;
                    break 2;
                }
            }
        }

        if (!$authorized) {
            $this->incidentService->record(
                type: 'registry_bypass',
                source: 'RegistryBypassDetector',
                risk: 'MEDIUM',
                details: [
                    'table' => $table,
                    'sql' => $sql,
                    'caller' => $backtrace[5]['class'] ?? 'unknown', // Surface level caller
                ]
            );

            Log::channel('governance_security')->warning("ZERO-TRUST BYPASS ATTEMPT: Direct access to {$table} detected.", [
                'sql' => $sql
            ]);
        }
    }
}
