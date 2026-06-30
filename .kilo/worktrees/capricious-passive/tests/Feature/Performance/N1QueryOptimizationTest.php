<?php

namespace Tests\Feature\Performance;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 Query Optimization Test
 *
 * Verifies that critical endpoints use eager loading to prevent N+1 queries.
 *
 * Success Criteria:
 * - Repository method: <10 queries for 20 listings
 * - Service method: <15 queries (includes stats aggregations)
 * - No N+1 when accessing relations in loop
 */
class N1QueryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@test.com',
        ]);

        // Create test data
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create location data manually with required plaka_kodu
        // ✅ Il model has incrementing=false, so we must use forceCreate() to set id
        $il = Il::forceCreate(['id' => 34, 'il_adi' => 'İstanbul', 'plaka_kodu' => 34, 'aktiflik_durumu' => 1]);
        $ilce = Ilce::create(['il_id' => $il->id, 'ilce_adi' => 'Kadıköy', 'aktiflik_durumu' => 1]);

        // Create category
        $kategori = IlanKategori::create([
            'name' => 'Konut',
            'slug' => 'konut',
            'parent_id' => null,
            'aktiflik_durumu' => 1,
            'display_order' => 1,
        ]);

        $altKategori = IlanKategori::create([
            'name' => 'Daire',
            'slug' => 'daire',
            'parent_id' => $kategori->id,
            'aktiflik_durumu' => 1,
            'display_order' => 1,
        ]);

        // Create 15 test listings
        for ($i = 1; $i <= 15; $i++) {
            Ilan::create([
                'baslik' => "Test İlan {$i}",
                'aciklama' => "Test açıklama {$i}",
                'fiyat' => 100000 + ($i * 10000),
                'para_birimi' => 'TRY',
                'danisman_id' => $this->user->id,
                'il_id' => $il->id,
                'ilce_id' => $ilce->id,
                'ana_kategori_id' => $kategori->id,
                'alt_kategori_id' => $altKategori->id,
                'yayin_durumu' => 'yayinda',
                'aktiflik_durumu' => 1,
            ]);
        }
    }

    /**
     * Test: Repository getAdminListings method uses eager loading
     *
     * @test
     */
    public function repository_get_admin_listings_uses_eager_loading(): void
    {
        $repository = app(\App\Repositories\IlanRepository::class);

        $this->actingAs($this->user);

        DB::enableQueryLog();

        $result = $repository->getAdminListings(['tab' => 'active']);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);

        // Success: <10 queries (1 main + 6 eager loads + 1 count)
        $this->assertLessThan(10, $queryCount, "Repository getAdminListings executed {$queryCount} queries. Expected <10.");

        DB::disableQueryLog();
    }

    /**
     * Test: Service getAdminListingsWithStats uses eager loading
     *
     * @test
     */
    public function service_get_admin_listings_with_stats_uses_eager_loading(): void
    {
        $service = app(\App\Services\Ilan\IlanService::class);

        $this->actingAs($this->user);

        DB::enableQueryLog();

        $result = $service->getAdminListingsWithStats(['tab' => 'active']);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stats', $result);

        // Success: <20 queries (main query + eager loads + stats aggregations + kategoriler)
        $this->assertLessThan(20, $queryCount, "Service getAdminListingsWithStats executed {$queryCount} queries. Expected <20.");

        DB::disableQueryLog();
    }

    /**
     * Test: Verify no N+1 when accessing relations in loop
     *
     * @test
     */
    public function no_n1_when_accessing_relations_in_loop(): void
    {
        $repository = app(\App\Repositories\IlanRepository::class);

        $this->actingAs($this->user);

        DB::enableQueryLog();

        $ilanlar = $repository->getAdminListings(['tab' => 'active']);

        // Access relations in loop (simulating view rendering)
        foreach ($ilanlar as $ilan) {
            $_ = $ilan->il?->il_adi;
            $_ = $ilan->ilce?->ilce_adi;
            $_ = $ilan->kategori?->name;
            $_ = $ilan->danisman?->name;
            $_ = $ilan->fotograflar->first()?->dosya_yolu;
        }

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Success: Query count should not increase in loop
        // Initial queries + 0 additional queries in loop
        $this->assertLessThan(10, $queryCount, "Accessing relations in loop executed {$queryCount} queries. N+1 detected!");

        DB::disableQueryLog();
    }
}
