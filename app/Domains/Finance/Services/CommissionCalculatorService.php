<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\Money;
use Illuminate\Support\Facades\Log;

/**
 * CommissionCalculatorService
 *
 * EX-002 Finance Agent — WAVE 2
 *
 * Rezervasyon bazında YALIHAN komisyonunu ve ev sahibi net ödemesini hesaplar.
 * Tüm hesaplamalar Value Object'ler üzerinden yapılır — iş mantığı burada.
 */
class CommissionCalculatorService
{
    /**
     * Rezervasyon tutarından komisyon ve net ödemeyi hesaplar.
     *
     * @return array{commission: Money, owner_net: Money, rate: CommissionRate}
     */
    public function calculate(Money $reservationAmount, CommissionRate $rate): array
    {
        $commission = $rate->calculateCommission($reservationAmount);
        $ownerNet   = $rate->calculateOwnerNet($reservationAmount);

        Log::info('CommissionCalculator: Calculation complete', [
            'gross_amount'       => $reservationAmount->getAmount(),
            'currency'           => $reservationAmount->getCurrency(),
            'commission_rate'    => $rate->getRate(),
            'commission_amount'  => $commission->getAmount(),
            'owner_net_amount'   => $ownerNet->getAmount(),
        ]);

        return [
            'commission' => $commission,
            'owner_net'  => $ownerNet,
            'rate'       => $rate,
        ];
    }

    /**
     * Birden fazla rezervasyon için toplam hesaplama.
     *
     * BLOCKER FIX: currency mismatch safety — tüm item'lar aynı currency'de olmalı.
     * Farklı currency bulunursa InvalidArgumentException fırlatılır; sessiz toplama engellenir.
     *
     * @param array<array{amount: float, currency: string, rate: float}> $items
     * @return array{total_gross: Money, total_commission: Money, total_owner_net: Money}
     */
    public function calculateBatch(array $items, string $currency = 'TRY'): array
    {
        $normalizedCurrency = strtoupper($currency);

        $totalGross      = Money::zero($normalizedCurrency);
        $totalCommission = Money::zero($normalizedCurrency);
        $totalOwnerNet   = Money::zero($normalizedCurrency);

        foreach ($items as $index => $item) {
            $itemCurrency = strtoupper($item['currency'] ?? $normalizedCurrency);

            // BLOCKER FIX: currency mismatch — sessiz toplama yasak
            if ($itemCurrency !== $normalizedCurrency) {
                throw new \InvalidArgumentException(
                    "Currency mismatch in batch calculation at index {$index}: "
                    . "expected '{$normalizedCurrency}', got '{$itemCurrency}'. "
                    . "Mixed-currency batch is not allowed."
                );
            }

            $amount = Money::of((float) $item['amount'], $normalizedCurrency);
            $rate   = CommissionRate::of((float) $item['rate']);

            $result = $this->calculate($amount, $rate);

            $totalGross      = $totalGross->add($amount);
            $totalCommission = $totalCommission->add($result['commission']);
            $totalOwnerNet   = $totalOwnerNet->add($result['owner_net']);
        }

        return [
            'total_gross'      => $totalGross,
            'total_commission' => $totalCommission,
            'total_owner_net'  => $totalOwnerNet,
        ];
    }

    /**
     * Varsayılan YALIHAN komisyon oranıyla hesaplama (%10).
     *
     * @return array{commission: Money, owner_net: Money, rate: CommissionRate}
     */
    public function calculateWithDefaultRate(Money $reservationAmount): array
    {
        return $this->calculate($reservationAmount, CommissionRate::default());
    }
}
