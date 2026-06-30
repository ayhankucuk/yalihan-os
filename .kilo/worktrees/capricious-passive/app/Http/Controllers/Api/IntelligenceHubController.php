<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * IntelligenceHub API Controller
 * 
 * Context7 Standard: C7-INTELLIGENCE-HUB-API-2026-01-07
 */
class IntelligenceHubController extends Controller
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * İlan sağlık analizi endpoint'i
     * 
     * GET /api/intelligence/listing-health/{ilanId}
     * 
     * @param int $ilanId
     * @return JsonResponse
     */
    public function getListingHealth(int $ilanId): JsonResponse
    {
        try {
            $result = $this->cortex->analyzeListingHealth($ilanId);
            return ResponseService::success($result, 'İlan sağlık analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('İlan sağlık analizi başarısız', $e);
        }
    }

    /**
     * Draft ilan için sağlık analizi (Wizard için)
     * 
     * POST /api/intelligence/draft-health
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDraftHealth(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ilan_data' => 'required|array',
            ]);

            // Draft verilerini geçici bir ilan modeline dönüştür
            $ilanData = $request->input('ilan_data');
            
            // Eğer ilan_id varsa, mevcut ilanı kullan
            if (isset($ilanData['id']) && $ilanData['id']) {
                return $this->getListingHealth($ilanData['id']);
            }

            // Draft için basit analiz (tam analiz için ilan kaydedilmeli)
            return ResponseService::success([
                'message' => 'Draft analizi için ilanı kaydedin',
                'can_analyze' => false,
            ], 'Draft analizi için ilan kaydı gerekli');

        } catch (\Exception $e) {
            return ResponseService::serverError('Draft sağlık analizi başarısız', $e);
        }
    }
}

