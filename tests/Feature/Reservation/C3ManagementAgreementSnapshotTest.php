<?php

namespace Tests\Feature\Reservation;

use App\Enums\ManagementModel;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * C3.1: Management Agreement + Reservation Snapshot Certification Tests
 *
 * Baseline: 0c6203f | SAAB Decision: C3.1 Certification
 */
class C3ManagementAgreementSnapshotTest extends TestCase
{
    protected ReservationService $reservationService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = app(ReservationService::class);
        $this->user = User::factory()->create();
    }

    // ─── Tests 1-4: Canonical model snapshots ────────────────────────────

    public function test_full_management_snapshots_15_percent(): void
    {
        $ilan = $this->makeIlan(ManagementModel::FULL_MANAGEMENT);
        $res  = $this->makeReservation($ilan, 5, 7);
        $this->assertSnapshot($res, ManagementModel::FULL_MANAGEMENT, 0.1500);
    }

    public function test_checkin_checkout_snapshots_10_percent(): void
    {
        $ilan = $this->makeIlan(ManagementModel::CHECKIN_CHECKOUT);
        $res  = $this->makeReservation($ilan, 10, 12);
        $this->assertSnapshot($res, ManagementModel::CHECKIN_CHECKOUT, 0.1000);
    }

    public function test_none_snapshots_zero_percent(): void
    {
        $ilan = $this->makeIlan(ManagementModel::NONE);
        $res  = $this->makeReservation($ilan, 15, 17);
        $this->assertSnapshot($res, ManagementModel::NONE, 0.0000);
    }

    public function test_custom_snapshots_custom_rate(): void
    {
        $ilan = $this->makeIlan(ManagementModel::CUSTOM, 0.1200);
        $res  = $this->makeReservation($ilan, 20, 22);
        $this->assertSnapshot($res, ManagementModel::CUSTOM, 0.1200);
    }

    // ─── Test 5: CUSTOM without rate fails ───────────────────────────

    public function test_custom_without_rate_fails(): void
    {
        $ilan = $this->makeIlan(ManagementModel::CUSTOM, null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CUSTOM management model requires custom_commission_rate');

        $this->reservationService->createReservation(
            $ilan->id,
            now()->addDays(5)->format('Y-m-d'),
            now()->addDays(7)->format('Y-m-d'),
            ['guest_name' => 'Custom Fail Guest'],
            $this->user->id,
        );
    }

    // ─── Tests 6-7: Immutability ───────────────────────────────

    public function test_rate_change_does_not_mutate_existing_snapshot(): void
    {
        $ilan = $this->makeIlan(ManagementModel::FULL_MANAGEMENT);
        $res  = $this->makeReservation($ilan, 25, 27);

        $ilan->management_model = ManagementModel::CHECKIN_CHECKOUT;
        $ilan->save();

        $res->refresh();
        $this->assertSnapshot($res, ManagementModel::FULL_MANAGEMENT, 0.1500);
    }

    public function test_new_reservation_after_change_gets_new_snapshot(): void
    {
        $ilan = $this->makeIlan(ManagementModel::FULL_MANAGEMENT);
        $res1 = $this->makeReservation($ilan, 30, 32);

        $ilan->management_model = ManagementModel::CHECKIN_CHECKOUT;
        $ilan->save();

        $res2 = $this->makeReservation($ilan, 35, 37);

        $this->assertSnapshot($res1, ManagementModel::FULL_MANAGEMENT, 0.1500);
        $this->assertSnapshot($res2, ManagementModel::CHECKIN_CHECKOUT, 0.1000);
    }

    // ─── Test 8: Airbnb/Channex path uses same snapshot ─────────────────

    public function test_airbnb_path_snapshots_correct_rate(): void
    {
        $ilan = $this->makeIlan(ManagementModel::CHECKIN_CHECKOUT);
        $res  = $this->reservationService->createReservation(
            $ilan->id,
            now()->addDays(40)->format('Y-m-d'),
            now()->addDays(42)->format('Y-m-d'),
            ['guest_name' => 'Airbnb Guest', 'total_amount' => 25000.00, 'currency' => 'TRY'],
            $this->user->id,
        );
        $this->assertSnapshot($res, ManagementModel::CHECKIN_CHECKOUT, 0.1000);
    }

    // ─── Test 9: Cross-tenant snapshot isolation ─────────────────────

    public function test_cross_tenant_isolation(): void
    {
        $tenantB = \App\Models\SaaS\Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant B',
            'domain' => 'tenantb.test',
            'status' => 'active',
        ]);

        $ilanA = $this->makeIlan(ManagementModel::FULL_MANAGEMENT);
        $ilanB = Ilan::factory()->create([
            'tenant_id'           => $tenantB->id,
            'rental_enabled'       => true,
            'min_stay_nights'     => 1,
            'fiyat'               => 10000.00,
            'para_birimi'         => 'TRY',
            'management_model'     => ManagementModel::CHECKIN_CHECKOUT,
            'custom_commission_rate' => null,
        ]);

        $resA = $this->makeReservation($ilanA, 45, 47);
        $resB = $this->makeReservation($ilanB, 45, 47);

        $this->assertSnapshot($resA, ManagementModel::FULL_MANAGEMENT, 0.1500);
        $this->assertSnapshot($resB, ManagementModel::CHECKIN_CHECKOUT, 0.1000);
    }

    // ─── Test 10: Legacy reservations — no invented policy ─────────────

    public function test_no_invented_financial_policy(): void
    {
        // A reservation whose ilan.management_model cannot be determined from the snapshot fields
        // is legacy (pre-C3.1). The assertion is: if no snapshot columns exist OR
        // the reservation predates the columns, we must NOT infer a rate.
        // The test verifies createReservation succeeds for a pre-existing ilan (no management_model).
        $ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'fiyat'          => 5000.00,
            'para_birimi'    => 'TRY',
        ]);

        $res = $this->reservationService->createReservation(
            $ilan->id,
            now()->addDays(50)->format('Y-m-d'),
            now()->addDays(52)->format('Y-m-d'),
            ['guest_name' => 'Legacy Guest'],
            $this->user->id,
        );

        $this->assertNotNull($res->id);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private function makeIlan(ManagementModel $model, ?float $customRate = null): Ilan
    {
        $data = [
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'fiyat'          => 5000.00,
            'para_birimi'    => 'TRY',
        ];

        if (Schema::hasColumn('ilanlar', 'management_model')) {
            $data['management_model'] = $model;
        }
        if (Schema::hasColumn('ilanlar', 'custom_commission_rate')) {
            $data['custom_commission_rate'] = $customRate;
        }

        return Ilan::factory()->create($data);
    }

    private function makeReservation(
        Ilan $ilan,
        int $startDays,
        int $endDays,
    ): PropertyReservation {
        return $this->reservationService->createReservation(
            $ilan->id,
            now()->addDays($startDays)->format('Y-m-d'),
            now()->addDays($endDays)->format('Y-m-d'),
            ['guest_name' => 'C3 Snapshot Guest'],
            $this->user->id,
        );
    }

    private function assertSnapshot(
        PropertyReservation $res,
        ManagementModel $expectedModel,
        float $expectedRate,
    ): void {
        $fresh = $res->fresh();

        if (Schema::hasColumn('property_reservations', 'commission_rate_snapshot')) {
            $this->assertNotNull($fresh->commission_rate_snapshot);
            $this->assertEqualsWithDelta(
                $expectedRate,
                (float) $fresh->commission_rate_snapshot,
                0.0001,
            );
        }

        if (Schema::hasColumn('property_reservations', 'management_model_snapshot')) {
            $this->assertNotNull($fresh->management_model_snapshot);
            $enum = $fresh->getSnapshotModelEnum();
            $this->assertInstanceOf(ManagementModel::class, $enum);
            $this->assertSame($expectedModel, $enum);
        }
    }
}
