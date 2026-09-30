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
 * Debug test — understand why legacy path title edit fails
 */
class DebugLegacyUpdateTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\SaaS\Tenant $tenant;
    private User $user;
    private Kisi $kisi;
    private IlanKategori $katA;
    private IlanKategori $katB;
    private \App\Models\Il $il;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = \App\Models\SaaS\Tenant::factory()->create(['name' => 'Test Tenant']);
        $adminRole = \App\Modules\Auth\Models\Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = new \App\Modules\Auth\Models\Role();
            $adminRole->name = 'admin';
            $adminRole->guard_name = 'web';
            $adminRole->description = 'Admin';
            $adminRole->save();
        }
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'debug-' . uniqid() . '@yalihan.local',
            'role_id' => $adminRole->id,
        ]);
        if (method_exists($this->user, 'assignRole')) $this->user->assignRole('admin');
        $this->kisi = Kisi::create([
            'tenant_id' => $this->tenant->id,
            'ad' => 'Test', 'soyad' => 'Kişi',
            'telefon' => '05390001111',
            'kisi_tipi' => 'lead',
        ]);
        $this->katA = IlanKategori::factory()->create(['name' => 'Konut']);
        $this->katB = IlanKategori::factory()->create(['name' => 'Arsa']);
        $this->il = \App\Models\Il::first() ?? \App\Models\Il::create(['il_adi' => 'Muğla']);
        app(TenantContextService::class)->setTenant($this->tenant);
    }

    public function test_debug_legacy_title_edit(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Original Başlık',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->katA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Yeni Başlık',
                'kisi_id' => $this->kisi->id,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->katA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
            ]);

        $response->assertStatus(302);

        // Fresh DB read — no in-memory caching
        $fresh = Talep::withTrashed()->find($talep->id);

        $this->assertEquals('Yeni Başlık', $fresh->baslik, 'baslik did not update');
        $this->assertEquals('Satılık', $fresh->talep_tipi);
        $this->assertEquals($this->katA->id, $fresh->alt_kategori_id);
    }

    public function test_debug_legacy_with_minimal_fields(): void
    {
        config(['crm.use_domain_talep' => false]);

        $talep = Talep::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Original',
            'talep_tipi' => 'Satılık',
            'alt_kategori_id' => $this->katA->id,
            'il_id' => $this->il->id,
            'kisi_id' => $this->kisi->id,
            'danisman_id' => $this->user->id,
            'talep_durumu' => 'yayinda',
        ]);

        // Using the same minimal payload pattern as existing passing tests
        $response = $this->actingAs($this->user)
            ->put(route('admin.talepler.update', $talep), [
                'baslik' => 'Yeni Başlık',
                'kisi_id' => $this->kisi->id,
                'tip' => 'Satılık',
                'alt_kategori_id' => $this->katA->id,
                'il_id' => $this->il->id,
                'talep_durumu' => 'yayinda',
                'ilce_id' => null,
                'mahalle_id' => null,
                'min_oda_sayisi' => null,
                'max_oda_sayisi' => null,
                'min_alan' => null,
                'max_alan' => null,
                'min_fiyat' => null,
                'max_fiyat' => null,
            ]);

        $response->assertStatus(302);

        $fresh = Talep::withTrashed()->find($talep->id);
        $this->assertEquals('Yeni Başlık', $fresh->baslik, 'baslik did not update with full payload');
    }
}
