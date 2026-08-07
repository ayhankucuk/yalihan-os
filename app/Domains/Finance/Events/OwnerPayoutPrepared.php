<?php

namespace App\Domains\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OwnerPayoutPrepared
 *
 * EX-002 Finance Agent — WAVE 3
 *
 * Bir ev sahibi ödemesi hazırlandığında fırlatılır.
 * Replay-safe: tüm veriler immutable constructor parametresi olarak taşınır.
 */
final class OwnerPayoutPrepared
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int    $payoutId,
        public readonly int    $tenantId,
        public readonly int    $ownerKisiId,
        public readonly int    $ilanId,
        public readonly float  $netOwnerPayout,
        public readonly string $currency,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly int    $reconciliationCount,
        public readonly int    $preparedBy,
    ) {}
}
