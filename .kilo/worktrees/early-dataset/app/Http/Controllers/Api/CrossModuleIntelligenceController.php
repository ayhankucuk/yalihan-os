<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Modules\Finans\Models\FinansalIslem;
use App\Services\Intelligence\CrossModuleIntelligenceService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-Module Intelligence API Controller
 * Context7: Modüller Arası Zeka API Endpoint'leri
 */
class CrossModuleIntelligenceController extends Controller
{
    public function __construct(
        private CrossModuleIntelligenceService $intelligence
    ) {}

    /**
     * CRM → İlan: Müşteri için ilan önerileri
     *
     * @param int $kisiId
     * @return JsonResponse
     */
    public function suggestListingsForCustomer(int $kisiId): JsonResponse
    {
        try {
            $kisi = Kisi::findOrFail($kisiId);
            $result = $this->intelligence->suggestListingsForCustomer($kisi);

            return ResponseService::success($result, 'İlan önerileri başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('İlan önerileri oluşturulamadı', $e);
        }
    }

    /**
     * İlan → Finans: Komisyon hesaplama
     *
     * @param int $ilanId
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateCommission(int $ilanId, Request $request): JsonResponse
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);
            $salePrice = $request->input('sale_price', $ilan->fiyat);
            $result = $this->intelligence->calculateCommissionFromSale($ilan, (float) $salePrice);

            return ResponseService::success($result, 'Komisyon başarıyla hesaplandı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Komisyon hesaplanamadı', $e);
        }
    }

    /**
     * Finans → Takım: Görev önceliklendirme
     *
     * @param int $islemId
     * @return JsonResponse
     */
    public function prioritizeTaskByCommission(int $islemId): JsonResponse
    {
        try {
            $islem = FinansalIslem::findOrFail($islemId);
            $result = $this->intelligence->prioritizeTaskByCommission($islem);

            return ResponseService::success($result, 'Görev önceliklendirme başarıyla yapıldı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Görev önceliklendirme yapılamadı', $e);
        }
    }

    /**
     * Takım → CRM: Müşteri skorlama
     *
     * @param int $kisiId
     * @return JsonResponse
     */
    public function scoreCustomerByTasks(int $kisiId): JsonResponse
    {
        try {
            $kisi = Kisi::findOrFail($kisiId);
            $result = $this->intelligence->scoreCustomerByTaskCompletion($kisi);

            return ResponseService::success($result, 'Müşteri skoru başarıyla hesaplandı.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Müşteri skoru hesaplanamadı', $e);
        }
    }

    /**
     * Unified Intelligence: Tüm modüllerden veri toplama
     *
     * @param int $kisiId
     * @return JsonResponse
     */
    public function getUnifiedIntelligence(int $kisiId): JsonResponse
    {
        try {
            $result = $this->intelligence->getUnifiedIntelligence($kisiId);

            return ResponseService::success($result, 'Unified intelligence başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Unified intelligence oluşturulamadı', $e);
        }
    }
}
