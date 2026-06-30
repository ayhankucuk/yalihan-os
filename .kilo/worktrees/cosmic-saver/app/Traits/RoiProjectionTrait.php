<?php

namespace App\Traits;

use App\Services\ROI\NeuralRoiEngine;

trait RoiProjectionTrait
{
    /**
     * İlan için yatırım analiz kartını üretir.
     *
     * Bu metod, dışarıdan sağlanan konum ve fiyat analiz verilerini kullanır.
     *
     * @param  array  $konumAnalizi   SpatialScoutService çıktısı
     * @param  array  $fiyatAnalizi   MarketAnalysisService çıktısı
     * @return array{
     *   amortisman_suresi_yil: float|null,
     *   yillik_verim_orani: float|null,
     *   projeksiyon_matrisi: array<int, array>,
     *   bes_yillik_tahmin: string,
     *   yatirim_skoru: float|null
     * }
     */
    public function olusturYatirimAnalizKarti(array $konumAnalizi, array $fiyatAnalizi): array
    {
        /** @var NeuralRoiEngine $engine */
        $engine = app(NeuralRoiEngine::class);

        return $engine->analizEt($this, $konumAnalizi, $fiyatAnalizi);
    }
}

