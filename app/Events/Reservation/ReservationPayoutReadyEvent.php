<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;

/**
 * ReservationPayoutReadyEvent — C3.3: Payout Readiness
 *
 * Fired AFTER ProcessFinancialCompletionJob has:
 *   1. Transitioned finansal_durum → CONFIRMED
 *   2. Created commission split + owner payable accrual ledger entries
 *
 * This event signals that a reservation is now READY FOR PAYOUT from the
 * owner's perspective — Yalihan owes the owner the entitlement amount.
 *
 * No automatic payment is triggered. This event surfaces the payout-ready
 * state to the admin/operator for human approval (SAAB principle:
 * "AI assists, humans approve strategic decisions").
 *
 * Scope exclusions (later waves):
 *   - Actual bank transfer
 *   - Channel fee deduction
 *   - KDV/tax allocation
 *   - Reconciliation
 *
 * Baseline: 76a467e (C3.2 Certified)
 */
final readonly class ReservationPayoutReadyEvent
{
    use \Illuminate\Foundation\Events\Dispatchable;

    public function __construct(
        public int     $reservationId,
        public int     $tenantId,
        public int     $ilanId,
        public string  $startDate,
        public string  $endDate,
        public int     $nights,
        public string  $guestName,
        public ?string $guestEmail,
        public ?string $guestPhone,
        public ?int    $guestCount,
        public float   $grossAmount,
        public string  $currency,
        public float   $lockedFxRate,
        public float   $commissionAmount,
        public float   $ownerEntitlement,
        public string  $managementModelSnapshot,
        public float   $commissionRateSnapshot,
        public string  $completedAt,
        public ?string $externalReservationId,
        public ?string $externalChannel,
        // Owner info (resolved from reservation → ilan → kisi chain)
        public ?int    $ownerKisiId,
        public ?string $ownerName,
        // Ilan info
        public string  $ilanBaslik,
        public ?string $ilanSlug,
    ) {}

    /**
     * Factory from model + computed financial fields.
     */
    public static function fromReservation(
        PropertyReservation $reservation,
        float   $grossAmount,
        float   $commissionAmount,
        float   $ownerEntitlement,
        ?int    $ownerKisiId = null,
        ?string $ownerName = null,
    ): self {
        $ilan = $reservation->ilan;

        return new self(
            reservationId:          $reservation->id,
            tenantId:              $reservation->tenant_id ?? 0,
            ilanId:               $reservation->ilan_id ?? $reservation->property_id,
            startDate:            $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            endDate:              $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            nights:               $reservation->nights,
            guestName:            $reservation->guest_name,
            guestEmail:           $reservation->guest_email,
            guestPhone:          $reservation->guest_phone,
            guestCount:          $reservation->guest_count,
            grossAmount:         $grossAmount,
            currency:            $reservation->currency ?? 'TRY',
            lockedFxRate:        (float) ($reservation->booking_fx_rate ?? 1.0),
            commissionAmount:     $commissionAmount,
            ownerEntitlement:    $ownerEntitlement,
            managementModelSnapshot: self::_snapshotToString($reservation->management_model_snapshot),
            commissionRateSnapshot: (float) ($reservation->commission_rate_snapshot ?? 0.0),
            completedAt:          now()->toIso8601String(),
            externalReservationId: $reservation->external_reservation_id,
            externalChannel:      $reservation->external_channel,
            ownerKisiId:         $ownerKisiId,
            ownerName:            $ownerName,
            ilanBaslik:          $ilan?->baslik ?? 'Bilinmeyen İlan',
            ilanSlug:            $ilan?->slug,
        );
    }

    /**
     * Safely convert management_model_snapshot to string.
     * Handles PHP enum instances and null values.
     */
    private static function _snapshotToString(mixed $val): string
    {
        if ($val instanceof \App\Enums\ManagementModel) {
            return $val->value;
        }
        return (string) ($val ?? 'UNKNOWN');
    }
}
