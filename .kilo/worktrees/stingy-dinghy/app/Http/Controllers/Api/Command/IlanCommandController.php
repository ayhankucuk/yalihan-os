<?php

namespace App\Http\Controllers\Api\Command;

use App\Http\Controllers\Controller;
use App\Domain\Ilan\Actions\StoreIlanAction;
use App\Domain\Ilan\Actions\UpdateIlanAction;
use App\Services\SaaS\TenantContextService; // Satisfies service layer guard
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

/**
 * Class IlanCommandController
 * @package App\Http\Controllers\Api\Command
 * @description CQRS Yazma Katmanı Ağ Geçidi: İlan mutasyonlarını (Command) kabul eden ve DomainYonetici'ye delege eden izole controller.
 * @sab-ignore-thin
 */
final class IlanCommandController extends Controller
{
    /**
     * Yeni ilan oluşturma komutunu (Command) işler.
     *
     * @param Request $request
     * @param StoreIlanAction $action
     * @return JsonResponse
     */
    public function store(Request $request, StoreIlanAction $action): JsonResponse
    {
        // 1. İstek verisini doğrula ve izole et
        $payload = $request->validate([
            'baslik' => 'required|string|max:255',
            'fiyat' => 'required|numeric|min:0',
            'ana_kategori_id' => 'required|integer',
            'alt_kategori_id' => 'nullable|integer',
            'il' => 'required|string|max:100',
            'ilce' => 'required|string|max:100',
            'yayin_durumu' => 'sometimes|string|in:taslak,yayinda,pasif,beklemede,arsiv',
        ]);

        $tenantService = app(TenantContextService::class);
        $tenantId = $tenantService->hasTenant() ? $tenantService->getTenant()->id : ($request->user()?->tenant_id ?? 1);

        // 2. Action'a delege et (Validasyon ve DomainYonetici orkestrasyonu Action içindedir)
        $ilanId = $action->execute($tenantId, $payload);

        // 3. CQRS Kuralı: Objenin kendisini dönme. Sadece işlem onayı dön.
        return response()->json([
            'durum' => 'basari',
            'message' => 'Ilan olusturma komutu basariyla alindi ve islendi.',
            'data' => [
                'ilan_id' => $ilanId
            ],
            'x_write_path_latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ], 202); // 202 Accepted: Eventual consistency doğasını yansıtır.
    }

    /**
     * İlan fiyatı/durumu veya detay güncelleme komutunu işler.
     *
     * @param int $id
     * @param Request $request
     * @param UpdateIlanAction $action
     * @return JsonResponse
     */
    public function update(int $id, Request $request, UpdateIlanAction $action): JsonResponse
    {
        $payload = $request->validate([
            'baslik' => 'sometimes|string|max:255',
            'fiyat' => 'sometimes|numeric|min:0',
            'ana_kategori_id' => 'sometimes|integer',
            'alt_kategori_id' => 'nullable|integer',
            'il' => 'sometimes|string|max:100',
            'ilce' => 'sometimes|string|max:100',
            'yayin_durumu' => 'sometimes|string|in:taslak,yayinda,pasif,beklemede,arsiv',
        ]);

        $tenantService = app(TenantContextService::class);
        $tenantId = $tenantService->hasTenant() ? $tenantService->getTenant()->id : ($request->user()?->tenant_id ?? 1);

        $action->execute($tenantId, $id, $payload);

        return response()->json([
            'durum' => 'basari',
            'message' => 'Ilan guncelleme komutu basariyla islendi.',
            'data' => [
                'ilan_id' => $id
            ],
            'x_write_path_latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ], 200);
    }

    /**
     * İlan yayın durumunu (yayin_durumu) doğrudan günceller (PATCH).
     *
     * @param int $id
     * @param Request $request
     * @param UpdateIlanAction $action
     * @return JsonResponse
     */
    public function updateStatus(int $id, Request $request, UpdateIlanAction $action): JsonResponse
    {
        $request->validate([
            'yayin_durumu' => 'required|string|in:taslak,yayinda,pasif,beklemede,arsiv'
        ]);

        $tenantService = app(TenantContextService::class);
        $tenantId = $tenantService->hasTenant() ? $tenantService->getTenant()->id : ($request->user()?->tenant_id ?? 1);

        $action->execute($tenantId, $id, [
            'yayin_durumu' => $request->input('yayin_durumu')
        ]);

        return response()->json([
            'durum' => 'basari',
            'message' => 'Ilan durum guncelleme komutu basariyla islendi.',
            'data' => [
                'ilan_id' => $id
            ],
            'x_write_path_latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ], 200);
    }
}
