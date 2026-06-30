<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AIService;
use App\Services\ListingNavigationService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;

/**
 * Listing Navigation API Controller
 *
 * Context7: Listing navigation API endpoints
 * - Get previous/next listings
 * - Get similar listings
 * - AI-powered navigation suggestions
 */
class ListingNavigationController extends Controller
{
    use ValidatesApiRequests;

    protected ListingNavigationService $navigationService;

    protected AIService $aiService;

    public function __construct(ListingNavigationService $navigationService, AIService $aiService)
    {
        $this->navigationService = $navigationService;
        $this->aiService = $aiService;
    }

    /**
     * Get navigation for a listing
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNavigation(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (optional validation)
        $validated = $this->validateRequestFlexible($request, [
            'mode' => 'sometimes|in:default,category,location',
            'kategori_id' => 'sometimes|exists:ilan_kategorileri,id',
            'yayin_durumu' => 'sometimes|string',
            'il_id' => 'sometimes|exists:iller,id',
            'ilce_id' => 'sometimes|exists:ilceler,id',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($ilanId);

            $mode = $request->input('mode', 'default'); // default, category, location
            $filters = $request->only(['kategori_id', 'yayin_durumu', 'il_id', 'ilce_id']);

            $navigation = match ($mode) {
                'category' => $this->navigationService->getByCategory($ilan),
                'location' => $this->navigationService->getByLocation($ilan),
                default => $this->navigationService->getNavigation($ilan, $filters)
            };

            return ResponseService::success([
                'navigation' => [
                    'previous' => $navigation['previous'] ? [
                        'id' => $navigation['previous']->id,
                        'baslik' => $navigation['previous']->baslik,
                        'fiyat' => $navigation['previous']->fiyat,
                        'para_birimi' => $navigation['previous']->para_birimi,
                        'url' => route('ilanlar.show', $navigation['previous']->id),
                    ] : null,
                    'next' => $navigation['next'] ? [
                        'id' => $navigation['next']->id,
                        'baslik' => $navigation['next']->baslik,
                        'fiyat' => $navigation['next']->fiyat,
                        'para_birimi' => $navigation['next']->para_birimi,
                        'url' => route('ilanlar.show', $navigation['next']->id),
                    ] : null,
                    'current_index' => $navigation['current_index'],
                    'total' => $navigation['total'],
                ],
            ], 'Navigasyon bilgileri başarıyla alındı');
        } catch (\Exception $e) {
            LogService::error('Navigation API failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('Navigasyon bilgileri alınırken hata oluştu', $e);
        }
    }

    /**
     * Get similar listings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSimilar(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (optional validation)
        $validated = $this->validateRequestFlexible($request, [
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($ilanId);
            $limit = $request->input('limit', 4);

            $similar = $this->navigationService->getSimilar($ilan, $limit);

            return ResponseService::success([
                'similar' => $similar->map(function ($similarIlan) {
                    return [
                        'id' => $similarIlan->id,
                        'baslik' => $similarIlan->baslik,
                        'fiyat' => $similarIlan->fiyat,
                        'para_birimi' => $similarIlan->para_birimi,
                        'kapak_fotografi_url' => $similarIlan->kapak_fotografi_url,
                        'url' => route('ilanlar.show', $similarIlan->id),
                    ];
                }),
            ], 'Benzer ilanlar başarıyla alındı');
        } catch (\Exception $e) {
            LogService::error('Similar listings API failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('Benzer ilanlar alınırken hata oluştu', $e);
        }
    }

    /**
     * Get AI-powered navigation suggestions
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAISuggestions(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (no validation needed, but consistent pattern)
        try {
            $ilan = Ilan::with(['kategori', 'il', 'ilce'])->findOrFail($ilanId);

            // AI service ile navigasyon önerileri
            $context = [
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'kategori' => $ilan->kategori->name ?? null,
                    'lokasyon' => ($ilan->il->il_adi ?? '').', '.($ilan->ilce->ilce_adi ?? ''),
                    'fiyat' => $ilan->fiyat,
                ],
                'type' => 'navigation_suggestions', // context7-ignore
            ];

            $suggestions = $this->aiService->suggest($context, 'navigation');

            return ResponseService::success([
                'suggestions' => $suggestions,
                'navigation_tips' => [
                    'Benzer fiyat aralığındaki ilanlara bakın',
                    'Aynı bölgedeki ilanları inceleyin',
                    'Kategori bazlı navigasyon kullanın',
                    'Mobilde swipe navigation deneyin',
                ],
            ], 'AI navigasyon önerileri başarıyla alındı');
        } catch (\Exception $e) {
            LogService::error('AI navigation suggestions failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('AI önerileri alınırken hata oluştu', $e);
        }
    }
}
