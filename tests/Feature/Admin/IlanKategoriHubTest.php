<?php

namespace Tests\Feature\Admin;

use App\Models\IlanKategori;
use App\Models\User;
use App\Services\Ilan\IlanKategoriService;
use Mockery;
use Tests\TestCase;

class IlanKategoriHubTest extends TestCase
{
    public function test_kategori_hub_page_loads_with_health_diagnostics(): void
    {
        $admin = User::factory()->admin()->create();

        $kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut-test-hub'],
            [
                'name' => 'Konut',
                'seviye' => 0,
                'aktiflik_durumu' => true,
            ]
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.ilan-kategorileri.index'));

        $response->assertOk();
        $response->assertSee('Kategori Yapılandırma Merkezi');
        $response->assertSee('Eksikleri Gör');
        $response->assertSee('Şablon Özellik Havuzu');
        $response->assertDontSee('Şablon İstatistik Servisi Uyarısı');
    }

    public function test_kategori_hub_renders_warning_banner_and_tehis_bekliyor_when_template_stats_error_occurs(): void
    {
        $admin = User::factory()->admin()->create();

        $kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut-error-test-hub'],
            [
                'name' => 'Konut',
                'seviye' => 0,
                'aktiflik_durumu' => true,
            ]
        );

        $mockService = Mockery::mock(IlanKategoriService::class);
        $mockService->shouldReceive('getDashboardData')
            ->once()
            ->andReturn([
                'parents' => collect([$kategori]),
                'children' => collect([]),
                'stats' => [
                    'toplam' => 1,
                    'ana_kategoriler' => 1,
                    'alt_kategoriler' => 0,
                    'aktif' => 1,
                    'pasif' => 0,
                    'bugun_eklenen' => 0,
                ],
                'ana_kategori_counts' => [],
                'alt_kategori_counts' => [],
                'yayin_tipi_counts' => [],
                'ust_kategoriler' => collect([]),
                'template_stats' => [],
                'template_stats_error' => true, // Simulating service error
            ]);

        $this->app->instance(IlanKategoriService::class, $mockService);

        $response = $this->actingAs($admin)
            ->get(route('admin.ilan-kategorileri.index'));

        $response->assertOk();
        $response->assertSee('Şablon İstatistik Servisi Uyarısı');
        $response->assertSee('Teşhis Bekliyor');
    }
}
