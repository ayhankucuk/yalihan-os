<?php

namespace Tests\Feature\Wizard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Features Non-Empty Test
 *
 * P0 Regression Test: Wizard features endpoint boş dönmemeli
 *
 * Context7: WFC-004 - Wizard Feature Non-Empty Guarantee
 *
 * Self-Contained: Test kendi domain seed'ini kurar (CI uyumlu)
 *
 * @group skip-until-migration-complete
 */
class FeaturesNonEmptyTest extends TestCase
{

    protected $arsaKategoriId;
    protected $konutKategoriId;
    protected $arsaSatilikPivotId;
    protected $konutSatilikPivotId;

    /**
     * Setup test environment with minimum domain seed
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1) Kategoriler oluştur
        $this->arsaKategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Arsa & Arazi',
            'slug' => 'arsa',
            'parent_id' => null,
            'seviye' => 0,
            'aktiflik_durumu' => 1,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->konutKategoriId = DB::table('ilan_kategorileri')->insertGetId([
            'name' => 'Konut',
            'slug' => 'konut',
            'parent_id' => null,
            'seviye' => 0,
            'aktiflik_durumu' => 1,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2) Yayın Tipi Şablonları oluştur
        DB::table('yayin_tipi_sablonlari')->updateOrInsert(
            ['slug' => 'satilik'],
            [
                'ad' => 'Satılık',
                'aktiflik_durumu' => 1,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $this->arsaSatilikPivotId = DB::table('yayin_tipi_sablonlari')->where('slug', 'satilik')->value('id');

        $this->konutSatilikPivotId = $this->arsaSatilikPivotId; // Global template is shared now

        // 3) Features oluştur (kritik slug'lar)
        $features = [
            ['name' => 'İmar Durumu', 'slug' => 'imar-durumu', 'type' => 'select'],
            ['name' => 'Alan (m²)', 'slug' => 'alan-m2', 'type' => 'number'],
            ['name' => 'Tapu Durumu', 'slug' => 'tapu-durumu', 'type' => 'select'],
            ['name' => 'KAKS', 'slug' => 'kaks', 'type' => 'number'],
            ['name' => 'TAKS', 'slug' => 'taks', 'type' => 'number'],
        ];

        foreach ($features as $feature) {
            DB::table('features')->insertOrIgnore([
                'name' => $feature['name'],
                'slug' => $feature['slug'],
                'type' => $feature['type'],
                'is_filterable' => 1,
                'is_searchable' => 1,
                'aktiflik_durumu' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4) Feature Assignments oluştur (Arsa için)
        $featureIds = DB::table('features')
            ->whereIn('slug', ['imar-durumu', 'alan-m2', 'tapu-durumu', 'kaks', 'taks'])
            ->pluck('id', 'slug');

        $assignmentOrder = 10;
        foreach (['imar-durumu', 'alan-m2', 'tapu-durumu', 'kaks', 'taks'] as $slug) {
            if (isset($featureIds[$slug])) {
                DB::table('feature_assignments')->insertOrIgnore([
                    'feature_id' => $featureIds[$slug],
                    'assignable_type' => 'App\\Models\\YayinTipiSablonu',
                    'assignable_id' => $this->arsaSatilikPivotId,
                    'is_required' => in_array($slug, ['imar-durumu', 'alan-m2', 'tapu-durumu']) ? 1 : 0,
                    'is_visible' => 1,
                    'display_order' => $assignmentOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assignmentOrder += 10;
            }
        }

        // Konut için de birkaç assignment
        foreach (['alan-m2', 'tapu-durumu'] as $slug) {
            if (isset($featureIds[$slug])) {
                DB::table('feature_assignments')->insertOrIgnore([
                    'feature_id' => $featureIds[$slug],
                    'assignable_type' => 'App\\Models\\YayinTipiSablonu',
                    'assignable_id' => $this->konutSatilikPivotId,
                    'is_required' => 1,
                    'is_visible' => 1,
                    'display_order' => $assignmentOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assignmentOrder += 10;
            }
        }
    }

    /**
     * @test
     * Arsa kategorisi için features endpoint boş dönmemeli
     */
    public function arsa_category_returns_non_empty_features(): void
    {
        // Arrange: Create test admin user with admin role
        $user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@admin.com',
            'role_id' => 1, // Admin role_id (SSOT)
            'aktiflik_durumu' => true,
        ]);

        // Act: Frontend features endpoint çağır
        try {
            $response = $this->actingAs($user)
                ->withoutMiddleware()
                ->withHeader('Accept', 'application/json')
                ->getJson("/api/v1/admin/category/arsa/frontend-features?yayin_tipi_id={$this->arsaSatilikPivotId}");
        } catch (\Exception $e) {
             $this->fail($e->getMessage());
        }

        // Assert: JSON + total_features > 0
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');

        $this->assertArrayHasKey(
            'metadata',
            $data,
            'Response metadata eksik!'
        );

        $this->assertArrayHasKey(
            'total_features',
            $data['metadata'],
            'total_features metadata eksik!'
        );

        $this->assertGreaterThan(
            0,
            $data['metadata']['total_features'],
            'P0 REGRESSION: Arsa kategorisi için özellikler boş döndü! ' .
                'Bu sorun tekrar yaşanmamalıydı. Bekçi kontrol edin.'
        );
    }

    /**
     * @test
     * Konut kategorisi için features endpoint boş dönmemeli
     */
    public function konut_category_returns_non_empty_features(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test2@admin.com',
            'role_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $this->withoutExceptionHandling();
        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->getJson("/api/v1/admin/category/konut/frontend-features?yayin_tipi_id={$this->konutSatilikPivotId}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertGreaterThan(
            0,
            $data['metadata']['total_features'],
            'Konut kategorisi için özellikler boş döndü!'
        );
    }

    /**
     * @test
     * API JSON döndürmeli (HTML değil)
     */
    public function features_endpoint_returns_json_not_html(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test3@admin.com',
            'role_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->getJson("/api/v1/admin/category/arsa/frontend-features?yayin_tipi_id={$this->arsaSatilikPivotId}");

        // Content-Type JSON olmalı
        $response->assertHeader('Content-Type', 'application/json');

        // Response body HTML içermemeli
        $content = $response->getContent();
        $this->assertStringNotContainsString(
            '<!DOCTYPE',
            $content,
            'P0 REGRESSION: API HTML döndü! JSON dönmeli.'
        );
        $this->assertStringNotContainsString(
            '<html',
            $content,
            'P0 REGRESSION: API HTML döndü! JSON dönmeli.'
        );
    }
}
