<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class IlanListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Cover image logic: Prefer 'kapak_fotografi', fallback to first photo, fallback to placeholder
        $coverImage = $this->fotograflar->where('kapak_fotografi', true)->first()
            ?? $this->fotograflar->sortBy('display_order')->first();

        $coverImageUrl = $coverImage
            ? \Illuminate\Support\Facades\Storage::url($coverImage->dosya_yolu)
            : 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=800&q=60'; // Optimized size

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
            'slug' => $this->slug,
            'price' => [
                'amount' => $this->fiyat,
                'currency' => $this->para_birimi,
                'formatted' => $currencySymbol . $formattedPrice,
            ],
            'image' => $coverImageUrl,
            'location' => [
                'city' => optional($this->il)->il_adi,
                'district' => optional($this->ilce)->ilce_adi,
                'neighborhood' => optional($this->mahalle)->mahalle_adi,
                'full_text' => implode(', ', array_filter([
                     optional($this->mahalle)->mahalle_adi,
                     optional($this->ilce)->ilce_adi,
                     optional($this->il)->il_adi
                ])),
            ],
            'features' => [
                'rooms' => $this->oda_sayisi,
                'area' => $this->brut_m2 ?? $this->alan_m2,
                'category' => optional($this->anaKategori)->name,
            ],
            'badges' => [
                'type' => $this->is_rent ? 'Kiralık' : 'Satılık', // Logic needed or simple mapping
                'featured' => (bool) $this->one_cikan,
            ],
            'published_at' => $this->created_at->diffForHumans(),
        ];
    }
}
