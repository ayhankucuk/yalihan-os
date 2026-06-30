<?php

namespace App\Services;

use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Cortex AI: ROI (Return on Investment) Calculation Engine
 *
 * Context7 Standard: C7-CORTEX-ROI-ENGINE-2025-12-23
 * Version: 1.0.0
 *
 * Bu servis, arsa ve turizm ilanlarının yatırım getirisini (ROI) hesaplar
 * ve additional_metadata JSON kolonuna yazar.
 *
 * ROI Formülleri:
 * - Arsa: (Proje Değeri - Maliyet) / Maliyet * 100
 * - Turizm: (Yıllık Gelir - Yıllık Maliyet) / Yatırım * 100
 *
 * Naming Convention: Context7 canonical fields (il_id, yayin_durumu, aktiflik_durumu, gosterim_sirasi)
 */
class CortexROIEngine
{
    /**
     * Arsa ROI hesapla
     *
     * Context7: Domain-specific business logic
     *
     * @param  Ilan  $ilan
     * @return array
     */
    public function calculateArsaROI(Ilan $ilan): array
    {
        if (! $ilan->arsaDetail) {
            Log::warning('Cortex ROI: Arsa detail bulunamadı', ['ilan_id' => $ilan->id]);

            return [
                'roi_percentage' => null,
                'roi_category' => 'insufficient_data',
                'calculation_date' => now()->toIso8601String(),
                'error' => 'Arsa detayı eksik',
            ];
        }

        $detail = $ilan->arsaDetail;

        // Base metrics
        $alanM2 = $detail->alan_m2 ?? 0;
        $birimFiyat = $alanM2 > 0 ? ($ilan->fiyat / $alanM2) : 0;

        // İmar katsayıları (KAKS ile inşaat alanı hesabı)
        $kaks = $detail->kaks ?? 0;
        $insaatAlanM2 = $alanM2 * $kaks;

        // Altyapı primi (tam altyapı varsa %10 bonus)
        $altyapiPrimi = $detail->altyapiTamMi() ? 1.10 : 1.0;

        // Konum primi (il_id bazlı basit mantık - gerçekte daha karmaşık olabilir)
        $konumPrimi = $this->getKonumPrimi($ilan->il_id);

        // Potansiyel proje değeri (m² başına ortalama satış fiyatı * inşaat alanı)
        $ortalamaM2SatisFiyati = 8000; // TL (örnek değer)
        $potansiyelProjeDegeri = $insaatAlanM2 * $ortalamaM2SatisFiyati * $altyapiPrimi * $konumPrimi;

        // ROI hesapla
        $maliyet = $ilan->fiyat;
        if ($maliyet > 0) {
            $roiPercentage = (($potansiyelProjeDegeri - $maliyet) / $maliyet) * 100;
        } else {
            $roiPercentage = 0;
        }

        // ROI kategorisi
        $roiCategory = match (true) {
            $roiPercentage >= 100 => 'excellent',
            $roiPercentage >= 50 => 'very_good',
            $roiPercentage >= 25 => 'good',
            $roiPercentage >= 10 => 'moderate',
            $roiPercentage > 0 => 'low',
            default => 'negative',
        };

        return [
            'roi_percentage' => round($roiPercentage, 2),
            'roi_category' => $roiCategory,
            'calculation_date' => now()->toIso8601String(),
            'metrics' => [
                'alan_m2' => $alanM2,
                'birim_fiyat' => round($birimFiyat, 2),
                'kaks' => $kaks,
                'insaat_alan_m2' => round($insaatAlanM2, 2),
                'potansiyel_proje_degeri' => round($potansiyelProjeDegeri, 2),
                'altyapi_primi' => $altyapiPrimi,
                'konum_primi' => $konumPrimi,
            ],
            'imar_bilgisi' => [
                'imar_durumu' => $detail->imar_durumu,
                'tapu_durumu' => $detail->tapu_statusu,
                'krediye_uygun' => $detail->krediye_uygun,
            ],
        ];
    }

