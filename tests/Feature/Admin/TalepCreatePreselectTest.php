<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TalepCreatePreselectTest extends TestCase
{
    protected User $admin;

    protected ?Role $adminRole = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RoleMiddleware::class,
            VerifyCsrfToken::class,
        ]);

        $this->adminRole = Role::where('name', 'admin')->orderBy('id')->first();
        if (! $this->adminRole) {
            $this->adminRole = new Role;
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);

        DB::table('iller')->insertOrIgnore([
            'id' => 1,
            'il_adi' => 'MUĞLA',
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ilceler')->insertOrIgnore([
            'id' => 1,
            'il_id' => 1,
            'ilce_adi' => 'BODRUM',
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_view_with_kisi_id_preselects_customer_and_shows_budget_fields(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Murat',
            'soyad' => 'Demir',
            'telefon' => '05321112233',
            'eposta' => 'murat@example.com',
            'aktiflik_durumu' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.talepler.create', ['kisi_id' => $kisi->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.talepler.create');
        $response->assertViewHas('selectedKisi', function ($viewKisi) use ($kisi) {
            return $viewKisi && $viewKisi->id === $kisi->id;
        });

        // Verify preselection in rendered HTML
        $response->assertSee('Murat Demir');
        $response->assertSee('05321112233');

        // Verify budget fields exist
        $response->assertSee('Bütçe ve Fiyat Kriterleri');
        $response->assertSee('min_fiyat');
        $response->assertSee('max_fiyat');
        $response->assertSee('para_birimi');
    }

    public function test_store_persists_budget_fields_and_customer_id(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Selin',
            'soyad' => 'Yıldız',
            'aktiflik_durumu' => true,
        ]);

        $payload = [
            'baslik' => 'Yalıkavak Deniz Manzaralı Villa Talebi',
            'tip' => 'Satılık',
            'talep_durumu' => 'Aktif',
            'il_id' => 1,
            'ilce_id' => 1,
            'kisi_id' => $kisi->id,
            'danisman_id' => $this->admin->id,
            'min_fiyat' => 1500000,
            'max_fiyat' => 3500000,
            'para_birimi' => 'EUR',
            'notlar' => 'Özel havuzlu olması tercih ediliyor.',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.talepler.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('talepler', [
            'baslik' => 'Yalıkavak Deniz Manzaralı Villa Talebi',
            'kisi_id' => $kisi->id,
            'min_fiyat' => 1500000,
            'max_fiyat' => 3500000,
            'para_birimi' => 'EUR',
        ]);
    }

    public function test_update_modifies_budget_fields(): void
    {
        $kisi = Kisi::factory()->create(['aktiflik_durumu' => true]);

        $talep = Talep::create([
            'tenant_id' => $this->getDefaultTenantId(),
            'baslik' => 'İlk Talep Başlığı',
            'talep_tipi' => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id' => 1,
            'ilce_id' => 1,
            'kisi_id' => $kisi->id,
            'danisman_id' => $this->admin->id,
            'min_fiyat' => 1000000,
            'max_fiyat' => 2000000,
            'para_birimi' => 'TRY',
        ]);

        $updatePayload = [
            'baslik' => 'Güncellenmiş Talep Başlığı',
            'tip' => 'Satılık',
            'talep_durumu' => 'yayinda',
            'il_id' => 1,
            'ilce_id' => 1,
            'kisi_id' => $kisi->id,
            'danisman_id' => $this->admin->id,
            'min_fiyat' => 120000,
            'max_fiyat' => 250000,
            'para_birimi' => 'USD',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.talepler.update', $talep), $updatePayload);

        $response->assertRedirect();

        $this->assertDatabaseHas('talepler', [
            'id' => $talep->id,
            'baslik' => 'Güncellenmiş Talep Başlığı',
            'min_fiyat' => 120000,
            'max_fiyat' => 250000,
            'para_birimi' => 'USD',
        ]);
    }
}
