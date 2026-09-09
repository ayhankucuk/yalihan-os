<?php

namespace App\Support\Governance\Audit\DTO;

use App\Support\Governance\Audit\Contracts\AuditCheckContract;
use App\Support\Governance\Audit\Enums\AuditResult;
use App\Support\Governance\Audit\Enums\AuditSeverity;
use App\Support\Governance\Audit\Enums\EvidenceLabel;

/**
 * Individual audit check result.
 *
 * @implements AuditCheckContract
 */
final readonly class AuditCheckResult implements AuditCheckContract
{
    /**
     * @param array<array{file: string, line: int, snippet: string, severity: string}> $findings
     */
    public function __construct(
        private string $name,
        private AuditResult $status,
        private EvidenceLabel $label,
        private AuditSeverity $severity,
        private string $summary,
        private array $findings,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getLabel(): string
    {
        return $this->label->value;
    }

    public function getSeverity(): string
    {
        return $this->severity->value;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function getFindingCount(): int
    {
        return count($this->findings);
    }

    public function toArray(): array
    {
        return [
            'check' => $this->name,
            'status' => $this->status->value,
            'label' => $this->label->value,
            'severity' => $this->severity->value,
            'message' => $this->summary,
            'finding_count' => $this->getFindingCount(),
            'findings' => $this->findings,
            'icon' => $this->status->icon(),
        ];
    }

    /**
     * Create a passed check with no findings.
     */
    public static function pass(
        string $name,
        string $summary = 'Check passed',
        ?EvidenceLabel $label = null,
    ): self {
        return new self(
            name: $name,
            status: AuditResult::PASS,
            label: $label ?? EvidenceLabel::REPO_VERIFIED,
            severity: AuditSeverity::INFO,
            summary: $summary,
            findings: [],
        );
    }

    /**
     * Create a failed check with findings.
     */
    public static function fail(
        string $name,
        string $summary,
        array $findings = [],
        EvidenceLabel $label = EvidenceLabel::BLOCKED_NEEDS_FIX,
        AuditSeverity $severity = AuditSeverity::HIGH,
    ): self {
        return new self(
            name: $name,
            status: AuditResult::FAIL,
            label: $label,
            severity: $severity,
            summary: $summary,
            findings: $findings,
        );
    }

    /**
     * Create a warning check.
     */
    public static function warn(
        string $name,
        string $summary,
        array $findings = [],
        EvidenceLabel $label = EvidenceLabel::INFERRED,
        AuditSeverity $severity = AuditSeverity::MEDIUM,
    ): self {
        return new self(
            name: $name,
            status: AuditResult::WARN,
            label: $label,
            severity: $severity,
            summary: $summary,
            findings: $findings,
        );
    }

    /**
     * Create a skipped check.
     */
    public static function skip(
        string $name,
        string $summary = 'Check skipped',
    ): self {
        return new self(
            name: $name,
            status: AuditResult::SKIP,
            label: EvidenceLabel::UNKNOWN,
            severity: AuditSeverity::INFO,
            summary: $summary,
            findings: [],
        );
    }

    /**
     * Create from array (e.g., parsed from JSON output).
     */
    public static function fromArray(array $data): self
    {
        $status = $data['status'] ?? 'fail';
        $severity = $data['severity'] ?? 'medium';
        $label = $data['label'] ?? 'REPO_VERIFIED';

        // Convert string enums
        $statusEnum = AuditResult::fromLegacy($status);
        $severityEnum = AuditSeverity::fromLegacy($severity);

        // Try to convert label string to enum
        try {
            $labelEnum = EvidenceLabel::from($label);
        } catch (\ValueError) {
            $labelEnum = EvidenceLabel::UNKNOWN;
        }

        return new self(
            name: $data['name'] ?? $data['check'] ?? 'unknown',
            status: $statusEnum,
            label: $labelEnum,
            severity: $severityEnum,
            summary: $data['summary'] ?? $data['message'] ?? 'Check completed',
            findings: $data['findings'] ?? [],
        );
    }
}