    /**
     * Turizm ROI hesapla
     *
     * Context7: Domain-specific business logic
     *
     * @param  Ilan  $ilan
     * @return array
     */
    public function calculateTurizmROI(Ilan $ilan): array
    {
        if (! $ilan->turizmDetail) {
            Log::warning('Cortex ROI: Turizm detail bulunamadı', ['ilan_id' => $ilan->id]);

            return [
                'roi_percentage' => null,
                'roi_category' => 'insufficient_data',
                'calculation_date' => now()->toIso8601String(),
                'error' => 'Turizm detayı eksik',
            ];
        }

        $detail = $ilan->turizmDetail;

        // Günlük fiyat bazlı yıllık gelir tahmini
        $gunlukFiyat = $detail->gunluk_fiyat ?? 0;
        $haftalikFiyat = $detail->haftalik_fiyat ?? ($gunlukFiyat * 7);
        $aylikFiyat = $detail->aylik_fiyat ?? ($gunlukFiyat * 30);

        // Doluluk oranı tahmini (sezon + özellikler bazlı)
        $dolulukOrani = $this->estimateDolulukOrani($detail);

        // Sezon uzunluğu (varsayılan: 120 gün)
        $sezonGunSayisi = 120;
        if ($detail->sezon_baslangic && $detail->sezon_bitis) {
            $sezonGunSayisi = $detail->sezon_baslangic->diffInDays($detail->sezon_bitis);
        }

        // Yıllık gelir = (günlük fiyat * doluluk oranı * sezon gün sayısı)
        $yillikGelir = $gunlukFiyat * ($dolulukOrani / 100) * $sezonGunSayisi;

        // Yıllık maliyet tahmini (bakım, vergiler, sigorta vb. - %15)
        $yillikMaliyet = $yillikGelir * 0.15;

        // Net yıllık gelir
        $netYillikGelir = $yillikGelir - $yillikMaliyet;

        // ROI hesapla
        $yatirim = $ilan->fiyat;
        if ($yatirim > 0) {
            $roiPercentage = ($netYillikGelir / $yatirim) * 100;
        } else {
            $roiPercentage = 0;
        }

        // Geri ödeme süresi (yıl)
        $geriOdemeSuresi = $netYillikGelir > 0 ? ($yatirim / $netYillikGelir) : 0;

        // ROI kategorisi
        $roiCategory = match (true) {
            $roiPercentage >= 15 => 'excellent',
            $roiPercentage >= 10 => 'very_good',
            $roiPercentage >= 7 => 'good',
            $roiPercentage >= 5 => 'moderate',
            $roiPercentage > 0 => 'low',
            default => 'negative',
        };

        return [
            'roi_percentage' => round($roiPercentage, 2),
            'roi_category' => $roiCategory,
            'calculation_date' => now()->toIso8601String(),
            'payback_period_years' => round($geriOdemeSuresi, 1),
            'metrics' => [
                'gunluk_fiyat' => $gunlukFiyat,
                'doluluk_orani' => $dolulukOrani,
                'sezon_gun_sayisi' => $sezonGunSayisi,
                'yillik_gelir' => round($yillikGelir, 2),
                'yillik_maliyet' => round($yillikMaliyet, 2),
                'net_yillik_gelir' => round($netYillikGelir, 2),
            ],
            'ozellikler' => [
                'havuz_var' => $detail->havuz_var,
                'bebek_uygun' => $detail->bebek_uygun,
                'cocuk_uygun' => $detail->cocuk_uygun,
                'min_konaklama' => $detail->min_konaklama,
            ],
        ];
    }

