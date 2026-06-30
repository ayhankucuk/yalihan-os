<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
// use App\Models\Deprecated\IlanViewDaily;
use Illuminate\Http\Request;

class HybridController extends Controller
{
    protected readonly \App\Services\Ilan\IlanSearchService $searchService;

    public function __construct(\App\Services\Ilan\IlanSearchService $searchService)
    {
        $this->searchService = $searchService;
    }


    public function listings(Request $request)
    {
        $days = (int) ($request->get('days', 7));
        if ($days < 1) {
            $days = 7;
        } if ($days > 30) {
            $days = 30;
        }
        $start = now()->subDays($days - 1)->startOfDay()->toDateString();

        $publicQuery = Ilan::public();
        $crmOnlyQuery = Ilan::query()->where('crm_only', true);

        $publicCount = $publicQuery->count();
        $crmOnlyCount = $crmOnlyQuery->count();

        $topPublic = $this->searchService->getTopViewedListings($start, 10);


        $recentCrmOnly = $crmOnlyQuery->latest('updated_at')->limit(10)->get(['id', 'baslik', 'yayin_durumu', 'updated_at']);

        return response()->json([
            'success' => true,
            'days' => $days,
            'public' => [
                'count' => $publicCount,
                'top' => $topPublic->map(fn ($i) => [
                    'ilan_id' => $i->ilan_id,
                    'baslik' => $i->baslik,
                    'fiyat' => $i->fiyat,
                    'para_birimi' => $i->para_birimi,
                    'views' => (int) $i->views,
                ]),
            ],
            'crm_only' => [
                'count' => $crmOnlyCount,
                'recent' => $recentCrmOnly->map(fn ($r) => [
                    'id' => $r->id,
                    'baslik' => $r->baslik,
                    'yayin_durumu' => $r->yayin_durumu,
                    'state' => $r->yayin_durumu, // context7-ignore
                    'updated_at' => optional($r->updated_at)->toDateTimeString(),
                ]),
            ],
        ]);
    }
}
