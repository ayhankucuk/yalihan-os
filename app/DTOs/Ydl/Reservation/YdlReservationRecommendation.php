<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlReservationRecommendation — Immutable DTO for agent-readable reservation readiness.
 *
 * PILOT-002 Wave 1
 *
 * Produced by: ReservationReadinessService
 * Consumed by: Agent prompts, orchestrator, test assertions
 *
 * @readonly
 */
final class YdlReservationRecommendation
{
    public const DECISION_RESERVATION_READY  = 'RESERVATION_READY';
    public const DECISION_MISSING_FIELDS    = 'MISSING_FIELDS';
    public const DECISION_BLOCKED_GATE      = 'BLOCKED_GATE';
    public const DECISION_CONFLICT          = 'CONFLICT_DETECTED';
    public const DECISION_UNAVAILABLE       = 'PROPERTY_UNAVAILABLE';

    public const PILOT = 'PILOT-002';

    public function __construct(
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string                $ydlAuthority,
        /** self::DECISION_* constant */
        public readonly string                $decision,
        public readonly string                $decisionLabel,
        public readonly string                $rationale,
        public readonly string                $confidence,
        public readonly bool                  $humanApprovalRequired,
        public readonly bool                  $canReserve,
        public readonly bool                  $rentalEnabled,
        public readonly int                   $minStayNights,
        public readonly int                   $requestedNights,
        /** @var string[] */
        public readonly array                 $missingFields,
        /** @var string[] */
        public readonly array                 $blockingReasons,
        public readonly ?string               $conflictReservationId,
        public readonly string                $startDate,
        public readonly string                $endDate,
        /** ISO8601 timestamp */
        public readonly string                $evaluatedAt,
        public readonly string                $snapshotId,
    ) {}

    public function isReady(): bool
    {
        return $this->decision === self::DECISION_RESERVATION_READY;
    }

    public function isBlocked(): bool
    {
        return $this->decision === self::DECISION_BLOCKED_GATE;
    }

    public function isConflict(): bool
    {
        return $this->decision === self::DECISION_CONFLICT;
    }

    public function hasMissingFields(): bool
    {
        return $this->decision === self::DECISION_MISSING_FIELDS;
    }

    public function toArray(): array
    {
        return [
            'pilot'                    => self::PILOT,
            'ilan_id'                  => $this->ilanId,
            'tenant_id'                => $this->tenantId,
            'ydl_authority'           => $this->ydlAuthority,
            'decision'                => $this->decision,
            'decision_label'          => $this->decisionLabel,
            'rationale'               => $this->rationale,
            'confidence'              => $this->confidence,
            'human_approval_required'  => $this->humanApprovalRequired,
            'can_reserve'             => $this->canReserve,
            'rental_enabled'          => $this->rentalEnabled,
            'min_stay_nights'         => $this->minStayNights,
            'requested_nights'        => $this->requestedNights,
            'missing_fields'          => $this->missingFields,
            'blocking_reasons'        => $this->blockingReasons,
            'conflict_reservation_id'  => $this->conflictReservationId,
            'start_date'             => $this->startDate,
            'end_date'               => $this->endDate,
            'evaluated_at'           => $this->evaluatedAt,
            'snapshot_id'            => $this->snapshotId,
        ];
    }

    public function toMarkdown(): string
    {
        $icon = match ($this->decision) {
            self::DECISION_RESERVATION_READY => '✅',
            self::DECISION_CONFLICT         => '🛑',
            self::DECISION_BLOCKED_GATE     => '🛑',
            self::DECISION_UNAVAILABLE     => '⚠️',
            default                         => '⚠️',
        };

        $lines = [
            "## YDL — Reservation Readiness — " . self::PILOT,
            '',
            "**Ilan:** `#{$this->ilanId}` | **Tenant:** `#{$this->tenantId}` | **Authority:** `{$this->ydlAuthority}`",
            "**Karar:** {$icon} **{$this->decisionLabel}**",
            "**Gerekçe:** {$this->rationale}",
            "**Güven:** {$this->confidence}",
            '',
            "**İnsan Onayı:** " . ($this->humanApprovalRequired ? '✅ Zorunlu' : '❌ Gerekmiyor'),
            "**Rezerve Edilebilir:** " . ($this->canReserve ? '✅ Evet' : '❌ Hayır'),
            '',
            "**Tarih:** {$this->startDate} → {$this->endDate} ({$this->requestedNights} gece)",
            "**Min Stay:** {$this->minStayNights} gece | **Rental Enabled:** " . ($this->rentalEnabled ? '✅' : '❌'),
        ];

        if (! empty($this->missingFields)) {
            $lines[] = '';
            $lines[] = '**Eksik Alanlar:**';
            foreach ($this->missingFields as $field) {
                $lines[] = "- {$field}";
            }
        }

        if (! empty($this->blockingReasons)) {
            $lines[] = '';
            $lines[] = '**Bloke Edenler:**';
            foreach ($this->blockingReasons as $reason) {
                $lines[] = "- {$reason}";
            }
        }

        if ($this->conflictReservationId !== null) {
            $lines[] = '';
            $lines[] = "**Çakışan Rezervasyon:** `#{$this->conflictReservationId}`";
        }

        $lines[] = '';
        $lines[] = "**Değerlendirme:** {$this->evaluatedAt}";

        return implode("\n", $lines);
    }
}
