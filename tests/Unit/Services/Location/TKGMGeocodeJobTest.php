<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Location;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Tenant;
use App\Jobs\Location\TKGMGeocodeJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * TKGMGeocodeJobTest
 *
 * Sprint 6.2: Tests geocoding execution and fallback behavior.
 */
class TKGMGeocodeJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Add geometry columns if missing (SQLite testing parity)
        if (!\Illuminate\Support\Facades\Schema::hasColumn('ilanlar', 'geometry_type')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE ilanlar ADD COLUMN geometry_type VARCHAR(20) NULL;');
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn('ilanlar', 'geometry')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE ilanlar ADD COLUMN geometry JSON NULL;');
        }

        $this->tenant = Tenant::create([
            'name'            => 'Geocode Test Tenant',
            'domain'          => 'geocode.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test successful geocoding coordinates lookup and save.
     */
    public function test_geocode_success_updates_coordinates(): void
    {
        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'adres'     => 'Yalıkavak Marina',
            'lat'       => 0.0,
            'lng'       => 0.0,
        ]);

        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '37.1042',
                    'lon' => '27.2900',
                ]
            ], 200)
        ]);

        $job = new TKGMGeocodeJob($ilan->id, $this->user->id);
        $job->handle();

        $ilan->refresh();

        $this->assertEquals(37.1042, $ilan->lat);
        $this->assertEquals(27.2900, $ilan->lng);
    }

    /**
     * Test geocoding fallback to defaults on API failure.
     */
    public function test_geocode_failure_applies_default_fallbacks(): void
    {
        // 1. Setup location data for fallback
        \Illuminate\Support\Facades\DB::table('iller')->insertOrIgnore([
            'id'              => 48,
            'il_adi'          => 'Muğla',
            'plaka_kodu'      => '48',
            'lat'             => 37.2153,
            'lng'             => 28.3636,
            'aktiflik_durumu' => 1,
        ]);

        \Illuminate\Support\Facades\DB::table('ilceler')->insertOrIgnore([
            'id'              => 1,
            'il_id'           => 48,
            'ilce_adi'        => 'Bodrum',
            'lat'             => 37.0344,
            'lng'             => 27.4305,
            'aktiflik_durumu' => 1,
        ]);

        $ilan = Ilan::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'adres'      => 'Invalid Non-existent Address',
            'il_id'      => 48,
            'ilce_id'    => 1,
            'mahalle_id' => null,
            'lat'        => 0.0,
            'lng'        => 0.0,
        ]);

        // Mock API returning empty array (no results)
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200)
        ]);

        $job = new TKGMGeocodeJob($ilan->id, $this->user->id);
        $job->handle();

        $ilan->refresh();

        // Should fallback to Bodrum district coordinates (37.0344, 27.4305)
        $this->assertEquals(37.0344, $ilan->lat);
        $this->assertEquals(27.4305, $ilan->lng);
    }
}
