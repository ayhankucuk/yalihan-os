<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\User;
use App\Models\Ilan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class MobileListingTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_view_listing_detail()
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create(['yayin_durumu' => 'yayinda', 'baslik' => 'Test Detay']);

        $response = $this->getJson(route('api.mobile.listings.show', $ilan->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Detay')
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'price', 'description', 'features', 'location', 'images', 'agent', 'is_favorite'
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_missing_listing()
    {
        Sanctum::actingAs($this->user);
        $response = $this->getJson(route('api.mobile.listings.show', 99999));
        $response->assertStatus(404);
    }
}
