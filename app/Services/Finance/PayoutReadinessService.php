<?php

namespace App\Services\Finance;

use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use App\ValueObjects\TransactionStatus;

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

        if (!$reservation) {
            return null;
        }

        return $this->buildReadinessState($reservation);
    }

    /**
     * Get all payout-ready reservations for a tenant.
     *
     * Filters:
     *   - finansal_durum = CONFIRMED
     *   - commission_rate_snapshot IS NOT NULL (C3.2 requirement)
     *   - cancellation_date IS NULL
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

        return $reservations->map(fn($r) => $this->buildReadinessState($r))->filter()->values()->all();
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
            if (!isset($grouped[$ownerKey])) {
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
     * Returns null if the reservation is not payout-ready (NULL snapshot, not CONFIRMED, etc.)
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
        $hasCommissionEntry = $ledgerEntries->contains(fn($e) => str_contains($e['sebep'] ?? '', 'Komisyon Tahsili'));
        $hasOwnerEntry = $ledgerEntries->contains(fn($e) => str_contains($e['sebep'] ?? '', 'Sahip Tahakkuk'));

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

            // Management model
            'management_model_snapshot' => (string) ($reservation->management_model_snapshot ?? 'UNKNOWN'),
            'management_model_label' => $this->getModelLabel((string) ($reservation->management_model_snapshot ?? null)),

            // Owner
            'owner_kisi_id' => $ownerKisiId,
            'owner_name' => $ownerName,

            // State flags
            'is_ready' => $hasCommissionEntry || $hasOwnerEntry,
            'has_commission_ledger_entry' => $hasCommissionEntry,
            'has_owner_ledger_entry' => $hasOwnerEntry,
            'is_legacy_null_snapshot' => false, // Always false here — NULL is already filtered
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
     */
    private function determineReadinessStatus(PropertyReservation $reservation, bool $hasOwnerEntry): string
    {
        if ($reservation->finansal_durum !== TransactionStatus::CONFIRMED) {
            return 'waiting_completion';
        }
        if ($reservation->commission_rate_snapshot === null) {
            return 'legacy_no_snapshot';
        }
        if ($hasOwnerEntry) {
            return 'ready_for_payout';
        }
        return 'awaiting_accrual';
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'ready_for_payout' => 'Ödeme Bekliyor',
            'waiting_completion' => 'Tamamlanma Bekliyor',
            'legacy_no_snapshot' => 'Eski Rezervasyon',
            'awaiting_accrual' => 'Tahakkuk Bekliyor',
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

    private function getReservationLedgerEntries(int $reservationId, int $tenantId): \Illuminate\Support\Collection
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
        if (!$ilan) {
            return null;
        }
        $sahibi = $ilan->ilanSahibi;
        if ($sahibi) {
            return trim(($sahibi->ad . ' ' . ($sahibi->soyad ?? '')));
        }
        return null;
    }
}
