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
        // ownerEntitlement: C3.2 value (gross - yalihan_commission) — for ledger accrual
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
        // C4.1: Channel Fee Snapshot
        public ?float  $channelFeeAmount,
        public ?string $channelFeeCurrency,
        public ?float  $channelFeeRate,
        public ?string $channelFeeSource,
        public ?string $channelFeeBearer,
        public bool    $channelFeeIsVerified,
        // C4.1: ownerEntitlementAfterChannel = gross - channelFee - yalihanComm
        // null means channel fee unknown (payout blocked until reconciled)
        public ?float  $ownerEntitlementAfterChannel,
    ) {}

    /**
     * Factory from model + computed financial fields.
     *
     * C4.1 Invariant 1:
     *   ownerEntitlementAfterChannel = grossAmount - channelFeeAmount - commissionAmount
     *   For OWNER_BORNE model.
     *
     * C4.1 Invariant 2:
     *   If channel fee source is UNKNOWN or bearer requires fee but fee is unknown:
     *   ownerEntitlementAfterChannel = null → payout readiness BLOCKED
     */
    public static function fromReservation(
        PropertyReservation $reservation,
        float   $grossAmount,
        float   $commissionAmount,
        float   $ownerEntitlement,
        ?int    $ownerKisiId = null,
        ?string $ownerName = null,
        ?float  $channelFeeAmount = null,
        ?string $channelFeeCurrency = null,
        ?float  $channelFeeRate = null,
        ?string $channelFeeSource = null,
        ?string $channelFeeBearer = null,
        bool    $channelFeeIsVerified = false,
    ): self {
        $ilan = $reservation->ilan;

        // C4.1 Invariant 1: compute ownerEntitlementAfterChannel
        // Only if channel fee is known and bearer requires it (OWNER_BORNE, COMMISSION_SHARE)
        $ownerEntitlementAfterChannel = self::computeOwnerEntitlementAfterChannel(
            $grossAmount,
            $commissionAmount,
            $channelFeeAmount,
            $channelFeeBearer,
        );

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
            guestCount:          $reservation->guest_count ?? 0,
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
            // C4.1: Channel Fee Snapshot
            channelFeeAmount:       $channelFeeAmount,
            channelFeeCurrency:    $channelFeeCurrency,
            channelFeeRate:        $channelFeeRate,
            channelFeeSource:      $channelFeeSource,
            channelFeeBearer:      $channelFeeBearer,
            channelFeeIsVerified:  $channelFeeIsVerified,
            // C4.1: Derived
            ownerEntitlementAfterChannel: $ownerEntitlementAfterChannel,
        );
    }

    /**
     * C4.1 Invariant 1 & 2 implementation.
     *
     * Returns ownerEntitlementAfterChannel = gross - channelFee - yalihanCommission
     * For OWNER_BORNE model.
     *
     * Returns null if:
     *   - channel fee amount is null (Invariant 2: UNKNOWN → BLOCK payout)
     *   - bearer is YALIHAN_BORNE (channel fee is Yalihan's cost, not deducted from owner)
     *
     * C4.1 Invariant 2:
     *   UNKNOWN channel fee → null → payout readiness BLOCKED
     */
    private static function computeOwnerEntitlementAfterChannel(
        float   $grossAmount,
        float   $commissionAmount,
        ?float  $channelFeeAmount,
        ?string $channelFeeBearer,
    ): ?float {
        // YALIHAN_BORNE: channel fee is Yalihan's problem
        // Owner gets gross - yalihan commission (same as C3.2)
        if ($channelFeeBearer === 'YALIHAN_BORNE') {
            return $grossAmount - $commissionAmount;
        }

        // OWNER_BORNE and COMMISSION_SHARE: need channel fee to be known
        if ($channelFeeBearer === 'OWNER_BORNE' || $channelFeeBearer === 'COMMISSION_SHARE') {
            if ($channelFeeAmount === null) {
                // C4.1 Invariant 2: DO NOT GUESS
                return null;
            }
            return $grossAmount - $channelFeeAmount - $commissionAmount;
        }

        // Bearer unknown: treat as unknown channel fee — block payout
        // (C4.1 Invariant 2: UNKNOWN → BLOCK)
        return null;
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
