<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class IlanDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Gallery
        $gallery = $this->fotograflar->sortBy('display_order')->map(function ($photo) {
            return [
                'id' => $photo->id,
                'url' => Storage::url($photo->dosya_yolu),
                'is_cover' => (bool) $photo->kapak_fotografi,
            ];
        });

        // Price formatting
        $formattedPrice = number_format($this->fiyat, 0, ',', '.');
        $currencySymbol = match ($this->para_birimi) {
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            default => $this->para_birimi,
        };

        return [
            'id' => $this->id,
            'title' => $this->baslik,
            'description' => strip_tags($this->aciklama), // Clean HTML for mobile
            'price' => [
                'amount' => $this->fiyat,
                'currency' => $this->para_birimi,
                'formatted' => $currencySymbol . $formattedPrice,
            ],
            'location' => [
                'city' => $this->il->il_adi ?? null,
                'district' => $this->ilce->ilce_adi ?? null,
                'neighborhood' => $this->mahalle->mahalle_adi ?? null,
                'coordinates' => [
                    'lat' => (float) ($this->latitude ?? $this->lat),
                    'lng' => (float) ($this->longitude ?? $this->lng),
                ],
            ],
            'attributes' => [
                'rooms' => $this->oda_sayisi,
                'bathrooms' => $this->banyo_sayisi,
                'area_gross' => $this->brut_m2,
                'area_net' => $this->net_m2,
                'building_age' => $this->bina_yasi,
                'floor' => $this->bulundugu_kat,
                'heating' => $this->isitma_tipi,
            ],
            'gallery' => $gallery,
            'agent' => new AgentResource($this->danisman),
            'virtual_tour' => $this->sanal_tur_url,
            'video' => $this->youtube_video_url,
            'meta' => [
                'published_at' => $this->created_at->toIso8601String(),
                'views' => $this->goruntulenme_sayisi ?? 0,
                'favorites' => $this->favori_sayisi ?? 0,
            ],
        ];
    }
}
