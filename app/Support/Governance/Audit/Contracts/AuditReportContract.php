<?php

namespace App\Support\Governance\Audit\Contracts;

/**
 * Common audit report contract for Yalıhan OS layered audit system.
 *
 * All audit commands MUST return results conforming to this interface.
 * This ensures consistent JSON output, exit codes, and CI integration.
 *
 * @example JSON output:
 * {
 *   "command": "yalihan:check drift schema",
 *   "version": "1.0.0",
 *   "status": "pass|warn|fail",
 *   "success": true|false,
 *   "summary": {
 *     "total_checks": 5,
 *     "passed": 4,
 *     "warnings": 1,
 *     "failures": 0
 *   },
 *   "checks": [...],
 *   "evidence_label": "REPO_VERIFIED",
 *   "generated_at": "2026-09-03T12:00:00+03:00",
 *   "git_commit": "abc1234",
 *   "duration_ms": 150
 * }
 */
interface AuditReportContract
{
    // ── Identity ─────────────────────────────────────────────────────────────

    public function getCommand(): string;
    public function getVersion(): string;

    // ── Status ───────────────────────────────────────────────────────────────

    /**
     * @return 'pass'|'warn'|'fail'
     */
    public function getStatus(): string;

    /**
     * True when no failures and acceptable warning count.
     */
    public function isSuccess(): bool;

    // ── Summary ──────────────────────────────────────────────────────────────

    public function getTotalChecks(): int;
    public function getPassed(): int;
    public function getWarnings(): int;
    public function getFailures(): int;

    // ── Detailed Results ────────────────────────────────────────────────────

    /**
     * @return array<array{check: string, status: string, label: string, severity: string, message: string, findings: array}>
     */
    public function getChecks(): array;

    /**
     * @return array<array{file: string, line: int, snippet: string, severity: string}>
     */
    public function getFindings(): array;

    // ── Provenance ──────────────────────────────────────────────────────────

    public function getEvidenceLabel(): string;
    public function getGeneratedAt(): string;
    public function getGitCommit(): ?string;
    public function getDurationMs(): int;

    // ── Exit Code ───────────────────────────────────────────────────────────

    /**
     * 0 = pass (no blockers)
     * 1 = fail (blockers found)
     * 2 = system error
     */
    public function getExitCode(): int;

    // ── Serialization ──────────────────────────────────────────────────────

    public function toArray(): array;
    public function toJson(): string;
}
