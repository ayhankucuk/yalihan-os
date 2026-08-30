<?php

namespace Tests\Feature\Wizard;

use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Step4GisPersistenceContractTest
 *
 * Contract: Step 4 GIS data (lat/lng, enlem/boylam, il_id/ilce_id/mahalle_id,
 * boundary_geojson polygon, centroid extraction) must persist to ilanlar
 * without silent data-loss.
 *
 * Scenarios:
 *   1. Canonical lat/lng persists to ilanlar row
 *   2. Legacy enlem/boylam bridged to lat/lng via prepareForValidation
 *   3. il_id, ilce_id, mahalle_id persist (Bodrum district data via TurkiyeLocationSeeder)
 *   4. Polygon boundary_geojson persists and geometry_type = polygon
 *   5. Polygon centroid auto-extracted when lat/lng absent
 *   6. Point geometry_type set when lat/lng present without polygon
 *   7. Transaction rollback: DomainException aborts entire store (no partial row)
 *   8. TurkiyeLocationSeeder: Muğla il (id=48) exists in iller table
 *   9. TurkiyeLocationSeeder: Bodrum ilce exists in ilceler table
 */
class Step4GisPersistenceContractTest extends TestCase
{
    use DatabaseTransactions;

    private int $tenantA = 1;

    private function makeRole(string $name): Role
    {
        $role = Role::where('name', $name)->first();
        if (!$role) {
            $role = new Role();
            $role->name = $name;
            $role->save();
        }
        return $role;
    }

    private function makeDanisman(int $tenantId): User
    {
        $role = $this->makeRole('danisman');
        return User::factory()->create([
            'role_id'         => $role->id,
            'tenant_id'       => $tenantId,
            'aktiflik_durumu' => 1,
        ]);
    }

    private function makeKisi(int $tenantId): Kisi
    {
        return Kisi::factory()->create(['tenant_id' => $tenantId]);
    }

