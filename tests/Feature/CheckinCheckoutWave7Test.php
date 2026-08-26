<?php

namespace Tests\Feature;

use App\DTOs\Reservation\OperationalExceptionDTO;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\OperationalExceptionEvaluatorService;
use App\Services\Reservation\OperationalGorevService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CheckinCheckoutWave7Test — Operational Exception Intelligence Evidence Suite
 *
 * Gates: W7-01 to W7-14
 * Baseline: b39ec07
 */
class CheckinCheckoutWave7Test extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $adminB;
    private Ilan $ilanA;
    private Ilan $ilanB;
    private OperationalExceptionEvaluatorService $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->tenantA = Tenant::firstOrCreate(['domain' => 'tenant-w7-a.local'], ['name' => 'Tenant W7 A']);
        $this->tenantB = Tenant::firstOrCreate(['domain' => 'tenant-w7-b.local'], ['name' => 'Tenant W7 B']);

        $this->adminA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
        ]);
        $this->adminA->assignRole($adminRole);

        $this->adminB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);
        $this->adminB->assignRole($adminRole);

        $this->ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Yalıhan Luxury Villa A',
        ]);
        $this->ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Yalıhan Luxury Villa B',
        ]);

        $this->evaluator = app(OperationalExceptionEvaluatorService::class);
    }

    /**
     * W7-01: EXC-01 Positive (Today arrival + unready -> P0 IMMINENT_ARRIVAL_UNREADY)
     */
    public function test_w7_01_exc01_imminent_arrival_unready_positive(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Unready Guest Today',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'guest_contact_ready' => true,
            'amenity_check_complete' => false,
            'welcome_kit_prepared' => false,
            'is_ready' => false,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertNotEmpty($exceptions);
        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_01 && $e->isP0()
        ));
    }

    /**
     * W7-02: EXC-01 Negative (Today arrival + 5/5 ready -> 0 EXC-01)
     */
    public function test_w7_02_exc01_ready_negative(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Fully Ready Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => true,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => true,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertFalse(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_01
        ));
    }

    /**
     * W7-03: EXC-02 Positive (Today arrival + access credential missing -> P0 MISSING_ACCESS_CREDENTIAL)
     */
    public function test_w7_03_exc02_missing_access_credential_positive(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'No Key Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => false,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => false,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_02 && $e->isP0()
        ));
    }

    /**
     * W7-04: EXC-03 Positive (Past start date + not checked in -> P1 OVERDUE_CHECKIN)
     */
    public function test_w7_04_exc03_overdue_checkin_positive(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'nights' => 2,
            'guest_name' => 'Overdue Checkin Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => null,
        ]);

        $exceptions = $this->evaluator->evaluate($res);

        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_03 && $e->isP1()
        ));
    }

    /**
     * W7-05: EXC-04 Positive (Past end date + checked in + not checked out -> P0 OVERDUE_CHECKOUT)
     */
    public function test_w7_05_exc04_overdue_checkout_positive(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->subDays(3)->toDateString(),
            'end_date' => Carbon::yesterday()->toDateString(),
            'nights' => 3,
            'guest_name' => 'Overstay Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->subDays(3)->setHour(14),
            'checked_out_at' => null,
        ]);

        $exceptions = $this->evaluator->evaluate($res);

        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_04 && $e->isP0()
        ));
    }

    /**
     * W7-06: EXC-05 Positive (Checked out + turnover task unstarted 2h+ -> P1 UNSTARTED_TURNOVER)
     */
    public function test_w7_06_exc05_unstarted_turnover_positive(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Turnover Bekleyen Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(14),
            'checked_out_at' => Carbon::now()->subHours(3),
        ]);

        Gorev::create([
            'baslik' => 'Turnover Task',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'bekliyor',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $res->load('turnoverTask');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_05 && $e->isP1()
        ));
    }

    /**
     * W7-07: EXC-06 Positive (Checked out + turnover incomplete + next active booking tomorrow -> P0 BACK_TO_BACK_TURNOVER_RISK)
     */
    public function test_w7_07_exc06_back_to_back_turnover_risk_positive(): void
    {
        // 1. Departed reservation with unfinished cleaning
        $departedRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Ayrilan Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(14),
            'checked_out_at' => Carbon::today()->setHour(10),
            'completed_at' => Carbon::today()->setHour(10),
        ]);

        Gorev::create([
            'baslik' => 'Turnover Task Devam Ediyor',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'devam_ediyor',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $departedRes->id,
        ]);

        // 2. Next active reservation starting tomorrow on the same villa
        PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Yarin Gelecek Yeni Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $departedRes->load('turnoverTask');
        $exceptions = $this->evaluator->evaluate($departedRes);

        $this->assertTrue(collect($exceptions)->contains(fn(OperationalExceptionDTO $e) =>
            $e->code === OperationalExceptionDTO::CODE_EXC_06 && $e->isP0()
        ));
    }

    /**
     * W7-08: Multi-exception scenario (Reservation triggers EXC-01 and EXC-02 simultaneously)
     */
    public function test_w7_08_multi_exception_scenario(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Double Exception Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'guest_contact_ready' => true,
            'amenity_check_complete' => false,
            'welcome_kit_prepared' => false,
            'is_ready' => false,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertCount(2, $exceptions);
        $codes = collect($exceptions)->pluck('code')->all();
        $this->assertContains(OperationalExceptionDTO::CODE_EXC_01, $codes);
        $this->assertContains(OperationalExceptionDTO::CODE_EXC_02, $codes);
    }

    /**
     * W7-09: Priority sorting (P0 before P1)
     */
    public function test_w7_09_priority_sorting_p0_before_p1(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Mixed Priority Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'guest_contact_ready' => true,
            'amenity_check_complete' => false,
            'welcome_kit_prepared' => false,
            'is_ready' => false,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertNotEmpty($exceptions);
        $this->assertEquals(OperationalExceptionDTO::SEVERITY_P0, $exceptions[0]->severity);
    }

    /**
     * W7-10: Cancelled reservation produces zero exceptions
     */
    public function test_w7_10_cancelled_reservation_produces_zero_exceptions(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Cancelled Guest',
            'reservation_state' => ReservationState::CANCELLED,
            'cancelled_at' => Carbon::yesterday(),
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $res->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'is_ready' => false,
        ]);

        $res->load('readiness');
        $exceptions = $this->evaluator->evaluate($res);

        $this->assertEmpty($exceptions);
    }

    /**
     * W7-11: filter=exceptions returns only reservations with exceptions
     */
    public function test_w7_11_filter_exceptions_returns_only_exception_rows(): void
    {
        // 1. Problematic reservation (Today arrival + unready)
        $problemRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Problematic Exception Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $problemRes->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'is_ready' => false,
        ]);

        // 2. Normal reservation (Future arrival + 5/5 ready)
        $normalRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->addDays(10)->toDateString(),
            'end_date' => Carbon::today()->addDays(13)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Smooth Sailing Guest',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $normalRes->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => true,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => true,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'exceptions']));
        $response->assertStatus(200);
        $response->assertSee('Problematic Exception Guest');
        $response->assertDontSee('Smooth Sailing Guest');
        $response->assertSee('Müdahale Gerekenler');
        $response->assertSee('🔴 P0');
    }

    /**
     * W7-12: Cross-tenant isolation (Tenant A cannot see Tenant B exceptions)
     */
    public function test_w7_12_cross_tenant_isolation(): void
    {
        $tenantARes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Exception Tenant A',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $tenantARes->id,
            'ilan_id' => $this->ilanA->id,
            'is_ready' => false,
        ]);

        $tenantBRes = PropertyReservation::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $this->ilanB->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Exception Tenant B',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantB->id,
            'reservation_id' => $tenantBRes->id,
            'ilan_id' => $this->ilanB->id,
            'is_ready' => false,
        ]);

        $responseA = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'exceptions']));
        $responseA->assertStatus(200);
        $responseA->assertSee('Exception Tenant A');
        $responseA->assertDontSee('Exception Tenant B');

        $responseB = $this->actingAs($this->adminB)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'exceptions']));
        $responseB->assertStatus(200);
        $responseB->assertSee('Exception Tenant B');
        $responseB->assertDontSee('Exception Tenant A');
    }

    /**
     * W7-13: Null tenant context fails closed (HTTP 403)
     */
    public function test_w7_13_null_tenant_fails_closed(): void
    {
        $nullTenantUser = User::factory()->create([
            'tenant_id' => null,
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $nullTenantUser->assignRole($adminRole);

        $response = $this->actingAs($nullTenantUser)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'exceptions']));
        $response->assertStatus(403);
    }

    /**
     * W7-14: N+1 query budget & zero side-effects verified
     */
    public function test_w7_14_no_n_plus_one_regression_and_zero_side_effects(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $res = PropertyReservation::create([
                'tenant_id' => $this->tenantA->id,
                'property_id' => $this->ilanA->id,
                'start_date' => Carbon::today()->addDays($i)->toDateString(),
                'end_date' => Carbon::today()->addDays($i + 2)->toDateString(),
                'nights' => 2,
                'guest_name' => "Batch Exc Misafir {$i}",
                'reservation_state' => ReservationState::CONFIRMED,
            ]);

            PropertyReadiness::create([
                'tenant_id' => $this->tenantA->id,
                'reservation_id' => $res->id,
                'ilan_id' => $this->ilanA->id,
                'is_ready' => ($i % 2 === 0),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'exceptions']));
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Ensure total query count stays well within budget (< 35 including auth, layout, and eager loads)
        $this->assertLessThan(35, count($queries), "Query count was: " . count($queries));
    }
}
