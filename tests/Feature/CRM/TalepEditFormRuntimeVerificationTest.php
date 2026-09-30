<?php

namespace Tests\Feature\CRM;

use Tests\TestCase;
use App\Models\Talep;
use App\Models\Kisi;
use App\Models\User;
use App\Models\Tenant;
use App\Models\IlanKategori;
use App\Services\SaaS\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CRM_03 TALEP EDIT FORM RUNTIME VERIFICATION
 * Independent proof: HTTP update + fresh DB readback
 * Does NOT rely on test payload assertions — measures actual DB state
 */
class TalepEditFormRuntimeVerificationTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\SaaS\Tenant $tenant;
    private User $user;
    private Kisi $kisi;
    private IlanKategori $kategoriA;
    private IlanKategori $kategoriB;
    private \App\Models\Il $il; // Valid city for il_id FK

    private function createTalep(array $attributes = []): Talep
    {
        return Talep::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ], $attributes));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = \App\Models\SaaS\Tenant::factory()->create(['name' => 'Yalıkavak Emlak']);

        // Create admin role so hasRole('admin') returns true
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'runtime-verif-' . uniqid() . '@yalihan.local',
            'role_id' => $adminRole->id,
        ]);
        if (method_exists($this->user, 'assignRole')) {
            $this->user->assignRole('admin');
        }
        $this->kisi = Kisi::create([
            'tenant_id' => $this->tenant->id,
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'telefon' => '05550001111',
            'kisi_tipi' => \App\Enums\KisiTipi::ALICI->value,
        ]);
        $this->kategoriA = IlanKategori::factory()->create(['name' => 'Konut']);
        $this->kategoriB = IlanKategori::factory()->create(['name' => 'Arsa']);
        $this->il = \App\Models\Il::first() ?? \App\Models\Il::create(['il_adi' => 'Muğla']);

        app(TenantContextService::class)->setTenant($this->tenant);
    }

    // ─────────────────────────────────────────────────────────────
    // RUNTIME MATRIX: LEGACY PATH
    // ─────────────────────────────────────────────────────────────

    public function test_legacy_ordinary_edit_leaves_baslik_tip_altkategori_unchanged(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Yalıkavak 3+1 Villa Talebi',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $originalBaslik = $talep->baslik;
        $originalTip = $talep->talep_tipi;
        $originalKat = $talep->alt_kategori_id;

        // Update only unrelated field (notlar)
        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => $originalBaslik,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
                'notlar' => 'Updated by runtime verification test',
            ])
            ->assertStatus(302);

        // FRESH DB READBACK — no in-memory caching
        $fresh = Talep::withTrashed()->find($talep->id);

        $this->assertEquals($originalBaslik, $fresh->baslik, 'baslik changed on ordinary edit');
        $this->assertEquals($originalTip, $fresh->talep_tipi, 'talep_tipi changed on ordinary edit');
        $this->assertEquals($originalKat, $fresh->alt_kategori_id, 'alt_kategori_id changed on ordinary edit');
    }

    public function test_legacy_title_edit_persists_new_baslik_to_db(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Yalıkavak 3+1 Villa Talebi',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Gümbet 4+1 Daire Talebi',
                'kisi_id' => $this->kisi->id,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals('Gümbet 4+1 Daire Talebi', $fresh->baslik);
    }

    public function test_legacy_category_edit_a_to_b_persists_to_db(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Test Talep',
                'kisi_id' => $this->kisi->id,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriB->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals($this->kategoriB->id, $fresh->alt_kategori_id);
    }

    public function test_legacy_type_edit_satilik_to_kiralik_persists_to_db(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Test Talep',
                'kisi_id' => $this->kisi->id,
                'tip' => 'Kiralık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals('Kiralık', $fresh->talep_tipi);
    }

    // ─────────────────────────────────────────────────────────────
    // RUNTIME MATRIX: DOMAIN PATH
    // ─────────────────────────────────────────────────────────────

    public function test_domain_ordinary_edit_leaves_baslik_tip_altkategori_unchanged(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Yalıkavak 3+1 Villa Talebi',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $originalBaslik = $talep->baslik;
        $originalTip = $talep->talep_tipi;
        $originalKat = $talep->alt_kategori_id;

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => $originalBaslik,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
                'notlar' => 'Updated by runtime verification test',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);

        $this->assertEquals($originalBaslik, $fresh->baslik, 'baslik changed on ordinary edit');
        $this->assertEquals($originalTip, $fresh->talep_tipi, 'talep_tipi changed on ordinary edit');
        $this->assertEquals($originalKat, $fresh->alt_kategori_id, 'alt_kategori_id changed on ordinary edit');
    }

    public function test_domain_title_edit_persists_new_baslik_to_db(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Yalıkavak 3+1 Villa Talebi',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Gümbet 4+1 Daire Talebi',
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals('Gümbet 4+1 Daire Talebi', $fresh->baslik);
    }

    public function test_domain_category_edit_a_to_b_persists_to_db(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Test Talep',
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->kategoriB->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals($this->kategoriB->id, $fresh->alt_kategori_id);
    }

    public function test_domain_type_edit_satilik_to_kiralik_persists_to_db(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Test Talep',
                'tip' => 'Kiralık',
                'alt_kategori_id' => $this->kategoriA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ])
            ->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals('Kiralık', $fresh->talep_tipi);
    }

    // ─────────────────────────────────────────────────────────────
    // RENDERED FORM CONTRACT: canonical names must be present
    // stale names must NOT be present
    // ─────────────────────────────────────────────────────────────

    public function test_edit_form_contains_canonical_names_and_not_stale_names(): void
    {
        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Talep',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->kategoriA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        app(TenantContextService::class)->setTenant($this->tenant);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Illuminate\Auth\Middleware\Authorize::class)
            ->get(route('admin.talepler.edit', $talep));

        $response->assertStatus(200);

        // Canonical names MUST be present
        $response->assertSee('name="baslik"', false);
        $response->assertSee('name="tip"', false);
        $response->assertSee('name="alt_kategori_id"', false);

        // Stale names MUST NOT be present anywhere
        $response->assertDontSee('name="talep_tipi"');
        $response->assertDontSee('name="category_id"');
    }
}
