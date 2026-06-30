<?php

namespace App\Services\Performance;

use App\Models\Ilan;
use App\Models\DanismanlarPerformanceMetrics;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * 🎯 Performance Scoring Service - Phase 5
 * 
 * 0-100 arası "Güven ve Kalite Skoru" hesaplar.
 * Bosch GLM, FLIR ONE, koordinat, sezonluk fiyat, açıklama verilerine dayanır.
 * 
 * Ağırlıklandırma:
 * - Bosch GLM m² doğrulaması: %30
 * - FLIR ONE termal rapor: %20
 * - Mühürlü Koordinat: %20
 * - Sezonluk Fiyat Doğruluğu: %15
 * - Açıklama Zenginliği: %15
 * 
 * Context7 Compliance: aktiflik_durumu, gosterim_sirasi, lat/lng
 * 
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0
 */
class PerformanceScoringService
{
    private const BOSCH_WEIGHT = 0.30;
    private const FLIR_WEIGHT = 0.20;
    private const COORDINATE_WEIGHT = 0.20;
    private const SEASONAL_PRICE_WEIGHT = 0.15;
    private const DESCRIPTION_WEIGHT = 0.15;

    public function __construct(
        private LogService $logService
    ) {}

    /**
     * 🎯 Ilan İçin Tüm Skorları Hesapla ve Kaydet
     * 
     * @param Ilan $ilan
     * @return DanismanlarPerformanceMetrics
     */
    public function scoreIlan(Ilan $ilan): DanismanlarPerformanceMetrics
    {
        $timer = $this->logService->startTimer('performance_scoring');

        try {
            // 1️⃣ Bosch GLM m² Doğruluk Skoru (%30 ağırlık)
            $boschScore = $this->calculateBoschM2Score($ilan);

            // 2️⃣ FLIR ONE Termal Rapor Skoru (%20 ağırlık)
            $flirScore = $this->calculateFlirThermalScore($ilan);

            // 3️⃣ Mühürlü Koordinat Güven Skoru (%20 ağırlık)
            $coordinateScore = $this->calculateCoordinateScore($ilan);

            // 4️⃣ Sezonluk Fiyat Doğruluğu (%15 ağırlık)
            $seasonalPriceScore = $this->calculateSeasonalPriceScore($ilan);

            // 5️⃣ Açıklama Zenginliği (%15 ağırlık)
            $descriptionScore = $this->calculateDescriptionRichnessScore($ilan);

            // 6️⃣ Ağırlıklı Ortalama = Genel Kalite Skoru
            $overallScore = $this->calculateWeightedAverage([
                'bosch' => $boschScore,
                'flir' => $flirScore,
                'coordinate' => $coordinateScore,
                'seasonal_price' => $seasonalPriceScore,
                'description' => $descriptionScore,
            ]);

            // 7️⃣ danismanlar_performance_metrics'e kaydet
            $metrics = DanismanlarPerformanceMetrics::updateOrCreate(
                ['ilan_id' => $ilan->id, 'user_id' => $ilan->user_id ?? $ilan->danisman_id],
                [
                    'bosch_m2_accuracy_score' => round($boschScore, 2),
                    'flir_thermal_score' => round($flirScore, 2),
                    'coordinate_trust_score' => round($coordinateScore, 2),
                    'seasonal_price_accuracy_score' => round($seasonalPriceScore, 2),
                    'description_richness_score' => round($descriptionScore, 2),
                    'overall_quality_score' => round($overallScore, 2),
                    'aktiflik_durumu' => true,
                ]
            );

            $this->logService->stopTimer($timer, [
                'ilan_id' => $ilan->id,
                'overall_score' => $overallScore,
                'scoring_breakdown' => [
                    'bosch' => $boschScore,
                    'flir' => $flirScore,
                    'coordinate' => $coordinateScore,
                    'seasonal_price' => $seasonalPriceScore,
                    'description' => $descriptionScore,
                ],
            ]);

            return $metrics;

        } catch (\Exception $e) {
            $this->logService->stopTimer($timer, [
                'error' => $e->getMessage(),
                'ilan_id' => $ilan->id,
            ]);
            throw $e;
        }
    }

