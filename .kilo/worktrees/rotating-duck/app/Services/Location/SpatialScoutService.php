<?php

namespace App\Services\Location;

use App\Services\Location\PoiService;

class SpatialScoutService
{
    private PoiService $poiService;

    public function __construct(PoiService $poiService)
    {
        $this->poiService = $poiService;
    }

    /**
     * Konumsal etki skorunu hesapla.
     *
     * @param  float       $lat
     * @param  float       $lng
     * @param  string|null $kategori  (arsa, villa, apartman, isyeri vb. ileride genişletilebilir)
     * @param  float       $yaricapKm
     * @return array{konum_etki_skoru: float, poi_analiz_matrisi: array<int, array>, deger_odagi_mesafesi: int|null, firsat_adayi: bool}
     */
    public function hesapla(float $lat, float $lng, ?string $kategori = null, float $yaricapKm = 1.0): array
    {
        $agirlikHaritasi = config('spatial.poi_agirlik_haritasi', []);

        $tipListesi = $this->toplaTanimliTipler($agirlikHaritasi);

        $poiKoleksiyonu = $this->poiService->findNearby(
            $lat,
            $lng,
            $yaricapKm,
            ['types' => $tipListesi] // context7-ignore
        );

        $toplam = 50.0;
        $analizKayitlari = [];
        $enYuksekPozitif = null;

        foreach ($poiKoleksiyonu as $poi) {
            $tur = $poi['poi_turu'] ?? '';
            $metre = isset($poi['distance'])
                ? (int) $poi['distance']
                : (int) round(($poi['distance_km'] ?? 0) * 1000);

            if ($metre <= 0) {
                continue;
            }

            $katki = 0.0;
            $kaynakEtiketler = [];

            foreach ($agirlikHaritasi as $anahtar => $ayar) {
                if (! $this->turEslesirMi($tur, $ayar['tipler'] ?? [])) {
                    continue;
                }

                [$puan, $etiket] = $this->hesaplaTekPoiKatkisi($metre, $ayar);

                if ($puan === 0.0) {
                    continue;
                }

                $katki += $puan;
                if ($etiket !== null) {
                    $kaynakEtiketler[] = $etiket;
                }
            }

            if ($katki === 0.0) {
                continue;
            }

            $toplam += $katki;

            $analizKayitlari[] = [
                'poi_adi' => $poi['poi_adi'] ?? null,
                'poi_turu' => $tur,
                'metre' => $metre,
                'agirlik_aciklamasi' => implode(' + ', $kaynakEtiketler),
                'puan' => round($katki, 2),
            ];

            if ($katki > 0 && ($enYuksekPozitif === null || $katki > $enYuksekPozitif['puan'])) {
                $enYuksekPozitif = [
                    'metre' => $metre,
                    'puan' => $katki,
                ];
            }
        }

        $skor = max(0.0, min(100.0, $toplam));

        return [
            'konum_etki_skoru' => round($skor, 2),
            'poi_analiz_matrisi' => $analizKayitlari,
            'deger_odagi_mesafesi' => $enYuksekPozitif['metre'] ?? null,
            'firsat_adayi' => $skor >= 80.0,
        ];
    }

    /**
     * Ağırlık haritasındaki tüm POI tiplerini tek düz listeye indirger.
     *
     * @param  array $agirlikHaritasi
     * @return array<int, string>
     */
    private function toplaTanimliTipler(array $agirlikHaritasi): array
    {
        $tipler = [];

        foreach ($agirlikHaritasi as $ayar) {
            foreach ($ayar['tipler'] ?? [] as $tip) {
                $tipler[] = $tip;
            }
        }

        return array_values(array_unique($tipler));
    }

    /**
     * POI türü, tanımlı tip listesi ile eşleşiyor mu?
     *
     * @param  string        $poiTuru
     * @param  array<string> $tipler
     * @return bool
     */
    private function turEslesirMi(string $poiTuru, array $tipler): bool
    {
        if ($poiTuru === '') {
            return false;
        }

        foreach ($tipler as $pattern) {
            if ($pattern === $poiTuru) {
                return true;
            }

            if (str_contains($poiTuru, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tek bir POI için katkı puanını hesapla.
     *
     * @param  int   $metre
     * @param  array $ayar
     * @return array{0: float, 1: ?string}
     */
    private function hesaplaTekPoiKatkisi(int $metre, array $ayar): array
    {
        $puan = 0.0;
        $etiket = null;

        if (isset($ayar['pozitif_esik_metre'], $ayar['pozitif_puan']) && $metre <= (int) $ayar['pozitif_esik_metre']) {
            $puan += (float) $ayar['pozitif_puan'];
            $etiket = $ayar['etiket'] . ' (yakın çevre)';
        }

        if (isset($ayar['negatif_esik_metre'], $ayar['negatif_puan']) && $metre <= (int) $ayar['negatif_esik_metre']) {
            $puan += (float) $ayar['negatif_puan'];
            $etiket = ($etiket ? $etiket . ' / ' : '') . $ayar['etiket'] . ' (olumsuz etki)';
        }

        return [$puan, $etiket];
    }
}

