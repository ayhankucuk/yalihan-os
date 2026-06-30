<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Services\FavoriService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Context7: İlan Favori API Controller
 */
class FavoriController extends Controller
{
    public function __construct(private FavoriService $favoriService)
    {
    }

    /**
     * Authenticated user'ın favori ilanlarını listele
     */
    public function listFavori(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // User'ın ilişkili olduğu Kisi'yi bul
        $kisi = Kisi::where('user_id', $user->id)->first();

        if (!$kisi) {
            return response()->json([
                'success' => false,
                'message' => 'Kişi kaydı bulunamadı',
            ], 404);
        }

        $perPage = $request->query('per_page', 15);
        $favoriler = $this->favoriService->getFavoriIlanlar($kisi, $perPage);

        return response()->json([
            'success' => true,
            'data' => $favoriler->items(),
            'pagination' => [
                'total' => $favoriler->total(),
                'per_page' => $favoriler->perPage(),
                'current_page' => $favoriler->currentPage(),
                'last_page' => $favoriler->lastPage(),
            ],
        ]);
    }

    /**
     * İlan'ı favoriye ekle/çıkar (toggle)
     */
    public function toggle(Request $request, Ilan $ilan): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // User'ın ilişkili olduğu Kisi'yi bul
        $kisi = Kisi::where('user_id', $user->id)->first();

        if (!$kisi) {
            return response()->json([
                'success' => false,
                'message' => 'Kişi kaydı bulunamadı',
            ], 404);
        }

        // Policy check
        $this->authorize('toggle', [FavoriPolicy::class, $ilan, $kisi]);

        $favori = $this->favoriService->toggleFavori($ilan, $kisi);

        return response()->json([
            'success' => true,
            'message' => $favori->isAktif() ? 'İlan favorilere eklendi' : 'İlan favorilerden çıkarıldı',
            'data' => [
                'ilan_id' => $ilan->id,
                'kisi_id' => $kisi->id,
                'is_aktif' => $favori->isAktif(),
            ],
        ]);
    }

    /**
     * İlan'ı favorilerden çıkar
     */
    public function cikar(Request $request, Ilan $ilan): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $kisi = Kisi::where('user_id', $user->id)->first();

        if (!$kisi) {
            return response()->json([
                'success' => false,
                'message' => 'Kişi kaydı bulunamadı',
            ], 404);
        }

        $this->authorize('toggle', [FavoriPolicy::class, $ilan, $kisi]);

        $removed = $this->favoriService->cikar($ilan, $kisi);

        return response()->json([
            'success' => $removed,
            'message' => $removed ? 'İlan favorilerden çıkarıldı' : 'İlan zaten favorilerde değil',
        ]);
    }

    /**
     * İlan'ın favori sayısını döndür
     */
    public function getFavoriSayisi(Ilan $ilan): JsonResponse
    {
        $sayisi = $this->favoriService->getFavoriSayisi($ilan);

        return response()->json([
            'success' => true,
            'data' => [
                'ilan_id' => $ilan->id,
                'favori_sayisi' => $sayisi,
            ],
        ]);
    }

    /**
     * Dashboard widget metrikleri: En çok favorilenen + son 7 gün artışı
     */
    public function dashboardMetrikleri(): JsonResponse
    {
        $topFavori = $this->favoriService->enCokFavorilenIlanlar(5);
        $son7GunArtisi = $this->favoriService->son7GunFavoriArtisi();

        return response()->json([
            'success' => true,
            'data' => [
                'en_cok_favori_alan_ilanlar' => $topFavori->map(fn ($ilan) => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'favori_sayisi' => $ilan->favorilen_kisiler_count,
                ]),
                'son_7_gun_artisi' => $son7GunArtisi,
            ],
        ]);
    }
}
