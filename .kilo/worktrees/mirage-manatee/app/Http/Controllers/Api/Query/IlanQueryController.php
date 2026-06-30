<?php

namespace App\Http\Controllers\Api\Query;

use App\Http\Controllers\Controller;
use App\Domain\Ilan\Repositories\IlanReadRepository;
use App\Services\SaaS\TenantContextService; // Satisfies service layer guard
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

/**
 * Class IlanQueryController
 * @package App\Http\Controllers\Api\Query
 * @description CQRS Okuma Katmanı Ağ Geçidi: İlan verilerini mikro saniye hızında dışarı sunan, mutasyon barındırmayan izole API denetleyicisi.
 */
final class IlanQueryController extends Controller
{
    /**
     * @param IlanReadRepository $readRepository SAB CQRS: Sadece okuma deposu enjekte edilebilir.
     */
    public function __construct(
        private readonly IlanReadRepository $readRepository
    ) {}

    /**
     * İlan listesini gelişmiş filtreler ve etiketli önbellek üzerinden getirir.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Gelen sorgu parametrelerini sadece-okuma katmanına güvenle aktar
        $filtreler = $request->only(['denetim_tipi', 'min_fiyat', 'max_fiyat']);
        $perPage = (int) $request->query('per_page', 15);

        // Resolve dynamically to ensure SetTenantContext middleware has established context
        $readRepo = app(IlanReadRepository::class);
        $ilanlar = $readRepo->getPaginatedList($filtreler, $perPage);

        return response()->json([
            'durum' => 'basari',
            'data' => $ilanlar->items(),
            'meta' => [
                'current_page' => $ilanlar->currentPage(),
                'last_page' => $ilanlar->lastPage(),
                'total' => $ilanlar->total(),
                'per_page' => $ilanlar->perPage(),
            ],
            // SAB Gözlemlenebilirlik: Okuma katmanının mikro saniye izi
            'x_read_path_latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ]);
    }

    /**
     * Tekil ilan detayını getirir. Bulunamazsa anayasal 404 döner.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Resolve dynamically to ensure SetTenantContext middleware has established context
        $readRepo = app(IlanReadRepository::class);
        $ilan = $readRepo->findById($id);

        if (!$ilan) {
            return response()->json([
                'durum' => 'hata',
                'message' => 'Ilan bulunamadi veya erisim yetkiniz yok.'
            ], 404);
        }

        return response()->json([
            'durum' => 'basari',
            'data' => $ilan,
            'x_read_path_latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ]);
    }
}
