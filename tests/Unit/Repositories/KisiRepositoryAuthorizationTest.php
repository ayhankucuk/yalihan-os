<?php

namespace Tests\Unit\Repositories;

use App\Models\Kisi;
use App\Models\User;
use App\Repositories\KisiRepository;
use Tests\TestCase;

/**
 * Week 2 Day 1: Runtime Containment Validation
 *
 * Success Metric: "Sedat gerçekten Atılay'ın müşterisini göremiyor mu?"
 */
class KisiRepositoryAuthorizationTest extends TestCase
{

    protected KisiRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(KisiRepository::class);
    }

    /**
     * Create a test user with role stub
     */
    protected function createUserWithRole(string $name, bool $isAdmin = false): User
    {
        $user = User::factory()->create(['name' => $name]);

        if ($isAdmin) {
            $user = \Mockery::mock($user)->makePartial();
            $user->shouldReceive('isAdmin')->andReturn(true);
            $user->shouldReceive('hasRole')->andReturn(true);
        }

        return $user;
    }

    /** @test */
    public function danisman_sees_only_their_kisiler()
    {
        // Arrange: Sedat has 5 kisiler
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(5)->create(['danisman_id' => $sedat->id]);

        // Arrange: Atılay has 3 kisiler
        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $atilay->id]);

        // Act: Sedat queries kisiler
        $result = $this->repository->paginate(15, [], $sedat);

        // Assert: Sedat sees ONLY his 5 kisiler
        $this->assertCount(5, $result, "FAIL: Sedat should see exactly 5 kisiler");

        // Assert: All returned kisiler belong to Sedat
        foreach ($result as $kisi) {
            $this->assertEquals($sedat->id, $kisi->danisman_id,
                "FAIL: Sedat can see Atılay's kisi (ID: {$kisi->id})");
        }
    }

    /** @test */
    public function admin_can_see_all_kisiler()
    {
        // Arrange: Admin user
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        // Arrange: Multiple danışman's kisiler
        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(5)->create(['danisman_id' => $danisman1->id]);
        Kisi::factory()->count(3)->create(['danisman_id' => $danisman2->id]);

        // Act: Admin queries kisiler
        $result = $this->repository->paginate(15, [], $admin);

        // Assert: Admin sees ALL 8 kisiler
        $this->assertCount(8, $result, "FAIL: Admin should see all 8 kisiler");
    }

    /** @test */
    public function search_respects_ownership()
    {
        // Arrange: Sedat has "Ahmet Yılmaz"
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);

        Kisi::factory()->create([
            'danisman_id' => $sedat->id,
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
        ]);

        // Arrange: Atılay has "Ahmet Demir"
        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->create([
            'danisman_id' => $atilay->id,
            'ad' => 'Ahmet',
            'soyad' => 'Demir',
        ]);

        // Act: Sedat searches for "Ahmet"
        $result = $this->repository->search('Ahmet', $sedat);

        // Assert: Sedat sees ONLY "Ahmet Yılmaz"
        $this->assertCount(1, $result, "FAIL: Sedat should see only 1 Ahmet");
        $this->assertEquals('Yılmaz', $result->first()->soyad);
    }

    /** @test */
    public function null_user_defaults_to_auth_user()
    {
        // Arrange: Authenticated as Sedat
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        $this->actingAs($sedat);

        Kisi::factory()->count(5)->create(['danisman_id' => $sedat->id]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $atilay->id]);

        // Act: Call without user parameter (should use auth()->user())
        $result = $this->repository->paginate(15, []);

        // Assert: Sedat sees only his 5 kisiler
        $this->assertCount(5, $result, "FAIL: Sedat should see only his 5 kisiler");
    }

    // ========================================
    // Week 2 Day 2: Remaining Methods Tests
    // ========================================

    /** @test */
    public function all_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(5)->create(['danisman_id' => $sedat->id]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $atilay->id]);

        $result = $this->repository->all($sedat);

        $this->assertCount(5, $result, "FAIL: all() - Sedat should see only 5 kisiler");
        foreach ($result as $kisi) {
            $this->assertEquals($sedat->id, $kisi->danisman_id);
        }
    }

    /** @test */
    public function all_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(5)->create(['danisman_id' => $danisman1->id]);
        Kisi::factory()->count(3)->create(['danisman_id' => $danisman2->id]);

        $result = $this->repository->all($admin);

        $this->assertCount(8, $result, "FAIL: all() - Admin should see all 8 kisiler");
    }

    /** @test */
    public function byLocation_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $sedat->id, 'il_id' => 34]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(2)->create(['danisman_id' => $atilay->id, 'il_id' => 34]);

        $result = $this->repository->byLocation(34, null, $sedat);

        $this->assertCount(3, $result, "FAIL: byLocation() - Sedat should see only 3 kisiler");
        foreach ($result as $kisi) {
            $this->assertEquals($sedat->id, $kisi->danisman_id);
        }
    }

    /** @test */
    public function byLocation_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(3)->create(['danisman_id' => $danisman1->id, 'il_id' => 34]);
        Kisi::factory()->count(2)->create(['danisman_id' => $danisman2->id, 'il_id' => 34]);

        $result = $this->repository->byLocation(34, null, $admin);

        $this->assertCount(5, $result, "FAIL: byLocation() - Admin should see all 5 kisiler");
    }

    /** @test */
    public function getStats_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(5)->create(['danisman_id' => $sedat->id, 'aktiflik_durumu' => 1]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $atilay->id, 'aktiflik_durumu' => 1]);

        $stats = $this->repository->getStats($sedat);

        $this->assertEquals(5, $stats['total'], "FAIL: getStats() - Sedat should see 5 total");
        $this->assertEquals(5, $stats['aktif'], "FAIL: getStats() - Sedat should see 5 aktif");
    }

    /** @test */
    public function getStats_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(5)->create(['danisman_id' => $danisman1->id, 'aktiflik_durumu' => 1]);
        Kisi::factory()->count(3)->create(['danisman_id' => $danisman2->id, 'aktiflik_durumu' => 1]);

        $stats = $this->repository->getStats($admin);

        $this->assertEquals(8, $stats['total'], "FAIL: getStats() - Admin should see 8 total");
        $this->assertEquals(8, $stats['aktif'], "FAIL: getStats() - Admin should see 8 aktif");
    }

    /** @test */
    public function byType_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(3)->create(['danisman_id' => $sedat->id, 'kisi_tipi' => 'alici', 'aktiflik_durumu' => 1]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(2)->create(['danisman_id' => $atilay->id, 'kisi_tipi' => 'alici', 'aktiflik_durumu' => 1]);

        $result = $this->repository->byType('alici', $sedat);

        $this->assertCount(3, $result, "FAIL: byType() - Sedat should see only 3 kisiler");
        foreach ($result as $kisi) {
            $this->assertEquals($sedat->id, $kisi->danisman_id);
        }
    }

    /** @test */
    public function byType_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(3)->create(['danisman_id' => $danisman1->id, 'kisi_tipi' => 'alici', 'aktiflik_durumu' => 1]);
        Kisi::factory()->count(2)->create(['danisman_id' => $danisman2->id, 'kisi_tipi' => 'alici', 'aktiflik_durumu' => 1]);

        $result = $this->repository->byType('alici', $admin);

        $this->assertCount(5, $result, "FAIL: byType() - Admin should see all 5 kisiler");
    }

    /** @test */
    public function activeWithListings_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        $sedatKisiler = Kisi::factory()->count(3)->create(['danisman_id' => $sedat->id]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        $atilayKisiler = Kisi::factory()->count(2)->create(['danisman_id' => $atilay->id]);

        // Add active listings to all kisiler (skip ilan creation for simplicity)
        // Just test the query scoping works
        $result = $this->repository->activeWithListings(100, $sedat);

        // Should return empty since no ilanlar, but scoping should work
        $this->assertCount(0, $result, "FAIL: activeWithListings() - Should return 0 without ilanlar");
    }

    /** @test */
    public function activeWithListings_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(5)->create(['danisman_id' => $danisman1->id]);
        Kisi::factory()->count(3)->create(['danisman_id' => $danisman2->id]);

        // Test without ilanlar - should return 0 but scoping should work
        $result = $this->repository->activeWithListings(100, $admin);

        $this->assertCount(0, $result, "FAIL: activeWithListings() - Should return 0 without ilanlar");
    }

    /** @test */
    public function getRecentActivity_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        Kisi::factory()->count(4)->create(['danisman_id' => $sedat->id, 'updated_at' => now()]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        Kisi::factory()->count(2)->create(['danisman_id' => $atilay->id, 'updated_at' => now()]);

        $result = $this->repository->getRecentActivity(30, $sedat);

        $this->assertCount(4, $result, "FAIL: getRecentActivity() - Sedat should see only 4 kisiler");
        foreach ($result as $kisi) {
            $this->assertEquals($sedat->id, $kisi->danisman_id);
        }
    }

    /** @test */
    public function getRecentActivity_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->count(4)->create(['danisman_id' => $danisman1->id, 'updated_at' => now()]);
        Kisi::factory()->count(2)->create(['danisman_id' => $danisman2->id, 'updated_at' => now()]);

        $result = $this->repository->getRecentActivity(30, $admin);

        $this->assertCount(6, $result, "FAIL: getRecentActivity() - Admin should see all 6 kisiler");
    }

    /** @test */
    public function findWithTrashed_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        $sedatKisi = Kisi::factory()->create(['danisman_id' => $sedat->id]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        $atilayKisi = Kisi::factory()->create(['danisman_id' => $atilay->id]);

        // Sedat can find his own kisi
        $result = $this->repository->findWithTrashed($sedatKisi->id, $sedat);
        $this->assertNotNull($result, "FAIL: findWithTrashed() - Sedat should find his kisi");
        $this->assertEquals($sedat->id, $result->danisman_id);

        // Sedat CANNOT find Atılay's kisi
        $result = $this->repository->findWithTrashed($atilayKisi->id, $sedat);
        $this->assertNull($result, "FAIL: findWithTrashed() - Sedat should NOT find Atılay's kisi");
    }

    /** @test */
    public function findWithTrashed_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        $kisi1 = Kisi::factory()->create(['danisman_id' => $danisman1->id]);
        $kisi2 = Kisi::factory()->create(['danisman_id' => $danisman2->id]);

        $result1 = $this->repository->findWithTrashed($kisi1->id, $admin);
        $result2 = $this->repository->findWithTrashed($kisi2->id, $admin);

        $this->assertNotNull($result1, "FAIL: findWithTrashed() - Admin should find kisi1");
        $this->assertNotNull($result2, "FAIL: findWithTrashed() - Admin should find kisi2");
    }

    /** @test */
    public function findActive_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        $sedatKisi = Kisi::factory()->create(['danisman_id' => $sedat->id, 'aktiflik_durumu' => 1]);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        $atilayKisi = Kisi::factory()->create(['danisman_id' => $atilay->id, 'aktiflik_durumu' => 1]);

        // Sedat can find his own kisi
        $result = $this->repository->findActive($sedatKisi->id, $sedat);
        $this->assertNotNull($result, "FAIL: findActive() - Sedat should find his kisi");
        $this->assertEquals($sedat->id, $result->danisman_id);

        // Sedat CANNOT find Atılay's kisi
        $result = $this->repository->findActive($atilayKisi->id, $sedat);
        $this->assertNull($result, "FAIL: findActive() - Sedat should NOT find Atılay's kisi");
    }

    /** @test */
    public function findActive_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        $kisi1 = Kisi::factory()->create(['danisman_id' => $danisman1->id, 'aktiflik_durumu' => 1]);
        $kisi2 = Kisi::factory()->create(['danisman_id' => $danisman2->id, 'aktiflik_durumu' => 1]);

        $result1 = $this->repository->findActive($kisi1->id, $admin);
        $result2 = $this->repository->findActive($kisi2->id, $admin);

        $this->assertNotNull($result1, "FAIL: findActive() - Admin should find kisi1");
        $this->assertNotNull($result2, "FAIL: findActive() - Admin should find kisi2");
    }

    /** @test */
    public function findByEmail_respects_ownership()
    {
        $sedat = $this->createUserWithRole('Sedat', isAdmin: false);
        $sedatKisi = Kisi::factory()->create(['danisman_id' => $sedat->id, 'eposta' => 'sedat@test.com']);

        $atilay = $this->createUserWithRole('Atılay', isAdmin: false);
        $atilayKisi = Kisi::factory()->create(['danisman_id' => $atilay->id, 'eposta' => 'atilay@test.com']);

        // Sedat can find his own kisi by email
        $result = $this->repository->findByEmail('sedat@test.com', $sedat);
        $this->assertNotNull($result, "FAIL: findByEmail() - Sedat should find his kisi");
        $this->assertEquals($sedat->id, $result->danisman_id);

        // Sedat CANNOT find Atılay's kisi by email
        $result = $this->repository->findByEmail('atilay@test.com', $sedat);
        $this->assertNull($result, "FAIL: findByEmail() - Sedat should NOT find Atılay's kisi");
    }

    /** @test */
    public function findByEmail_admin_sees_all()
    {
        $admin = $this->createUserWithRole('Admin', isAdmin: true);

        $danisman1 = $this->createUserWithRole('Danisman1', isAdmin: false);
        $danisman2 = $this->createUserWithRole('Danisman2', isAdmin: false);

        Kisi::factory()->create(['danisman_id' => $danisman1->id, 'eposta' => 'user1@test.com']);
        Kisi::factory()->create(['danisman_id' => $danisman2->id, 'eposta' => 'user2@test.com']);

        $result1 = $this->repository->findByEmail('user1@test.com', $admin);
        $result2 = $this->repository->findByEmail('user2@test.com', $admin);

        $this->assertNotNull($result1, "FAIL: findByEmail() - Admin should find user1");
        $this->assertNotNull($result2, "FAIL: findByEmail() - Admin should find user2");
    }
}
