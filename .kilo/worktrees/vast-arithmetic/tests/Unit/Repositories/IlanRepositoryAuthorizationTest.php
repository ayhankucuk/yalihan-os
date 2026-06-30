<?php

namespace Tests\Unit\Repositories;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\User;
use App\Repositories\IlanRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class IlanRepositoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected IlanRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // Since we are not testing IlanCrudService or YalihanLifecycle here directly, 
        // we can just resolve the repository from the container which handles dependencies.
        $this->repository = $this->app->make(IlanRepository::class);
    }

    /**
     * Create a mocked user with a specific role for testing without hitting DB roles
     */
    protected function createUserWithRole(string $name, int $id, bool $isAdmin = false): User
    {
        $user = User::factory()->create(['id' => $id, 'name' => $name]);

        if ($isAdmin) {
            $user = Mockery::mock($user)->makePartial();
            $user->shouldReceive('isAdmin')->andReturn(true);
            $user->shouldReceive('hasRole')->andReturn(true);
        }

        return $user;
    }

    /** @test */
    public function null_user_sees_nothing_deterministic_fail()
    {
        $danisman = User::factory()->create();

        // Create an active listing
        Ilan::factory()->create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'aktiflik_durumu' => 1
        ]);

        // Null user is represented by auth()->user() returning null (default when not actingAs)
        $this->assertNull(auth()->user());

        $results = $this->repository->active();
        
        // Should return 0 items because of null user block
        $this->assertCount(0, $results);
    }

    /** @test */
    public function danisman_sees_only_own_ilanlar()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        // Danışman 1's listings
        Ilan::factory()->count(3)->create([
            'danisman_id' => $danisman1->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value
        ]);

        // Danışman 2's listings
        Ilan::factory()->count(2)->create([
            'danisman_id' => $danisman2->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value
        ]);

        // Act as Danışman 1
        $this->actingAs($danisman1);
        $results = $this->repository->active();

        $this->assertCount(3, $results);
        foreach ($results as $ilan) {
            $this->assertEquals($danisman1->id, $ilan->danisman_id);
        }
    }

    /** @test */
    public function admin_sees_all_ilanlar()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $danisman = $this->createUserWithRole('Danışman', 2, false);

        Ilan::factory()->count(3)->create([
            'danisman_id' => $danisman->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value
        ]);

        Ilan::factory()->count(2)->create([
            'danisman_id' => $admin->id, // Admin can also have listings
            'yayin_durumu' => IlanDurumu::YAYINDA->value
        ]);

        // Act as Admin
        $this->actingAs($admin);
        $results = $this->repository->active();

        // Admin sees all 5 listings
        $this->assertCount(5, $results);
    }

    /** @test */
    public function danisman_cannot_update_cross_tenant_ilan_returns_404()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        $ilan2 = Ilan::factory()->create([
            'danisman_id' => $danisman2->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value
        ]);

        $this->actingAs($danisman1);

        $this->expectException(ModelNotFoundException::class);
        
        // This should throw ModelNotFoundException (which translates to 404 in HTTP context)
        $this->repository->update($ilan2->id, ['baslik' => 'Hacked']);
    }

    /** @test */
    public function aggregation_stats_reflect_only_owned_ilanlar()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        // Danışman 1 has 1 active, 1 draft
        Ilan::factory()->create(['danisman_id' => $danisman1->id, 'yayin_durumu' => IlanDurumu::YAYINDA->value, 'fiyat' => 1000]);
        Ilan::factory()->create(['danisman_id' => $danisman1->id, 'yayin_durumu' => IlanDurumu::TASLAK->value, 'fiyat' => 500]);

        // Danışman 2 has 3 active
        Ilan::factory()->count(3)->create(['danisman_id' => $danisman2->id, 'yayin_durumu' => IlanDurumu::YAYINDA->value, 'fiyat' => 2000]);

        $this->actingAs($danisman1);
        $stats1 = $this->repository->getStats();

        $this->assertEquals(2, $stats1['total']);
        $this->assertEquals(1, $stats1['aktif']);
        $this->assertEquals(1, $stats1['draft']);
        $this->assertEquals(1000, $stats1['avg_price']);

        $this->actingAs($danisman2);
        $stats2 = $this->repository->getStats();

        $this->assertEquals(3, $stats2['total']);
        $this->assertEquals(3, $stats2['aktif']);
        $this->assertEquals(0, $stats2['draft']);
        $this->assertEquals(2000, $stats2['avg_price']);
    }

    /** @test */
    public function admin_aggregation_stats_reflect_all_ilanlar()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $danisman = $this->createUserWithRole('Danışman 1', 2, false);

        // Danışman has 2 active
        Ilan::factory()->count(2)->create(['danisman_id' => $danisman->id, 'yayin_durumu' => IlanDurumu::YAYINDA->value]);
        // Admin has 1 active
        Ilan::factory()->create(['danisman_id' => $admin->id, 'yayin_durumu' => IlanDurumu::YAYINDA->value]);

        $this->actingAs($admin);
        $stats = $this->repository->getStats();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(3, $stats['aktif']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
