<?php

namespace App\Enums;

/**
 * SettlementStatus — C5.1: Aggregate settlement lifecycle state
 *
 * Covers: provider_settlements.settlement_status
 *
 * SAAB C5.1 boundary: No automatic payout release from this status.
 */
enum SettlementStatus: string
{
    case PENDING     = 'pending';      // Not yet processed
    case ALLOCATED  = 'allocated';    // Assigned to reconciliation execution
    case RECONCILED  = 'reconciled'; // Matched and closed
    case DISCREPANCY = 'discrepancy'; // Δ > 0, awaiting resolution

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Bekliyor',
            self::ALLOCATED  => 'Tahsis Edildi',
            self::RECONCILED  => 'Mutabık',
            self::DISCREPANCY => 'Uyuşmazlık',
        };
    }
}
