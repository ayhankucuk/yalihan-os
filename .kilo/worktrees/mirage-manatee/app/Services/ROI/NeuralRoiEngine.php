<?php

namespace App\Services\ROI;

use App\Enums\ImarDurumu;
use App\Models\Ilan;

/**
 * Neural ROI Engine (Yatırım Analizörü)
 *
 * Context7: Yatırım analizi için konum, fiyat ve imar verilerini birleştirir.
 *
 * Yasaklı kelimeler yerine mühürlü alanlar kullanılır:
 * - amortisman_suresi_yil
 * - yillik_verim_orani
 * - projeksiyon_matrisi
 * - yatirim_skoru
 *
 * [PROSES_MÜHRÜ: YALIHAN_SPATIAL_ROI_0206]
 */
class NeuralRoiEngine
{
    /**
     * İlan için yatırım analiz kartı üretir.
     *
     * Girdi:
     * - SpatialScoutService çıktısı (konum_etki_skoru + poi_analiz_matrisi)
     * - MarketAnalysisService çıktısı (fiyat_analiz_verisi)
     *
     * @param  Ilan  $ilan
     * @param  array $konumAnalizi
     * @param  array $fiyatAnalizi
     * @return array{
     *   amortisman_suresi_yil: float|null,
     *   yillik_verim_orani: float|null,
     *   projeksiyon_matrisi: array<int, array>,
     *   bes_yillik_tahmin: string,
     *   yatirim_skoru: float|null
     * }
     */
    public function analizEt(Ilan $ilan, array $konumAnalizi, array $fiyatAnalizi): array
    {
        $fiyat = (float) ($ilan->fiyat ?? 0);
        $alanM2 = $this->alanM2Sec($ilan);
        $konumSkoru = (float) ($konumAnalizi['konum_etki_skoru'] ?? 0.0);
        $bolgeOrtalamaM2 = isset($fiyatAnalizi['avg_unit_price']) ? (float) $fiyatAnalizi['avg_unit_price'] : null;

        if ($fiyat <= 0 || $alanM2 === null || $alanM2 <= 0 || $bolgeOrtalamaM2 === null || $bolgeOrtalamaM2 <= 0) {
            return $this->bosKart();
        }

        $ayarlar = $this->ayarlar();

        $konumCarpani = $this->konumCarpaniHesapla($konumSkoru, $ayarlar['konum_carpan_alt'], $ayarlar['konum_carpan_ust']);
        $aylikKiraM2 = $bolgeOrtalamaM2 * $ayarlar['kira_carpan_m2'] * $konumCarpani;
        $tahminiAylikKira = $alanM2 * $aylikKiraM2;

        if ($tahminiAylikKira <= 0) {
            return $this->bosKart();
        }

        $yillikKira = $tahminiAylikKira * 12;
        $amortismanSuresiYil = $fiyat / $yillikKira;
        $yillikVerimOrani = ($yillikKira / $fiyat) * 100;

        $imarDurumuEnum = $this->imarEnumGetir($ilan);
        $projeksiyonMatrisi = $this->projeksiyonOlustur($fiyat, $konumSkoru, $imarDurumuEnum, $ayarlar);
        $besYillikTahminMetni = $this->projeksiyonMetniOlustur($projeksiyonMatrisi);

        $yatirimSkoru = $this->yatirimSkoruHesapla(
            $amortismanSuresiYil,
            $konumSkoru,
            $fiyatAnalizi['market_pulse'] ?? null,
            $ayarlar
        );

        return [
            'amortisman_suresi_yil' => round($amortismanSuresiYil, 2),
            'yillik_verim_orani' => round($yillikVerimOrani, 2),
            'projeksiyon_matrisi' => $projeksiyonMatrisi,
            'bes_yillik_tahmin' => $besYillikTahminMetni,
            'yatirim_skoru' => $yatirimSkoru !== null ? round($yatirimSkoru, 1) : null,
        ];
    }

    /**
     * Mülkün alan bilgisini belirler.
     */
    protected function alanM2Sec(Ilan $ilan): ?float
    {
        if ($ilan->alan_m2) {
            return (float) $ilan->alan_m2;
        }

        if ($ilan->brut_m2) {
            return (float) $ilan->brut_m2;
        }

        if ($ilan->net_m2) {
            return (float) $ilan->net_m2;
        }

        return null;
    }

    /**
     * Konum skoruna göre çarpan hesaplar.
     */
    protected function konumCarpaniHesapla(float $konumSkoru, float $alt, float $ust): float
    {
        $oran = max(0.0, min(1.0, $konumSkoru / 100.0));
        return $alt + ($ust - $alt) * $oran;
    }

    /**
     * İmar durumu enum değerini belirler.
     */
    protected function imarEnumGetir(Ilan $ilan): ?ImarDurumu
    {
        $hamImar = $ilan->imar_statusu ?? null;
        return ImarDurumu::normalize($hamImar);
    }

