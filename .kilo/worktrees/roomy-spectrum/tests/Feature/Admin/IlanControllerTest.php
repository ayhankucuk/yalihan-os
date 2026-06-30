<?php

namespace Tests\Feature\Admin;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class IlanControllerTest extends TestCase
{

    /**
     * Test IlanController index page
     */
    public function test_ilan_controller_index(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/ilanlar');

        $response->assertStatus(200);
    }

    /**
     * Test IlanController index with filters
     */
    public function test_ilan_controller_index_with_filters(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/ilanlar?yayin_durumu=Aktif&min_fiyat=100000&max_fiyat=500000'); // ✅ SAB compliance

        $response->assertStatus(200);
    }

    /**
     * Test IlanController store method
     */
    public function test_ilan_controller_store(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $kategori = IlanKategori::create([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'aktiflik_durumu' => true, // ✅ SAB compliance
            'display_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post('/admin/ilanlar', [
                'baslik' => 'Test İlan',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda', // ✅ SAB compliance
                'alt_kategori_id' => $kategori->id,
            ]);

        // Should redirect or return success
        $response->assertStatus(302); // Redirect after create
    }

    /**
     * Test IlanController store validation
     */
    public function test_ilan_controller_store_validation(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)
            ->post('/admin/ilanlar', []);

        // Should return validation error
        $response->assertStatus(302); // Redirect with validation errors
    }

    /**
     * Test IlanController show method
     */
    public function test_ilan_controller_show(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $ilan = Ilan::create([
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda', // ✅ SAB compliance
        ]);

        $response = $this->actingAs($user)
            ->get("/admin/ilanlar/{$ilan->id}");

        $response->assertStatus(200);
    }

    /**
     * Test IlanController update method
     */
    public function test_ilan_controller_update(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $ilan = Ilan::create([
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda', // ✅ SAB compliance
        ]);

        $response = $this->actingAs($user)
            ->put("/admin/ilanlar/{$ilan->id}", [
                'baslik' => 'Updated İlan',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda', // ✅ SAB compliance
            ]);

        // Should redirect after update
        $response->assertStatus(302);
    }

    /**
     * Test IlanController destroy method
     */
    public function test_ilan_controller_destroy(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $ilan = Ilan::create([
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda', // ✅ SAB compliance
        ]);

        $response = $this->actingAs($user)
            ->delete("/admin/ilanlar/{$ilan->id}");

        // Should redirect after delete
        $response->assertStatus(302);
    }

    /**
     * Test IlanController filter method
     */
    public function test_ilan_controller_filter(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/ilanlar/filter?yayin_durumu=Aktif&min_fiyat=100000'); // ✅ SAB compliance

        // Should return JSON response for AJAX requests
        $response->assertStatus(200);
    }

    /**
     * Test IlanController bulkAction method
     */
    public function test_ilan_controller_bulk_action(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $ilan1 = Ilan::create([
            'baslik' => 'Test İlan 1',
            'fiyat' => 100000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'yayinda', // ✅ SAB compliance
        ]);

        $ilan2 = Ilan::create([
            'baslik' => 'Test İlan 2',
            'fiyat' => 200000,
            'para_birimi' => 'TL',
            'yayin_durumu' => 'Pasif', // ✅ SAB compliance
        ]);

        $response = $this->actingAs($user)
            ->postJson('/admin/ilanlar/bulk-action', [
                'action' => 'activate',
                'ids' => [$ilan1->id, $ilan2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * Test IlanController requires authentication
     */
    public function test_ilan_controller_requires_authentication(): void
    {
        $response = $this->get('/admin/ilanlar');

        // Should redirect to login
        $response->assertStatus(302);
    }
}
