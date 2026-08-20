<?php

namespace Tests\Feature;

use App\DTOs\Reservation\OperationalExceptionDTO;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wave7PhaseB1PilotBootstrapTest — Test evidence for Wave 7 Phase B1 Pilot Onboarding.
 */
class Wave7PhaseB1PilotBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_pilot_bootstrap_command_runs_idempotently(): void
    {
        $this->artisan('ops:bootstrap-pilot-villa')
            ->assertExitCode(0);

        $this->assertDatabaseHas('tenants', [
            'domain' => 'yalihan.com.tr',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@yalihan.com.tr',
        ]);

        $this->assertDatabaseHas('ilanlar', [
            'baslik' => 'Yalıhan Mandarin Oriental Luxury Villa #101',
            'rental_enabled' => true,
        ]);

        $this->assertDatabaseHas('ilan_takvim_sync', [
            'external_listing_id' => 'CHNX-BODRUM-VILLA-101',
            'is_sync_active' => true,
        ]);

        // Second run must succeed without duplicates
        $this->artisan('ops:bootstrap-pilot-villa')
            ->assertExitCode(0);

        $this->assertEquals(1, Tenant::where('domain', 'yalihan.com.tr')->count());
        $this->assertEquals(1, User::where('email', 'admin@yalihan.com.tr')->count());
        $this->assertEquals(1, Ilan::withoutGlobalScopes()->where('baslik', 'Yalıhan Mandarin Oriental Luxury Villa #101')->count());
    }

    public function test_inbound_pilot_reservation_creates_full_operational_chain(): void
    {
        $this->artisan('ops:bootstrap-pilot-villa', ['--test-ingest' => true])
            ->assertExitCode(0);

        $reservation = PropertyReservation::withoutGlobalScopes()->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('Ali & Selin Demir (Pilot Misafir)', $reservation->guest_name);
        $this->assertEquals(3, $reservation->nights);

        // Readiness initialized
        $readiness = PropertyReadiness::withoutGlobalScopes()->where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($readiness);
        $this->assertFalse((bool)$readiness->is_ready);

        // Prep task created
        $prepTask = Gorev::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($prepTask);

        // Operational Control Surface Blade view test
        $admin = User::where('email', 'admin@yalihan.com.tr')->first();
        $response = $this->actingAs($admin)->get(route('admin.yazlik-kiralama.bookings'));
        $response->assertStatus(200);
        $response->assertSee('Ali & Selin Demir (Pilot Misafir)');
        $response->assertSee('Yalıhan Mandarin Oriental Luxury Villa #101');
        $response->assertSee('Müdahale Gerekenler');
        $response->assertSee('🔴 P0');
    }
}