    /**
     * Değer artış projeksiyon matrisi oluşturur (5 yıllık).
     *
     * Neural Link: konum_skora ve imar_durumu'na göre büyüme oranı belirlenir.
     * Dinamik çarpan: Villa İmarlı için ekstra nadirik çarpanı uygulanır.
     *
     * @return array<int, array{yil: int, tahmini_deger: float, artis_orani_yuzde: float}>
     */
    protected function projeksiyonOlustur(float $baslangicDegeri, float $konumSkoru, ?ImarDurumu $imarDurumu, array $ayarlar): array
    {
        $yillikArtisOrani = $this->yillikArtisOraniHesapla($konumSkoru, $ayarlar);

        if ($imarDurumu === ImarDurumu::VILLA_IMARLI) {
            $yillikArtisOrani += $ayarlar['nadirik_carpan'];
        }

        $yillikArtisOrani = max(0.0, $yillikArtisOrani);

        $projeksiyon = [];
        $mevcutDeger = $baslangicDegeri;

        for ($yil = 1; $yil <= 5; $yil++) {
            $mevcutDeger *= (1 + $yillikArtisOrani);
            $projeksiyon[] = [
                'yil' => $yil,
                'tahmini_deger' => round($mevcutDeger, 2),
                'artis_orani_yuzde' => round($yillikArtisOrani * 100, 2),
            ];
        }

        return $projeksiyon;
    }

    /**
     * Yıllık artış oranını konum skoruna göre belirler.
     */
    protected function yillikArtisOraniHesapla(float $konumSkoru, array $ayarlar): float
    {
        if ($konumSkoru >= 85) {
            return $ayarlar['artis_cok_yuksek'];
        }

        if ($konumSkoru >= 75) {
            return $ayarlar['artis_yuksek'];
        }

        if ($konumSkoru >= 65) {
            return $ayarlar['artis_orta'];
        }

        return $ayarlar['artis_dusuk'];
    }

    /**
     * Projeksiyon matrisinden okunabilir bir özet metin üretir.
     */
    protected function projeksiyonMetniOlustur(array $projeksiyonMatrisi): string
    {
        if (empty($projeksiyonMatrisi)) {
            return '';
        }

        $ilk = $projeksiyonMatrisi[0];
        $son = $projeksiyonMatrisi[count($projeksiyonMatrisi) - 1];

        $toplamArtisYuzde = ($son['tahmini_deger'] > 0 && $ilk['tahmini_deger'] > 0)
            ? (($son['tahmini_deger'] - $ilk['tahmini_deger']) / $ilk['tahmini_deger']) * 100
            : 0.0;

        return sprintf(
            '5 yıllık tahmini değer: %.0f → %.0f (%+.1f%%)',
            $ilk['tahmini_deger'],
            $son['tahmini_deger'],
            $toplamArtisYuzde
        );
    }

    /**
     * Yatırım skorunu amortisman süresi, konum değeri ve piyasa nabzına göre hesaplar.
     */
    protected function yatirimSkoruHesapla(float $amortismanSuresiYil, float $konumSkoru, ?string $marketPulse, array $ayarlar): ?float
    {
        if ($amortismanSuresiYil <= 0) {
            return null;
        }

        $amortismanPuan = $this->amortismanPuanHaritalama(
            $amortismanSuresiYil,
            $ayarlar['amortisman_ideal_alt'],
            $ayarlar['amortisman_ideal_ust']
        );

        $trendPuan = match ($marketPulse) {
            'high' => 80.0,
            'medium' => 60.0,
            'low' => 40.0,
            default => 50.0,
        };

        $amortismanAgirlik = $ayarlar['agirlik_amortisman'];
        $konumAgirlik = $ayarlar['agirlik_konum'];
        $trendAgirlik = $ayarlar['agirlik_trend'];

        $skor = ($amortismanPuan * $amortismanAgirlik)
            + ($konumSkoru * $konumAgirlik)
            + ($trendPuan * $trendAgirlik);

        return max(0.0, min(100.0, $skor));
    }

    /**
     * Amortisman süresini 0-100 arası puana çevirir.
     */
    protected function amortismanPuanHaritalama(float $yilSayisi, float $idealAlt, float $idealUst): float
    {
        if ($yilSayisi <= $idealAlt) {
            return 100.0;
        }

        if ($yilSayisi >= $idealUst) {
            return 10.0;
        }

        $oran = ($yilSayisi - $idealAlt) / max(1.0, ($idealUst - $idealAlt));

        return 100.0 - ($oran * 90.0);
    }

    /**
     * Boş kart çıktısı.
     */
    protected function bosKart(): array
    {
        return [
            'amortisman_suresi_yil' => null,
            'yillik_verim_orani' => null,
            'projeksiyon_matrisi' => [],
            'bes_yillik_tahmin' => '',
            'yatirim_skoru' => null,
        ];
    }

    /**
     * Motor ayarları (Sabitler).
     */
    protected function ayarlar(): array
    {
        return [
            'kira_carpan_m2' => 0.006,
            'konum_carpan_alt' => 0.8,
            'konum_carpan_ust' => 1.2,
            'artis_cok_yuksek' => 0.08,
            'artis_yuksek' => 0.06,
            'artis_orta' => 0.04,
            'artis_dusuk' => 0.02,
            'nadirik_carpan' => 0.05,
            'amortisman_ideal_alt' => 10.0,
            'amortisman_ideal_ust' => 30.0,
            'agirlik_amortisman' => 0.4,
            'agirlik_konum' => 0.4,
            'agirlik_trend' => 0.2,
        ];
    }
}