    /**
     * Minimal base data to pass IlanCrudService::store without hitting
     * category/schema validation (no ana_kategori_id required for service layer).
     */
    private function baseData(User $auth, Kisi $kisi, array $overrides = []): array
    {
        return array_merge([
            'baslik'              => 'GIS Test İlan',
            'danisman_id'         => $auth->id,
            'ilan_sahibi_id'      => $kisi->id,
            'fiyat'               => 0,
            'para_birimi'         => 'TRY',
            'fiyat_gosterim_modu' => 'exact',
            'yayin_durumu'        => 'taslak',
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 1 — Canonical lat/lng persists
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_canonical_lat_lng_persists(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                'lat' => 37.0344,
                'lng' => 27.4305,
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEqualsWithDelta(37.0344, (float) $fresh->lat, 0.0001);
        $this->assertEqualsWithDelta(27.4305, (float) $fresh->lng, 0.0001);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 2 — Legacy enlem/boylam bridged by prepareForValidation
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_legacy_enlem_boylam_bridged_to_lat_lng(): void
    {
        // The bridge is in StoreIlanRequest::prepareForValidation.
        // IlanCrudService::handleLocation also accepts enlem/boylam directly.
        // We test the service layer directly (bridge already applied before request reaches service).
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        // Service layer reads enlem/boylam via handleLocation fallback
        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                'enlem' => 37.0344,
                'boylam' => 27.4305,
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEqualsWithDelta(37.0344, (float) $fresh->lat, 0.0001,
            'enlem must be persisted as lat via service-layer handleLocation bridge.'
        );
        $this->assertEqualsWithDelta(27.4305, (float) $fresh->lng, 0.0001,
            'boylam must be persisted as lng via service-layer handleLocation bridge.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 3 — il_id/ilce_id/mahalle_id persist
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_il_ilce_mahalle_ids_persist(): void
    {
        // Seed location data inline (TurkiyeLocationSeeder may not run in tests)
        $existingIl = DB::table('iller')->where('plaka_kodu', '48')->first();
        $ilId = $existingIl?->id ?? DB::table('iller')->insertGetId([
            'il_adi'    => 'Muğla',
            'plaka_kodu' => '48',
            'lat'       => 37.2154,
            'lng'       => 28.3636,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ilceId = DB::table('ilceler')->insertGetId([
            'il_id'    => $ilId,
            'ilce_adi' => 'Bodrum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $mahalleId = DB::table('mahalleler')->insertGetId([
            'ilce_id'     => $ilceId,
            'mahalle_adi' => 'Yalıkavak',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                'il_id'      => $ilId,
                'ilce_id'    => $ilceId,
                'mahalle_id' => $mahalleId,
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertSame($ilId,      $fresh->il_id);
        $this->assertSame($ilceId,    $fresh->ilce_id);
        $this->assertSame($mahalleId, $fresh->mahalle_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 4 — Polygon boundary_geojson persists, geometry_type = polygon
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_polygon_boundary_geojson_persists(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        $geojson = [
            'type' => 'Polygon',
            'coordinates' => [[
                [27.4000, 37.0000],
                [27.4100, 37.0000],
                [27.4100, 37.0100],
                [27.4000, 37.0100],
                [27.4000, 37.0000],
            ]],
        ];

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                'lat'              => 37.005,
                'lng'              => 27.405,
                'boundary_geojson' => json_encode($geojson),
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertSame('polygon', $fresh->geometry_type,
            'geometry_type must be polygon when boundary_geojson is provided.'
        );
        $this->assertNotNull($fresh->geometry,
            'geometry column must be populated from boundary_geojson.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 5 — Polygon centroid auto-extracted when lat/lng absent
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_polygon_centroid_extracted_when_lat_lng_absent(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        // Square centroid: lng=(27.40+27.41)/2=27.405, lat=(37.00+37.01)/2=37.005
        $geojson = [
            'type' => 'Polygon',
            'coordinates' => [[
                [27.4000, 37.0000],
                [27.4100, 37.0000],
                [27.4100, 37.0100],
                [27.4000, 37.0100],
                [27.4000, 37.0000], // closing point = first point
            ]],
        ];

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                // No lat/lng — centroid must be auto-extracted from polygon
                'boundary_geojson' => json_encode($geojson),
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertNotNull($fresh->lat, 'lat must be auto-extracted from polygon centroid.');
        $this->assertNotNull($fresh->lng, 'lng must be auto-extracted from polygon centroid.');
        $this->assertEqualsWithDelta(37.005, (float) $fresh->lat, 0.001,
            'Centroid lat should be approx midpoint of polygon.'
        );
        $this->assertEqualsWithDelta(27.405, (float) $fresh->lng, 0.001,
            'Centroid lng should be approx midpoint of polygon.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 6 — Point geometry_type set when lat/lng present without polygon
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_point_geometry_type_set_when_lat_lng_without_polygon(): void
    {
        $auth = $this->makeDanisman($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);
        $this->actingAs($auth);

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($auth, $kisi, [
                'lat' => 37.0344,
                'lng' => 27.4305,
            ])
        );

        $this->assertSame('point', $ilan->fresh()->geometry_type,
            'geometry_type must be point when lat/lng provided without boundary_geojson.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 7 — Transaction rollback: DomainException → no partial row
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_transaction_rollback_on_domain_exception(): void
    {
        $authA  = $this->makeDanisman($this->tenantA);
        $authB  = $this->makeDanisman(2); // different tenant
        $kisi   = $this->makeKisi($this->tenantA);
        $this->actingAs($authA);

        $countBefore = DB::table('ilanlar')->count();

        try {
            app(IlanCrudService::class)->store(
                $this->baseData($authA, $kisi, [
                    'danisman_id' => $authB->id, // cross-tenant → DomainException
                    'lat'         => 37.0344,
                    'lng'         => 27.4305,
                ])
            );
            $this->fail('Expected DomainException was not thrown.');
        } catch (\DomainException $e) {
            // Expected
        }

        $countAfter = DB::table('ilanlar')->count();
        $this->assertSame($countBefore, $countAfter,
            'No partial ilanlar row must be inserted when store() throws DomainException.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 8 — TurkiyeLocationSeeder: Muğla (id=48) exists in iller
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_turkiye_location_seeder_mugla_exists(): void
    {
        // Run TurkiyeLocationSeeder inline to guarantee data
        $this->seed(\Database\Seeders\TurkiyeLocationSeeder::class);

        $mugla = DB::table('iller')->where('plaka_kodu', '48')->first();

        $this->assertNotNull($mugla, 'Muğla (plaka_kodu=48) must exist in iller after TurkiyeLocationSeeder.');
        $this->assertSame('Muğla', $mugla->il_adi);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 9 — TurkiyeLocationSeeder: Bodrum ilçesi exists in ilceler
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_turkiye_location_seeder_bodrum_exists(): void
    {
        $this->seed(\Database\Seeders\TurkiyeLocationSeeder::class);

        $mugla  = DB::table('iller')->where('plaka_kodu', '48')->first();
        $this->assertNotNull($mugla, 'Muğla must exist before checking Bodrum.');

        $bodrum = DB::table('ilceler')
            ->where('il_id', $mugla->id)
            ->where('ilce_adi', 'Bodrum')
            ->first();

        $this->assertNotNull($bodrum, 'Bodrum must exist in ilceler under Muğla after TurkiyeLocationSeeder.');
    }
}
