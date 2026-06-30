<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    /**
     * Handle the global search request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'ilanlar' => [],
                    'kisiler' => [],
                    'gorevler' => [],
                    'leadler' => [],
                ]
            ]);
        }

        $results = [
            'ilanlar' => $this->searchIlanlar($query),
            'kisiler' => $this->searchKisiler($query),
            'gorevler' => $this->searchGorevler($query),
            'leadler' => $this->searchLeadler($query),
        ];

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    private function searchIlanlar($q)
    {
        return Ilan::where('baslik', 'like', "%{$q}%")
            ->orWhere('referans_no', 'like', "%{$q}%")
            ->orWhere('aciklama', 'like', "%{$q}%")
            ->latest()
            ->limit(5)
            ->get(['id', 'baslik', 'referans_no', 'fiyat', 'para_birimi'])
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->baslik,
                    'subtitle' => $item->referans_no,
                    'meta' => number_format($item->fiyat) . ' ' . $item->para_birimi,
                    'url' => route('admin.ilanlar.show', $item->id),
                    'type' => 'İlan' // context7-ignore
                ];
            });
    }

    private function searchKisiler($q)
    {
        return Kisi::where('ad', 'like', "%{$q}%")
            ->orWhere('soyad', 'like', "%{$q}%")
            ->orWhere('telefon', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->latest()
            ->limit(5)
            ->get(['id', 'ad', 'soyad', 'telefon'])
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->ad . ' ' . $item->soyad,
                    'subtitle' => $item->telefon,
                    'meta' => 'Müşteri',
                    'url' => route('admin.kisiler.show', $item->id),
                    'type' => 'Müşteri' // context7-ignore
                ];
            });
    }

    private function searchGorevler($q)
    {
        return Gorev::where('baslik', 'like', "%{$q}%")
            ->orWhere('aciklama', 'like', "%{$q}%")
            ->latest()
            ->limit(5)
            ->get(['id', 'baslik', 'yayin_durumu'])
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->baslik,
                    'subtitle' => $item->yayin_durumu,
                    'meta' => 'Görev',
                    'url' => route('admin.gorevler.show', $item->id), // Varsayılan route
                    'type' => 'Görev' // context7-ignore
                ];
            });
    }

    private function searchLeadler($q)
    {
        return Lead::where('ad', 'like', "%{$q}%")
            ->orWhere('soyad', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->latest()
            ->limit(5)
            ->get(['id', 'ad', 'soyad', 'email'])
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->ad . ' ' . $item->soyad,
                    'subtitle' => $item->email,
                    'meta' => 'Potansiyel',
                    'url' => route('admin.leadler.show', $item->id),
                    'type' => 'Lead' // context7-ignore
                ];
            });
    }
}
