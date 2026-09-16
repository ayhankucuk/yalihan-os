<?php

namespace Tests\Feature\CRM;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * TalepControllerStranglerFigTest
 *
 * Validates equivalence of Admin\TalepController when:
 * - config('crm.use_domain_talep') === false (Legacy path)
 * - config('crm.use_domain_talep') === true (Domain UseCase path)
 */
class TalepControllerStranglerFigTest extends TestCase
{
    use RefreshDatabase;

    private object $admin;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
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
            'email' => 'crm-admin-' . uniqid() . '@yalihan.local',
            'role_id' => $adminRole->id,
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

        $this->kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'telefon' => '05321112233',
        ]);
    }

    /** @test */
    public function index_works_in_legacy_mode(): void
    {
        config(['crm.use_domain_talep' => false]);

        Talep::factory()->create([
            'baslik' => 'Bodrum Villa Talebi Legacy',
            'kisi_id' => $this->kisi->id,
            'il_id' => $this->il->id,
            'danisman_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.talepler.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.talepler.index');
        $response->assertViewHas('talepler');
        $response->assertViewHas('istatistikler');
        $response->assertViewHas('statuslar');
    }

    /** @test */
    public function index_works_in_domain_mode(): void
    {
        config(['crm.use_domain_talep' => true]);

        Talep::factory()->create([
            'baslik' => 'Bodrum Villa Talebi Domain',
            'kisi_id' => $this->kisi->id,
            'il_id' => $this->il->id,
            'danisman_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.talepler.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.talepler.index');
        $response->assertViewHas('talepler');
        $response->assertViewHas('istatistikler');
        $response->assertViewHas('statuslar');
    }

    /** @test */
    public function create_form_renders_in_both_modes(): void
    {
        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $response = $this->actingAs($this->admin)->get(route('admin.talepler.create'));

            $response->assertStatus(200);
            $response->assertViewIs('admin.talepler.create');
            $response->assertViewHas('iller');
            $response->assertViewHas('kategoriler');
        }
    }

    /** @test */
    public function store_creates_talep_in_domain_mode(): void
    {
        config(['crm.use_domain_talep' => true]);

        $payload = [
            'baslik'       => 'Yalıkavak Deniz Manzaralı Villa',
            'aciklama'     => 'Acil arayışta',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'ilce_id'      => $this->ilce->id,
            'mahalle_id'   => $this->mahalle->id,
            'kisi_id'      => $this->kisi->id,
            'danisman_id'  => $this->admin->id,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('talepler', [
            'baslik'  => 'Yalıkavak Deniz Manzaralı Villa',
            'kisi_id' => $this->kisi->id,
            'il_id'   => $this->il->id,
        ]);
    }

    /** @test */
    public function store_creates_talep_with_spillover_in_domain_mode(): void
    {
        config(['crm.use_domain_talep' => true]);

        $payload = [
            'baslik'       => 'Gümüşlük Taş Ev',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_ad'      => 'Mehmet',
            'kisi_soyad'   => 'Kaya',
            'kisi_telefon' => '05443332211',
            'kisi_email'   => 'mehmet.kaya@example.com',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.talepler.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('kisiler', [
            'ad'      => 'Mehmet',
            'soyad'   => 'Kaya',
            'telefon' => '05443332211',
        ]);
        $this->assertDatabaseHas('talepler', [
            'baslik' => 'Gümüşlük Taş Ev',
            'il_id'  => $this->il->id,
        ]);
    }

    /** @test */
    public function show_displays_talep_in_both_modes(): void
    {
        $talep = Talep::factory()->create([
            'baslik'      => 'Gündoğan Müstakil Villa',
            'kisi_id'     => $this->kisi->id,
            'il_id'       => $this->il->id,
            'danisman_id' => $this->admin->id,
        ]);

        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $response = $this->actingAs($this->admin)->get(route('admin.talepler.show', $talep->id));

            $response->assertStatus(200);
            $response->assertViewIs('admin.talepler.show');
            $response->assertViewHas('talep');
        }
    }

    /** @test */
    public function update_modifies_talep_in_domain_mode(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::factory()->create([
            'baslik'       => 'Eski Başlık',
            'kisi_id'      => $this->kisi->id,
            'il_id'        => $this->il->id,
            'talep_durumu' => 'yayinda',
            'tip'          => 'Satılık',
            'danisman_id'  => $this->admin->id,
        ]);

        $payload = [
            'baslik'       => 'Güncellenmiş Başlık',
            'aciklama'     => 'Yeni açıklama',
            'tip'          => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id'        => $this->il->id,
            'kisi_id'      => $this->kisi->id,
            'min_fiyat'    => 10000000,
            'max_fiyat'    => 25000000,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.talepler.update', $talep->id), $payload);

        $response->assertRedirect(route('admin.talepler.show', $talep->id));
        $this->assertDatabaseHas('talepler', [
            'id'        => $talep->id,
            'baslik'    => 'Güncellenmiş Başlık',
            'min_fiyat' => 10000000,
            'max_fiyat' => 25000000,
        ]);
    }

    /** @test */
    public function destroy_soft_deletes_talep_in_domain_mode(): void
    {
        config(['crm.use_domain_talep' => true]);

        $talep = Talep::factory()->create([
            'baslik'      => 'Silinecek Talep',
            'kisi_id'     => $this->kisi->id,
            'il_id'       => $this->il->id,
            'danisman_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.talepler.destroy', $talep->id));

        $response->assertRedirect(route('admin.talepler.index'));
        $this->assertSoftDeleted('talepler', ['id' => $talep->id]);
    }

    /** @test */
    public function search_returns_json_in_both_modes(): void
    {
        Talep::factory()->create([
            'baslik'      => 'Özel Arama Hedefi',
            'kisi_id'     => $this->kisi->id,
            'il_id'       => $this->il->id,
            'danisman_id' => $this->admin->id,
        ]);

        foreach ([false, true] as $useDomain) {
            config(['crm.use_domain_talep' => $useDomain]);

            $response = $this->actingAs($this->admin)->getJson(route('admin.talepler.search', ['q' => 'Arama']));

            $response->assertStatus(200);
            $response->assertJsonStructure([
                '*' => ['id', 'text', 'value'],
            ]);
        }
    }
}
