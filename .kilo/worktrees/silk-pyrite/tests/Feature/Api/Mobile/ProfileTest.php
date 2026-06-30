<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_show_profile()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.mobile.profile.show'));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.name', $this->user->name);
    }

    /** @test */
    public function it_can_update_profile()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'New Name',
            'phone' => '05551234567',
            'bio' => 'Test bio',
        ];

        $response = $this->putJson(route('api.mobile.profile.update'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'New Name',
            'telefon' => '05551234567',
            'bio' => 'Test bio',
        ]);
    }

    /** @test */
    public function it_can_update_profile_photo()
    {
        $this->withoutExceptionHandling();
        Sanctum::actingAs($this->user);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson(route('api.mobile.profile.photo'), [
            'photo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['profile_photo_url']]);

        $this->assertNotNull($this->user->fresh()->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists($this->user->fresh()->profile_photo_path));
    }
}
