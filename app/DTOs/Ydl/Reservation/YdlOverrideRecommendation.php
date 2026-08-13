<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlOverrideRecommendation — Immutable DTO for conflict override readiness.
 *
 * PILOT-002 Wave 3
 *
 * @readonly
 */
final class YdlOverrideRecommendation
{
    public const DECISION_OVERRIDE_READY       = 'OVERRIDE_READY';
    public const DECISION_OVERRIDE_BLOCKED      = 'OVERRIDE_BLOCKED';
    public const DECISION_OVERRIDE_UNAUTHORIZED = 'OVERRIDE_UNAUTHORIZED';

    public const PILOT = 'PILOT-002';

    public function __construct(
        public readonly int                   $conflictReservationId,
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string                $ydlAuthority,
        /** self::DECISION_* constant */
        public readonly string                $decision,
        public readonly string               $decisionLabel,
        public readonly string               $rationale,
        public readonly string               $confidence,
        public readonly bool                 $humanApprovalRequired,
        public readonly bool                 $canOverride,
        public readonly ?int               $authorizedUserId,
        /** @var string[] */
        public readonly array                $overrideReasons,
        public readonly string               $startDate,
        public readonly string               $endDate,
        /** ISO8601 timestamp */
        public readonly string               $evaluatedAt,
        public readonly string               $snapshotId,
    ) {}

    public function isReady(): bool
    {
        return $this->decision === self::DECISION_OVERRIDE_READY;
    }

    public function isBlocked(): bool
    {
        return $this->decision === self::DECISION_OVERRIDE_BLOCKED;
    }

    public function isUnauthorized(): bool
    {
        return $this->decision === self::DECISION_OVERRIDE_UNAUTHORIZED;
    }

    public function toArray(): array
    {
        return [
            'pilot'                   => self::PILOT,
            'conflict_reservation_id'  => $this->conflictReservationId,
            'ilan_id'                 => $this->ilanId,
            'tenant_id'               => $this->tenantId,
            'ydl_authority'          => $this->ydlAuthority,
            'decision'               => $this->decision,
            'decision_label'         => $this->decisionLabel,
            'rationale'              => $this->rationale,
            'confidence'             => $this->confidence,
            'human_approval_required' => $this->humanApprovalRequired,
            'can_override'          => $this->canOverride,
            'authorized_user_id'     => $this->authorizedUserId,
            'override_reasons'       => $this->overrideReasons,
            'start_date'            => $this->startDate,
            'end_date'              => $this->endDate,
            'evaluated_at'           => $this->evaluatedAt,
            'snapshot_id'            => $this->snapshotId,
        ];
    }
}
