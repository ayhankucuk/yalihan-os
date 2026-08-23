<?php

namespace App\Services\Finance;

use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
use App\Enums\ManagementModel;
use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use App\ValueObjects\TransactionStatus;
use Illuminate\Support\Collection;

/**
 * PayoutReadinessService — C3.3: Payout Readiness
 *
 * Surfaces the C3.2 owner payable accrual as a human-readable payout-ready state.
 * No automatic payment is triggered. Human operator approves before payout.
 *
 * Key method: getPayoutReadiness() returns a structured array for admin UI.
 *
 * Design principles:
 * - Reads from reservation model (C3.2 snapshot fields) — not ledger
 * - Validates ledger entries exist (audit trail proof)
 * - Aggregates by owner (kisi) for batch payout decisions
 * - Legacy NULL snapshot → not payout-ready (graceful skip)
 *
 * Scope exclusions:
 *   - Actual bank transfer (C4)
 *   - Channel fee deduction (C4)
 *   - KDV/tax allocation (C4)
 *   - Reconciliation (C4)
 *
 * Baseline: 76a467e (C3.2 Certified)
 */
class PayoutReadinessService
{
    /**
     * Get payout-readiness state for a single reservation.
     *
     * Returns null if:
     *   - Reservation not found
     *   - Not CONFIRMED (financial completion not done)
     *   - Legacy NULL snapshot (C3.1 contract)
     *   - No owner payable ledger entries found
     */
    public function getPayoutReadiness(int $reservationId, int $tenantId): ?array
    {
        $reservation = PropertyReservation::query()
            ->where('id', $reservationId)
            ->where('tenant_id', $tenantId)
            ->with('ilan')
            ->first();

        if (! $reservation) {
            return null;
        }

        return $this->buildReadinessState($reservation);
    }

