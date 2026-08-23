<?php

namespace App\Enums;

/**
 * ReconciliationResult — C5.1: Outcome of a single reconciliation execution
 *
 * Covers: reconciliation_executions.result
 *
 * SAAB C5.1 Invariant: RECONCILED != PAYOUT_SETTLED.
 * Reconciliation result only marks the matching state.
 * Actual payout release is a separate C5.6 decision.
 */
enum ReconciliationResult: string
{
    case EXACT_MATCH     = 'exact_match';
    case WITHIN_TOLERANCE = 'within_tolerance';
    case DISCREPANCY    = 'discrepancy';
    case NO_MATCH       = 'no_match';
    case PENDING       = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::EXACT_MATCH     => 'Tam Eslesme',
            self::WITHIN_TOLERANCE => 'Tolerans Icerisinde',
            self::DISCREPANCY    => 'Uyumsuzluk',
            self::NO_MATCH       => 'Eslesme Yok',
            self::PENDING       => 'Bekliyor',
        };
    }

    public function isReconciled(): bool
    {
        return in_array($this, [self::EXACT_MATCH, self::WITHIN_TOLERANCE], true);
    }
}