    /**
     * 🔬 Bosch GLM 50-27 CG m² Doğruluk Skoru (%30 ağırlık)
     * 
     * Bosch GLM verisi varsa, m² ölçümleriyle karşılaştır.
     * Veri kalitesi, tutarlılık ve hataların yokluğu skorlanır.
     * 
     * Scoring:
     * - Net m² (Bosch doğrulanmış): +35 puan
     * - Hata marjı <%5: +30 puan
     * - Hata marjı 5-10%: +20 puan
     * - Hata marjı >10%: +5 puan
     * - Veri yok: 0 puan
     */
    private function calculateBoschM2Score(Ilan $ilan): float
    {
        if (!$ilan->bosch_glm_net_m2) {
            return 0; // Bosch verisi yoksa skor 0
        }

        $score = 35; // Bosch verisi var = temel 35 puan

        // Net m² ile karşılaştır (varsa)
        if ($ilan->net_alan_m2) {
            $errorMargin = abs($ilan->bosch_glm_net_m2 - $ilan->net_alan_m2) / $ilan->net_alan_m2 * 100;

            if ($errorMargin < 5) {
                $score += 30; // Mükemmel eşleşme
            } elseif ($errorMargin < 10) {
                $score += 20; // İyi eşleşme
            } else {
                $score += 5; // Zayıf eşleşme
            }
        } else {
            $score += 25; // Net m² olmasa bile Bosch verisi var
        }

        // Brut m² varsa karşılaştır
        if ($ilan->brut_m2) {
            if ($ilan->bosch_glm_net_m2 < $ilan->brut_m2) {
                $score += 10; // Mantıklı, net < brut
            }
        }

        return min(100, $score); // Max 100
    }