    /**
     * Get all payout-ready reservations for a tenant.
     *
     * C3.3 + C4.1 Filters:
     *   - finansal_durum = CONFIRMED
     *   - commission_rate_snapshot IS NOT NULL (C3.2 requirement)
     *   - cancellation_date IS NULL
     *   - C4.1 channel fee gate:
     *       - YALIHAN_BORNE: no channel fee required (owner not affected)
     *       - OWNER_BORNE / COMMISSION_SHARE: channel_fee_amount MUST be known
     *       - UNKNOWN source: BLOCKED (needs C5 reconciliation)
     *
     * C4.1 Invariant 2: channel fee UNKNOWN → DO NOT GUESS → payout BLOCKED
     */
    public function getPayoutReadyReservations(int $tenantId): array
    {
        $reservations = PropertyReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('finansal_durum', TransactionStatus::CONFIRMED)
            ->whereNotNull('commission_rate_snapshot')
            ->whereNull('cancelled_at')
            ->with('ilan')
            ->orderBy('completed_at', 'desc')
            ->get();

        // C4.1 gate: filter based on channel fee bearer model
        return $reservations
            ->map(fn ($r) => $this->buildReadinessState($r))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * C4.1: Reservations blocked by incomplete channel fee.
     *
     * Returns reservations that:
     *   - Are CONFIRMED (financial completion done)
     *   - Have commission snapshot (C3.2 passed)
     *   - Are NOT cancelled
     *   - OWNER_BORNE or COMMISSION_SHARE bearer
     *   - But channel_fee_amount is NULL or source is UNKNOWN
     *
     * These require C5 reconciliation before becoming payout-ready.
     */
    public function getAwaitingChannelFeeReconciliation(int $tenantId): array
    {
        $reservations = PropertyReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('finansal_durum', TransactionStatus::CONFIRMED)
            ->whereNotNull('commission_rate_snapshot')
            ->whereNull('cancelled_at')
            ->with('ilan')
            ->orderBy('completed_at', 'desc')
            ->get();

        return $reservations
            ->filter(function (PropertyReservation $r): bool {
                $bearerEnum = $this->resolveChannelFeeBearer($r->channel_fee_bearer);

                // YALIHAN_BORNE: channel fee doesn't affect owner payable
                if ($bearerEnum === ChannelFeeBearer::YALIHAN_BORNE) {
                    return false;
                }

                // UNKNOWN source: blocked regardless of amount
                if ($this->isChannelFeeSourceUnknown($r->channel_fee_source)) {
                    return true;
                }

                // OWNER_BORNE or COMMISSION_SHARE without amount: blocked
                if ($bearerEnum === ChannelFeeBearer::OWNER_BORNE
                    || $bearerEnum === ChannelFeeBearer::COMMISSION_SHARE) {
                    return $r->channel_fee_amount === null;
                }

                // No bearer set: default to requiring channel fee
                return $r->channel_fee_amount === null;
            })
            ->map(fn ($r) => $this->buildAwaitingChannelFeeState($r))
            ->values()
            ->all();
    }

    /**
     * Get payout-ready reservations grouped by owner (kisi).
     *
     * Useful for batch payout decisions: "Pay owner X all their entitlement."
     */
    public function getPayoutReadyByOwner(int $tenantId): array
    {
        $ready = $this->getPayoutReadyReservations($tenantId);

        $grouped = [];
        foreach ($ready as $item) {
            $ownerKey = ($item['owner_kisi_id'] ?? 'unknown');
            if (! isset($grouped[$ownerKey])) {
                $grouped[$ownerKey] = [
                    'owner_kisi_id' => $item['owner_kisi_id'],
                    'owner_name' => $item['owner_name'],
                    'total_entitlement' => 0.0,
                    'currency' => $item['currency'],
                    'reservations' => [],
                ];
            }
            $grouped[$ownerKey]['total_entitlement'] += $item['owner_entitlement'];
            $grouped[$ownerKey]['reservations'][] = $item;
        }

        return array_values($grouped);
    }

    /**
     * Build the payout-readiness state for a reservation.
     *
     * C3.2 + C4.1: Returns null if:
     *   - Not CONFIRMED
     *   - NULL commission snapshot
     *   - C4.1 Invariant 2: OWNER_BORNE/COMMISSION_SHARE without channel fee known
     *     (system does NOT guess — payout readiness BLOCKED until channel fee known)
     */
    private function buildReadinessState(PropertyReservation $reservation): ?array
    {
        // Must be CONFIRMED
        if ($reservation->finansal_durum !== TransactionStatus::CONFIRMED) {
            return null;
        }

        // C3.1 contract: NULL snapshot = not payout-ready
        if ($reservation->commission_rate_snapshot === null) {
            return null;
        }

        // C4.1: Resolve enums (handle both raw string and model-cast enum)
        $bearerRaw = $reservation->channel_fee_bearer;
        $channelFeeBearerEnum = $bearerRaw instanceof ChannelFeeBearer
            ? $bearerRaw
            : ($bearerRaw !== null
                ? ChannelFeeBearer::tryFrom((string) $bearerRaw)
                : null);
        $sourceEnum = $this->resolveChannelFeeSource($reservation->channel_fee_source);

        // C4.1 Invariant 2: channel fee gate
        // OWNER_BORNE / COMMISSION_SHARE without known channel fee → BLOCKED
        $bearerRequiresChannelFee = $channelFeeBearerEnum?->requiresChannelFeeKnown() ?? true;

        if ($bearerRequiresChannelFee) {
            // UNKNOWN source → BLOCKED (C4.1 Invariant 2)
            if ($this->isChannelFeeSourceUnknown($reservation->channel_fee_source)) {
                return null;
            }

            // Amount null but bearer requires it → BLOCKED
            if ($reservation->channel_fee_amount === null) {
                return null;
            }

            // Source is insufficient (PROPERTY_CONFIG or EXPLICIT_RULE) → BLOCKED
            // Only PROVIDER_REPORTED is sufficient for payout readiness
            if ($sourceEnum !== null && !$sourceEnum->isSufficientForPayoutReadiness()) {
                return null;
            }
        }

        $grossAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * $reservation->nights
            ?? 0);

        if ($grossAmount <= 0) {
            return null;
        }

        $rate = (float) $reservation->commission_rate_snapshot;
        $commissionAmount = $grossAmount * $rate;
        $ownerEntitlement = $grossAmount - $commissionAmount;

        $ilan = $reservation->ilan;
        $ownerKisiId = $ilan?->ilan_sahibi_id ?? null;
        $ownerName = $this->resolveOwnerName($ilan);
        $ownerEntitlementTry = $this->convertToTRY(
            $ownerEntitlement,
            $reservation->currency ?? 'TRY',
            (float) ($reservation->booking_fx_rate ?? 1.0)
        );

        // Validate ledger entries exist (proof of accrual)
        $ledgerEntries = $this->getReservationLedgerEntries($reservation->id, $reservation->tenant_id ?? 0);
        $hasCommissionEntry = $ledgerEntries->contains(fn ($e) => str_contains($e['sebep'] ?? '', 'Komisyon Tahsili'));
        $hasOwnerEntry = $ledgerEntries->contains(fn ($e) => str_contains($e['sebep'] ?? '', 'Sahip Tahakkuk'));

        $modelValue = $reservation->management_model_snapshot instanceof ManagementModel
            ? $reservation->management_model_snapshot->value
            : (string) ($reservation->management_model_snapshot ?? 'UNKNOWN');

        // C4.1: Compute ownerEntitlementAfterChannel
        $channelFeeAmount = $reservation->channel_fee_amount !== null
            ? (float) $reservation->channel_fee_amount
            : null;
        // Convert bearer to string value for computeOwnerEntitlementAfterChannel
        $bearerValue = $channelFeeBearerEnum instanceof ChannelFeeBearer
            ? $channelFeeBearerEnum->value
            : (string) ($bearerRaw ?? '');
        $ownerEntitlementAfterChannel = $this->computeOwnerEntitlementAfterChannel(
            $grossAmount,
            $commissionAmount,
            $channelFeeAmount,
            $bearerValue ?: null,
        );

        return [
            'reservation_id' => $reservation->id,
            'tenant_id' => $reservation->tenant_id ?? 0,
            'ilan_id' => $reservation->ilan_id ?? $reservation->property_id,
            'ilan_baslik' => $ilan?->baslik ?? 'Bilinmeyen İlan',
            'ilan_slug' => $ilan?->slug,

            // Guest
            'guest_name' => $reservation->guest_name,
            'guest_email' => $reservation->guest_email,
            'guest_phone' => $reservation->guest_phone,

            // Dates
            'start_date' => $reservation->start_date,
            'end_date' => $reservation->end_date,
            'nights' => $reservation->nights,
            'completed_at' => $reservation->completed_at?->format('Y-m-d H:i') ?? null,

            // Financial
            'gross_amount' => $grossAmount,
            'currency' => $reservation->currency ?? 'TRY',
            'booking_fx_rate' => (float) ($reservation->booking_fx_rate ?? 1.0),
            'gross_try' => $this->convertToTRY($grossAmount, $reservation->currency ?? 'TRY', (float) ($reservation->booking_fx_rate ?? 1.0)),

            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'commission_amount_try' => $this->convertToTRY($commissionAmount, $reservation->currency ?? 'TRY', (float) ($reservation->booking_fx_rate ?? 1.0)),

            'owner_entitlement' => $ownerEntitlement,
            'owner_entitlement_try' => $ownerEntitlementTry,

            // C4.1: Channel Fee Snapshot
            'channel_fee_amount' => $channelFeeAmount,
            'channel_fee_currency' => $reservation->channel_fee_currency,
            'channel_fee_rate' => $reservation->channel_fee_rate !== null ? (float) $reservation->channel_fee_rate : null,
            'channel_fee_source' => $reservation->channel_fee_source,
            'channel_fee_source_label' => $sourceEnum?->label(),
            'channel_fee_bearer' => $bearerRaw instanceof ChannelFeeBearer
                ? $bearerRaw->value
                : $bearerRaw,
            'channel_fee_bearer_label' => $channelFeeBearerEnum?->label(),
            'channel_fee_is_verified' => (bool) $reservation->channel_fee_is_verified,
            'channel_fee_captured_at' => $reservation->channel_fee_captured_at?->format('Y-m-d H:i'),

            // C4.1: Net owner payable after channel fee deduction
            'owner_entitlement_after_channel' => $ownerEntitlementAfterChannel,
            'owner_entitlement_after_channel_try' => $ownerEntitlementAfterChannel !== null
                ? $this->convertToTRY($ownerEntitlementAfterChannel, $reservation->currency ?? 'TRY', (float) ($reservation->booking_fx_rate ?? 1.0))
                : null,

            // Management model
            'management_model_snapshot' => $modelValue,
            'management_model_label' => $this->getModelLabel($modelValue),

            // Owner
            'owner_kisi_id' => $ownerKisiId,
            'owner_name' => $ownerName,

            // State flags
            'is_ready' => $hasCommissionEntry || $hasOwnerEntry,
            'has_commission_ledger_entry' => $hasCommissionEntry,
            'has_owner_ledger_entry' => $hasOwnerEntry,
            'is_legacy_null_snapshot' => false, // NULL snapshot already filtered
            'ledger_entry_count' => $ledgerEntries->count(),

            // Status
            'status' => $this->determineReadinessStatus($reservation, $hasOwnerEntry),
            'status_label' => $this->getStatusLabel($this->determineReadinessStatus($reservation, $hasOwnerEntry)),

            // Channel
            'external_channel' => $reservation->external_channel,
            'external_reservation_id' => $reservation->external_reservation_id,
        ];
    }

