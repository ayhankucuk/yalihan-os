<?php

namespace Tests\Feature\Security;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Role;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class DebugDuplicateTest extends TestCase
{

    public function test_debug_duplicate_500()
    {
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'danışman']);
        
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
        
        if ($response->getStatusCode() !== 200) {
            $this->fail('Duplicate endpoint returned ' . $response->getStatusCode() . ': ' . $response->getContent());
        }
        
        $response->assertOk();
    }
}
