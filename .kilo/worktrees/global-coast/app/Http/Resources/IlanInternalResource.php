<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * İlan Internal API Resource
 *
 * Context7 Standardı: C7-API-RESOURCE-INTERNAL-2025-11-05
 *
 * "İlanlarım" sayfası ve internal kullanım için - tüm bilgileri içerir
 */
class IlanInternalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $data = [
            // Temel Bilgiler
            'id' => $this->id,
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'yayin_durumu' => $this->yayin_durumu,

            // Referans & Dosyalama Sistemi (INTERNAL)
            'referans_no' => $this->referans_no,
            'dosya_adi' => $this->dosya_adi,

            // Portal ID'leri (INTERNAL)
            'portal_numbers' => [
                'sahibinden_id' => $this->sahibinden_id,
                'emlakjet_id' => $this->emlakjet_id,
                'hepsiemlak_id' => $this->hepsiemlak_id,
                'zingat_id' => $this->zingat_id,
                'hurriyetemlak_id' => $this->hurriyetemlak_id,
            ],

            // Portal Sync Durumu (INTERNAL)
            'portal_senkronizasyon_durumu' => $this->portal_senkronizasyon_durumu,
            'portal_pricing' => $this->portal_pricing,

            // Fiyat Bilgileri
            'price' => $this->price,
            'currency' => $this->currency,

            // Rental / Hybrid Core (INTERNAL)
            'rental' => [
                'minimum_stay' => $this->minimum_stay,
                'maximum_stay' => $this->maximum_stay,
                'check_in_time' => $this->check_in_time,
                'check_out_time' => $this->check_out_time,
                'max_guests' => $this->max_guests,
                'base_guest_count' => $this->base_guest_count,
                'extra_guest_fee' => $this->extra_guest_fee,
                'cleaning_fee' => $this->cleaning_fee,
                'security_deposit' => $this->security_deposit,
                'booking_type' => $this->booking_type,
                'cancellation_policy' => $this->cancellation_policy,
            ],

            // Lokasyon Bilgileri
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

            // Kategori Bilgileri
            'kategori' => $this->whenLoaded('kategori', function () {
                return [
                    'id' => $this->kategori->id,
                    'name' => $this->kategori->name,
                ];
            }),

            // Özellikler
            'metrekare' => $this->metrekare,
            'oda_sayisi' => $this->oda_sayisi,
            'banyo_sayisi' => $this->banyo_sayisi,
            'balkon_sayisi' => $this->balkon_sayisi,

            // İlan Sahibi Bilgileri (INTERNAL - sadece ilan sahibi veya danışman)
            'ilan_sahibi' => $this->whenLoaded('ilanSahibi', function () {
                return [
                    'id' => $this->ilanSahibi->id,
                    'ad' => $this->ilanSahibi->ad,
                    'soyad' => $this->ilanSahibi->soyad,
                    'tam_ad' => $this->ilanSahibi->tam_ad,
                    'telefon' => $this->ilanSahibi->telefon,
                    'cep_telefonu' => $this->ilanSahibi->cep_telefonu,
                    'email' => $this->ilanSahibi->email,
                ];
            }),

            // Danışman Bilgileri (INTERNAL)
            'danisman_id' => $this->danisman_id,

            // Fotoğraflar
            'fotograflar' => $this->whenLoaded('fotograflar', function () {
                return $this->fotograflar->map(function ($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url ?? asset('storage/'.$foto->dosya_yolu),
                        'sira' => $foto->sira,
                        'kapak_fotografi' => $foto->kapak_fotografi ?? false,
                        'dosya_yolu' => $foto->dosya_yolu, // INTERNAL
                        'alt_text' => $foto->alt_text ?? null, // INTERNAL
                    ];
                })->sortBy('sira')->values();
            }),

            // Tarihler
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Churn Risk (Runtime appendage)
            'churn_risk' => $this->churn_risk ?? null,

            // Konumsal Zeka & Fırsat Analizi (Runtime - Opportunity Engine)
            'konum_etki_skoru' => $this->konum_etki_skoru ?? null,
            'poi_analiz_matrisi' => $this->poi_analiz_matrisi ?? null,
            'deger_odagi_mesafesi' => $this->deger_odagi_mesafesi ?? null,
            'firsat_mühru' => $this->firsat_mühru ?? false,
            'yatirim_segmenti' => $this->yatirim_segmenti ?? null,
            'analitik_ozet_widget' => $this->analitik_ozet_widget ?? null,
        ];

        return $data;
    }
}
