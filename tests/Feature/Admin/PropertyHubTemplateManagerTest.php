<?php

namespace Tests\Feature\Admin;

use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use Tests\TestCase;

class PropertyHubTemplateManagerTest extends TestCase
{
    public function test_template_manager_page_loads_successfully(): void
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

        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik-villa-test'],
            [
                'ad' => 'Satilik Villa',
                'kategori_id' => $kategori->id,
                'aktiflik_durumu' => true,
                'display_order' => 1,
            ]
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.property-hub.templates.index'));

        $response->assertOk();
        $response->assertSee('Master Şablon Yöneticisi');
        $response->assertSee('Satılık Villa');
    }

    public function test_template_edit_query_route_loads_with_template(): void
    {
        $admin = User::factory()->admin()->create();

        $kategori = IlanKategori::firstOrCreate(
            ['slug' => 'arsa-test-hub'],
            [
                'name' => 'Arsa',
                'seviye' => 0,
                'aktiflik_durumu' => true,
            ]
        );

        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'tarla-satilik-test'],
            [
                'ad' => 'Tarla Satilik',
                'kategori_id' => $kategori->id,
                'aktiflik_durumu' => true,
                'display_order' => 1,
            ]
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.property-hub.templates.edit', [
                'kategori_id' => $kategori->id,
                'yayin_tipi_id' => $template->id,
            ]));

        $response->assertOk();
    }
}
