<?php

namespace Tests\Feature\Api;

use App\Models\Ilan;
use App\Modules\Auth\Models\Role;
use App\Models\User;
use App\Services\Admin\ReportingService;
use Tests\TestCase;

/**
 * Phase 17 Safety Net — ReportingController Contract Test
 *
 * PURPOSE:
 * Kilit: API'nin dışarıya açtığı `listing_id` sözleşmesini kilitler.
 * Migration (listing_id -> ilan_id) çalışmadan ÖNCE bu test yeşil olmalıdır.
 * Migration sonrası da backward-compatible adapter'ın çalıştığı bu test ile kanıtlanır.
 *
 * DOSYA: routes/api/v1/admin.php → GET /api/v1/admin/reporting/metrics
 * KAYNAK: App\Http\Controllers\Api\Admin\ReportingController::getMetrics
 */
class ReportingControllerContractTest extends TestCase
{

    private Ilan $ilan;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['name' => 'admin']);
        $this->user = User::factory()->create([
            'role_id' => 1,
            'email_verified_at' => now(),
        ]);

        // Gerçek bir Ilan kaydı oluştur (prod'daki veriyi simüle eder)
        $this->ilan = Ilan::factory()->create([
            'yayin_durumu' => 'yayinda',
        ]);

        // ReportingService'i partial mock yap — DB çağrılarını gerçek yapar, metric hesaplamaları mock'lanır
        $this->mock(ReportingService::class, function ($mock) {
            $mock->shouldReceive('calculateOccupancy')->andReturn(85.5);
            $mock->shouldReceive('calculateADR')->andReturn(1250.0);
            $mock->shouldReceive('calculateRevPAR')->andReturn(1062.5);
        });
    }

    /**
     * BARIKAT 1: API `listing_id` query parametresini hâlâ kabul ediyor mu?
     * Renamed olan alan DB'de (ilan_id) bile olsa, dış contract bozulmamalı.
     */
    public function test_api_accepts_listing_id_as_query_parameter(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/reporting/metrics?listing_id={$this->ilan->id}&start_date=2026-01-01&end_date=2026-03-01"
        );

        // 422 olmamalı — listing_id validation geçmeli
        $response->assertStatus(200);
    }

    /**
     * BARIKAT 2: Response JSON şeması kilitli — listing_id response'ta mevcut olmalı.
     * Bu test, rename sonrası response'taki anahtar adının değişip değişmediğini yakalar.
     */
    public function test_response_json_shape_contains_listing_id(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/reporting/metrics?listing_id={$this->ilan->id}&start_date=2026-01-01&end_date=2026-03-01"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'listing_id',
                'period' => ['start', 'end'],
                'metrics' => ['occupancy_rate', 'adr', 'revpar', 'currency'],
            ],
        ]);
    }

    /**
     * BARIKAT 3: listing_id eksikse 422 döner — Contract korunuyor mu?
     */
    public function test_api_returns_422_when_listing_id_missing(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/admin/reporting/metrics?start_date=2026-01-01&end_date=2026-03-01'
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.listing_id', fn($errors) => count($errors) > 0);
    }

    /**
     * BARIKAT 4: Var olmayan bir listing_id verilirse 422 döner.
     * Adapter eklenirse bile hayalet ID geçemez.
     */
    public function test_api_returns_422_for_nonexistent_listing_id(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/admin/reporting/metrics?listing_id=9999999&start_date=2026-01-01&end_date=2026-03-01'
        );

        $response->assertStatus(422);
    }

    /**
     * BARIKAT 5: Dönüş değerlerinin listing_id değeri doğru mu?
     * Frontend'in kullandığı data.listing_id gerçek ID ile eşleşmeli.
     */
    public function test_response_listing_id_matches_requested_id(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/reporting/metrics?listing_id={$this->ilan->id}&start_date=2026-01-01&end_date=2026-03-01"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.listing_id', $this->ilan->id);
    }
}
