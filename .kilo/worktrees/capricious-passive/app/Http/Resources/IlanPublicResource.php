<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * İlan Public API Resource
 *
 * Context7 Standardı: C7-API-RESOURCE-PUBLIC-2025-11-05
 *
 * Mahrem bilgileri gizler, sadece public API'de görüntülenmesi gereken alanları döndürür
 */
class IlanPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            // Temel Bilgiler (Public)
            'id' => $this->id,
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'yayin_durumu' => $this->yayin_durumu,

            // Fiyat Bilgileri (Public)
            'price' => $this->price,
            'currency' => $this->currency,

            // Lokasyon Bilgileri (Public)
            'il' => $this->whenLoaded('il', function () {
                return [
                    'id' => $this->il->id,
                    'il_adi' => $this->il->il_adi,
                ];
            }),
            'ilce' => $this->whenLoaded('ilce', function () {
                return [
                    'id' => $this->ilce->id,
                    'ilce_adi' => $this->ilce->ilce_adi,
                ];
            }),
            'mahalle' => $this->whenLoaded('mahalle', function () {
                return [
                    'id' => $this->mahalle->id,
                    'mahalle_adi' => $this->mahalle->mahalle_adi,
                ];
            }),
            'adres' => $this->adres,

            // Kategori Bilgileri (Public)
            'kategori' => $this->whenLoaded('kategori', function () {
                return [
                    'id' => $this->kategori->id,
                    'name' => $this->kategori->name,
                ];
            }),

            // Özellikler (Public)
            'metrekare' => $this->metrekare,
            'oda_sayisi' => $this->oda_sayisi,
            'banyo_sayisi' => $this->banyo_sayisi,
            'balkon_sayisi' => $this->balkon_sayisi,

            // Fotoğraflar (Public)
            'fotograflar' => $this->whenLoaded('fotograflar', function () {
                return $this->fotograflar->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url ?? asset('storage/'.$foto->dosya_yolu),
                        'sira' => $foto->sira,
                        'kapak_fotografi' => $foto->kapak_fotografi ?? false,
                    ];
                })->sortBy('sira')->values();
            }),

            // Tarihler (Public)
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Konumsal Zeka (Public - Sadece fırsat mühru varsa)
            'konum_etki_skoru' => $this->when($this->firsat_mühru ?? false, function () {
                return $this->konum_etki_skoru ?? null;
            }),
            'poi_analiz_matrisi' => $this->when($this->firsat_mühru ?? false, function () {
                return $this->poi_analiz_matrisi ?? null;
            }),
            'firsat_mühru' => $this->firsat_mühru ?? false,
            'analitik_ozet_widget' => $this->when($this->firsat_mühru ?? false, function () {
                return $this->analitik_ozet_widget ?? null;
            }),

            // MAHREM BİLGİLER GİZLENİR:
            // - referans_no (internal kullanım için)
            // - dosya_adi (sadece internal kullanım)
            // - sahibinden_id, emlakjet_id, hepsiemlak_id, zingat_id, hurriyetemlak_id (portal ID'leri)
            // - portal_senkronizasyon_durumu (internal sync statusu)
            // - portal_pricing (internal fiyat bilgileri)
            // - ilanSahibi bilgileri (telefon, email, adres detayları)
            // - danisman_id (internal)
            // - notlar (internal notlar)
        ];
    }
}
