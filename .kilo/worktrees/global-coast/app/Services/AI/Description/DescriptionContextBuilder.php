<?php

namespace App\Services\AI\Description;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use Illuminate\Support\Facades\Log;

/**
 * Description Context Builder
 *
 * AI description üretimi için gerekli context'i oluşturur.
 * Mevcut ilan verilerini, özellikleri, fotoğrafları ve lokasyon bilgilerini toplar.
 *
 * KURALLAR:
 * 1. ASLA varsayım yapma - sadece mevcut verileri kullan
 * 2. ASLA veri olmayan alanları yazma
 * 3. ASLA fiyat, kişi sayısı, mesafe uydurma
 */
class DescriptionContextBuilder
{
    /**
     * Build context for AI description generation
     */
    public function build(Ilan $ilan): array
    {
        $context = [
            'ilan' => $this->extractIlanData($ilan),
            'ozellikler' => $this->extractFeatures($ilan),
            'lokasyon' => $this->extractLocation($ilan),
            'fiyat' => $this->extractPrice($ilan),
            'kategori' => $this->extractCategory($ilan),
            'medya' => $this->extractMediaSummary($ilan),
        ];

        Log::info('Description context built', [
            'ilan_id' => $ilan->id,
            'context_keys' => array_keys($context),
        ]);

        return $context;
    }

    /**
     * Extract basic listing data
     */
    protected function extractIlanData(Ilan $ilan): array
    {
        $data = [
            'id' => $ilan->id,
            'baslik' => $ilan->baslik,
            'kategori' => $ilan->altKategori?->name ?? $ilan->anaKategori?->name,
        ];

        // Konut tipi (oda sayısı vb.)
        if ($ilan->oda_sayisi) {
            $data['oda_sayisi'] = $ilan->oda_sayisi;
        }

        // Alan bilgileri
        if ($ilan->net_m2) {
            $data['net_m2'] = $ilan->net_m2;
        }
        if ($ilan->brut_m2) {
            $data['brut_m2'] = $ilan->brut_m2;
        }
        if ($ilan->alan_m2) {
            $data['alan_m2'] = $ilan->alan_m2;
        }

        // Bina bilgileri
        if ($ilan->bina_yasi) {
            $data['bina_yasi'] = $ilan->bina_yasi;
        }
        if ($ilan->kat) {
            $data['kat'] = $ilan->kat;
        }
        if ($ilan->toplam_kat) {
            $data['toplam_kat'] = $ilan->toplam_kat;
        }

        // Banyo
        if ($ilan->banyo_sayisi) {
            $data['banyo_sayisi'] = $ilan->banyo_sayisi;
        }

        return $data;
    }

    /**
     * Extract features (boolean properties)
     */
    protected function extractFeatures(Ilan $ilan): array
    {
        $features = [
            'konfor' => [],
            'dis_alan' => [],
            'guvenlik' => [],
            'diger' => [],
        ];

        // Havuz ve Deniz
        if ($ilan->havuz_var) {
            $features['dis_alan']['ozel_havuz'] = true;
            if ($ilan->havuz_isitmali) {
                $features['dis_alan']['isitmali_havuz'] = true;
            }
        }

        if ($ilan->deniz_manzarali) {
            $features['dis_alan']['deniz_manzarasi'] = true;
        }

        // Bahçe
        if ($ilan->bahce_var) {
            $features['dis_alan']['bahce'] = true;
            if ($ilan->barbeku_var) {
                $features['dis_alan']['barbeku'] = true;
            }
        }

        // İç Konfor
        if ($ilan->esyali) {
            $features['konfor']['esyali'] = true;
        }
        if ($ilan->isitma) {
            $features['konfor']['isitma'] = $ilan->isitma;
        }
        if ($ilan->isinma_tipi) {
            $features['konfor']['isinma_tipi'] = $ilan->isinma_tipi;
        }

        // Güvenlik
        if (!empty($ilan->site_ozellikleri)) {
            $features['guvenlik']['site_icerisinde'] = true;
        }

        // Diğer özellikler
        if ($ilan->evcil_hayvan_uygun) {
            $features['diger']['evcil_hayvan_uygun'] = true;
        }
        if ($ilan->sigara_icilmez) {
            $features['diger']['sigara_yasak'] = true;
        }

        return array_filter($features); // Boş kategorileri kaldır
    }

    /**
     * Extract location data
     */
    protected function extractLocation(Ilan $ilan): array
    {
        $location = [];

        if ($ilan->il) {
            $location['il'] = $ilan->il->il_adi;
        }
        if ($ilan->ilce) {
            $location['ilce'] = $ilan->ilce->ilce_adi;
        }
        if ($ilan->mahalle) {
            $location['mahalle'] = $ilan->mahalle->mahalle_adi;
        }

        if ($ilan->adres) {
            $location['adres'] = $ilan->adres;
        }

        // Koordinatlar (opsiyonel)
        if ($ilan->lat && $ilan->lng) {
            $location['koordinat'] = [
                'lat' => $ilan->lat,
                'lng' => $ilan->lng,
            ];
        }

        return $location;
    }

    /**
     * Extract price information
     */
    protected function extractPrice(Ilan $ilan): array
    {
        $price = [];

        if ($ilan->fiyat) {
            $price['tutar'] = $ilan->fiyat;
            $price['para_birimi'] = $ilan->para_birimi ?? 'TRY';
        }

        if ($ilan->fiyat_gosterim_modu) {
            $price['gosterim_modu'] = $ilan->fiyat_gosterim_modu;
        }

        return $price;
    }

    /**
     * Extract category information
     */
    protected function extractCategory(Ilan $ilan): array
    {
        $category = [];

        $category['tip'] = $ilan->altKategori?->name ?? $ilan->anaKategori?->name;
        $category['yayin_tipi'] = $ilan->yayinTipi?->name;

        return array_filter($category);
    }

    /**
     * Extract media summary (without image URLs)
     */
    protected function extractMediaSummary(Ilan $ilan): array
    {
        $media = [];

        // Fotoğraf sayısı
        $photoCount = $ilan->fotograflar()->count();
        if ($photoCount > 0) {
            $media['foto_sayisi'] = $photoCount;
        }

        // Video
        if ($ilan->video_url || $ilan->youtube_video_url) {
            $media['video_var'] = true;
        }

        // Sanal tur
        if ($ilan->sanal_tur_url) {
            $media['sanal_tur_var'] = true;
        }

        return $media;
    }

    /**
     * Validate context completeness for description generation
     */
    public function validate(array $context): array
    {
        $errors = [];

        // Temel lokasyon gerekli
        if (empty($context['lokasyon']['il'])) {
            $errors[] = 'il gerekli';
        }

        // Kategori gerekli
        if (empty($context['kategori']['tip'])) {
            $errors[] = 'kategori gerekli';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}