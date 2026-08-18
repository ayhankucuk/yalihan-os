<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlReservationContextOutput — Immutable DTO for reservation authority context.
 *
 * PILOT-002 Wave 1
 *
 * Authority levels mirror YdlContextOutput:
 *   FULL         → tüm operasyonlara açık
 *   LIMITED      → scope intersection gerekli
 *   STOP         → tüm operasyonlar bloklanır
 *   NO_SPRINT    → YDL context yok, reservation geçersiz
 *
 * @readonly
 */
final class YdlReservationContextOutput
{
    public const AUTHORITY_FULL                = 'FULL';
    public const AUTHORITY_LIMITED            = 'LIMITED';
    public const AUTHORITY_STOP               = 'STOP';
    public const AUTHORITY_NO_SPRINT          = 'NO_SPRINT';

    public function __construct(
        public readonly string $sprint,
        public readonly string $sprintStatus,
        public readonly string $authorityLevel,
        public readonly string $authorityRationale,
        /** @var string[] */
        public readonly array  $blockedScopes,
        public readonly string $gitBranch,
        public readonly string $gitCommit,
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
            authorityLevel:            self::AUTHORITY_NO_SPRINT,
            authorityRationale:        'No active YDL sprint — reservation pipeline unavailable',
            blockedScopes:             [],
            gitBranch:                '',
            gitCommit:                '',
            sabViolationsNew:          0,
            sabViolationsBlocking:     0,
            lastUpdated:               '',
            snapshotId:                '',
        );
    }

    public function isFull(): bool
    {
        return $this->authorityLevel === self::AUTHORITY_FULL;
    }

    public function isLimited(): bool
    {
        return $this->authorityLevel === self::AUTHORITY_LIMITED;
    }

    public function isStopped(): bool
    {
        return $this->authorityLevel === self::AUTHORITY_STOP;
    }

    public function isNoSprint(): bool
    {
        return $this->authorityLevel === self::AUTHORITY_NO_SPRINT;
    }

    public function hasBlockingScopeIntersection(string $scope): bool
    {
        return in_array($scope, $this->blockedScopes, true);
    }

    public function toArray(): array
    {
        return [
            'sprint'                   => $this->sprint,
            'sprint_status'           => $this->sprintStatus,
            'authority_level'          => $this->authorityLevel,
            'authority_rationale'      => $this->authorityRationale,
            'blocked_scopes'           => $this->blockedScopes,
            'git_branch'              => $this->gitBranch,
            'git_commit'              => $this->gitCommit,
            'sab_violations_new'      => $this->sabViolationsNew,
            'sab_violations_blocking' => $this->sabViolationsBlocking,
            'last_updated'            => $this->lastUpdated,
            'snapshot_id'             => $this->snapshotId,
        ];
    }
}
