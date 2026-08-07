<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Events\PayoutReconciled;
use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\Models\PayoutReconciliation;
use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\Money;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayoutReconciliationService
 *
 * EX-002 Finance Agent — WAVE 2
 *
 * Bir AirbnbPayoutImport kaydını rezervasyonlarla eşleştirir.
 * Her rezervasyon için komisyon hesaplar ve PayoutReconciliation oluşturur.
 * Idempotent: aynı import için tekrar çalıştırılabilir.
 */
class PayoutReconciliationService
{
    public function __construct(
        private readonly CommissionCalculatorService $calculator,
    ) {}

    /**
     * Bir import kaydını tüm rezervasyonlarla reconcile eder.
     *
     * @return array{reconciled: int, unmatched: int, errors: int}
     */
    public function reconcile(AirbnbPayoutImport $import, CommissionRate $rate): array
    {
        $stats = ['reconciled' => 0, 'unmatched' => 0, 'errors' => 0];

        Log::info('PayoutReconciliation: Starting reconciliation', [
            'import_id' => $import->id,
            'tenant_id' => $import->tenant_id,
            'period'    => $import->period_start . ' – ' . $import->period_end,
        ]);

        $import->markAsProcessing();

        $period = PayoutPeriod::of(
            $import->period_start->toDateString(),
            $import->period_end->toDateString(),
        );

        // Dönem içindeki rezervasyonları çek — tenant izolasyonu zorunlu
        $reservations = PropertyReservation::with('ilan')
            ->where('tenant_id', $import->tenant_id)
            ->where('start_date', '>=', $period->getStartDateString())
            ->where('start_date', '<=', $period->getEndDateString())
            ->orderBy('id')
            ->get();

        foreach ($reservations as $reservation) {
            try {
                $this->reconcileReservation($import, $reservation, $rate, $period);
                $stats['reconciled']++;
            } catch (\Throwable $e) {
                Log::error('PayoutReconciliation: Failed to reconcile reservation', [
                    'import_id'      => $import->id,
                    'reservation_id' => $reservation->id,
                    'error'          => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // Import'u reconciled olarak işaretle
        $import->markAsReconciled();

        Log::info('PayoutReconciliation: Reconciliation complete', array_merge(
            ['import_id' => $import->id],
            $stats,
        ));

        PayoutReconciled::dispatch(
            $import->id,
            $import->tenant_id,
            $stats['reconciled'],
            $stats['unmatched'],
            $stats['errors'],
            $import->period_start->toDateString(),
            $import->period_end->toDateString(),
        );

        return $stats;
    }

    /**
     * Tek bir rezervasyonu reconcile eder — idempotent.
     */
    private function reconcileReservation(
        AirbnbPayoutImport $import,
        PropertyReservation $reservation,
        CommissionRate $rate,
        PayoutPeriod $period,
    ): void {
        $idempotencyKey = $period->toReconciliationKey(
            $import->tenant_id,
            $import->id,
            $reservation->id,
        );

        // Idempotency check — aynı key ile kayıt varsa atla
        $existing = PayoutReconciliation::where('idempotency_key', $idempotencyKey)
            ->orderBy('id')
            ->first();

        if ($existing) {
            Log::info('PayoutReconciliation: Skipping duplicate', [
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        // Rezervasyon tutarını al
        $reservationAmount = Money::of(
            (float) ($reservation->total_price ?? 0),
            $import->currency,
        );

        // Komisyon hesapla
        $result = $this->calculator->calculate($reservationAmount, $rate);

        DB::transaction(function () use (
            $import, $reservation, $rate, $result, $idempotencyKey
        ): void {
            PayoutReconciliation::create([
                'tenant_id'                  => $import->tenant_id,
                'airbnb_payout_import_id'    => $import->id,
                'reservation_id'             => $reservation->id,
                'ilan_id'                    => $reservation->ilan_id,
                'idempotency_key'            => $idempotencyKey,
                'reservation_amount'         => $result['commission']->add($result['owner_net'])->getAmount(),
                'yalihan_commission_rate'    => $rate->getRate(),
                'yalihan_commission_amount'  => $result['commission']->getAmount(),
                'owner_net_amount'           => $result['owner_net']->getAmount(),
                'currency'                   => $import->currency,
                'reconciliation_status'      => PayoutReconciliation::STATUS_MATCHED,
            ]);
        });
    }

    /**
     * Eşleşmeyen tutarlar için unmatched kayıt oluşturur.
     */
    public function createUnmatchedRecord(
        AirbnbPayoutImport $import,
        float $amount,
        string $reason,
        PayoutPeriod $period,
    ): PayoutReconciliation {
        $idempotencyKey = $period->toReconciliationKey(
            $import->tenant_id,
            $import->id,
            null,
        );

        return PayoutReconciliation::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'tenant_id'                  => $import->tenant_id,
                'airbnb_payout_import_id'    => $import->id,
                'reservation_id'             => null,
                'ilan_id'                    => null,
                'reservation_amount'         => $amount,
                'yalihan_commission_rate'    => 0,
                'yalihan_commission_amount'  => 0,
                'owner_net_amount'           => 0,
                'currency'                   => $import->currency,
                'reconciliation_status'      => PayoutReconciliation::STATUS_UNMATCHED,
                'notes'                      => $reason,
            ],
        );
    }
}
