<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Role;
use Tests\TestCase;

/**
 * İlan yayin_durumu Authorization Test Suite
 * 
 * Context7: Ownership kontrolü güvenlik testleri
 * 
 * Test Scenarios:
 * 1. Danışman kendi ilanını toggle edebilir
 * 2. Danışman başkasının ilanını toggle edemez
 * 3. Editör başkasının ilanını değiştiremez
 * 4. Superadmin başkasının ilanını değiştirebilir
 * 5. API publish/unpublish aynı kurala uyar
 * @group skip-until-migration-complete
 */
class IlanYayinDurumuAuthorizationTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'danışman']);
        Role::create(['name' => 'editör']);
    }

    /** @test */
    public function danisman_can_toggle_own_ilan()
    {
        $danismanRole = Role::where('name', 'danışman')->first();
        $danisman = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($danisman)
            ->post(route('admin.ilanlar.yayin.toggle', $ilan));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $ilan->refresh();
        $this->assertEquals('Pasif', $ilan->yayin_durumu);
    }

    /** @test */
    public function danisman_cannot_toggle_others_ilan()
    {
        $danismanRole = Role::where('name', 'danışman')->first();
        $danisman1 = User::factory()->create(['role_id' => $danismanRole->id]);
        $danisman2 = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman2->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($danisman1)
            ->post(route('admin.ilanlar.yayin.toggle', $ilan));
        
        $response->assertStatus(403);
        
        // İlan değişmemeli
        $ilan->refresh();
        $this->assertEquals('Aktif', $ilan->yayin_durumu);
    }

    /** @test */
    public function editor_cannot_toggle_others_ilan()
    {
        $editorRole = Role::where('name', 'editör')->first();
        $danismanRole = Role::where('name', 'danışman')->first();
        
        $editor = User::factory()->create(['role_id' => $editorRole->id]);
        $danisman = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($editor)
            ->post(route('admin.ilanlar.yayin.toggle', $ilan));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_toggle_any_ilan()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $danismanRole = Role::where('name', 'danışman')->first();
        
        $superadmin = User::factory()->create(['role_id' => $superadminRole->id]);
        $danisman = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($superadmin)
            ->post(route('admin.ilanlar.yayin.toggle', $ilan));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function danisman_can_update_own_ilan_yayin_durumu()
    {
        $danismanRole = Role::where('name', 'danışman')->first();
        $danisman = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => 'Taslak',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($danisman)
            ->patch(route('admin.ilanlar.yayin.update', $ilan), [
                'yayin_durumu' => 'yayinda',
            ]);
        
        $response->assertStatus(200);
        
        $ilan->refresh();
        $this->assertEquals('Aktif', $ilan->yayin_durumu);
    }

    /** @test */
    public function danisman_cannot_update_others_ilan_yayin_durumu()
    {
        $danismanRole = Role::where('name', 'danışman')->first();
        $danisman1 = User::factory()->create(['role_id' => $danismanRole->id]);
        $danisman2 = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman2->id,
            'yayin_durumu' => 'Taslak',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($danisman1)
            ->patch(route('admin.ilanlar.yayin.update', $ilan), [
                'yayin_durumu' => 'yayinda',
            ]);
        
        $response->assertStatus(403);
        
        $ilan->refresh();
        $this->assertEquals('Taslak', $ilan->yayin_durumu);
    }

    /** @test */
    public function danisman_cannot_duplicate_others_ilan()
    {
        $danismanRole = Role::where('name', 'danışman')->first();
        $danisman1 = User::factory()->create(['role_id' => $danismanRole->id]);
        $danisman2 = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman2->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($danisman1)
            ->post(route('admin.ilanlar.duplicate', $ilan));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_duplicate_any_ilan()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $danismanRole = Role::where('name', 'danışman')->first();
        
        $superadmin = User::factory()->create(['role_id' => $superadminRole->id]);
        $danisman = User::factory()->create(['role_id' => $danismanRole->id]);
        $ilan = Ilan::create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => 'yayinda',
            'baslik' => 'Test İlan',
            'fiyat' => 100000,
            'para_birimi' => 'TRY',
        ]);
        
        $response = $this->actingAs($superadmin)
            ->post(route('admin.ilanlar.duplicate', $ilan));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
