<?php

namespace App\Services\Listing;

use App\Models\Ilan;

/**
 * ListingScoreService
 *
 * SAB Phase 17B §3: Score Splitting
 *
 * completion_score: Zorunlu alanların tam doluluğu (0-100, PUBLISH GATE)
 * quality_score   : Optimizasyon puanı (0-100, sadece ranking etkiler)
 */
class ListingScoreService
{
    /**
     * Compute and save scores to model (Phase 17B §3)
     */
    public function refreshAndPersistScores(Ilan $ilan): void
    {
        $ilan->completion_score = $this->computeCompletionScore($ilan);
        $ilan->quality_score    = $this->computeQualityScore($ilan);
        $ilan->save();
    }

    /**
     * Zorunlu alanlar — bunların tümü dolu olmalı.
     */
    private const ZORUNLU = [
        'baslik'          => 'Başlık',
        'aciklama'        => 'Açıklama',
        'fiyat'           => 'Fiyat',
        'il_id'           => 'İl',
        'ilce_id'         => 'İlçe',
        'ana_kategori_id' => 'Kategori',
        'yayin_tipi_id'   => 'Yayın Tipi (Junction)',
        'ilan_sahibi_id'  => 'İlan Sahibi',
    ];

    /**
     * @return int 0-100 arası completion skoru
     */
    public function computeCompletionScore(Ilan $ilan): int
    {
        $doluluklar = [];

        foreach (self::ZORUNLU as $alan => $label) {
            $doluluklar[] = $this->alanDolu($ilan, $alan) ? 1 : 0;
        }

        $fotografVar = $this->hasFotograf($ilan);
        $doluluklar[] = $fotografVar ? 1 : 0;

        $toplamKriteri = count(self::ZORUNLU) + 1;
        $tammlanan     = array_sum($doluluklar);

        return (int) round(($tammlanan / $toplamKriteri) * 100);
    }

    /**
     * @return float 0-100 arası quality skoru
     */
    public function computeQualityScore(Ilan $ilan): float
    {
        $puan = 0;

        // Başlık kalitesi (0-30)
        $puan += (int) round($this->baslikKalite($ilan->baslik) * 0.30);

        // Açıklama kalitesi (0-40)
        $puan += (int) round($this->aciklamaKalite($ilan->aciklama) * 0.40);

        // Fotoğraf bonus (0-30): 3+ fotoğraf = tam puan
        if ($this->hasFotograf($ilan)) {
            $adet = $ilan->relationLoaded('fotograflar')
                ? $ilan->fotograflar->count()
                : $ilan->fotograflar()->count();
            $puan += min(30, (int) round($adet / 3 * 30));
        }

        return (float) min(100, $puan);
    }

    /**
     * API Response'ları ve Dashboard için detaylı analiz
     */
    public function computeBreakdown(Ilan $ilan): array
    {
        $zorunluAlanlar = ['ok' => true, 'missing' => []];

        foreach (self::ZORUNLU as $alan => $label) {
            if (!$this->alanDolu($ilan, $alan)) {
                $zorunluAlanlar['ok'] = false;
                $zorunluAlanlar['missing'][] = $label;
            }
        }

        $fotografCount = $ilan->relationLoaded('fotograflar')
            ? $ilan->fotograflar->count()
            : $ilan->fotograflar()->count();

        return [
            'zorunlu_alanlar' => $zorunluAlanlar,
            'baslik'          => ['ok' => $this->alanDolu($ilan, 'baslik')],
            'aciklama'        => ['ok' => $this->alanDolu($ilan, 'aciklama')],
            'fotograf'        => [
                'ok'    => $fotografCount >= 1,
                'count' => $fotografCount,
                'min'   => 1
            ]
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function hasFotograf(Ilan $ilan): bool
    {
        return $ilan->relationLoaded('fotograflar')
            ? $ilan->fotograflar->isNotEmpty()
            : $ilan->fotograflar()->exists();
    }

    private function alanDolu(Ilan $ilan, string $alan): bool
    {
        $deger = $ilan->getAttribute($alan);

        if ($alan === 'fiyat') {
            return is_numeric($deger) && (float) $deger > 0;
        }

        if ($alan === 'aciklama') {
            return is_string($deger) && mb_strlen(trim($deger)) >= 50;
        }

        if ($alan === 'baslik') {
            return is_string($deger) && mb_strlen(trim($deger)) >= 10;
        }

        return ! empty($deger);
    }

    private function baslikKalite(?string $baslik): int
    {
        if (! $baslik) return 0;
        $len = mb_strlen(trim($baslik));
        if ($len >= 40 && $len <= 80) return 100;
        if ($len >= 15) return 60;
        return 20;
    }

    private function aciklamaKalite(?string $aciklama): int
    {
        if (! $aciklama) return 0;
        $len = mb_strlen(trim($aciklama));
        if ($len >= 300) return 100;
        if ($len >= 100) return 60;
        if ($len >= 50) return 30;
        return 10;
    }
}