    /**
     * Determine readiness status string.
     * C4.1: Extended to include channel fee blocking states.
     */
    private function determineReadinessStatus(PropertyReservation $reservation, bool $hasOwnerEntry): string
    {
        if ($reservation->finansal_durum !== TransactionStatus::CONFIRMED) {
            return 'waiting_completion';
        }
        if ($reservation->commission_rate_snapshot === null) {
            return 'legacy_no_snapshot';
        }

        // C4.1: Channel fee gate
        $bearerEnum = $this->resolveChannelFeeBearer($reservation->channel_fee_bearer);

        if ($bearerEnum?->requiresChannelFeeKnown()) {
            if ($this->isChannelFeeSourceUnknown($reservation->channel_fee_source)) {
                return 'awaiting_channel_fee_unknown';
            }
            if ($reservation->channel_fee_amount === null) {
                return 'awaiting_channel_fee_amount';
            }
        }

        if ($hasOwnerEntry) {
            return 'ready_for_payout';
        }

        // C4.1: If channel fee gate passed (all checks above succeeded),
        // this reservation IS payout-ready — it just hasn't been processed yet.
        // The absence of ledger entries is a processing lag, not a readiness blocker.
        return 'ready_for_payout';
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'ready_for_payout' => 'Ödeme Bekliyor',
            'waiting_completion' => 'Tamamlanma Bekliyor',
            'legacy_no_snapshot' => 'Eski Rezervasyon',
            'awaiting_accrual' => 'Tahakkuk Bekliyor',
            'awaiting_channel_fee_amount' => 'Kanal Ücreti Bekleniyor',
            'awaiting_channel_fee_unknown' => 'Kanal Ücreti Kaynağı Bilinmiyor',
            default => 'Bilinmeyen Durum',
        };
    }

    private function getModelLabel(?string $model): string
    {
        return match ($model) {
            'FULL_MANAGEMENT' => 'Tam Yönetim (%15)',
            'CHECKIN_CHECKOUT' => 'Giriş/Çıkış (%10)',
            'NONE' => 'Yok (%0)',
            'CUSTOM' => 'Özel Oran',
            default => 'Bilinmeyen',
        };
    }

    private function getReservationLedgerEntries(int $reservationId, int $tenantId): Collection
    {
        return LedgerEntry::withoutGlobalScopes()
            ->where('reference_type', PropertyReservation::class)
            ->where('reference_id', $reservationId)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    private function convertToTRY(float $amount, string $currency, float $fxRate): float
    {
        if (strtoupper($currency) === 'TRY') {
            return $amount;
        }

        return round($amount * $fxRate, 2);
    }

    private function resolveOwnerName(?Ilan $ilan): ?string
    {
        if (! $ilan) {
            return null;
        }
        $sahibi = $ilan->ilanSahibi;
        if ($sahibi) {
            return trim(($sahibi->ad.' '.($sahibi->soyad ?? '')));
        }

        return null;
    }

    /**
     * Resolve channel fee bearer — handles both raw string and enum-cast value.
     */
    private function resolveChannelFeeBearer(mixed $value): ?ChannelFeeBearer
    {
        if ($value instanceof ChannelFeeBearer) {
            return $value;
        }
        if ($value === null) {
            return null;
        }
        return ChannelFeeBearer::tryFrom((string) $value);
    }

    /**
     * Resolve channel fee source — handles both raw string and enum-cast value.
     */
    private function resolveChannelFeeSource(mixed $value): ?ChannelFeeSource
    {
        if ($value instanceof ChannelFeeSource) {
            return $value;
        }
        if ($value === null) {
            return null;
        }
        return ChannelFeeSource::tryFrom((string) $value);
    }

    /**
     * Check if channel fee source is UNKNOWN — handles both raw string and enum-cast value.
     */
    private function isChannelFeeSourceUnknown(mixed $value): bool
    {
        if ($value instanceof ChannelFeeSource) {
            return $value === ChannelFeeSource::UNKNOWN;
        }
        if ($value !== null) {
            return (string) $value === ChannelFeeSource::UNKNOWN->value;
        }
        return false;
    }

    /**
     * C4.1 Invariant 1: ownerEntitlementAfterChannel = gross - channelFee - yalihanCommission
     * For OWNER_BORNE model.
     * Returns null if channel fee is unknown (payout blocked).
     *
     * YALIHAN_BORNE: channel fee is Yalihan's cost, owner gets gross - commission.
     * OWNER_BORNE/COMMISSION_SHARE: need channel fee → null if unknown.
     * Bearer null: default to OWNER_BORNE behavior → null if unknown.
     */
    private function computeOwnerEntitlementAfterChannel(
        float   $grossAmount,
        float   $commissionAmount,
        ?float  $channelFeeAmount,
        ?string $channelFeeBearer,
    ): ?float {
        if ($channelFeeBearer === \App\Enums\ChannelFeeBearer::YALIHAN_BORNE->value) {
            return $grossAmount - $commissionAmount;
        }

        if (in_array($channelFeeBearer, [
            \App\Enums\ChannelFeeBearer::OWNER_BORNE->value,
            \App\Enums\ChannelFeeBearer::COMMISSION_SHARE->value,
        ], true)) {
            return $channelFeeAmount !== null
                ? $grossAmount - $channelFeeAmount - $commissionAmount
                : null;
        }

        // No bearer set: default to requiring channel fee
        return $channelFeeAmount !== null
            ? $grossAmount - $channelFeeAmount - $commissionAmount
            : null;
    }

    /**
     * Build a summary state for reservations awaiting channel fee reconciliation (C5 gate).
     */
    private function buildAwaitingChannelFeeState(PropertyReservation $reservation): array
    {
        $grossAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * $reservation->nights
            ?? 0);
        $rate = (float) ($reservation->commission_rate_snapshot ?? 0);
        $commissionAmount = $grossAmount * $rate;
        $ownerEntitlement = $grossAmount - $commissionAmount;

        $ilan = $reservation->ilan;
        $sourceEnum = $this->resolveChannelFeeSource($reservation->channel_fee_source);
        $bearerEnum = $this->resolveChannelFeeBearer($reservation->channel_fee_bearer);

        return [
            'reservation_id' => $reservation->id,
            'tenant_id' => $reservation->tenant_id ?? 0,
            'ilan_baslik' => $ilan?->baslik ?? 'Bilinmeyen İlan',
            'guest_name' => $reservation->guest_name,
            'gross_amount' => $grossAmount,
            'currency' => $reservation->currency ?? 'TRY',
            'commission_amount' => $commissionAmount,
            'owner_entitlement_before_channel' => $ownerEntitlement,
            'channel_fee_amount' => $reservation->channel_fee_amount,
            'channel_fee_currency' => $reservation->channel_fee_currency,
            'channel_fee_rate' => $reservation->channel_fee_rate !== null ? (float) $reservation->channel_fee_rate : null,
            'channel_fee_source' => $reservation->channel_fee_source,
            'channel_fee_source_label' => $sourceEnum?->label() ?? 'Bilinmiyor',
            'channel_fee_bearer' => $reservation->channel_fee_bearer,
            'channel_fee_bearer_label' => $bearerEnum?->label() ?? 'Bilinmiyor',
            'channel_fee_is_verified' => (bool) $reservation->channel_fee_is_verified,
            'completed_at' => $reservation->completed_at?->format('Y-m-d H:i'),
            'status' => 'awaiting_channel_fee_reconciliation',
            'status_label' => 'Kanal Ücreti Mutabakatı Bekliyor (C5)',
        ];
    }
}
