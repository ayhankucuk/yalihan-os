<?php

namespace Tests\Unit\Repositories;

use App\Enums\TalepDurumu;
use App\Models\Talep;
use App\Models\User;
use App\Repositories\TalepRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TalepRepositoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected TalepRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(TalepRepository::class);
    }

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

        // Create an active talep
        Talep::factory()->create([
            'danisman_id' => $danisman->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);

        $this->assertNull(auth()->user());

        $results = $this->repository->getTalepler();
        $this->assertCount(0, $results);
    }

    /** @test */
    public function danisman_sees_only_own_talepler()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        Talep::factory()->count(3)->create([
            'danisman_id' => $danisman1->id,
            'talep_durumu' => TalepDurumu::AKTIF->value
        ]);

        Talep::factory()->count(2)->create([
            'danisman_id' => $danisman2->id,
            'talep_durumu' => TalepDurumu::AKTIF->value
        ]);

        $this->actingAs($danisman1);
        $results = $this->repository->getTalepler();

        $this->assertCount(3, $results);
        foreach ($results as $talep) {
            $this->assertEquals($danisman1->id, $talep->danisman_id);
        }
    }

    /** @test */
    public function admin_sees_all_talepler()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $danisman = $this->createUserWithRole('Danışman', 2, false);

        Talep::factory()->count(3)->create([
            'danisman_id' => $danisman->id,
            'talep_durumu' => TalepDurumu::AKTIF->value
        ]);

        Talep::factory()->count(2)->create([
            'danisman_id' => $admin->id,
            'talep_durumu' => TalepDurumu::AKTIF->value
        ]);

        $this->actingAs($admin);
        $results = $this->repository->getTalepler();

        $this->assertCount(5, $results);
    }

    /** @test */
    public function danisman_cannot_update_cross_tenant_talep_returns_404()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        $talep2 = Talep::factory()->create([
            'danisman_id' => $danisman2->id,
            'talep_durumu' => TalepDurumu::AKTIF->value
        ]);

        $this->actingAs($danisman1);

        $this->expectException(ModelNotFoundException::class);
        
        $this->repository->update($talep2->id, ['baslik' => 'Hacked']);
    }

    /** @test */
    public function aggregation_stats_reflect_only_owned_talepler()
    {
        $danisman1 = $this->createUserWithRole('Danışman 1', 1, false);
        $danisman2 = $this->createUserWithRole('Danışman 2', 2, false);

        Talep::factory()->create(['danisman_id' => $danisman1->id, 'talep_durumu' => TalepDurumu::AKTIF->value]);
        Talep::factory()->create(['danisman_id' => $danisman1->id, 'talep_durumu' => TalepDurumu::BEKLEMEDE->value]);

        Talep::factory()->count(3)->create(['danisman_id' => $danisman2->id, 'talep_durumu' => TalepDurumu::AKTIF->value]);

        $this->actingAs($danisman1);
        $stats1 = $this->repository->getSummaryStats();

        $this->assertEquals(2, $stats1['toplam']);
        $this->assertEquals(1, $stats1['aktif']);
        $this->assertEquals(1, $stats1['beklemede']);

        $this->actingAs($danisman2);
        $stats2 = $this->repository->getSummaryStats();

        $this->assertEquals(3, $stats2['toplam']);
        $this->assertEquals(3, $stats2['aktif']);
        $this->assertEquals(0, $stats2['beklemede']);
    }

    /** @test */
    public function admin_aggregation_stats_reflect_all_talepler()
    {
        $admin = $this->createUserWithRole('Admin', 1, true);
        $danisman = $this->createUserWithRole('Danışman 1', 2, false);

        Talep::factory()->count(2)->create(['danisman_id' => $danisman->id, 'talep_durumu' => TalepDurumu::AKTIF->value]);
        Talep::factory()->create(['danisman_id' => $admin->id, 'talep_durumu' => TalepDurumu::AKTIF->value]);

        $this->actingAs($admin);
        $stats = $this->repository->getSummaryStats();

        $this->assertEquals(3, $stats['toplam']);
        $this->assertEquals(3, $stats['aktif']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
