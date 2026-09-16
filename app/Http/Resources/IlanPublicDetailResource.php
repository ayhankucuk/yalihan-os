<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * İlan Detay — Anonim / Public görünüm
 *
 * ADR-Ilan-Erisim-Politikasi: TIKLANABİLİR alanlar döner.
 * Koordinatlar ~1km hassasiyetle yuvarlanır.
 * Danışman bilgisi: yalnız name + avatar.
 * Korunan alanlar (adres, galeri, telefon, ilan_no) döner.
 */
class IlanPublicDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        // Yaklaşık koordinat: 0.01° ≈ 1km (anonimlik garantisi değil, yaklaşık konum)
        $lat = $this->lat ?? $this->latitude;
        $lng = $this->lng ?? $this->longitude;
        $approxLat = $lat !== null ? round((float) $lat, 2) : null;
        $approxLng = $lng !== null ? round((float) $lng, 2) : null;

        // Kapak fotoğrafı
        $cover = $this->fotograflar?->where('kapak_fotografi', true)->first()
            ?? $this->fotograflar?->sortBy('display_order')->first();
        $coverUrl = $cover
            ? Storage::url($cover->dosya_yolu)
            : null;

        return [
            // Temel (Public)
            'id' => $this->id,
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'yayin_durumu' => $this->yayin_durumu,

            // Fiyat (Public)
            'price' => $this->fiyat,
            'currency' => $this->para_birimi ?? 'TRY',

            // Konum — yalnızca il/ilce/mahalle (adres korunan)
            'il' => $this->whenLoaded('il', function () {
                $il = $this->getRelation('il');
                return $il && is_object($il) ? [
                    'id' => $il->id,
                    'il_adi' => $il->il_adi ?? $il->name,
                ] : null;
            }),
            'ilce' => $this->whenLoaded('ilce', function () {
                $ilce = $this->getRelation('ilce');
                return $ilce && is_object($ilce) ? [
                    'id' => $ilce->id,
                    'ilce_adi' => $ilce->ilce_adi ?? $ilce->name,
                ] : null;
            }),
            'mahalle' => $this->whenLoaded('mahalle', function () {
                $mahalle = $this->getRelation('mahalle');
                return $mahalle && is_object($mahalle) ? [
                    'id' => $mahalle->id,
                    'mahalle_adi' => $mahalle->mahalle_adi ?? $mahalle->name,
                ] : null;
            }),

            // Yaklaşık koordinat (korunan değil, yaklaşık)
            'coordinates' => [
                'lat' => $approxLat,
                'lng' => $approxLng,
                '_precision_note' => '~1km yuvarlanmış; tam koordinat yetki gerektirir',
            ],

            // Özellikler (Public)
            'ozellikler' => [
                'alan_m2' => $this->brut_m2 ?? $this->alan_m2,
                'oda_sayisi' => $this->oda_sayisi,
                'banyo_sayisi' => $this->banyo_sayisi,
                'kat' => $this->bulundugu_kat,
                'toplam_kat' => $this->toplam_kat,
                'bina_yasi' => $this->bina_yasi,
                'isitma' => $this->isitma_tipi,
            ],

            // Kategori (Public)
            'kategori' => $this->whenLoaded('anaKategori', fn () => [
                'id' => $this->anaKategori->id,
                'name' => $this->anaKategori->name,
            ]),

            // Kapak fotoğrafı (galeri korunan)
            'kapak_fotografi' => $coverUrl,

            // Danışman — yalnız name + avatar
            'danisman' => $this->whenLoaded('danisman', fn () => [
                'id' => $this->danisman->id,
                'name' => $this->danisman->name,
                'avatar' => $this->danisman->profile_photo_url,
            ]),

            // Tarihler (Public)
            'created_at' => $this->created_at?->toIso8601String(),

            // KORUNAN — bu kaynak public görünüm olduğundan dönmeyecek alanlar:
            //   adres, telefon, email, whatsapp, ilan_no, goruntulenme,
            //   galeri (tüm fotoğraflar), sanal_tur_url, youtube_video_url,
            //   taslak yayin_durumu
        ];
    }
}
