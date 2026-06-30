<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\V2\Ilan;
use App\Services\Response\ResponseService;
use App\Services\Mobile\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileListingController extends Controller
{
    /**
     * Display the specified resource.
     * GET /api/v1/mobile/listings/{id}
     */
    public function show($id)
    {
        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->findOrFail($id);

        $isFavorite = false;
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $isFavorite = $user->favoriIlanlar()->where('ilan_id', $ilan->id)->exists();
        }

        // Increment view count (simple implementation)
        $ilan->increment('goruntulenme');

        // Similar Listings (Simpler logic: same category, same district)
        $similar = Ilan::with(['il', 'ilce', 'fotograflar'])
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('alt_kategori_id', $ilan->alt_kategori_id)
            ->where('ilce_id', $ilan->ilce_id)
            ->where('id', '!=', $ilan->id)
            ->take(4)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'baslik' => $item->baslik,
                'fiyat' => $item->fiyat,
                'para_birimi' => $item->para_birimi,
                'image' => $item->kapak_fotografi ?? null,
                'location' => ($item->ilce->ilce_adi ?? '') . ', ' . ($item->il->il_adi ?? ''),
            ]);

        $data = [
            'id' => $ilan->id,
            'title' => $ilan->baslik,
            'price' => $ilan->fiyat,
            'currency' => $ilan->para_birimi,
            'description' => $ilan->aciklama,
            'features' => $this->extractFeatures($ilan),
            'location' => [
                'lat' => (float) $ilan->lat,
                'lng' => (float) $ilan->lng,
                'full_address' => $ilan->adres ?? (($ilan->mahalle->mahalle_adi ?? '').', '.($ilan->ilce->ilce_adi ?? '').', '.($ilan->il->il_adi ?? '')),
                'city' => $ilan->il->il_adi ?? '',
                'district' => $ilan->ilce->ilce_adi ?? '',
            ],
            'images' => $ilan->fotograflar->pluck('url')->map(fn($url) => asset($url)), // Assuming url attribute or storage link
            'agent' => $ilan->danisman ? [
                'name' => $ilan->danisman->name,
                'phone' => $ilan->danisman->telefon ?? null, // Assuming telefon col exists
                'photo' => $ilan->danisman->profile_photo_url ?? null,
            ] : null,
            'is_favorite' => $isFavorite,
            'similar_listings' => $similar,
            'dates' => [
                'created_at' => $ilan->created_at->format('d.m.Y'),
                'updated_at' => $ilan->updated_at->format('d.m.Y'),
            ]
        ];

        return ResponseService::success($data, 'İlan detayı getirildi');
    }

    private function extractFeatures($ilan)
    {
        // Map attributes to list
        $features = [];
        if ($ilan->oda_sayisi) $features[] = "{$ilan->oda_sayisi} Oda";
        if ($ilan->banyo_sayisi) $features[] = "{$ilan->banyo_sayisi} Banyo";
        if ($ilan->brut_m2) $features[] = "{$ilan->brut_m2} m² (Brüt)";
        if ($ilan->net_m2) $features[] = "{$ilan->net_m2} m² (Net)";
        if ($ilan->isitma) $features[] = "Isıtma: {$ilan->isitma}";
        if ($ilan->esyali) $features[] = "Eşyalı";
        // Check boolean columns/json if V2 has them, otherwise basic attributes
        return $features;
    }
}
