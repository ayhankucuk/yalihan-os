<?php

namespace Tests\Feature\Api;

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use Tests\TestCase;

/**
 * Phase 17 Safety Net — AIDealPredictorController Contract Test
 *
 * PURPOSE:
 * Kilit: AI Deal Predictor API'sinin dışarıya açtığı `listing_id` sözleşmesini kilitler.
 * Bu endpoint hem API hem de Frontend JS tarafından tüketilmektedir.
 * Migration öncesi ve backward-compatible adapter eklendikten sonra bu test yeşil kalmalıdır.
 *
 * ROUTE: GET /api/v1/ai/deal-predictor?listing_id=123
 * KAYNAK: App\Http\Controllers\Api\V1\AIDealPredictorController::predict
 */
class AIDealPredictorControllerContractTest extends TestCase
{

    private Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ilan = Ilan::factory()->create([
            'yayin_durumu' => 'yayinda',
        ]);

        // YalihanCortex mock: gerçek AI çağrısı yapılmaz, contract testi olduğu için shape odaklı
        $this->mock(YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('predictDeal')->andReturn([
                'success'        => true,
                'listing_id'     => $this->ilan->id,
                'score'          => 78.5,
                'recommendation' => 'Satış olasılığı yüksek.',
                'confidence'     => 0.82,
            ]);
        });
    }

    /**
     * BARIKAT 1: API `listing_id` query parametresini tüketiyor mu?
     * Rename (ilan_id) yapılsa bile dış API `listing_id` kabul etmeye devam etmeli.
     */
    public function test_endpoint_accepts_listing_id_parameter(): void
    {
        $response = $this->getJson(
            "/api/v1/ai/deal-predictor?listing_id={$this->ilan->id}"
        );

        // 422 veya 404 olmamalı — listing_id validation geçmeli
        $response->assertStatus(200);
    }

    /**
     * BARIKAT 2: Response JSON yapısı tam olarak kilitli — tüm kritik anahtarlar korunmalı.
     * AI servis refactor'ı veya Phase 17 rename bu shape'i bozmamalı.
     */
    public function test_response_contains_full_expected_shape(): void
    {
        $response = $this->getJson(
            "/api/v1/ai/deal-predictor?listing_id={$this->ilan->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'listing_id',
            'score',
            'recommendation',
            'confidence',
        ]);
    }


    /**
     * BARIKAT 3: `listing_id` eksikse validation 422 döndürür.
     * Controller'ın `required` kuralı korunuyor mu?
     */
    public function test_validation_fails_when_listing_id_is_missing(): void
    {
        $response = $this->getJson('/api/v1/ai/deal-predictor');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['listing_id']);
    }

    /**
     * BARIKAT 4: Var olmayan listing_id 422 döndürür (exists:ilanlar,id kuralı).
     * Ghost ID'ler sisteme giremez.
     */
    public function test_validation_fails_for_nonexistent_listing_id(): void
    {
        $response = $this->getJson('/api/v1/ai/deal-predictor?listing_id=9999999');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['listing_id']);
    }

    /**
     * BARIKAT 5: YalihanCortex hata dönerse endpoint HTTP 500 kodu döner.
     * Error response contract kilitli.
     */
    public function test_returns_500_when_cortex_fails(): void
    {
        // Override mock to simulate failure
        $this->mock(YalihanCortex::class, function ($mock) {
            $mock->shouldReceive('predictDeal')->andReturn([
                'success' => false,
                'error'   => 'Cortex engine unavailable',
            ]);
        });

        $response = $this->getJson(
            "/api/v1/ai/deal-predictor?listing_id={$this->ilan->id}"
        );

        $response->assertStatus(500);
        $response->assertJsonStructure(['success', 'error']);
        $response->assertJsonPath('success', false);
    }
}
