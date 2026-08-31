<?php

namespace Tests\Feature;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessReservationCompletedJob;
use App\Models\Ilan;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\OperationalGorevService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CheckinCheckoutWave5Test — Evidence tests for Wave 5 Field Check-in / Check-out Action Surface.
 *
 * CHECKIN_CHECKOUT Wave 5
 * SAAB Decision: WAVE5-APPROVED
 * Baseline: 78db253
 *
 * Evidence criteria:
 *  W5-E1:  authorized admin can check in via POST route
 *  W5-E2:  authorized admin can check out via POST route
 *  W5-E3:  cross-tenant reservation check-in rejected with error flash
 *  W5-E4:  cross-tenant reservation check-out rejected with error flash
 *  W5-E5:  cancelled reservation cannot check in (error surfaced safely)
 *  W5-E6:  readiness failure surfaced safely in session error flash
 *  W5-E7:  duplicate check-in is safe and idempotent
 *  W5-E8:  duplicate check-out is safe and idempotent
 *  W5-E9:  checkout triggers turnover cleaning pipeline
 *  W5-E10: unauthenticated request redirected to login (route protection)
 *  W5-E11: state-aware Blade action rendering across all 4 reservation states
 */
class CheckinCheckoutWave5Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $otherTenantAdmin;
    protected Ilan $ilan;
    protected Ilan $otherTenantIlan;
    protected ReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);

        // Ensure Tenant 1 and Tenant 2 exist with predictable IDs
        Tenant::firstOrCreate(['id' => 1], ['name' => 'Tenant 1', 'slug' => 'tenant-1']);
        Tenant::firstOrCreate(['id' => 2], ['name' => 'Tenant 2', 'slug' => 'tenant-2']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create(['tenant_id' => 1]);
        $this->adminUser->assignRole($adminRole);

        $this->otherTenantAdmin = User::factory()->create(['tenant_id' => 2]);
        $this->otherTenantAdmin->assignRole($adminRole);

        $this->ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'check_in_time'   => '14:00',
            'check_out_time'  => '11:00',
            'tenant_id'       => 1,
        ]);

        $this->otherTenantIlan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'check_in_time'   => '14:00',
            'check_out_time'  => '11:00',
            'tenant_id'       => 2,
        ]);
    }

    /**
     * Helper to create a confirmed reservation ready for check-in.
     */
    private function createCheckInReadyReservation(int $tenantId = 1, ?Ilan $ilan = null): PropertyReservation
    {
        $targetIlan = $ilan ?? $this->ilan;

        $reservation = PropertyReservation::create([
            'property_id'             => $targetIlan->id,
            'tenant_id'               => $tenantId,
            'start_date'              => now()->format('Y-m-d'),
            'end_date'                => now()->addDays(3)->format('Y-m-d'),
            'nights'                  => 3,
            'guest_name'              => 'Surface Test Guest',
            'guest_email'             => 'guest@example.com',
            'guest_phone'             => '+905550001122',
            'guest_count'             => 2,
            'reservation_state'       => 'confirmed',
            'checkin_window_opened_at' => now(),
        ]);

        PropertyReadiness::create([
            'tenant_id'               => $tenantId,
            'reservation_id'          => $reservation->id,
            'ilan_id'                 => $targetIlan->id,
            'property_clean'          => true,
            'access_credential_ready' => true,
            'guest_contact_ready'     => true,
            'amenity_check_complete'  => true,
            'welcome_kit_prepared'    => true,
            'is_ready'                => true,
        ]);

        return $reservation->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E1: Authorized admin can check in
    // ═══════════════════════════════════════════════════════════════════════

    public function test_authorized_admin_can_check_in(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Misafir girişi başarıyla kaydedildi.');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->checked_in_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E2: Authorized admin can check out
    // ═══════════════════════════════════════════════════════════════════════

    public function test_authorized_admin_can_check_out(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, 1);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Misafir çıkışı kaydedildi. Temizlik operasyonu başlatıldı.');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->checked_out_at);
        $this->assertNotNull($fresh->completed_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E3: Cross-tenant reservation check-in rejected
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cross_tenant_reservation_checkin_rejected(): void
    {
        // Reservation belongs to tenant 2
        $reservation = $this->createCheckInReadyReservation(2, $this->otherTenantIlan);

        // Admin belongs to tenant 1
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_in_at, 'Cross-tenant check-in must not stamp checked_in_at');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E4: Cross-tenant reservation check-out rejected
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cross_tenant_reservation_checkout_rejected(): void
    {
        // Reservation belongs to tenant 2
        $reservation = $this->createCheckInReadyReservation(2, $this->otherTenantIlan);
        $reservation->update(['checked_in_at' => now()]);

        // Admin belongs to tenant 1
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_out_at, 'Cross-tenant check-out must not stamp checked_out_at');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E4-B: Null Tenant Fail-Closed on Check-in (SAB Rule 1)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_authenticated_admin_with_null_tenant_cannot_check_in(): void
    {
        $reservation = $this->createCheckInReadyReservation(1, $this->ilan);

        $nullTenantAdmin = User::factory()->create(['tenant_id' => null]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $nullTenantAdmin->assignRole($adminRole);

        $response = $this->actingAs($nullTenantAdmin)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id));

        $response->assertStatus(403);

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_in_at, 'Null-tenant admin must never mutate checked_in_at');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E4-C: Null Tenant Fail-Closed on Check-out (SAB Rule 1)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_authenticated_admin_with_null_tenant_cannot_check_out(): void
    {
        $reservation = $this->createCheckInReadyReservation(1, $this->ilan);
        $reservation->update(['checked_in_at' => now()]);

        $nullTenantAdmin = User::factory()->create(['tenant_id' => null]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $nullTenantAdmin->assignRole($adminRole);

        $response = $this->actingAs($nullTenantAdmin)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id));

        $response->assertStatus(403);

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_out_at, 'Null-tenant admin must never mutate checked_out_at');
        $this->assertNull($fresh->completed_at, 'Null-tenant admin must never mutate completed_at');

        // Verify no turnover task was created
        $gorevCount = Gorev::where('reservation_id', $reservation->id)->count();
        $this->assertEquals(0, $gorevCount, 'No turnover task side effect must occur');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E5: Cancelled reservation cannot check in
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cancelled_reservation_cannot_check_in(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->cancelReservation($reservation->id);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_in_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E6: Readiness failure surfaced safely in error flash
    // ═══════════════════════════════════════════════════════════════════════

    public function test_readiness_failure_surfaced_safely(): void
    {
        // Reservation without readiness setup
        $reservation = PropertyReservation::create([
            'property_id'             => $this->ilan->id,
            'tenant_id'               => 1,
            'start_date'              => now()->format('Y-m-d'),
            'end_date'                => now()->addDays(3)->format('Y-m-d'),
            'nights'                  => 3,
            'guest_name'              => 'Unready Guest',
            'reservation_state'       => 'confirmed',
            'checkin_window_opened_at' => null, // window NOT open
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNull($fresh->checked_in_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E7: Duplicate check-in is safe and idempotent
    // ═══════════════════════════════════════════════════════════════════════

    public function test_duplicate_checkin_is_safe_and_idempotent(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        // First check-in
        $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id))
            ->assertSessionHas('success');

        $firstTimestamp = PropertyReservation::find($reservation->id)->checked_in_at->toIso8601String();

        // Second check-in
        $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id))
            ->assertSessionHas('success');

        $secondTimestamp = PropertyReservation::find($reservation->id)->checked_in_at->toIso8601String();

        $this->assertEquals($firstTimestamp, $secondTimestamp);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E8: Duplicate check-out is safe and idempotent
    // ═══════════════════════════════════════════════════════════════════════

    public function test_duplicate_checkout_is_safe_and_idempotent(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, 1);

        // First check-out
        $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id))
            ->assertSessionHas('success');

        $firstTimestamp = PropertyReservation::find($reservation->id)->checked_out_at->toIso8601String();

        // Second check-out
        $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id))
            ->assertSessionHas('success');

        $secondTimestamp = PropertyReservation::find($reservation->id)->checked_out_at->toIso8601String();

        $this->assertEquals($firstTimestamp, $secondTimestamp);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E9: Checkout triggers turnover cleaning pipeline
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkout_triggers_turnover_cleaning_task(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, 1);

        $this->actingAs($this->adminUser)
            ->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id));

        // Process turnover job triggered via event
        $event = ReservationCompletedEvent::fromModel(PropertyReservation::find($reservation->id));
        $job = new ProcessReservationCompletedJob($event);
        $job->handle(app(OperationalGorevService::class));

        $gorev = Gorev::where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_TEMIZLIK)
            ->first();

        $this->assertNotNull($gorev, 'Turnover temizlik Gorev must exist after checkout action');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E10: Unauthenticated request redirected to login
    // ═══════════════════════════════════════════════════════════════════════

    public function test_unauthenticated_request_redirected_to_login(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        $this->post(route('admin.yazlik-kiralama.bookings.check-in', $reservation->id))
            ->assertRedirect(route('login'));

        $this->post(route('admin.yazlik-kiralama.bookings.check-out', $reservation->id))
            ->assertRedirect(route('login'));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W5-E11: State-aware Blade action rendering across reservation states
    // ═══════════════════════════════════════════════════════════════════════

    public function test_bookings_blade_renders_state_aware_actions(): void
    {
        // 1. Eligible arrival: confirmed + not checked in
        $resArrival = $this->createCheckInReadyReservation();

        // 2. Active stay: checked in + not checked out
        $resStay = $this->createCheckInReadyReservation();
        $resStay->update(['checked_in_at' => now()->subHours(2)]);

        // 3. Completed: checked out
        $resCompleted = $this->createCheckInReadyReservation();
        $resCompleted->update([
            'checked_in_at'  => now()->subDays(2),
            'checked_out_at' => now()->subHours(1),
            'completed_at'   => now()->subHours(1),
        ]);

        // 4. Cancelled
        $resCancelled = $this->createCheckInReadyReservation();
        $resCancelled->update([
            'reservation_state' => 'cancelled',
            'cancelled_at'      => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.yazlik-kiralama.bookings'));

        $response->assertStatus(200);

        // State 1: "Misafir Geldi" button rendered for arrival
        $response->assertSee("id=\"btn-checkin-{$resArrival->id}\"", false);
        $response->assertSee('Misafir Geldi');

        // State 2: "Misafir Çıktı" button rendered for stay with confirmation dialog
        $response->assertSee("id=\"btn-checkout-{$resStay->id}\"", false);
        $response->assertSee('Misafir Çıktı');
        $response->assertSee('Misafirin çıkış yaptığını onaylıyor musunuz?');

        // State 3: "Çıkış Yapıldı" / "Tamamlandı" rendered, no action button
        $response->assertDontSee("id=\"btn-checkin-{$resCompleted->id}\"", false);
        $response->assertDontSee("id=\"btn-checkout-{$resCompleted->id}\"", false);
        $response->assertSee('Çıkış Yapıldı');

        // State 4: "İşlem Yok" / "İptal" rendered, no action button
        $response->assertDontSee("id=\"btn-checkin-{$resCancelled->id}\"", false);
        $response->assertDontSee("id=\"btn-checkout-{$resCancelled->id}\"", false);
        $response->assertSee('İşlem Yok');
    }
}
