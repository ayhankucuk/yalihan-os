<?php

namespace Tests\Unit\Domains\Finance;

use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\Models\OwnerPayout;
use App\Domains\Finance\Models\PayoutReconciliation;
use App\Domains\Finance\Services\CommissionCalculatorService;
use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\Money;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/**
 * FinanceAgentRemediationTest
 *
 * EX-002 Finance Agent — REMEDIATION RE-REVIEW
 *
 * SAAB Board tarafından talep edilen blocker kanıt testleri.
 * Her test bir önceki blocker maddesine karşılık gelir.
 *
 * BLOCKER listesi:
 * 1. OwnerPayout relation düzeltildi mi?
 * 2. $this->save() state bypass engellendi mi?
 * 3. tenant-scoped idempotency doğru mu?
 * 4. DB unique constraint var mı?
 * 5. unmatched reconciliation kaydı oluşuyor mu?
 * 6. state transition bypass engellendi mi?
 * 7. currency mismatch güvenli mi?
 * + Finance formula fixture (Airbnb gross → komisyon → owner net)
 */
class FinanceAgentRemediationTest extends TestCase
{
    private CommissionCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculatorService();
    }

    // ─── BLOCKER 1: OwnerPayout relation scope ────────────────────────────────

    /** @test */
    public function owner_payout_reconciliations_relation_filters_by_tenant_and_approved_status(): void
    {
        // OwnerPayout modeli reconciliations relation'ında tenant_id + STATUS_APPROVED filtresi var mı?
        $ownerPayout = new OwnerPayout([
            'tenant_id'               => 99,
            'owner_kisi_id'           => 1,
            'ilan_id'                 => 42,
            'idempotency_key'         => 'test',
            'period_start'            => '2026-07-01',
            'period_end'              => '2026-07-31',
            'gross_rental_income'     => 1000.0,
            'total_yalihan_commission'=> 100.0,
            'net_owner_payout'        => 900.0,
            'currency'                => 'TRY',
            'payout_status'           => 'draft',
        ]);

        $query    = $ownerPayout->reconciliations();
        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        // tenant_id filtresi var mı?
        $this->assertStringContainsString('tenant_id', $sql);

        // reconciliation_status filtresi var mı?
        $this->assertStringContainsString('reconciliation_status', $sql);

        // 'approved' değeri binding'lerde var mı? (query builder ? placeholder kullanır)
        $this->assertContains(
            PayoutReconciliation::STATUS_APPROVED,
            $bindings,
            "reconciliations() relation must filter by STATUS_APPROVED in bindings"
        );
    }

    // ─── BLOCKER 2 + 6: State transition bypass engeli ────────────────────────

    /** @test */
    public function owner_payout_cannot_approve_when_already_paid(): void
    {
        $payout = new OwnerPayout(['payout_status' => 'paid', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/cannot approve/i');

        $payout->approve(1);
    }

    /** @test */
    public function owner_payout_cannot_mark_as_paid_when_not_approved(): void
    {
        $payout = new OwnerPayout(['payout_status' => 'draft', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/cannot mark payout/i');

        $payout->markAsPaid(1, 'REF-001');
    }

    /** @test */
    public function owner_payout_cannot_mark_as_paid_when_pending_approval(): void
    {
        $payout = new OwnerPayout(['payout_status' => 'pending_approval', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/cannot mark payout/i');

        $payout->markAsPaid(1, 'REF-001');
    }

    /** @test */
    public function owner_payout_cannot_cancel_when_already_paid(): void
    {
        $payout = new OwnerPayout(['payout_status' => 'paid', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/already paid/i');

        $payout->cancel('test reason');
    }

    /** @test */
    public function owner_payout_cannot_submit_for_approval_when_not_draft(): void
    {
        $payout = new OwnerPayout(['payout_status' => 'pending_approval', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/expected .draft/i');

        $payout->submitForApproval(1);
    }

    /** @test */
    public function airbnb_payout_import_cannot_mark_reconciled_as_processing(): void
    {
        $import = new AirbnbPayoutImport(['import_status' => 'reconciled', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/already reconciled/i');

        $import->markAsProcessing();
    }

    /** @test */
    public function airbnb_payout_import_cannot_mark_failed_as_reconciled(): void
    {
        $import = new AirbnbPayoutImport(['import_status' => 'failed', 'id' => 1]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/failed/i');

        $import->markAsReconciled();
    }

    // ─── BLOCKER 3: Tenant-scoped idempotency key ─────────────────────────────

    /** @test */
    public function reconciliation_keys_are_unique_across_tenants(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');

        $keyT1 = $period->toReconciliationKey(1, 100, 42);
        $keyT2 = $period->toReconciliationKey(2, 100, 42);

        $this->assertNotEquals($keyT1, $keyT2, 'Same reservation in different tenants must produce different keys');
    }

    /** @test */
    public function reconciliation_keys_are_unique_per_reservation(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');

        $key1 = $period->toReconciliationKey(1, 100, 42);
        $key2 = $period->toReconciliationKey(1, 100, 43);

        $this->assertNotEquals($key1, $key2);
    }

    /** @test */
    public function unmatched_reconciliation_key_is_unique_per_import(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');

        $keyImport1 = $period->toReconciliationKey(1, 100, null);
        $keyImport2 = $period->toReconciliationKey(1, 200, null);

        $this->assertNotEquals($keyImport1, $keyImport2, 'Different imports must produce different unmatched keys');
        $this->assertStringContainsString('unmatched', $keyImport1);
        $this->assertStringContainsString('100', $keyImport1);
    }

    /** @test */
    public function owner_payout_idempotency_key_includes_tenant_ilan_and_period(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');

        $key = $period->toIdempotencyKey(5, 99);

        $this->assertStringContainsString('5', $key);
        $this->assertStringContainsString('99', $key);
        $this->assertStringContainsString('20260701', $key);
    }

    // ─── BLOCKER 7: Currency mismatch safety ──────────────────────────────────

    /** @test */
    public function batch_calculation_throws_on_mixed_currencies(): void
    {
        $items = [
            ['amount' => 1000.0, 'currency' => 'TRY', 'rate' => 10.0],
            ['amount' => 500.0,  'currency' => 'USD', 'rate' => 10.0], // farklı currency
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/currency mismatch/i');

        $this->calculator->calculateBatch($items, 'TRY');
    }

    /** @test */
    public function batch_calculation_succeeds_with_same_currencies(): void
    {
        $items = [
            ['amount' => 1000.0, 'currency' => 'TRY', 'rate' => 10.0],
            ['amount' => 2000.0, 'currency' => 'TRY', 'rate' => 10.0],
        ];

        $result = $this->calculator->calculateBatch($items, 'TRY');

        $this->assertEquals(3000.0, $result['total_gross']->getAmount());
        $this->assertEquals(300.0, $result['total_commission']->getAmount());
        $this->assertEquals(2700.0, $result['total_owner_net']->getAmount());
    }

    // ─── Finance Formula Fixtures (Business Outcome doğrulama) ───────────────

    /**
     * @test
     *
     * YALIHAN iş kuralı: Airbnb gross'tan %10 komisyon alır.
     * Temizlik ücreti brüt tutara dahil edilmez (ayrı kalem).
     *
     * Senaryo: 5 gece × 2000 TRY = 10.000 TRY gross
     * Airbnb fees: 500 TRY
     * YALIHAN net: 10.000 - 500 = 9.500 TRY
     * YALIHAN komisyon (%10): 950 TRY
     * Owner net: 9.500 - 950 = 8.550 TRY
     */
    public function finance_formula_fixture_basic_reservation(): void
    {
        $airbnbGross     = Money::of(10_000.0, 'TRY');
        $airbnbFees      = Money::of(500.0, 'TRY');
        $yalihanNet      = $airbnbGross->subtract($airbnbFees);
        $commissionRate  = CommissionRate::of(10.0);

        $commission = $commissionRate->calculateCommission($yalihanNet);
        $ownerNet   = $commissionRate->calculateOwnerNet($yalihanNet);

        $this->assertEquals(9_500.0, $yalihanNet->getAmount());
        $this->assertEquals(950.0, $commission->getAmount());
        $this->assertEquals(8_550.0, $ownerNet->getAmount());

        // Owner net + komisyon = yalihan net
        $this->assertEquals(
            $yalihanNet->getAmount(),
            $commission->add($ownerNet)->getAmount()
        );
    }

    /** @test */
    public function finance_formula_fixture_multiple_reservations(): void
    {
        // 3 rezervasyon için toplam hesaplama
        $reservations = [
            ['net' => 2000.0, 'rate' => 10.0],  // 200 TRY komisyon, 1800 TRY owner
            ['net' => 3500.0, 'rate' => 10.0],  // 350 TRY komisyon, 3150 TRY owner
            ['net' => 1200.0, 'rate' => 10.0],  // 120 TRY komisyon, 1080 TRY owner
        ];

        $totalNet        = Money::zero('TRY');
        $totalCommission = Money::zero('TRY');
        $totalOwnerNet   = Money::zero('TRY');

        foreach ($reservations as $res) {
            $amount = Money::of($res['net'], 'TRY');
            $rate   = CommissionRate::of($res['rate']);
            $result = $this->calculator->calculate($amount, $rate);

            $totalNet        = $totalNet->add($amount);
            $totalCommission = $totalCommission->add($result['commission']);
            $totalOwnerNet   = $totalOwnerNet->add($result['owner_net']);
        }

        $this->assertEquals(6_700.0, $totalNet->getAmount());
        $this->assertEquals(670.0, $totalCommission->getAmount());
        $this->assertEquals(6_030.0, $totalOwnerNet->getAmount());

        // Toplam kontrolü
        $this->assertEquals(
            $totalNet->getAmount(),
            $totalCommission->add($totalOwnerNet)->getAmount()
        );
    }

    /** @test */
    public function finance_formula_owner_net_never_negative(): void
    {
        // %100 komisyon dahi owner net 0 olmalı, negatif olmamalı
        $amount = Money::of(1000.0, 'TRY');
        $rate   = CommissionRate::of(100.0);

        $ownerNet = $rate->calculateOwnerNet($amount);

        $this->assertEquals(0.0, $ownerNet->getAmount());
        $this->assertFalse($ownerNet->isGreaterThan(Money::of(0.01, 'TRY')));
    }

    /** @test */
    public function finance_formula_commission_plus_owner_net_always_equals_gross(): void
    {
        // Farklı oranlar için invariant: komisyon + owner net = gross
        $grossAmounts = [500.0, 1234.56, 9999.99, 100_000.0];
        $rates        = [5.0, 10.0, 15.0, 20.0];

        foreach ($grossAmounts as $grossValue) {
            foreach ($rates as $rateValue) {
                $gross  = Money::of($grossValue, 'TRY');
                $rate   = CommissionRate::of($rateValue);
                $result = $this->calculator->calculate($gross, $rate);

                $reconstructed = $result['commission']->add($result['owner_net']);

                $this->assertEqualsWithDelta(
                    $gross->getAmount(),
                    $reconstructed->getAmount(),
                    0.01,
                    "Invariant failed for gross={$grossValue}, rate={$rateValue}%"
                );
            }
        }
    }
}
