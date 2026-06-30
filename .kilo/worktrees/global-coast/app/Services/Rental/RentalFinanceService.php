<?php

namespace App\Services\Rental;

use App\Models\RentalEvKarti;
use App\Models\RentalGelirKalemi;
use App\Models\RentalGiderKalemi;

/**
 * SAB Phase 17C: Rental Finance Service
 *
 * Tek hesaplama noktası. Controller hesap yapmaz.
 * Gelir/gider/net/depozito özeti bu servisten gelir.
 */
class RentalFinanceService
{
    /**
     * Aylık finansal özet.
     *
     * @return array{toplam_gelir: float, toplam_gider: float, net: float, depozito_toplam: float, gelir_detay: array, gider_detay: array}
     */
    public function calculateMonthlySummary(RentalEvKarti $evKarti, int $yil, int $ay): array
    {
        $gelirler = $evKarti->gelirler()
            ->where('donem_yil', $yil)
            ->where('donem_ay', $ay)
            ->get();

        $giderler = $evKarti->giderler()
            ->where('donem_yil', $yil)
            ->where('donem_ay', $ay)
            ->get();

        $toplamGelir = $gelirler
            ->where('kalem_turu', '!=', RentalGelirKalemi::KALEM_DEPOZITO)
            ->sum('tutar');

        $depozitoToplam = $gelirler
            ->where('kalem_turu', RentalGelirKalemi::KALEM_DEPOZITO)
            ->sum('tutar');

        $toplamGider = $giderler->sum('tutar');

        return [
            'ev_karti_id'     => $evKarti->id,
            'donem_yil'       => $yil,
            'donem_ay'        => $ay,
            'toplam_gelir'    => round($toplamGelir, 2),
            'toplam_gider'    => round($toplamGider, 2),
            'net'             => round($toplamGelir - $toplamGider, 2),
            'depozito_toplam' => round($depozitoToplam, 2),
            'gelir_detay'     => $gelirler->map(fn($g) => [
                'id'         => $g->id,
                'kalem_turu' => $g->kalem_turu,
                'etiket'     => $g->kalem_turu_etiketi,
                'tutar'      => $g->tutar,
                'aciklama'   => $g->aciklama,
            ])->values()->toArray(),
            'gider_detay'     => $giderler->map(fn($g) => [
                'id'         => $g->id,
                'kalem_turu' => $g->kalem_turu,
                'etiket'     => $g->kalem_turu_etiketi,
                'tutar'      => $g->tutar,
                'aciklama'   => $g->aciklama,
            ])->values()->toArray(),
        ];
    }

    /**
     * Yıllık finansal özet (12 aylık toplam).
     *
     * @return array{toplam_gelir: float, toplam_gider: float, net: float, depozito_toplam: float, aylik: array}
     */
    public function calculateYearSummary(RentalEvKarti $evKarti, int $yil): array
    {
        $aylikOzet = [];
        $yillikGelir = 0;
        $yillikGider = 0;
        $yillikDepozito = 0;

        for ($ay = 1; $ay <= 12; $ay++) {
            $ozet = $this->calculateMonthlySummary($evKarti, $yil, $ay);
            $aylikOzet[] = $ozet;
            $yillikGelir += $ozet['toplam_gelir'];
            $yillikGider += $ozet['toplam_gider'];
            $yillikDepozito += $ozet['depozito_toplam'];
        }

        return [
            'ev_karti_id'     => $evKarti->id,
            'yil'             => $yil,
            'toplam_gelir'    => round($yillikGelir, 2),
            'toplam_gider'    => round($yillikGider, 2),
            'net'             => round($yillikGelir - $yillikGider, 2),
            'depozito_toplam' => round($yillikDepozito, 2),
            'aylik'           => $aylikOzet,
        ];
    }
}