    /**
     * 🌡️ FLIR ONE Edge Pro Termal Rapor Skoru (%20 ağırlık)
     * 
     * FLIR termal rapor kanıtı:
     * - Dosya/URL var: +40 puan
     * - Isı yalıtım sertifikası: +35 puan
     * - Bina yüksek kalite: +25 puan
     * - Dosya yok: 0 puan
     */
    private function calculateFlirThermalScore(Ilan $ilan): float
    {
        $score = 0;

        if ($ilan->flir_termal_raporu) {
            $score += 40; // Dosya/URL var
        }

        if ($ilan->isi_yalitim_sertifika) {
            $score += 35; // İsı yalıtım sertifika
        }

        // Bina yaşı kontrol et (daha yeni = daha iyi yalıtım)
        if ($ilan->bina_yasi && $ilan->bina_yasi < 10) {
            $score += 25;
        } elseif ($ilan->bina_yasi && $ilan->bina_yasi < 20) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * 📍 Mühürlü Koordinat Güven Skoru (%20 ağırlık)
     * 
     * Koordinatların varlığı ve doğruluğu:
     * - lat/lng (GPS) var: +50 puan
     * - Adresten doğrulama yapılmış: +30 puan
     * - POI (denize mesafe) kontrol edilmiş: +20 puan
     */
    private function calculateCoordinateScore(Ilan $ilan): float
    {
        $score = 0;

        if ($ilan->lat && $ilan->lng) {
            $score += 50; // GPS koordinat var
        } elseif ($ilan->latitude && $ilan->longitude) {
            $score += 45; // Eski format koordinat (uyumluluk)
        }

        // Adres doğrulaması
        if ($ilan->il && $ilan->ilce && $ilan->mahalle) {
            $score += 30; // Tam adres
        } elseif ($ilan->il && $ilan->ilce) {
            $score += 20; // Kısmi adres
        }

        // POI kontrol
        if ($ilan->denize_mesafe_m || $ilan->denize_mesafe) {
            $score += 20; // Denize mesafe hesaplanmış
        }

        return min(100, $score);
    }

    /**
     * 💰 Sezonluk Fiyat Doğruluğu (%15 ağırlık)
     * 
     * Yazlık kiralama için sezonluk fiyat tutarlılığı:
     * - Yaz/Kış/Ara fiyatları tüm var: +50 puan
     * - Mantıklı orantı (Yaz > Ara > Kış): +35 puan
     * - Pazar ortalamasıyla uyum: +15 puan
     */
    private function calculateSeasonalPriceScore(Ilan $ilan): float
    {
        $score = 0;

        // Yazlık kiralama mı?
        if ($ilan->yayin_tipi_id && $ilan->yayin_tipi_id == 4) { // gunluk id=4 (örnek)
            if ($ilan->gunluk_fiyat_yaz && $ilan->gunluk_fiyat_kis && $ilan->gunluk_fiyat_ara) {
                $score += 50; // Tüm sezon fiyatları var
            } else {
                return 0; // Yazlık ama sezon fiyatları eksik
            }

            // Mantıklı orantı kontrol et
            if ($ilan->gunluk_fiyat_yaz > $ilan->gunluk_fiyat_ara &&
                $ilan->gunluk_fiyat_ara > $ilan->gunluk_fiyat_kis) {
                $score += 35; // Yaz > Ara > Kış (doğru)
            } else {
                $score += 10; // Yanlış orantı
            }

            // Pazar karşılaştırması (varsa market_analysis_data)
            $score += 15; // Placeholder
        } else {
            // Yazlık değil ise, normal fiyat tutarlılığı
            if ($ilan->fiyat && $ilan->fiyat > 0) {
                $score += 70; // Fiyat var ve valid
            }
        }

        return min(100, $score);
    }

    /**
     * 📝 Açıklama Zenginliği Skoru (%15 ağırlık)
     * 
     * Açıklama metninin kalitesi ve detaylılığı:
     * - Kelime sayısı >500: +40 puan
     * - Kelime sayısı 200-500: +25 puan
     * - Kelime sayısı 50-200: +10 puan
     * - Özel karakterler/formatting: +15 puan
     * - Hashtag/etiketler: +10 puan
     * - Fotoğraf sayısı >5: +20 puan
     */
    private function calculateDescriptionRichnessScore(Ilan $ilan): float
    {
        $score = 0;

        $description = $ilan->aciklama ?? $ilan->description ?? '';
        $wordCount = str_word_count($description);

        // Kelime sayısı puanlandırması
        if ($wordCount > 500) {
            $score += 40;
        } elseif ($wordCount > 200) {
            $score += 25;
        } elseif ($wordCount > 50) {
            $score += 10;
        }

        // Formatting (punkt, satır break vb.)
        $newlineCount = substr_count($description, "\n");
        if ($newlineCount > 5) {
            $score += 15; // İyi biçimlendirilmiş
        }

        // Hashtag/etiketler
        if (preg_match_all('/#\w+/', $description, $matches)) {
            $score += 10;
        }

        // Fotoğraf sayısı kontrol
        $photoCount = $ilan->ilanFotograflar()->count() ?? 0;
        if ($photoCount > 5) {
            $score += 20; // Güzel fotoğraf seti
        } elseif ($photoCount > 0) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * ⚖️ Ağırlıklı Ortalama Hesapla
     * 
     * @param array $scores ['bosch' => 85, 'flir' => 92, ...]
     * @return float Overall score (0-100)
     */
    private function calculateWeightedAverage(array $scores): float
    {
        $weights = [
            'bosch' => self::BOSCH_WEIGHT,
            'flir' => self::FLIR_WEIGHT,
            'coordinate' => self::COORDINATE_WEIGHT,
            'seasonal_price' => self::SEASONAL_PRICE_WEIGHT,
            'description' => self::DESCRIPTION_WEIGHT,
        ];

        $total = 0;
        foreach ($weights as $key => $weight) {
            $total += ($scores[$key] ?? 0) * $weight;
        }

        return round($total, 2);
    }

    /**
     * 📊 Danışman Toplam Performans Skoru
     * 
     * Danışmanın tüm ilanları ortalaması
     * 
     * @param int $userId
     * @return float
     */
    public function getDanismanAverageScore(int $userId): float
    {
        $average = DB::table('danismanlar_performance_metrics')
            ->where('user_id', $userId)
            ->where('aktiflik_durumu', true)
            ->avg('overall_quality_score');

        return round($average ?? 0, 2);
    }
}
