<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class TakimYonetimiSmokeTest extends TestCase
{
    public function test_takimlar_endpoint_returns_json()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->getJson('/api/takim-yonetimi/takimlar');
        
        // Sadece endpoint var mı ve 200 dönüyor mu onaya yetecek bir kontrol
        $response->assertStatus(200);
    }
}
