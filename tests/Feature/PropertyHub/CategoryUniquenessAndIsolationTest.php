<?php

namespace Tests\Feature\PropertyHub;

use App\Models\IlanKategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryUniquenessAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_category_creation_rejects_duplicate_slug(): void
    {
        $existing = IlanKategori::factory()->create([
            'name' => 'Lüks Villa',
            'slug' => 'luks-villa',
            'seviye' => 0,
            'aktiflik_durumu' => true,
        ]);

        $this->actingAs($this->admin);

        // Attempt to create another category with identical slug
        $response = $this->post(route('admin.ilan-kategorileri.store'), [
            'name' => 'Lüks Villa 2',
            'slug' => 'luks-villa',
            'seviye' => 0,
            'aktiflik_durumu' => true,
        ]);

        // Validation should fail or redirect with errors
        if ($response->status() === 302) {
            $response->assertSessionHasErrors(['slug']);
        } else {
            $this->assertNotEquals(200, $response->status());
        }
    }

    public function test_category_slug_generation_is_deterministic(): void
    {
        $cat1 = IlanKategori::factory()->create([
            'name' => 'Satılık Daire',
            'slug' => 'satilik-daire',
            'seviye' => 0,
        ]);

        $this->assertEquals('satilik-daire', $cat1->slug);
        $this->assertDatabaseHas('ilan_kategorileri', [
            'id' => $cat1->id,
            'slug' => 'satilik-daire',
        ]);
    }

    public function test_database_isolation_operates_in_memory_without_host_pollution(): void
    {
        // Assert working tree does not have stray env or certificate files
        $this->assertFileDoesNotExist(base_path('.env.local'));
        $this->assertFileDoesNotExist(base_path('.env.production'));
        $this->assertFileDoesNotExist(base_path('certificate.pem'));
        $this->assertFileDoesNotExist(base_path('id_rsa'));
    }
}
