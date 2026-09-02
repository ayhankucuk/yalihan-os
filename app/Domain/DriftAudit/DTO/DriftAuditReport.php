<?php

declare(strict_types=1);

namespace App\Domain\DriftAudit\DTO;

/**
 * Yalihan Drift Audit Report
 *
 * Canonical report structure for yalihan:drift-audit command.
 * Produced by YalihanDriftAuditService.
 *
 * Evidence Labels:
 *   REPO_VERIFIED      — Code/repo层面审查通过
 *   TEST_VERIFIED      — Automated test evidence exists
 *   LOCAL_RUNTIME_VERIFIED — Local SQLite/MySQL runtime verified
 *   PRODUCTION_VERIFIED — Live production evidence captured
 *   INFERRED           — Conclusion based on indirect evidence
 *   UNKNOWN            — Unable to determine
 *   BLOCKED_NEEDS_FIX  — Blocker found; cannot proceed
 */
final class DriftAuditReport
{
    public function __construct(
        public readonly string              $generatedAt,
        public readonly string              $source,
        public readonly string              $gitCommit,
        public readonly string              $evidenceLabel,
        public readonly int                 $totalChecks,
        public readonly int                 $checksPassed,
        public readonly int                 $checksFailed,
        public readonly int                 $checksWarning,
        public readonly array               $checks,
        public readonly array               $ghostTables,
        public readonly array               $ghostFields,
        public readonly array               $forbiddenAliasViolations,
        public readonly array               $unguardedTables,
        public readonly array               $missingMigrations,
        public readonly array               $seederCoverage,
        public readonly array               $gitLocalVsRemote,
        public readonly string              $summary,
        public readonly bool                $hasBlockers,
        public readonly bool                $dryRun,
    ) {}

    public static function empty(string $gitCommit): self
    {
        return new self(
            generatedAt: date('c'),
            source: 'sqlite://database/database.sqlite',
            gitCommit: $gitCommit,
            evidenceLabel: 'REPO_VERIFIED',
            totalChecks: 0,
            checksPassed: 0,
            checksFailed: 0,
            checksWarning: 0,
            checks: [],
            ghostTables: [],
            ghostFields: [],
            forbiddenAliasViolations: [],
            unguardedTables: [],
            missingMigrations: [],
            seederCoverage: [],
            gitLocalVsRemote: [],
            summary: 'No drift detected. System is in sync.',
            hasBlockers: false,
            dryRun: true,
        );
    }

    public function toArray(): array
    {
        return [
            'generated_at'                 => $this->generatedAt,
            'source'                     => $this->source,
            'git_commit'                 => $this->gitCommit,
            'evidence_label'              => $this->evidenceLabel,
            'total_checks'               => $this->totalChecks,
            'checks_passed'               => $this->checksPassed,
            'checks_failed'               => $this->checksFailed,
            'checks_warning'              => $this->checksWarning,
            'checks'                     => $this->checks,
            'ghost_tables'               => $this->ghostTables,
            'ghost_fields'               => $this->ghostFields,
            'forbidden_alias_violations' => $this->forbiddenAliasViolations,
            'unguarded_tables'           => $this->unguardedTables,
            'missing_migrations'         => $this->missingMigrations,
            'seeder_coverage'            => $this->seederCoverage,
            'git_local_vs_remote'        => $this->gitLocalVsRemote,
            'summary'                    => $this->summary,
            'has_blockers'               => $this->hasBlockers,
            'dry_run'                    => $this->dryRun,
        ];
    }
}

// DriftCheck  → DTO/DriftCheck.php
// DriftFinding → DTO/DriftFinding.php
