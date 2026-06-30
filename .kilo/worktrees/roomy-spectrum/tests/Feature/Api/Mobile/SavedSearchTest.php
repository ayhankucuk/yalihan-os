<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_saved_search()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'Bodrum Yazlık',
            'criteria' => ['il' => 'Muğla', 'ilce' => 'Bodrum', 'type' => 'yazlik'],
            'notification_frequency' => 'daily',
        ];

        $response = $this->postJson(route('api.mobile.saved-searches.store'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Bodrum Yazlık');

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->user->id,
            'name' => 'Bodrum Yazlık',
            'notification_frequency' => 'daily',
        ]);
    }

    /** @test */
    public function it_can_list_saved_searches()
    {
        Sanctum::actingAs($this->user);

        $this->user->savedSearches()->create([
            'name' => 'Test Search',
            'criteria' => ['il' => 'İzmir'],
            'notification_frequency' => 'off',
        ]);

        $response = $this->getJson(route('api.mobile.saved-searches.index'));

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Test Search');
    }

    /** @test */
    public function it_can_delete_saved_search()
    {
        Sanctum::actingAs($this->user);

        $search = $this->user->savedSearches()->create([
            'name' => 'Test Search',
            'criteria' => ['il' => 'İzmir'],
            'notification_frequency' => 'off',
        ]);

        $response = $this->deleteJson(route('api.mobile.saved-searches.destroy', $search->id));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }
}
