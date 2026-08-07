<?php

namespace App\Domains\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AirbnbPayoutImported
 *
 * EX-002 Finance Agent — WAVE 3
 *
 * Bir Airbnb payout kaydı başarıyla import edildiğinde fırlatılır.
 * Replay-safe: tüm veriler immutable constructor parametresi olarak taşınır.
 */
final class AirbnbPayoutImported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int    $importId,
        public readonly int    $tenantId,
        public readonly string $airbnbPayoutId,
        public readonly float  $netAmount,
        public readonly string $currency,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly int    $importedBy,
    ) {}
}
