<?php

namespace Tests\Feature\Admin\CRM;

use App\Enums\KisiDurumu;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Kisi;
use App\Models\User;
use App\Modules\Auth\Models\Role;
use Tests\TestCase;

class PipelineControllerTest extends TestCase
{
    protected User $admin;

    protected ?Role $adminRole = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([RoleMiddleware::class]);

        $this->adminRole = Role::where('name', 'admin')->orderBy('id')->first();
        if (! $this->adminRole) {
            $this->adminRole = new Role;
            $this->adminRole->name = 'admin';
            $this->adminRole->save();
        }

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'name' => 'Admin User',
        ]);
    }

    public function test_pipeline_index_renders_successfully_for_authenticated_admin(): void
    {
        $this->withoutExceptionHandling();
        $kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'aktiflik_durumu' => true,
            'crm_surec_asamasi' => KisiDurumu::POTANSIYEL,
            'user_id' => $this->admin->id,
            'tenant_id' => $this->admin->tenant_id ?? 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.crm.pipeline.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.crm.pipeline.index');
        $response->assertSee('Satış Hunisi (Pipeline)');
        $response->assertSee('Ahmet Yılmaz');
        $response->assertSee('Potansiyel Lead');
        $response->assertSee('İletişimde / İlgili');
        $response->assertSee('Görüşme / Takipte');
        $response->assertSee('Sıcak Fırsat / Teklif');
        $response->assertSee('Kapanış (İşlem Yapmış)');
    }

    public function test_pipeline_update_stage_moves_person_to_new_stage(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Mehmet',
            'soyad' => 'Kaya',
            'aktiflik_durumu' => true,
            'crm_surec_asamasi' => KisiDurumu::POTANSIYEL,
            'user_id' => $this->admin->id,
            'tenant_id' => $this->admin->tenant_id ?? 1,
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.crm.pipeline.update-stage', ['kisi' => $kisi->id]),
            ['stage' => 'ilgili']
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'person' => [
                'id' => $kisi->id,
                'stage' => 'ilgili',
            ],
        ]);

        $this->assertEquals(KisiDurumu::ILGILI, $kisi->fresh()->crm_surec_asamasi);
    }

    public function test_pipeline_quick_note_records_interaction(): void
    {
        $kisi = Kisi::factory()->create([
            'ad' => 'Zeynep',
            'soyad' => 'Demir',
            'aktiflik_durumu' => true,
            'crm_surec_asamasi' => KisiDurumu::TAKIPTE,
            'user_id' => $this->admin->id,
            'tenant_id' => $this->admin->tenant_id ?? 1,
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.crm.pipeline.quick-note', ['kisi' => $kisi->id]),
            ['note' => 'Yalıkavak villası için randevu teyit edildi.']
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_pipeline_statistics_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.crm.pipeline.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'statistics',
            'conversion_rates',
        ]);
    }
}
