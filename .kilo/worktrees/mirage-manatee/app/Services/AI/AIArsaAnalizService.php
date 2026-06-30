<?php

namespace App\Services\AI;

use App\Models\AI\AILandPlotAnalysis;
use App\Models\Ilan;
use App\Services\Market\MarketAnalysisService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st*tus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class AIArsaAnalizService
{
    protected string $n8nWebhookUrl;

    protected MarketAnalysisService $marketAnalysisService;

    public function __construct(MarketAnalysisService $marketAnalysisService)
    {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url', '');
        $this->marketAnalysisService = $marketAnalysisService;
    }

    /**
     * Arsa analizi oluştur
     */
    public function generateAnalysis(int $ilanId, int $userId, string $analysisType = 'comprehensive'): AILandPlotAnalysis
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);

            // Market verilerini topla
            $marketData = $this->marketAnalysisService->collectLandPlotData(
                $ilan->il_id,
                $ilan->ilce_id,
                $ilan->mahalle_id
            );

            // n8n webhook'a istek gönder
            $response = Http::timeout(30)->post($this->n8nWebhookUrl.'/ai/arsa-analiz', [
                'ilan_id' => $ilanId,
                'analysis_type' => $analysisType,
                'ilan_data' => [
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'm2' => $ilan->brut_m2,
                    'il_id' => $ilan->il_id,
                    'ilce_id' => $ilan->ilce_id,
                    'mahalle_id' => $ilan->mahalle_id,
                ],
                'market_data' => $marketData,
            ]);

            if (! $response->successful()) {
                throw new \Exception('n8n webhook request failed: '.$response->getStatusCode());
            }

            $aiResponse = $response->json();

            // DB'ye kaydet
            $analysis = AILandPlotAnalysis::create([
                'ilan_id' => $ilanId,
                'analysis_type' => $analysisType,
                'analysis_data' => $aiResponse['analysis_data'] ?? [],
                'recommendations' => $aiResponse['recommendations'] ?? [],
                'market_data' => $marketData,
                'confidence_score' => $aiResponse['confidence_score'] ?? 0,
                'price_score' => $aiResponse['price_score'] ?? null,
                'risk_score' => $aiResponse['risk_score'] ?? null,
                'market_score' => $aiResponse['market_score'] ?? null,
                'suggested_price_min' => $aiResponse['suggested_price_min'] ?? null,
                'suggested_price_max' => $aiResponse['suggested_price_max'] ?? null,
                'current_price' => $ilan->fiyat,
                'ai_model_used' => $aiResponse['ai_model_used'] ?? 'anythingllm',
                'ai_prompt_version' => $aiResponse['ai_prompt_version'] ?? '1.0.0',
                'ai_generated_at' => now(),
                'created_by' => $userId,
            ]);

            Log::info('AI arsa analizi oluşturuldu', [
                'analysis_id' => $analysis->id,
                'ilan_id' => $ilanId,
            ]);

            return $analysis;
        } catch (\Exception $e) {
            Log::error('AI arsa analizi oluşturma hatası', [
                'error' => $e->getMessage(),
                'ilan_id' => $ilanId,
            ]);

            throw $e;
        }
    }
}
