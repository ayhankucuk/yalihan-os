<?php

namespace Tests\Feature\CRM;

use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Il;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * TalepStoreValidationAndCriteriaTest
 *
 * Regression test suite for CRM_03_TALEP_STORE_UPDATE_CONTRACT_REMEDIATION_02:
 * - F1: alt_kategori_id validation queries canonical 'ilan_kategorileri' table (no QueryException / HTTP 500)
 * - F2: min_fiyat, max_fiyat, notlar criteria whitelisted in store() and persist to DB
 * - Failure closed: nonexistent alt_kategori_id fails validation cleanly
 * - Parity across legacy and domain modes
 * - Omitted field safety
 */
class TalepStoreValidationAndCriteriaTest extends TestCase
{
    use RefreshDatabase;

    private object $admin;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private IlanKategori $kategoriVilla;
    private IlanKategori $kategoriDaire;
    private Kisi $kisi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\RoleMiddleware::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $adminRole = \App\Modules\Auth\Models\Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = new \App\Modules\Auth\Models\Role();
            $adminRole->name = 'admin';
            $adminRole->save();
        }

        $realAdmin = User::factory()->create([
            'email'     => 'talep-criteria-admin-' . uniqid() . '@yalihan.local',
            'role_id'   => $adminRole->id,
            'tenant_id' => 1,
        ]);
        $this->admin = Mockery::mock($realAdmin)->makePartial();
        $this->admin->shouldReceive('isAdmin')->andReturn(true);
        $this->admin->shouldReceive('hasRole')->andReturn(true);
        $this->admin->shouldReceive('can')->andReturn(true);
        $this->admin->shouldReceive('getAuthIdentifier')->andReturn($realAdmin->id);

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['id' => 48, 'il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Bodrum'],
            ['id' => 4801, 'aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Yalıkavak'],
            ['id' => 480101, 'aktiflik_durumu' => 1]
        );

        $this->kategoriVilla = IlanKategori::firstOrCreate(
            ['slug' => 'satilik-villa'],
            ['name' => 'Villa', 'aktiflik_durumu' => 1]
        );

        $this->kategoriDaire = IlanKategori::firstOrCreate(
            ['slug' => 'satilik-daire'],
            ['name' => 'Daire', 'aktiflik_durumu' => 1]
        );

        $this->kisi = Kisi::create([
            'ad'        => 'Bora',
            'soyad'     => 'Demir',
            'telefon'   => '05330001122',
            'eposta'    => 'bora.demir@example.test',
            'kisi_tipi' => 'lead',
        ]);
    }

    /**
     * Requirement A: CREATE WITH CATEGORY
     * Submit valid Talep with valid alt_kategori_id -> no QueryException, no HTTP 500,
     * Talep persists, alt_kategori_id matches.
     */
    public function test_create_with_valid_category_persists_alt_kategori_id(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $payload = [
                'baslik'          => 'Yalıkavak Lüks Villa Talebi ' . ($useDomain ? 'Domain' : 'Legacy'),
                'aciklama'        => 'Özel havuzlu ve deniz manzaralı.',
                'tip'             => 'Satılık',
                'alt_kategori_id' => $this->kategoriVilla->id,
                'talep_durumu'    => 'yayinda',
                'il_id'           => $this->il->id,
                'ilce_id'         => $this->ilce->id,
                'mahalle_id'      => $this->mahalle->id,
                'kisi_id'         => $this->kisi->id,
            ];

            $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $talep = Talep::where('baslik', $payload['baslik'])->first();
            $this->assertNotNull($talep, "Talep should be persisted in DB (useDomain: {$useDomain})");
            $this->assertEquals((int) $this->kategoriVilla->id, (int) $talep->alt_kategori_id);
        }
    }

    /**
     * Requirement B: INVALID CATEGORY FAILS CLOSED
     * Submit nonexistent alt_kategori_id -> validation rejection (session error / 422),
     * no HTTP 500, no unintended DB record.
     */
    public function test_invalid_category_fails_closed_without_http_500(): void
    {
        $nonExistentCategoryId = 999999;
        $this->assertFalse(
            IlanKategori::where('id', $nonExistentCategoryId)->exists(),
            'Precondition: Category ID must not exist'
        );

        $payload = [
            'baslik'          => 'Invalid Category Demand',
            'tip'             => 'Satılık',
            'alt_kategori_id' => $nonExistentCategoryId,
            'talep_durumu'    => 'yayinda',
            'il_id'           => $this->il->id,
            'kisi_id'         => $this->kisi->id,
        ];

        // 1. Web session validation failure
        $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['alt_kategori_id']);

        // 2. JSON validation failure (assert 422)
        $jsonResponse = $this->actingAs($this->admin)->postJson(route('admin.talepler.store'), $payload);
        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJsonValidationErrors(['alt_kategori_id']);

        // 3. Ensure no DB persistence occurred
        $this->assertDatabaseMissing('talepler', [
            'baslik'          => 'Invalid Category Demand',
            'alt_kategori_id' => $nonExistentCategoryId,
        ]);
    }

    /**
     * Requirement C: CREATE CRITERIA ROUND-TRIP
     * Submit min_fiyat, max_fiyat, notlar -> validated payload passes to store action ->
     * persisted in DB -> model read-back matches submitted values.
     */
    public function test_create_criteria_round_trip(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $payload = [
                'baslik'          => 'Bütçeli Villa Talebi ' . ($useDomain ? 'Domain' : 'Legacy'),
                'tip'             => 'Satılık',
                'alt_kategori_id' => $this->kategoriVilla->id,
                'talep_durumu'    => 'yayinda',
                'il_id'           => $this->il->id,
                'ilce_id'         => $this->ilce->id,
                'kisi_id'         => $this->kisi->id,
                'min_fiyat'       => 15000000.00,
                'max_fiyat'       => 35000000.00,
                'notlar'          => 'Müstakil parsel, en az 500m2 arsa payı aranıyor.',
            ];

            $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $talep = Talep::where('baslik', $payload['baslik'])->first();
            $this->assertNotNull($talep, "Talep should exist in DB (useDomain: {$useDomain})");
            $this->assertEquals(15000000.00, (float) $talep->min_fiyat);
            $this->assertEquals(35000000.00, (float) $talep->max_fiyat);
            $this->assertEquals('Müstakil parsel, en az 500m2 arsa payı aranıyor.', $talep->notlar);
        }
    }

    /**
     * Requirement D: UPDATE CATEGORY
     * Update existing Talep with valid alt_kategori_id -> no QueryException, no HTTP 500,
     * new category persists.
     */
    public function test_update_category_persists_cleanly(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $talep = Talep::create([
                'tenant_id'       => 1,
                'danisman_id'     => $this->admin->id,
                'kisi_id'         => $this->kisi->id,
                'baslik'          => 'Kategori Güncelleme Talebi ' . ($useDomain ? 'Domain' : 'Legacy'),
                'talep_tipi'      => 'Satılık',
                'talep_durumu'    => 'yayinda',
                'alt_kategori_id' => $this->kategoriVilla->id,
                'il_id'           => $this->il->id,
            ]);

            $updatePayload = [
                'baslik'          => $talep->baslik,
                'tip'             => 'Satılık',
                'alt_kategori_id' => $this->kategoriDaire->id,
                'talep_durumu'    => 'yayinda',
                'il_id'           => $this->il->id,
                'kisi_id'         => $this->kisi->id,
            ];

            $response = $this->actingAs($this->admin)->put(
                route('admin.talepler.update', $talep->id),
                $updatePayload
            );

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $fresh = $talep->fresh();
            $this->assertEquals(
                (int) $this->kategoriDaire->id,
                (int) $fresh->alt_kategori_id,
                "alt_kategori_id should be updated to Daire (useDomain: {$useDomain})"
            );
        }
    }

    /**
     * Update with invalid category fails closed.
     */
    public function test_update_with_invalid_category_fails_closed(): void
    {
        $talep = Talep::create([
            'tenant_id'       => 1,
            'danisman_id'     => $this->admin->id,
            'kisi_id'         => $this->kisi->id,
            'baslik'          => 'Update Invalid Category Test',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'alt_kategori_id' => $this->kategoriVilla->id,
            'il_id'           => $this->il->id,
        ]);

        $nonExistentCategoryId = 999999;
        $updatePayload = [
            'baslik'          => $talep->baslik,
            'tip'             => 'Satılık',
            'alt_kategori_id' => $nonExistentCategoryId,
            'talep_durumu'    => 'yayinda',
            'il_id'           => $this->il->id,
            'kisi_id'         => $this->kisi->id,
        ];

        $response = $this->actingAs($this->admin)->put(
            route('admin.talepler.update', $talep->id),
            $updatePayload
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['alt_kategori_id']);

        $this->assertEquals(
            (int) $this->kategoriVilla->id,
            (int) $talep->fresh()->alt_kategori_id,
            'Category should remain unchanged after invalid category submission'
        );
    }

    /**
     * Requirement E: EXISTING UPDATE CRITERIA
     * Update min_fiyat, max_fiyat, notlar -> persists correctly.
     */
    public function test_update_criteria_persists_correctly(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $talep = Talep::create([
                'tenant_id'       => 1,
                'danisman_id'     => $this->admin->id,
                'kisi_id'         => $this->kisi->id,
                'baslik'          => 'Kriter Güncelleme Talebi ' . ($useDomain ? 'Domain' : 'Legacy'),
                'talep_tipi'      => 'Satılık',
                'talep_durumu'    => 'yayinda',
                'il_id'           => $this->il->id,
                'min_fiyat'       => 5000000.00,
                'max_fiyat'       => 10000000.00,
                'notlar'          => 'Başlangıç kriteri',
            ]);

            $updatePayload = [
                'baslik'       => $talep->baslik,
                'tip'          => 'Satılık',
                'talep_durumu' => 'yayinda',
                'il_id'        => $this->il->id,
                'kisi_id'      => $this->kisi->id,
                'min_fiyat'    => 7500000.00,
                'max_fiyat'    => 14000000.00,
                'notlar'       => 'Revize bütçe ve deniz manzarası şartı eklendi.',
            ];

            $response = $this->actingAs($this->admin)->put(
                route('admin.talepler.update', $talep->id),
                $updatePayload
            );

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $fresh = $talep->fresh();
            $this->assertEquals(7500000.00, (float) $fresh->min_fiyat);
            $this->assertEquals(14000000.00, (float) $fresh->max_fiyat);
            $this->assertEquals('Revize bütçe ve deniz manzarası şartı eklendi.', $fresh->notlar);
        }
    }

    /**
     * Requirement F: OMITTED FIELD SAFETY
     * Create/update with omitted criteria -> no unexpected corruption/reset.
     */
    public function test_omitted_field_safety_on_create_and_update(): void
    {
        // 1. Create with omitted criteria
        $createPayload = [
            'baslik'       => 'Omitted Fields Create Test',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_id'      => $this->kisi->id,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $createPayload);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $talep = Talep::where('baslik', 'Omitted Fields Create Test')->first();
        $this->assertNotNull($talep);
        $this->assertNull($talep->min_fiyat);
        $this->assertNull($talep->max_fiyat);
        $this->assertNull($talep->notlar);
        $this->assertNull($talep->alt_kategori_id);

        // 2. Update without criteria fields does not crash and updates non-omitted fields safely
        $updatePayload = [
            'baslik'       => 'Omitted Fields Updated Baslik',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_id'      => $this->kisi->id,
        ];

        $updateResponse = $this->actingAs($this->admin)->put(
            route('admin.talepler.update', $talep->id),
            $updatePayload
        );
        $updateResponse->assertStatus(302);
        $updateResponse->assertSessionHasNoErrors();

        $fresh = $talep->fresh();
        $this->assertEquals('Omitted Fields Updated Baslik', $fresh->baslik);
        $this->assertNull($fresh->min_metrekare);
        $this->assertNull($fresh->max_metrekare);
    }

    /**
     * Requirement G: AREA CRITERIA ROUND-TRIP (CREATE & UPDATE) IN LEGACY & DOMAIN MODES
     * CREATE: 120 / 240 -> DB readback exactly 120 / 240
     * UPDATE: 150 / 300 -> DB readback exactly 150 / 300
     */
    public function test_area_criteria_round_trip_and_parity_across_legacy_and_domain(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            // 1. Create with area criteria
            $createPayload = [
                'baslik'         => 'Metrekare Kriterli Talep ' . ($useDomain ? 'Domain' : 'Legacy'),
                'tip'            => 'Satılık',
                'alt_kategori_id'=> $this->kategoriVilla->id,
                'talep_durumu'   => 'yayinda',
                'il_id'          => $this->il->id,
                'ilce_id'        => $this->ilce->id,
                'kisi_id'        => $this->kisi->id,
                'min_metrekare'  => 120,
                'max_metrekare'  => 240,
            ];

            $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $createPayload);
            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();

            $talep = Talep::where('baslik', $createPayload['baslik'])->first();
            $this->assertNotNull($talep, "Talep should exist in DB (useDomain: {$useDomain})");
            $this->assertSame(120, (int) $talep->min_metrekare);
            $this->assertSame(240, (int) $talep->max_metrekare);

            // 2. Update with new area criteria
            $updatePayload = [
                'baslik'         => $talep->baslik,
                'tip'            => 'Satılık',
                'talep_durumu'   => 'yayinda',
                'il_id'          => $this->il->id,
                'kisi_id'        => $this->kisi->id,
                'min_metrekare'  => 150,
                'max_metrekare'  => 300,
            ];

            $updateResponse = $this->actingAs($this->admin)->put(
                route('admin.talepler.update', $talep->id),
                $updatePayload
            );
            $updateResponse->assertStatus(302);
            $updateResponse->assertSessionHasNoErrors();

            $fresh = $talep->fresh();
            $this->assertSame(150, (int) $fresh->min_metrekare);
            $this->assertSame(300, (int) $fresh->max_metrekare);
        }
    }

    /**
     * Requirement H: AREA RANGE VALIDATION INVARIANT
     * When both min_metrekare and max_metrekare are provided,
     * max_metrekare must be >= min_metrekare.
     */
    public function test_invalid_area_range_fails_validation(): void
    {
        $invalidPayload = [
            'baslik'        => 'Invalid Area Range Demand',
            'tip'           => 'Satılık',
            'talep_durumu'  => 'yayinda',
            'il_id'         => $this->il->id,
            'kisi_id'       => $this->kisi->id,
            'min_metrekare' => 300,
            'max_metrekare' => 150,
        ];

        // 1. Session store failure
        $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $invalidPayload);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['max_metrekare']);

        // 2. JSON store failure (422)
        $jsonResponse = $this->actingAs($this->admin)->postJson(route('admin.talepler.store'), $invalidPayload);
        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJsonValidationErrors(['max_metrekare']);
    }
}
