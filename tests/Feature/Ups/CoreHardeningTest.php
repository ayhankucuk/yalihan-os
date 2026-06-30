<?php

namespace Tests\Feature\Ups;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\UpsTemplate;
use App\Models\YayinTipi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class CoreHardeningTest extends TestCase
{

    protected $admin;
    protected $junction;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure users table exists for admin
        $this->admin = User::firstOrCreate([
            'email' => 'admin@yalihan.com'
        ], [
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role_id' => 1
        ]);

        $kategori = IlanKategori::factory()->create(['name' => 'Test Kat', 'slug' => 'test-kat']);
        $yayinTipi = YayinTipi::firstOrCreate(['slug' => 'satilik'], ['name' => 'Satılık']);

        $this->junction = YayinTipiSablonu::firstOrCreate([
            'slug' => 'satilik'
        ], [
            'ad' => 'Satılık',
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id
        ]);

        // Ensure the junction has the right IDs if it was already existing
        $this->junction->update([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id
        ]);
    }





    /** @test */
    public function it_prevents_updating_sealed_template()
    {
        // Create sealed template
        $template = UpsTemplate::create([
            'yayin_tipi_sablonu_id' => $this->junction->id,
            'kategori_id' => 1,
            'yayin_tipi_id' => $this->junction->id,
            'template_json' => ['foo' => 'bar'],
            'template_version' => 1,
            'sealed_at' => now(), // SEALED
            'template_hash' => 'hash1',
            'aktiflik_durumu' => true
        ]);

        // Attempt update directly via model
        $template->update(['template_hash' => 'hacked']);

        // Assert no change
        $this->assertEquals('hash1', $template->fresh()->template_hash);
    }

    /** @test */
    public function it_validates_conflicting_fields_in_store_structure()
    {
        $this->actingAs($this->admin);

        $payload = [
            'junction_id' => $this->junction->id,
            'ups_json' => [
                'zorunlu_alanlar' => [1],
                'opsiyonel_alanlar' => [1], // Conflict!
            ]
        ];

        $response = $this->postJson(route('admin.property-hub.templates.ai-import'), $payload);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        // Check message content roughly
        $this->assertStringContainsString('Çakışma', $response->json('message'));
    }

    /** @test */
    public function it_canonicalizes_json_for_deterministic_hashing()
    {
        $this->actingAs($this->admin);

        // Seed features
        DB::table('features')->insert([
            ['id' => 1, 'name' => 'Feature A', 'slug' => 'feature-a', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Feature B', 'slug' => 'feature-b', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Payload 1: Order A
        $payload1 = [
            'junction_id' => $this->junction->id,
            'ups_json' => [
                'zorunlu_alanlar' => [2, 1], // IDs
                'opsiyonel_alanlar' => []
            ]
        ];

        // Payload 2: Order B (should result in same hash)
        $payload2 = [
            'junction_id' => $this->junction->id,
            'ups_json' => [
                'zorunlu_alanlar' => [1, 2], // IDs
                'opsiyonel_alanlar' => []
            ]
        ];

        // Store first
        $response = $this->actingAs($this->admin)->postJson(route('admin.property-hub.templates.ai-import'), $payload1);

        $response->assertOk();
        $hash1 = UpsTemplate::first()->template_hash;

        // Cleanup
        UpsTemplate::truncate();

        // Store second
        $this->actingAs($this->admin)->postJson(route('admin.property-hub.templates.ai-import'), $payload2)->assertOk();
        $hash2 = UpsTemplate::first()->template_hash;

        $this->assertEquals($hash1, $hash2, 'Hashes must be identical regardless of input sequence');
    }
}
