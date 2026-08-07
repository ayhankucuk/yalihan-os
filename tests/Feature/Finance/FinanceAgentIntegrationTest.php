<?php

namespace Tests\Feature\Finance;

use App\Domains\Finance\Events\AirbnbPayoutImported;
use App\Domains\Finance\Events\PayoutReconciled;
use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\Models\OwnerPayout;
use App\Domains\Finance\Models\PayoutReconciliation;
use App\Domains\Finance\Services\AirbnbPayoutImportService;
use App\Domains\Finance\Services\FinanceAgentFeatureFlags;
use App\Domains\Finance\Services\PayoutReconciliationService;
use App\Domains\Finance\Services\CommissionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * FinanceAgentIntegrationTest
 *
 * EX-002 Finance Agent — WAVE 5
 *
 * Finance Agent'ın uçtan uca akışını test eder:
 * - Import oluşturma + idempotency
 * - Event dispatch
 * - Model state transitions
 * - Tenant isolation
 */
class FinanceAgentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Finance Agent'ı test için aç
        Config::set('finance_agent.enabled', true);
        Config::set('finance_agent.pilot.strict_mode', false);
        Config::set('finance_agent.import.enabled', true);
        Config::set('finance_agent.reconciliation.auto_reconcile', false);
        Config::set('finance_agent.commission.default_rate', 10.0);
    }

    // ─── Import Tests ─────────────────────────────────────────────────────────

    /** @test */
    public function can_create_airbnb_payout_import(): void
    {
        Event::fake();

        $service = new AirbnbPayoutImportService(
            new PayoutReconciliationService(new CommissionCalculatorService()),
            new FinanceAgentFeatureFlags(),
        );

        $import = $service->import(1, [
            'airbnb_payout_id' => 'AIRBNB-TEST-001',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 5000.0,
            'airbnb_fees'      => 250.0,
            'net_amount'       => 4750.0,
            'currency'         => 'TRY',
            'raw_payload'      => null,
        ], 1);

        $this->assertInstanceOf(AirbnbPayoutImport::class, $import);
        $this->assertEquals('AIRBNB-TEST-001', $import->airbnb_payout_id);
        $this->assertEquals(4750.0, (float) $import->net_amount);
        $this->assertEquals('pending', $import->import_status);
        $this->assertEquals(1, $import->tenant_id);

        Event::assertDispatched(AirbnbPayoutImported::class, function ($event) use ($import) {
            return $event->importId === $import->id
                && $event->tenantId === 1
                && $event->airbnbPayoutId === 'AIRBNB-TEST-001';
        });
    }

    /** @test */
    public function import_is_idempotent(): void
    {
        Event::fake();

        $service = new AirbnbPayoutImportService(
            new PayoutReconciliationService(new CommissionCalculatorService()),
            new FinanceAgentFeatureFlags(),
        );

        $data = [
            'airbnb_payout_id' => 'AIRBNB-IDEMPOTENT-001',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 1000.0,
            'airbnb_fees'      => 0.0,
            'net_amount'       => 1000.0,
            'currency'         => 'TRY',
        ];

        $first  = $service->import(1, $data, 1);
        $second = $service->import(1, $data, 1);

        $this->assertEquals($first->id, $second->id);
        $this->assertDatabaseCount('airbnb_payout_imports', 1);

        // İlk import'ta event dispatch edildi, tekrarda edilmez
        Event::assertDispatchedTimes(AirbnbPayoutImported::class, 1);
    }

    /** @test */
    public function import_is_tenant_isolated(): void
    {
        Event::fake();

        $service = new AirbnbPayoutImportService(
            new PayoutReconciliationService(new CommissionCalculatorService()),
            new FinanceAgentFeatureFlags(),
        );

        $data = [
            'airbnb_payout_id' => 'AIRBNB-TENANT-001',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 1000.0,
            'airbnb_fees'      => 0.0,
            'net_amount'       => 1000.0,
            'currency'         => 'TRY',
        ];

        // Tenant 1 import eder
        $importT1 = $service->import(1, $data, 1);

        // Tenant 2 aynı airbnb_payout_id ile import edebilir — farklı tenant
        $dataT2 = array_merge($data, ['airbnb_payout_id' => 'AIRBNB-TENANT-002']);
        $importT2 = $service->import(2, $dataT2, 2);

        $this->assertNotEquals($importT1->id, $importT2->id);
        $this->assertEquals(1, $importT1->tenant_id);
        $this->assertEquals(2, $importT2->tenant_id);
    }

    /** @test */
    public function import_disabled_throws_exception(): void
    {
        Config::set('finance_agent.enabled', false);

        $service = new AirbnbPayoutImportService(
            new PayoutReconciliationService(new CommissionCalculatorService()),
            new FinanceAgentFeatureFlags(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/disabled/i');

        $service->import(1, [
            'airbnb_payout_id' => 'TEST',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 1000.0,
            'net_amount'       => 1000.0,
            'currency'         => 'TRY',
        ], 1);
    }

    // ─── Model State Tests ────────────────────────────────────────────────────

    /** @test */
    public function airbnb_payout_import_status_transitions_work(): void
    {
        $import = AirbnbPayoutImport::create([
            'tenant_id'        => 1,
            'airbnb_payout_id' => 'TEST-STATUS-001',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 1000.0,
            'airbnb_fees'      => 0.0,
            'net_amount'       => 1000.0,
            'currency'         => 'TRY',
            'import_status'    => 'pending',
        ]);

        $this->assertTrue($import->isPending());

        $import->markAsProcessing();
        $this->assertEquals('processing', $import->import_status);

        $import->markAsReconciled();
        $this->assertEquals('reconciled', $import->import_status);
        $this->assertTrue($import->isReconciled());
    }

    /** @test */
    public function owner_payout_status_transitions_work(): void
    {
        $payout = OwnerPayout::create([
            'tenant_id'               => 1,
            'owner_kisi_id'           => 99,
            'ilan_id'                 => 42,
            'idempotency_key'         => 'test-key-001',
            'period_start'            => '2026-07-01',
            'period_end'              => '2026-07-31',
            'gross_rental_income'     => 1000.0,
            'total_yalihan_commission'=> 100.0,
            'net_owner_payout'        => 900.0,
            'currency'                => 'TRY',
            'reconciliation_count'    => 1,
            'payout_status'           => 'draft',
        ]);

        $this->assertTrue($payout->isDraft());

        $payout->approve(1);
        $this->assertEquals('approved', $payout->payout_status);
        $this->assertEquals(1, $payout->approved_by);
        $this->assertNotNull($payout->approved_at);

        $payout->markAsPaid(1, 'REF-12345');
        $this->assertTrue($payout->isPaid());
        $this->assertEquals('REF-12345', $payout->payment_reference);
    }

    /** @test */
    public function payout_reconciliation_idempotency_key_is_unique(): void
    {
        AirbnbPayoutImport::create([
            'tenant_id'        => 1,
            'airbnb_payout_id' => 'TEST-REC-001',
            'period_start'     => '2026-07-01',
            'period_end'       => '2026-07-31',
            'gross_amount'     => 1000.0,
            'airbnb_fees'      => 0.0,
            'net_amount'       => 1000.0,
            'currency'         => 'TRY',
            'import_status'    => 'pending',
        ]);

        PayoutReconciliation::create([
            'tenant_id'                  => 1,
            'airbnb_payout_import_id'    => 1,
            'idempotency_key'            => 'unique-key-001',
            'reservation_amount'         => 1000.0,
            'yalihan_commission_rate'    => 10.0,
            'yalihan_commission_amount'  => 100.0,
            'owner_net_amount'           => 900.0,
            'currency'                   => 'TRY',
            'reconciliation_status'      => 'matched',
        ]);

        // Aynı idempotency_key ile ikinci kayıt girmeye çalış
        $this->expectException(\Illuminate\Database\QueryException::class);

        PayoutReconciliation::create([
            'tenant_id'                  => 1,
            'airbnb_payout_import_id'    => 1,
            'idempotency_key'            => 'unique-key-001', // Duplicate!
            'reservation_amount'         => 500.0,
            'yalihan_commission_rate'    => 10.0,
            'yalihan_commission_amount'  => 50.0,
            'owner_net_amount'           => 450.0,
            'currency'                   => 'TRY',
            'reconciliation_status'      => 'matched',
        ]);
    }
}
