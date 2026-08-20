<?php

namespace Tests\Feature;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\OperationalGorevService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CheckinCheckoutWave6Test — Operational Control Surface Evidence Suite
 *
 * Gates: W6-01 to W6-14
 * Baseline: 99034f8
 */
class CheckinCheckoutWave6Test extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $adminB;
    private Ilan $ilanA;
    private Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Tenants
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->tenantA = Tenant::firstOrCreate(['slug' => 'tenant-w6-a'], ['name' => 'Tenant W6 A']);
        $this->tenantB = Tenant::firstOrCreate(['slug' => 'tenant-w6-b'], ['name' => 'Tenant W6 B']);

        $this->adminA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
        ]);
        $this->adminA->assignRole($adminRole);

        $this->adminB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);
        $this->adminB->assignRole($adminRole);

        // 2. Listings
        $this->ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'baslik' => 'Bodrum Luxury Villa A',
        ]);
        $this->ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'baslik' => 'Bodrum Luxury Villa B',
        ]);
    }

    /**
     * W6-01: Reservation relations load correctly via Eloquent
     */
    public function test_w6_01_reservation_relations_load_correctly(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Caner Erkin',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $readiness = PropertyReadiness::create([
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

        $prepTask = Gorev::create([
            'baslik' => 'Villa Hazirlik Task',
            'gorev_tipi' => OperationalGorevService::TASK_HAZIRLIK,
            'gorev_durumu' => 'tamamlandi',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $turnoverTask = Gorev::create([
            'baslik' => 'Villa Temizlik Task',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'bekliyor',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $loaded = PropertyReservation::with(['readiness', 'operationalTasks', 'prepTask', 'turnoverTask'])
            ->find($res->id);

        $this->assertNotNull($loaded->readiness);
        $this->assertTrue($loaded->readiness->is_ready);
        $this->assertCount(2, $loaded->operationalTasks);
        $this->assertEquals($prepTask->id, $loaded->prepTask->id);
        $this->assertEquals($turnoverTask->id, $loaded->turnoverTask->id);
    }

    /**
     * W6-02: Readiness state is displayed on operations surface
     */
    public function test_w6_02_readiness_displayed_correctly(): void
    {
        $resReady = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Tam Hazir Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $resReady->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => true,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => true,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Hazır (5/5)');
    }

    /**
     * W6-03: Preparation task is displayed correctly on surface
     */
    public function test_w6_03_prep_task_displayed_correctly(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(4)->toDateString(),
            'nights' => 4,
            'guest_name' => 'Hazirlik Gorevli Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        Gorev::create([
            'baslik' => 'Pre-arrival Prep',
            'gorev_tipi' => OperationalGorevService::TASK_HAZIRLIK,
            'gorev_durumu' => 'tamamlandi',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Hazırlık');
    }

    /**
     * W6-04: Turnover task is displayed correctly on surface
     */
    public function test_w6_04_turnover_task_displayed_correctly(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Cikis Yapmis Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(14),
            'checked_out_at' => Carbon::today()->setHour(10),
            'completed_at' => Carbon::today()->setHour(10),
        ]);

        Gorev::create([
            'baslik' => 'Turnover Task',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'bekliyor',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Temizlik (bekliyor)');
    }

    /**
     * W6-05: arrival_today filter returns only today's arrivals
     */
    public function test_w6_05_arrival_today_filter(): void
    {
        $todayRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Bugun Gelen Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $futureRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->addDays(5)->toDateString(),
            'end_date' => Carbon::today()->addDays(8)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Gelecek Hafta Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'arrival_today']));
        $response->assertStatus(200);
        $response->assertSee('Bugun Gelen Misafir');
        $response->assertDontSee('Gelecek Hafta Misafir');
    }

    /**
     * W6-06: readiness_blocked filter returns only reservations with missing readiness
     */
    public function test_w6_06_readiness_blocked_filter(): void
    {
        $unreadyRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Eksik Readiness Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $unreadyRes->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => false,
            'access_credential_ready' => false,
            'guest_contact_ready' => true,
            'amenity_check_complete' => false,
            'welcome_kit_prepared' => false,
            'is_ready' => false,
        ]);

        $readyRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Hazir Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $readyRes->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => true,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => true,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'readiness_blocked']));
        $response->assertStatus(200);
        $response->assertSee('Eksik Readiness Misafir');
        $response->assertDontSee('Hazir Misafir');
    }

    /**
     * W6-07: in_house filter returns only currently staying guests
     */
    public function test_w6_07_in_house_filter(): void
    {
        $inHouseRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'nights' => 2,
            'guest_name' => 'Iceride Kalan Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(15),
            'checked_out_at' => null,
        ]);

        $futureRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Henuz Gelmemis Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => null,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'in_house']));
        $response->assertStatus(200);
        $response->assertSee('Iceride Kalan Misafir');
        $response->assertDontSee('Henuz Gelmemis Misafir');
    }

    /**
     * W6-08: turnover_pending filter returns checked-out reservations with pending turnover
     */
    public function test_w6_08_turnover_pending_filter(): void
    {
        $turnoverPendingRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Cikmis Temizlik Bekleyen',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(14),
            'checked_out_at' => Carbon::today()->setHour(11),
            'completed_at' => Carbon::today()->setHour(11),
        ]);

        Gorev::create([
            'baslik' => 'Turnover Bekliyor',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'bekliyor',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $turnoverPendingRes->id,
        ]);

        $turnoverDoneRes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Temizligi Bitmis',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(14),
            'checked_out_at' => Carbon::today()->setHour(10),
            'completed_at' => Carbon::today()->setHour(10),
        ]);

        Gorev::create([
            'baslik' => 'Turnover Bitti',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'tamamlandi',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $turnoverDoneRes->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings', ['filter' => 'turnover_pending']));
        $response->assertStatus(200);
        $response->assertSee('Cikmis Temizlik Bekleyen');
        $response->assertDontSee('Temizligi Bitmis');
    }

    /**
     * W6-09: Cross-tenant records are strictly invisible
     */
    public function test_w6_09_cross_tenant_records_invisible(): void
    {
        $tenantARes = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Gizli Tenant A Misafiri',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $tenantBRes = PropertyReservation::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $this->ilanB->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Gizli Tenant B Misafiri',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        $responseA = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $responseA->assertStatus(200);
        $responseA->assertSee('Gizli Tenant A Misafiri');
        $responseA->assertDontSee('Gizli Tenant B Misafiri');

        $responseB = $this->actingAs($this->adminB)->get(route('admin.yazlik-kiralama.bookings'));
        $responseB->assertStatus(200);
        $responseB->assertSee('Gizli Tenant B Misafiri');
        $responseB->assertDontSee('Gizli Tenant A Misafiri');
    }

    /**
     * W6-10: Null tenant context fails closed (HTTP 403)
     */
    public function test_w6_10_null_tenant_fails_closed(): void
    {
        $nullTenantUser = User::factory()->create([
            'tenant_id' => null,
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $nullTenantUser->assignRole($adminRole);

        $response = $this->actingAs($nullTenantUser)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(403);
    }

    /**
     * W6-11: Completed reservation timeline shows all stages completed
     */
    public function test_w6_11_completed_reservation_timeline(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->subDays(3)->toDateString(),
            'end_date' => Carbon::yesterday()->toDateString(),
            'nights' => 3,
            'guest_name' => 'Tamamlanmis Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->subDays(3)->setHour(14),
            'checked_out_at' => Carbon::yesterday()->setHour(11),
            'completed_at' => Carbon::yesterday()->setHour(11),
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

        Gorev::create([
            'baslik' => 'Temizlik Yapildi',
            'gorev_tipi' => OperationalGorevService::TASK_TEMIZLIK,
            'gorev_durumu' => 'tamamlandi',
            'ilan_id' => $this->ilanA->id,
            'reservation_id' => $res->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('✓ Temizlendi');
        $response->assertSee('✓ Çıkış');
        $response->assertSee('✓ Tamamlandı');
    }

    /**
     * W6-12: Cancelled reservation produces no false active state
     */
    public function test_w6_12_cancelled_reservation_produces_no_false_active(): void
    {
        $res = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'guest_name' => 'Iptal Edilmis Misafir',
            'reservation_state' => ReservationState::CANCELLED,
            'cancelled_at' => Carbon::yesterday(),
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('✕ Rezervasyon');
        $response->assertDontSee('Misafir Geldi');
        $response->assertDontSee('Misafir Çıktı');
    }

    /**
     * W6-13: Wave 5 action buttons remain fully functional
     */
    public function test_w6_13_wave5_action_buttons_remain_functional(): void
    {
        $resReady = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
            'nights' => 3,
            'guest_name' => 'Giris Butonlu Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
        ]);

        PropertyReadiness::create([
            'tenant_id' => $this->tenantA->id,
            'reservation_id' => $resReady->id,
            'ilan_id' => $this->ilanA->id,
            'property_clean' => true,
            'access_credential_ready' => true,
            'guest_contact_ready' => true,
            'amenity_check_complete' => true,
            'welcome_kit_prepared' => true,
            'is_ready' => true,
        ]);

        $resInHouse = PropertyReservation::create([
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'nights' => 1,
            'guest_name' => 'Cikis Butonlu Misafir',
            'reservation_state' => ReservationState::CONFIRMED,
            'checked_in_at' => Carbon::yesterday()->setHour(15),
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Misafir Geldi');
        $response->assertSee('Misafir Çıktı');
    }

    /**
     * W6-14: No N+1 regression on booking list (eager loading verified)
     */
    public function test_w6_14_no_n_plus_one_regression(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $res = PropertyReservation::create([
                'tenant_id' => $this->tenantA->id,
                'property_id' => $this->ilanA->id,
                'start_date' => Carbon::today()->addDays($i)->toDateString(),
                'end_date' => Carbon::today()->addDays($i + 2)->toDateString(),
                'nights' => 2,
                'guest_name' => "Batch Misafir {$i}",
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

            Gorev::create([
                'baslik' => "Task {$i}",
                'gorev_tipi' => OperationalGorevService::TASK_HAZIRLIK,
                'gorev_durumu' => 'bekliyor',
                'ilan_id' => $this->ilanA->id,
                'reservation_id' => $res->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->adminA)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 5 reservations should NOT generate 5*3=15 individual queries for relations.
        // Queries should be constant (< 30 including auth/session/role/layout checks).
        $this->assertLessThan(30, count($queries), "Query count was: " . count($queries));
    }
}
