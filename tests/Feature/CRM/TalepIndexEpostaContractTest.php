<?php

namespace Tests\Feature\CRM;

use App\Enums\KisiTipi;
use App\Http\Controllers\Admin\TalepController;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * TalepIndexEpostaContractTest
 *
 * Verifies that GET /admin/talepler loads formData with canonical 'eposta' column
 * on Kisi model and strictly preserves tenant isolation.
 */
class TalepIndexEpostaContractTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function talep_index_form_data_queries_eposta_and_enforces_tenant_isolation(): void
    {
        (new RoleSeeder())->run();

        $tenant1 = Tenant::firstOrCreate(
            ['id' => 1],
            ['name' => 'Tenant 1', 'domain' => 'tenant1.test', 'is_active' => true]
        );
        $tenant2 = Tenant::firstOrCreate(
            ['id' => 2],
            ['name' => 'Tenant 2', 'domain' => 'tenant2.test', 'is_active' => true]
        );

        $adminTenant1 = User::firstOrCreate(
            ['email' => 'admin_tenant1_test@yalihan.test'],
            ['name' => 'Admin T1', 'password' => bcrypt('secret'), 'tenant_id' => $tenant1->id, 'aktiflik_durumu' => 1]
        );
        $adminTenant1->assignRole('admin');

        $kisiTenant1 = Kisi::create([
            'tenant_id' => $tenant1->id,
            'ad' => 'Ahmet',
            'soyad' => 'Yilmaz',
            'telefon' => '05551112233',
            'eposta' => 'ahmet@example.com',
            'kisi_tipi' => KisiTipi::ALICI,
            'durum' => 'aktif',
        ]);

        $kisiTenant2 = Kisi::create([
            'tenant_id' => $tenant2->id,
            'ad' => 'Mehmet',
            'soyad' => 'Demir',
            'telefon' => '05554445566',
            'eposta' => 'mehmet@example.com',
            'kisi_tipi' => KisiTipi::ALICI,
            'durum' => 'aktif',
        ]);

        $this->actingAs($adminTenant1);
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($tenant1);
        session()->put('tenant_id', $tenant1->id);

        $controller = app(TalepController::class);
        $request = Request::create('/admin/talepler', 'GET');
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode() ?? 200);
        $viewData = $response->getData();
        $this->assertArrayHasKey('kisiler', $viewData);

        $kisiler = $viewData['kisiler'];
        $this->assertTrue($kisiler->contains('id', $kisiTenant1->id));
        $this->assertFalse($kisiler->contains('id', $kisiTenant2->id));

        $foundKisi = $kisiler->firstWhere('id', $kisiTenant1->id);
        $this->assertEquals('ahmet@example.com', $foundKisi->eposta);
    }
}
