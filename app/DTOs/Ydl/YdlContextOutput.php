<?php

namespace App\DTOs\Ydl;

/**
 * YdlContextOutput — Immutable DTO for agent-readable system state.
 *
 * YDL v1 Phase 3
 *
 * Produced by: YdlContextReader
 * Consumed by: agent prompts, ydl:context CLI
 *
 * @readonly
 */
final class YdlContextOutput
{
    public const AUTHORITY_FULL                = 'FULL';
    public const AUTHORITY_LIMITED_BY_BLOCKER = 'LIMITED_BY_BLOCKER';
    public const AUTHORITY_STOP               = 'STOP';
    public const AUTHORITY_NO_SPRINT          = 'NO_SPRINT';

    public const SAB_CLEAN     = 'CLEAN';
    public const SAB_WARNINGS  = 'WARNINGS';
    public const SAB_VIOLATIONS = 'VIOLATIONS';

    public function __construct(
        public readonly string $sprint,
        public readonly string $sprintStatus,
        public readonly string $recommendationAction,
        public readonly string $recommendationTarget,
        public readonly string $recommendationRationale,
        public readonly string $confidence,
        /** @var array<int, array{gate: string, id: string, type: string, owner: string, development_action: string}> */
        public readonly array  $activeBlockers,
        public readonly string $authorityLevel,
        public readonly string $authorityRationale,
        public readonly string $gitBranch,
        public readonly string $gitCommit,
        public readonly string $sabStatus,
        public readonly int    $sabViolationsNew,
        public readonly int    $sabViolationsBlocking,
        public readonly string $lastUpdated,
        public readonly string $snapshotId,
    ) {}

    public static function empty(): self
    {
        return new self(
            sprint:                   '',
            sprintStatus:             '',
            recommendationAction:      '',
            recommendationTarget:      '',
            recommendationRationale:   '',
            confidence:                '',
            activeBlockers:            [],
            authorityLevel:            self::AUTHORITY_NO_SPRINT,
            authorityRationale:        'No active sprint — no YDL context available',
            gitBranch:                '',
            gitCommit:                '',
            sabStatus:                self::SAB_CLEAN,
            sabViolationsNew:          0,
            sabViolationsBlocking:     0,
            lastUpdated:               '',
            snapshotId:                '',
        );
    }

    public function toArray(): array
    {
        return [
            'sprint'                   => $this->sprint,
            'sprint_status'           => $this->sprintStatus,
            'recommendation_action'   => $this->recommendationAction,
            'recommendation_target'    => $this->recommendationTarget,
            'recommendation_rationale' => $this->recommendationRationale,
            'confidence'               => $this->confidence,
            'active_blockers'          => $this->activeBlockers,
            'authority_level'          => $this->authorityLevel,
            'authority_rationale'      => $this->authorityRationale,
            'git_branch'              => $this->gitBranch,
            'git_commit'              => $this->gitCommit,
            'sab_status'              => $this->sabStatus,
            'sab_violations_new'      => $this->sabViolationsNew,
            'sab_violations_blocking' => $this->sabViolationsBlocking,
            'last_updated'            => $this->lastUpdated,
            'snapshot_id'             => $this->snapshotId,
        ];
    }

    /**
     * Human-readable Markdown for agent prompt injection.
     */
    public function toMarkdown(): string
    {
        if ($this->sprint === '') {
            return "## YDL State\n\n*No active sprint — run `php artisan ydl:state` to initialize.*\n";
        }

        $authorityIcon = match ($this->authorityLevel) {
            self::AUTHORITY_FULL                 => '✅',
            self::AUTHORITY_LIMITED_BY_BLOCKER  => '⚠️',
            self::AUTHORITY_STOP                 => '🛑',
            default                              => '❓',
        };

        $sabIcon = match ($this->sabStatus) {
            self::SAB_CLEAN      => '✅',
            self::SAB_WARNINGS   => '⚠️',
            self::SAB_VIOLATIONS => '🛑',
            default              => '❓',
        };

        $lines = [
            "## YDL State — Oturum Başlangıcı",
            '',
            "**Sprint:** {$this->sprint} | **Durum:** {$this->sprintStatus}",
            "**Yetki:** {$authorityIcon} {$this->authorityLevel} — {$this->authorityRationale}",
            "**Sıradaki:** {$this->recommendationAction} — {$this->recommendationTarget} | **Güven:** {$this->confidence}",
            '',
        ];

        if (count($this->activeBlockers) > 0) {
            $lines[] = '**Aktif Blocker\'lar:**';
            $lines[] = '';
            $lines[] = '| ID | Gate | Tip | Sahip | Aksiyon |';
            $lines[] = '|----|------|-----|-------|---------|';
            foreach ($this->activeBlockers as $b) {
                $lines[] = "| {$b['id']} | {$b['gate']} | {$b['type']} | {$b['owner']} | {$b['development_action']} |";
            }
            $lines[] = '';
        } else {
            $lines[] = '**Blokör yok.**';
            $lines[] = '';
        }

        $lines[] = "**SAB:** {$sabIcon} {$this->sabStatus} ({$this->sabViolationsNew} new / {$this->sabViolationsBlocking} blocking)";
        $lines[] = "**Git:** `{$this->gitBranch}` @ {$this->gitCommit}";
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        return implode("\n", $lines);
    }
}
