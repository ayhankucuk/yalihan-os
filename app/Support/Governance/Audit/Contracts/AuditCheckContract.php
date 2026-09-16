<?php

namespace App\Support\Governance\Audit\Contracts;

/**
 * Individual audit check result within an AuditReport.
 */
interface AuditCheckContract
{
    public function getName(): string;

    /**
     * @return 'pass'|'fail'|'warn'|'skip'
     */
    public function getStatus(): string;

    /**
     * @return 'REPO_VERIFIED'|'TEST_VERIFIED'|'LOCAL_RUNTIME_VERIFIED'|'PRODUCTION_VERIFIED'|'INFERRED'|'UNKNOWN'
     */
    public function getLabel(): string;

    /**
     * @return 'critical'|'high'|'medium'|'low'|'info'
     */
    public function getSeverity(): string;

    public function getSummary(): string;
    public function getFindings(): array;
    public function getFindingCount(): int;
    public function toArray(): array;
}
