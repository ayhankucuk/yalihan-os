<?php

namespace App\Domains\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PayoutReconciled
 *
 * EX-002 Finance Agent — WAVE 3
 *
 * Bir AirbnbPayoutImport reconciliation işlemi tamamlandığında fırlatılır.
 * Replay-safe: tüm veriler immutable constructor parametresi olarak taşınır.
 */
final class PayoutReconciled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int    $importId,
        public readonly int    $tenantId,
        public readonly int    $reconciledCount,
        public readonly int    $unmatchedCount,
        public readonly int    $errorCount,
        public readonly string $periodStart,
        public readonly string $periodEnd,
    ) {}
}
