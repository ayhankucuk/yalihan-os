<?php

namespace App\Enums;

/**
 * AllocationStatus — C5.1: Settlement allocation lifecycle
 *
 * Covers: settlement_allocations.allocation_status
 *
 * State machine:
 *   pending     → matched | discrepancy
 *   matched     → reconciled
 *   discrepancy → reconciled (manual override) | pending (retry)
 *   reconciled  → terminal
 */
enum AllocationStatus: string
{
    case PENDING     = 'pending';
    case MATCHED     = 'matched';
    case DISCREPANCY = 'discrepancy';
    case RECONCILED = 'reconciled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Bekliyor',
            self::MATCHED    => 'Eşleşti',
            self::DISCREPANCY => 'Uyumsuzluk',
            self::RECONCILED => 'Mutabık',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::RECONCILED;
    }
}