    /**
     * Genel Cortex Score hesapla (tüm metrikler)
     *
     * Context7: AI-powered scoring
     *
     * @param  Ilan  $ilan
     * @return array
     */
    public function calculateCortexScore(Ilan $ilan): array
    {
        $roiData = null;
        $domain = null;

        // Domain'e göre ROI hesapla
        if ($ilan->turizmDetail) {
            $domain = 'turizm';
            $roiData = $this->calculateTurizmROI($ilan);
        } elseif ($ilan->arsaDetail) {
            $domain = 'arsa';
            $roiData = $this->calculateArsaROI($ilan);
        }

        // Cortex Score (0-10 arası)
        $cortexScore = $this->calculateOverallScore($roiData, $ilan);

        return [
            'cortex_score' => $cortexScore,
            'alan' => $domain,
            'roi_bilgisi' => $roiData,
            'yatirim_potansiyeli' => match (true) {
                $cortexScore >= 8.5 => 'excellent',
                $cortexScore >= 7.0 => 'very_good',
                $cortexScore >= 5.5 => 'good',
                $cortexScore >= 4.0 => 'moderate',
                default => 'low',
            },
            'ai_tarafindan_uretildi' => true,
            'motor_versiyonu' => '1.0.0',
            'hesaplama_zamani' => now()->toIso8601String(),
        ];
    }

    /**
     * ROI verilerini additional_metadata'ya kaydet
     *
     * Context7: JSON metadata update
     *
     * @param  Ilan  $ilan
     * @return bool
     */
    public function saveToMetadata(Ilan $ilan): bool
    {
        try {
            $cortexData = $this->calculateCortexScore($ilan);

            // Mevcut metadata'yı al (JSON decode gerekiyorsa)
            $metadata = $ilan->additional_metadata;
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            } elseif (is_null($metadata)) {
                $metadata = [];
            }

            // Cortex verilerini ekle
            $metadata['cortex_ai'] = $cortexData;

            // Kaydet
            $ilan->update(['additional_metadata' => $metadata]);

            Log::info('Cortex ROI kaydedildi', [
                'ilan_id' => $ilan->id,
                'cortex_score' => $cortexData['cortex_score'],
                'domain' => $cortexData['domain'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Cortex ROI kaydetme hatası', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Konum primini hesapla (il_id bazlı)
     *
     * @param  int|null  $ilId
     * @return float
     */
    protected function getKonumPrimi(?int $ilId): float
    {
        // İstanbul, Ankara, İzmir gibi büyük şehirler
        $buyukSehirler = [34, 6, 35];

        // Turistik bölgeler (Antalya, Muğla, Aydın vb.)
        $turistikBolgeler = [7, 48, 9];

        if (in_array($ilId, $buyukSehirler)) {
            return 1.50; // %50 prim
        }

        if (in_array($ilId, $turistikBolgeler)) {
            return 1.30; // %30 prim
        }

        return 1.0; // Normal
    }

    /**
     * Doluluk oranını tahmin et
     *
     * @param  \App\Models\Dikey\IlanTurizmDetail  $detail
     * @return float
     */
    protected function estimateDolulukOrani($detail): float
    {
        $baseRate = 50.0; // %50 base

        // Havuz varsa +15%
        if ($detail->havuz_var) {
            $baseRate += 15;
        }

        // Bebek/çocuk uygunsa +10%
        if ($detail->bebek_uygun || $detail->cocuk_uygun) {
            $baseRate += 10;
        }

        // Min konaklama düşükse +5%
        if ($detail->min_konaklama && $detail->min_konaklama <= 3) {
            $baseRate += 5;
        }

        // Max %85
        return min($baseRate, 85.0);
    }

    /**
     * Genel skor hesapla (0-10)
     *
     * @param  array|null  $roiData
     * @param  Ilan  $ilan
     * @return float
     */
    protected function calculateOverallScore(?array $roiData, Ilan $ilan): float
    {
        if (! $roiData || ! isset($roiData['roi_percentage'])) {
            return 5.0; // Neutral score
        }

        $roiScore = match ($roiData['roi_category'] ?? 'moderate') {
            'excellent' => 10.0,
            'very_good' => 8.5,
            'good' => 7.0,
            'moderate' => 5.5,
            'low' => 4.0,
            default => 2.0,
        };

        // Fotoğraf varsa +0.5
        if ($ilan->fotograflar()->count() > 0) {
            $roiScore += 0.5;
        }

        // Detaylı açıklama varsa +0.5
        if (strlen($ilan->aciklama ?? '') > 200) {
            $roiScore += 0.5;
        }

        return min($roiScore, 10.0);
    }
}
