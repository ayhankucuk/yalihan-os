<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_register_a_device()
    {
        $this->withoutExceptionHandling();
        Sanctum::actingAs($this->user);

        $payload = [
            'device_id' => 'device-123',
            'fcm_token' => 'token-abc',
            'platform' => 'ios',
        ];

        $response = $this->postJson(route('api.mobile.device.register'), $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-123',
            'fcm_token' => 'token-abc',
            'platform' => 'ios',
        ]);
    }

    /** @test */
    public function it_can_update_existing_device_token()
    {
        Sanctum::actingAs($this->user);

        // Initial registration
        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => 'device-123',
            'fcm_token' => 'old-token',
            'platform' => 'ios',
        ]);

        $payload = [
            'device_id' => 'device-123',
            'fcm_token' => 'new-token',
            'platform' => 'ios',
        ];

        $response = $this->postJson(route('api.mobile.device.register'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-123',
            'fcm_token' => 'new-token',
        ]);
    }

    /** @test */
    public function it_can_unregister_a_device()
    {
        Sanctum::actingAs($this->user);

        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => 'device-123',
            'fcm_token' => 'token-abc',
            'platform' => 'ios',
        ]);

        $payload = ['device_id' => 'device-123'];

        $response = $this->postJson(route('api.mobile.device.unregister'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-123',
        ]);
    }

    /** @test */
    public function it_can_list_notifications()
    {
        Sanctum::actingAs($this->user);

        // Create notification manually
        $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Hello World'],
            'read_at' => null,
        ]);

        $response = $this->getJson(route('api.mobile.notifications.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'type', 'data', 'read_at', 'human_time']
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        Sanctum::actingAs($this->user);

        $notification = $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Read Me'],
            'read_at' => null,
        ]);

        $response = $this->postJson(route('api.mobile.notifications.read', $notification->id));

        $response->assertStatus(200);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        Sanctum::actingAs($this->user);

        $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => '1'],
            'read_at' => null,
        ]);
        $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => '2'],
            'read_at' => null,
        ]);

        $response = $this->postJson(route('api.mobile.notifications.read-all'));

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }
}
