<?php

namespace App\Domain\Workforce\Support;

use App\Models\Ilan;
use Illuminate\Support\Facades\DB;

/**
 * QualityScorer — Sprint 7.2 Phase 2
 *
 * İlanın kalite skorunu hesaplar (0-100).
 * Çoklu boyutlu puanlama: completeness, richness, location, pricing, media.
 */
class QualityScorer
{
    /** Maksimum skor */
    public const MAX_SCORE = 100;

    /** Her boyutun ağırlığı */
    private const WEIGHTS = [
        'completeness' => 0.35,  // Ne kadarı dolu
        'richness'     => 0.25,  // Ne kadar zengin (özellikler, detaylar)
        'location'     => 0.20,  // Konum kalitesi
        'pricing'      => 0.10,  // Fiyatlandırma kalitesi
        'media'        => 0.10,  // Medya (fotoğraf, video)
    ];

    /**
     * İlanın kalite skorunu hesaplar.
     *
     * @param array<string, mixed> $ilanData
     * @param array<array{field: string, severity: string}> $missingFields
     * @return array{score: int, breakdown: array<string, array{score: int, max: int, weight: float, label: string}>}
     */
    public function score(Ilan $ilan, array $ilanData, array $missingFields): array
    {
        $breakdown = [];
        $totalScore = 0.0;

        // 1. Completeness (doluluk oranı)
        $completeness = $this->scoreCompleteness($ilan, $ilanData);
        $totalScore += $completeness['score'] * self::WEIGHTS['completeness'];
        $breakdown['completeness'] = $completeness;

        // 2. Richness (özellik zenginliği)
        $richness = $this->scoreRichness($ilan);
        $totalScore += $richness['score'] * self::WEIGHTS['richness'];
        $breakdown['richness'] = $richness;

        // 3. Location (konum kalitesi)
        $location = $this->scoreLocation($ilan);
        $totalScore += $location['score'] * self::WEIGHTS['location'];
        $breakdown['location'] = $location;

        // 4. Pricing (fiyatlandırma)
        $pricing = $this->scorePricing($ilan, $ilanData);
        $totalScore += $pricing['score'] * self::WEIGHTS['pricing'];
        $breakdown['pricing'] = $pricing;

        // 5. Media (varsayılan)
        $media = $this->scoreMedia($ilan);
        $totalScore += $media['score'] * self::WEIGHTS['media'];
        $breakdown['media'] = $media;

        return [
            'score' => (int) min(100, max(0, round($totalScore))),
            'breakdown' => $breakdown,
            'grade' => $this->scoreToGrade((int) min(100, round($totalScore))),
        ];
    }

    /**
     * Doluluk skoru.
     */
    private function scoreCompleteness(Ilan $ilan, array $ilanData): array
    {
        $totalFields = 0;
        $filledFields = 0;

        $importantFields = [
            'baslik', 'fiyat', 'brut_m2', 'kategori', 'yayin_tipi',
            'adres', 'lat', 'lng', 'bina_yasi', 'oda_sayisi',
            'banyo_sayisi', 'kat', 'isinma_tipi', 'esyali',
        ];

        foreach ($importantFields as $field) {
            $totalFields++;
            $value = $ilanData[$field] ?? $ilan->$field ?? null;
            if ($value !== null && $value !== '') {
                $filledFields++;
            }
        }

        $ratio = $totalFields > 0 ? $filledFields / $totalFields : 0;

        return [
            'score' => (int) round($ratio * 100),
            'max' => 100,
            'weight' => self::WEIGHTS['completeness'],
            'label' => 'Doluluk',
            'detail' => "{$filledFields}/{$totalFields} alan dolu",
        ];
    }

    /**
     * Zenginlik skoru (özellik sayısı + detay).
     */
    private function scoreRichness(Ilan $ilan): array
    {
        $featureCount = DB::table('feature_assignments')
            ->where('assignable_type', Ilan::class)
            ->where('assignable_id', $ilan->getKey())
            ->count();

        // Feature count'a göre puan (20 özellik = 100 puan)
        $ratio = min(1.0, $featureCount / 20);

        return [
            'score' => (int) round($ratio * 100),
            'max' => 100,
            'weight' => self::WEIGHTS['richness'],
            'label' => 'Zenginlik',
            'detail' => "{$featureCount} özellik atanmış",
        ];
    }

    /**
     * Konum skoru.
     */
    private function scoreLocation(Ilan $ilan): array
    {
        $score = 0;
        $max = 100;

        if ($ilan->lat && $ilan->lng) {
            $score += 50; // Koordinat varsa
        }

        if ($ilan->adres) {
            $score += 30; // Adres varsa
        }

        if ($ilan->ilce_id || $ilan->mahalle_id) {
            $score += 20; // İlçe/mahalle varsa
        }

        return [
            'score' => $score,
            'max' => $max,
            'weight' => self::WEIGHTS['location'],
            'label' => 'Konum',
            'detail' => $score >= 80 ? 'Tam konum bilgisi' : ($score >= 50 ? 'Kısmi konum' : 'Konum eksik'),
        ];
    }

    /**
     * Fiyatlandırma skoru.
     */
    private function scorePricing(Ilan $ilan, array $ilanData): array
    {
        $score = 0;
        $max = 100;

        if ($ilan->fiyat && $ilan->fiyat > 0) {
            $score += 50; // Fiyat var
        }

        if ($ilan->fiyat_gosterim_modu) {
            $score += 20; // Gösterim modu belirlenmiş
        }

        if ($ilan->para_birimi) {
            $score += 15; // Para birimi var
        }

        if ($ilan->fiyat && $ilan->brut_m2 && $ilan->brut_m2 > 0) {
            $score += 15; // Birim fiyat hesaplanabilir
        }

        return [
            'score' => $score,
            'max' => $max,
            'weight' => self::WEIGHTS['pricing'],
            'label' => 'Fiyatlandırma',
            'detail' => $ilan->fiyat ? number_format($ilan->fiyat) . ' TL' : 'Fiyat yok',
        ];
    }

    /**
     * Medya skoru (varsayılan — fotoğraf sayısı bilinmiyorsa).
     */
    private function scoreMedia(Ilan $ilan): array
    {
        // Fotoğraf sayısı workspace'ten alınabilir ama şimdilik varsayılan
        return [
            'score' => 50, // Belirsiz = orta
            'max' => 100,
            'weight' => self::WEIGHTS['media'],
            'label' => 'Medya',
            'detail' => 'Medya kontrolü gerekiyor',
        ];
    }

    /**
     * Skoru nota çevir.
     */
    private function scoreToGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }
}
