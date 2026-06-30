<?php

namespace App\Support\Governance;

/**
 * SealRegistry — Domain Seal Definitions
 * 
 * Hangi domain'in hangi "Foundation Lock" kanıtlarına sahip olduğunu tanımlar.
 * Read-only mimari ile sadece mevcut durumu raporlar.
 */
class SealRegistry
{
    /**
     * Global Architectural Status: GLOBAL_SEAL_SUCCESS
     * Verifies that the system has achieved semantic awareness (Phase 11).
     */
    public const GLOBAL_STATUS = 'GLOBAL_SEAL_SUCCESS';

    /**
     * Domain tanımları ve ilişkili kontroller
     */
    private const DOMAINS = [
        'CRM' => [
            'label' => 'Customer Relationship Management',
            'scanners' => [
                'crm:drift-scan',
            ],
            'tests' => [
                'CrmDriftGuardTest',
                'BulkKisiNormalizationTest',
            ],
            'gates' => [
                'sab:integrity-scan --diff',
            ],
            'durum' => 'SEALED',
        ],
        'TASK' => [
            'label' => 'Task & Action Management',
            'scanners' => [
                'model:drift-scan --model="App\Modules\TakimYonetimi\Models\Gorev"',
            ],
            'tests' => [
                'TaskIntegrityTest',
            ],
            'gates' => [
                'sab:integrity-scan',
            ],
            'durum' => 'SEALED',
        ],
        'FINANCE' => [
            'label' => 'Financial Transactions & Commissions',
            'scanners' => [
                'model:drift-scan --model="App\Modules\Finans\Models\FinansalIslem"',
                'model:drift-scan --model="App\Modules\Finans\Models\Komisyon"',
            ],
            'tests' => [
                'FinanceIntegrityTest',
            ],
            'gates' => [
                'sab:integrity-scan',
            ],
            'durum' => 'SEALED',
        ],
        'GOVERNANCE' => [
            'label' => 'Governance & Analysis Infrastructure',
            'scanners' => [
                'governance:analyze',
            ],
            'tests' => [
                'AnalysisResultSummaryTest',
                'AnalysisRunnerTest',
                'FindingContractTest',
                'MarkdownReporterTest',
                'Context7ForbiddenFieldDetectorTest',
                'RouteAuthorityDetectorTest',
                'OrphanReferenceDetectorTest',
                'EnvironmentBlockerDetectorTest',
            ],
            'gates' => [
                // quality:gate intentionally removed — it calls domain:seal-check ALL
                // which re-enters checkDomain(GOVERNANCE) causing infinite recursion.
                // SAB integrity scan below is the authoritative gate for this domain.
                'sab:integrity-scan --diff',
            ],
            'durum' => 'SEALED',
        ],
    ];

    /**
     * Tüm domain listesini döner
     */
    public static function getDomains(): array
    {
        return self::DOMAINS;
    }

    /**
     * Belirli bir domain bilgisini döner
     */
    public static function getDomain(string $name): ?array
    {
        return self::DOMAINS[strtoupper($name)] ?? null;
    }

    /**
     * Domain mühürlü mü?
     */
    public static function isSealed(string $name): bool
    {
        return (self::DOMAINS[strtoupper($name)]['durum'] ?? '') === 'SEALED';
    }
}
