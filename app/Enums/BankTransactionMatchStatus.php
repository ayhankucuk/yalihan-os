<?php

namespace App\Enums;

/**
 * BankTransactionMatchStatus — C5.1: Bank transaction matching state
 *
 * Covers: bank_transactions.match_status
 */
enum BankTransactionMatchStatus: string
{
    case UNMATCHED = 'unmatched';  // No settlement linked yet
    case MATCHED   = 'matched';    // Linked to a settlement
    case IGNORED   = 'ignored';    // Manual ignore (operator decision)

    public function label(): string
    {
        return match ($this) {
            self::UNMATCHED => 'Eşleşmedi',
            self::MATCHED   => 'Eşleşti',
            self::IGNORED    => 'Yoksayıldı',
        };
    }
}
