<?php

namespace App\Services\ChannelManager;

use App\Models\Ilan;
use App\Services\PropertyPricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * RateProjectionService — Project canonical rates into channel-adapter-ready format.
 *
 * Sprint 4.14 — Booking.com Provider Wave 5
 * ADR-W5-01: PropertyPricingService is the canonical rate source.
 * ADR-W5-02: Currency is sourced from Ilan.para_birimi (native currency).
 *
 * This service does NOT:
 *  - Calculate prices (delegates to PropertyPricingService)
 *  - Make pricing decisions
 *  - Write to any database table
 *
 * It ONLY projects already-resolved canonical rates into:
 *  [['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]
 *
 * Each element = one night's rate for Booking.com OTA_HotelRateAmountNotif.
 */
class RateProjectionService
{
    public function __construct(
        private readonly PropertyPricingService $pricingService,
    ) {}

    /**
     * Project nightly rates for a date range.
     *
     * @param int    $ilanId
     * @param int    $tenantId
     * @param string $fromDate  Y-m-d inclusive
     * @param string $toDate    Y-m-d exclusive
     * @return array [['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]
     */
    public function projectRates(int $ilanId, int $tenantId, string $fromDate, string $toDate): array
    {
        $ilan = Ilan::withoutGlobalScopes()
            ->where('id', $ilanId)
            ->first();

        if ($ilan === null) {
            Log::warning('RateProjectionService: ilan not found', [
                'ilan_id' => $ilanId,
            ]);
            return [];
        }

        // Tenant isolation: skip if ilan has tenant_id AND it doesn't match.
        // Guard against missing tenant_id column in test DBs.
        if (array_key_exists('tenant_id', $ilan->getAttributes()) && (int) $ilan->tenant_id !== $tenantId) {
            Log::warning('RateProjectionService: cross-tenant blocked', [
                'ilan_id'       => $ilanId,
                'tenant_id'     => $tenantId,
                'ilan_tenant_id' => $ilan->tenant_id,
            ]);
            return [];
        }

        $currency = strtoupper($ilan->para_birimi ?? 'TRY');
        $start = Carbon::parse($fromDate)->startOfDay();
        $end   = Carbon::parse($toDate)->startOfDay();
        $rates = [];

        while ($start->lt($end)) {
            $date = $start->format('Y-m-d');
            [$nightlyRate, ,] = $this->pricingService->resolveNightlyRateForDate($ilanId, $date);

            $rates[] = [
                'date'     => $date,
                'rate'     => (float) $nightlyRate,
                'currency' => $currency,
            ];

            $start->addDay();
        }

        Log::debug('RateProjectionService: projected', [
            'ilan_id'    => $ilanId,
            'tenant_id'  => $tenantId,
            'from'       => $fromDate,
            'to'         => $toDate,
            'nights'     => count($rates),
            'currency'   => $currency,
        ]);

        return $rates;
    }
}
