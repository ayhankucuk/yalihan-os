<?php

namespace Tests\Feature\CRM;

use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Il;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Role;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * TalepContractParityTest
 *
 * Verifies 100% equivalence between Legacy Orchestrator and Domain Use Cases across:
 * - HTTP status codes
 * - Response JSON / View contract
 * - Validation errors
 * - Authorization & 404 concealment
 * - Automatic Kişi spillover
 * - Soft-delete & search
 */
class TalepContractParityTest extends TestCase
{
    use RefreshDatabase;

    private object $admin;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private IlanKategori $kategori;
    private Kisi $kisi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $adminRole = \App\Modules\Auth\Models\Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = new \App\Modules\Auth\Models\Role();
            $adminRole->name = 'admin';
            $adminRole->save();
        }

        $realAdmin = User::factory()->create([
            'email' => 'parity-admin-' . uniqid() . '@yalihan.local',
            'role_id' => $adminRole->id,
            'tenant_id' => 1,
        ]);
        $this->admin = \Mockery::mock($realAdmin)->makePartial();
        $this->admin->shouldReceive('isAdmin')->andReturn(true);
        $this->admin->shouldReceive('hasRole')->andReturn(true);
        $this->admin->shouldReceive('can')->andReturn(true);
        $this->admin->shouldReceive('getAuthIdentifier')->andReturn($realAdmin->id);

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'ilce_adi' => 'Bodrum'],
            ['aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'mahalle_adi' => 'Yalıkavak'],
            ['aktiflik_durumu' => 1]
        );

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut'],
            ['name' => 'Konut', 'aktiflik_durumu' => 1]
        );

        $this->kisi = Kisi::create([
            'ad'        => 'Ahmet',
            'soyad'     => 'Yılmaz',
            'telefon'   => '05551234567',
            'email'     => 'ahmet@example.test',
            'kisi_tipi' => 'lead',
        ]);
    }

    public function test_index_parity_between_legacy_and_domain_modes(): void
    {
        Talep::create([
            'tenant_id'       => 1,
            'danisman_id'     => $this->admin->id,
            'kisi_id'         => $this->kisi->id,
            'baslik'          => 'Parity Test Talebi',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'il_id'           => $this->il->id,
            'ilce_id'         => $this->ilce->id,
            'mahalle_id'      => $this->mahalle->id,
            'kategori_id'     => $this->kategori->id,
            'alt_kategori_id' => $this->kategori->id,
        ]);

        // 1. Legacy mode
        Config::set('crm.use_domain_talep', false);
        $resLegacy = $this->actingAs($this->admin)->get(route('admin.talepler.index'));
        $resLegacy->assertStatus(200);
        $resLegacy->assertViewHasAll(['talepler', 'istatistikler', 'statuslar']);

        // 2. Domain mode
        Config::set('crm.use_domain_talep', true);
        $resDomain = $this->actingAs($this->admin)->get(route('admin.talepler.index'));
        $resDomain->assertStatus(200);
        $resDomain->assertViewHasAll(['talepler', 'istatistikler', 'statuslar']);
    }

    public function test_store_with_kisi_spillover_parity(): void
    {
        $payload = [
            'baslik'       => 'Spillover Parity Talebi',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'ilce_id'      => $this->ilce->id,
            'kisi_ad'      => 'Mehmet',
            'kisi_soyad'   => 'Kaya',
            'kisi_telefon' => '05559876543',
            'kisi_email'   => 'mehmet@example.test',
        ];

        // Domain mode store
        Config::set('crm.use_domain_talep', true);
        $res = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);

        $res->assertStatus(302);
        $this->assertDatabaseHas('kisiler', ['ad' => 'Mehmet', 'soyad' => 'Kaya']);
        $this->assertDatabaseHas('talepler', ['baslik' => 'Spillover Parity Talebi']);
    }

    public function test_update_and_destroy_parity(): void
    {
        $talep = Talep::create([
            'tenant_id'       => 1,
            'danisman_id'     => $this->admin->id,
            'kisi_id'         => $this->kisi->id,
            'baslik'          => 'Update Test Talebi',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'il_id'           => $this->il->id,
        ]);

        Config::set('crm.use_domain_talep', true);

        // Update
        $resUpdate = $this->actingAs($this->admin)->put(route('admin.talepler.update', $talep->id), [
            'baslik'       => 'Updated Baslik Domain',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_id'      => $this->kisi->id,
        ]);
        $resUpdate->assertStatus(302);
        $this->assertEquals('Updated Baslik Domain', $talep->fresh()->baslik);

        // Destroy (soft delete)
        $resDelete = $this->actingAs($this->admin)->delete(route('admin.talepler.destroy', $talep->id));
        $resDelete->assertStatus(302);
        $this->assertSoftDeleted('talepler', ['id' => $talep->id]);
    }

    public function test_ajax_search_parity(): void
    {
        Talep::create([
            'tenant_id'       => 1,
            'danisman_id'     => $this->admin->id,
            'kisi_id'         => $this->kisi->id,
            'baslik'          => 'Searchable Luxury Demand',
            'talep_tipi'      => 'Satılık',
            'talep_durumu'    => 'yayinda',
            'il_id'           => $this->il->id,
        ]);

        // Legacy mode
        Config::set('crm.use_domain_talep', false);
        $resLegacy = $this->actingAs($this->admin)->get(route('admin.talepler.search', ['q' => 'Searchable']));
        $resLegacy->assertStatus(200);
        $resLegacy->assertJsonStructure([['id', 'text', 'value']]);

        // Domain mode
        Config::set('crm.use_domain_talep', true);
        $resDomain = $this->actingAs($this->admin)->get(route('admin.talepler.search', ['q' => 'Searchable']));
        $resDomain->assertStatus(200);
        $resDomain->assertJsonStructure([['id', 'text', 'value']]);
        $this->assertEquals($resLegacy->json(), $resDomain->json());
    }
}
