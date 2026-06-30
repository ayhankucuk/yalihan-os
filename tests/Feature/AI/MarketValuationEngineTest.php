<?php

namespace Tests\Feature\AI;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\MarketValuationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketValuationEngineTest extends TestCase
{
    protected MarketValuationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('market_listings')) {
            Schema::create('market_listings', function (Blueprint $table) {
                $table->id();
                $table->string('location_il');
                $table->string('location_ilce')->nullable();
                $table->string('location_mahalle')->nullable();
                $table->integer('m2_brut')->default(0);
                $table->decimal('price', 15, 2)->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->timestamp('ilan_tarihi')->nullable();
                $table->timestamps();
            });
        }

        $this->service = new MarketValuationService;
    }

    /**
     * Test the full engine pipeline
     */
    public function test_engine_valuation_calculation()
    {
        // Add fake comparables (5 items to use IQR correctly)
        DB::table('market_listings')->insert([
            ['location_il' => 'TestIl', 'location_ilce' => 'TestIlce', 'location_mahalle' => 'TestMahalle', 'm2_brut' => 100, 'price' => 1000000, 'is_active' => 1, 'ilan_tarihi' => now()->subDays(5)],
            ['location_il' => 'TestIl', 'location_ilce' => 'TestIlce', 'location_mahalle' => 'TestMahalle', 'm2_brut' => 100, 'price' => 1020000, 'is_active' => 1, 'ilan_tarihi' => now()->subDays(12)],
            ['location_il' => 'TestIl', 'location_ilce' => 'TestIlce', 'location_mahalle' => 'TestMahalle', 'm2_brut' => 100, 'price' => 1100000, 'is_active' => 1, 'ilan_tarihi' => now()->subDays(15)],
            ['location_il' => 'TestIl', 'location_ilce' => 'TestIlce', 'location_mahalle' => 'TestMahalle', 'm2_brut' => 100, 'price' => 1050000, 'is_active' => 1, 'ilan_tarihi' => now()->subDays(10)],
            ['location_il' => 'TestIl', 'location_ilce' => 'TestIlce', 'location_mahalle' => 'TestMahalle', 'm2_brut' => 100, 'price' => 9000000, 'is_active' => 1, 'ilan_tarihi' => now()->subDays(2)], // Outlier
        ]);

        $query = [
            'il' => 'TestIl',
            'ilce' => 'TestIlce',
            'mahalle' => 'TestMahalle',
            'm2' => 100,
            'asset_type' => 'Konut',
        ];

        $response = $this->service->evaluateQuery($query);

        $this->assertEquals(true, $response['is_success']);

        $data = $response['data'];

        // Unit prices: 10000, 10200, 10500, 11000, 90000
        // filtered: 10000, 10200, 10500, 11000
        // Median of 4 elements: (10200 + 10500) / 2 = 10350
        $this->assertEquals(10350, $data['median_m2_price']);

        // Estimated Value = 10350 * 100
        $this->assertEquals(1035000, $data['estimated_value']);

        $this->assertEquals(4, $data['comparable_count']);

        $this->assertEquals('HIGH', $data['liquidity_score']);

        $this->assertGreaterThan(0, $data['confidence_score']);
        $this->assertLessThanOrEqual(100, $data['confidence_score']);

        // Check CQRS projection insertion
        $this->assertDatabaseHas('market_valuation_reports', [
            'location_il' => 'TestIl',
            'estimated_value' => 1035000,
        ]);

        // Cleanup
        DB::table('market_listings')->where('location_il', 'TestIl')->delete();
        DB::table('market_valuation_reports')->where('location_il', 'TestIl')->delete();
    }

    /**
     * Test Controller fetching via API
     */
    public function test_api_controller_fetches_report()
    {
        // Add fake comparables (Need >=4 for stable tests)
        DB::table('market_listings')->insert([
            ['location_il' => 'ApiIl', 'location_ilce' => 'ApiIlce', 'location_mahalle' => 'ApiMahalle', 'm2_brut' => 100, 'price' => 2000000, 'is_active' => 1, 'ilan_tarihi' => now()],
            ['location_il' => 'ApiIl', 'location_ilce' => 'ApiIlce', 'location_mahalle' => 'ApiMahalle', 'm2_brut' => 100, 'price' => 2100000, 'is_active' => 1, 'ilan_tarihi' => now()],
            ['location_il' => 'ApiIl', 'location_ilce' => 'ApiIlce', 'location_mahalle' => 'ApiMahalle', 'm2_brut' => 100, 'price' => 1950000, 'is_active' => 1, 'ilan_tarihi' => now()],
            ['location_il' => 'ApiIl', 'location_ilce' => 'ApiIlce', 'location_mahalle' => 'ApiMahalle', 'm2_brut' => 100, 'price' => 2050000, 'is_active' => 1, 'ilan_tarihi' => now()],
        ]);

        $tenant = Tenant::create(['name' => 'Advisor Tenant', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/advisor/valuation/query', [
            'il' => 'ApiIl',
            'ilce' => 'ApiIlce',
            'mahalle' => 'ApiMahalle',
            'm2' => 100,
            'asset_type' => 'Arsa',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'estimated_value',
                'median_m2_price',
                'price_range_low',
                'price_range_high',
                'market_trend',
                'liquidity_score',
                'confidence_score',
                'comparable_count',
            ],
        ]);

        // Cleanup
        DB::table('market_listings')->where('location_il', 'ApiIl')->delete();
        DB::table('market_valuation_reports')->where('location_il', 'ApiIl')->delete();
    }
}
