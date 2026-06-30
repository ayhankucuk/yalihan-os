<?php

namespace Tests\Feature\Wizard;

use Tests\TestCase;
use App\Models\User;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;

/**
 * @group skip-until-migration-complete
 */
class TemplateResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Assuming admin user exists or creating one
        $this->actingAs(User::factory()->create(['role_id' => 1])); // Admin
    }

    /** @test */
    public function it_resolves_context_for_created_category_and_publication_type()
    {
        // Create Data
        $category = IlanKategori::factory()->create([
            'name' => 'Arsa (Konut/Villa)',
            'slug' => 'arsa-konut-villa',
            'seviye' => 1
        ]);

        $pubType = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık', 'aktiflik_durumu' => 1]
        );

        $response = $this->getJson("/api/v1/wizard/context?alt_kategori_id={$category->id}&junction_id={$pubType->id}");

        $response->assertStatus(200);

        // Check structure within data wrapper if present
        $json = $response->json();

        // Handle wrapped response
        $data = $json['data'] ?? $json; // If wrapped in data
        $success = $data['success'] ?? $json['success'] ?? false;
        $context = $data['context'] ?? $json['context'] ?? null;

        $this->assertTrue($success, 'Response success is false');
        $this->assertNotNull($context, 'Context not found in response');

        // Check content
        $this->assertEquals($category->id, $context['category']['id']);
        $this->assertEquals($pubType->id, $context['yayin_tipi']['id']);

        // Template should be resolved (fallback or valid)
        $this->assertNotNull($context['template']['name']);
    }
}
