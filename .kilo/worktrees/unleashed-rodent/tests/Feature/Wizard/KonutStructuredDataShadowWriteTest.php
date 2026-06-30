<?php

namespace Tests\Feature\Wizard;

use App\Models\Feature;
use App\Models\Ilan;
use App\Models\User;
use Tests\TestCase;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class KonutStructuredDataShadowWriteTest extends TestCase
{
    use \Illuminate\Foundation\Testing\WithoutMiddleware;

    protected $admin;
    protected $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->ilan = Ilan::factory()->create();

        // Seed necessary features for shadow write
        Feature::create(['name' => 'Brüt Alan', 'slug' => 'brut-alan', 'type' => 'number']);
        Feature::create(['name' => 'Net Alan', 'slug' => 'net-alan', 'type' => 'number']);
        Feature::create(['name' => 'Yapım Yılı', 'slug' => 'yapim-yili', 'type' => 'number']);
        Feature::create(['name' => 'İmar Durumu', 'slug' => 'imar-durumu', 'type' => 'select']);
    }

    /** @test */
    public function it_shadow_writes_structured_data_to_feature_assignments()
    {
        // 1. Prepare Payload
        $payload = [
            'brut_m2' => 120,
            'net_m2' => 100,
            'bina_yasi' => 5,
            'tapu_imar' => [
                'imar_durumu' => 'konut',
            ],
            'oda_sayisi' => 3,
            'konut_tipi' => 'Daire', // Required
            'salon_sayisi' => 1,     // Required
            'banyo_sayisi' => 1,     // Required
            'lokasyon' => [          // Required
                'il_id' => 1,
                'ilce_id' => 1,
            ],
            'fiyat' => [             // Required
                'satilik_fiyat' => 1000000,
                'para_birimi' => 'TL',
            ]
        ];

        // 2. Mock 'Konut Satılık' Check (Controller checks structured_data_scope or creates it)
        // Actually controller checks validateStructuredData using scope, but update overrides it?
        // Let's check store method again. It doesn't check 'scope' in store, it sets it.
        // Wait, validateStructuredData DOES check scope. But store() doesn't seem to enforce pre-existing scope,
        // it sets it: 'structured_data_scope' => 'konut_satilik'.

        // 3. Send Request
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.ilanlar.structured-data.konut.store', $this->ilan->id), [
                'structured_data' => $payload
            ]);

        // 4. Assert JSON Persistence (Standard Behavior)
        if ($response->getStatusCode() !== 200) {
            $this->fail('Structured data store returned ' . $response->getStatusCode());
        }
        $response->assertOk();

        $this->ilan->refresh();
        $this->assertEquals('konut_satilik', $this->ilan->structured_data_scope);
        $this->assertEquals(120, $this->ilan->structured_data['brut_m2']);

        // 5. Assert Shadow Write (New Behavior)
        // Check pivot table
        $this->assertTrue($this->ilan->features()->exists(), 'Features relationship should exist');

        $features = $this->ilan->features()->get()->pluck('pivot.value', 'slug');

        // brut-alan -> 120
        $this->assertArrayHasKey('brut-alan', $features);
        $this->assertEquals('120', $features['brut-alan']);

        // net-alan -> 100
        $this->assertArrayHasKey('net-alan', $features);
        $this->assertEquals('100', $features['net-alan']);

        // yapim-yili -> 5 (mapped from bina_yasi)
        $this->assertArrayHasKey('yapim-yili', $features);
        $this->assertEquals('5', $features['yapim-yili']);

        // imar-durumu -> konut
        $this->assertArrayHasKey('imar-durumu', $features);
        $this->assertEquals('konut', $features['imar-durumu']);

        // Skipped check
        $this->assertArrayNotHasKey('oda-sayisi', $features, 'Oda sayısı should not be mapped as feature does not exist');
    }
}
