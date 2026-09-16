<?php

namespace App\Support\Governance\Audit\DTO;

use App\Support\Governance\Audit\Contracts\AuditReportContract;
use App\Support\Governance\Audit\Enums\AuditResult;
use App\Support\Governance\Audit\Enums\AuditSeverity;
use App\Support\Governance\Audit\Enums\EvidenceLabel;

/**
 * Standardized audit report DTO.
 *
 * All audit commands should produce reports conforming to this structure.
 * Ensures consistent JSON output, exit codes, and CI integration.
 *
 * @implements AuditReportContract
 */
final readonly class AuditReport implements AuditReportContract
{
    /**
     * @param array<AuditCheckResult> $checks
     * @param array<array{file: string, line: int, snippet: string, severity: string}> $findings
     */
    public function __construct(
        private string $command,
        private string $version,
        private array $checks,
        private array $findings,
        private string $evidenceLabel,
        private string $generatedAt,
        private ?string $gitCommit,
        private int $durationMs,
    ) {}

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        if ($this->getFailures() > 0) {
            return 'fail';
        }
        if ($this->getWarnings() > 0) {
            return 'warn';
        }
        return 'pass';
    }

    public function isSuccess(): bool
    {
        return $this->getStatus() !== 'fail';
    }

    public function getTotalChecks(): int
    {
        return count($this->checks);
    }

    public function getPassed(): int
    {
        return count(array_filter(
            $this->checks,
            fn($check) => $check->getStatus() === AuditResult::PASS->value
        ));
    }

    public function getWarnings(): int
    {
        return count(array_filter(
            $this->checks,
            fn($check) => $check->getStatus() === AuditResult::WARN->value
        ));
    }

    public function getFailures(): int
    {
        return count(array_filter(
            $this->checks,
            fn($check) => $check->getStatus() === AuditResult::FAIL->value
        ));
    }

    public function getChecks(): array
    {
        return array_map(fn($check) => $check->toArray(), $this->checks);
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function getEvidenceLabel(): string
    {
        return $this->evidenceLabel;
    }

    public function getGeneratedAt(): string
    {
        return $this->generatedAt;
    }

    public function getGitCommit(): ?string
    {
        return $this->gitCommit;
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }

    public function getExitCode(): int
    {
        // Exit code 2 = system error only
        // Exit code 1 = failures found
        // Exit code 0 = pass/warn only
        if ($this->getFailures() > 0) {
            return 1;
        }
        return 0;
    }

    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'version' => $this->version,
            'status' => $this->getStatus(),
            'success' => $this->isSuccess(),
            'summary' => [
                'total_checks' => $this->getTotalChecks(),
                'passed' => $this->getPassed(),
                'warnings' => $this->getWarnings(),
                'failures' => $this->getFailures(),
            ],
            'checks' => $this->getChecks(),
            'findings' => $this->getFindings(),
            'evidence_label' => $this->getEvidenceLabel(),
            'generated_at' => $this->getGeneratedAt(),
            'git_commit' => $this->gitCommit,
            'duration_ms' => $this->durationMs,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create an empty report with no findings.
     */
    public static function empty(string $command, ?string $gitCommit = null): self
    {
        return new self(
            command: $command,
            version: '1.0.0',
            checks: [],
            findings: [],
            evidenceLabel: EvidenceLabel::REPO_VERIFIED->value,
            generatedAt: date('c'),
            gitCommit: $gitCommit ?? self::getCurrentGitCommit(),
            durationMs: 0,
        );
    }

    /**
     * Get current Git commit hash.
     */
    private static function getCurrentGitCommit(): ?string
    {
        try {
            return trim(shell_exec('git rev-parse HEAD 2>/dev/null')) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
