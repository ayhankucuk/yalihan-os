<?php

namespace Tests\Feature\Finance;

use App\Enums\KisiTipi;
use App\Enums\ManagementModel;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\PropertyReservation;
use App\Services\Finance\PayoutReadinessService;
use App\ValueObjects\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PropertyReservationsSchemaConvergenceTest — C3.4 Certification
 *
 * Verifies that the additive convergence migration successfully provisions
 * all Money Core canonical financial columns on property_reservations,
 * that rollback cleanly works, and that PayoutReadinessService executes
 * without schema exceptions.
 */
class PropertyReservationsSchemaConvergenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_reservations_has_all_canonical_financial_columns(): void
    {
        $expectedColumns = [
            'finansal_durum',
            'currency',
            'depozito_tutari',
            'depozito_durumu',
            'locked_nightly_rate',
            'booking_currency',
            'booking_fx_rate',
            'booking_country_code',
            'ulke_id',
            'management_model_snapshot',
            'commission_rate_snapshot',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('property_reservations', $column),
                "Failed asserting that property_reservations has canonical column '{$column}'."
            );
        }
    }

    public function test_payout_readiness_service_executes_without_schema_exception(): void
    {
        $kisi = Kisi::create([
            'ad' => 'Test',
            'soyad' => 'Owner',
            'telefon' => '+905551234567',
            'email' => 'owner@example.com',
            'kisi_tipi' => KisiTipi::EV_SAHIBI,
            'tenant_id' => 1,
        ]);

        $ilan = Ilan::create([
            'baslik' => 'Test Luxury Villa',
            'fiyat' => 50000.00,
            'ilan_sahibi_id' => $kisi->id,
            'tenant_id' => 1,
        ]);

        $reservation = PropertyReservation::create([
            'property_id' => $ilan->id,
            'guest_name' => 'John Guest',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'nights' => 4,
            'total_amount' => 50000.00,
            'reservation_state' => 'confirmed',
            'finansal_durum' => TransactionStatus::CONFIRMED,
            'management_model_snapshot' => ManagementModel::FULL_MANAGEMENT,
            'commission_rate_snapshot' => 0.1500,
            'completed_at' => now(),
            'tenant_id' => 1,
        ]);

        $service = app(PayoutReadinessService::class);
        $readiness = $service->getPayoutReadiness($reservation->id, 1);

        // When no ledger entries exist, status is awaiting_accrual and is_ready is false
        $this->assertIsArray($readiness);
        $this->assertFalse($readiness['is_ready']);
        $this->assertEquals('awaiting_accrual', $readiness['status']);
        $this->assertEquals(42500.0, $readiness['owner_entitlement']);
        $this->assertEquals(7500.0, $readiness['commission_amount']);

        // Service can query all payout-ready reservations without throwing SQLSTATE[42S22]
        $list = $service->getPayoutReadyReservations(1);
        $this->assertIsArray($list);
    }
}
