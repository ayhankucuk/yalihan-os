<?php

namespace App\Domain\Workforce\Support;

use App\Models\FeaturePack;
use App\Models\Ilan;
use App\Models\Ozellik;

/**
 * FeaturePackRecommender — Sprint 7.2 Phase 2
 *
 * Bir ilan için en uygun Feature Pack'i önerir.
 * Mevcut özellikler + kategori + yayın tipi kombinasyonuna göre seçim yapar.
 */
class FeaturePackRecommender
{
    /**
     * Bir ilan için en uygun Feature Pack'i döndürür.
     *
     * @param array<string, mixed> $ilanData
     */
    public function recommend(array $ilanData, Ilan $ilan): ?FeaturePack
    {
        $kategori = $ilanData['kategori'] ?? $ilan->kategori;
        $altKategori = $ilanData['alt_kategori'] ?? $ilan->alt_kategori;
        $yayinTipi = $ilanData['yayin_tipi'] ?? $ilan->yayin_tipi;
        $ozellikler = $this->getIlanOzellikleri($ilan);

        // Aktif paketleri al
        $packs = FeaturePack::aktif()->ordered()->get();

        if ($packs->isEmpty()) {
            return null;
        }

        $bestPack = null;
        $bestScore = -1;

        foreach ($packs as $pack) {
            $score = $this->calculateMatchScore($pack, $kategori, $altKategori, $yayinTipi, $ozellikler);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPack = $pack;
            }
        }

        return $bestPack;
    }

    /**
     * Paketin ilanla eşleşme skorunu hesapla.
     *
     * @param array<int, string> $ozellikler
     */
    private function calculateMatchScore(
        FeaturePack $pack,
        ?string $kategori,
        ?string $altKategori,
        ?string $yayinTipi,
        array $ozellikler
    ): int {
        $score = 0;

        // Kategori eşleşmesi (en yüksek ağırlık)
        $kategoriIds = $pack->kategori_ids ?? [];
        if (!empty($kategoriIds)) {
            $kategoriName = $this->kategoriIdToName($kategoriIds, $altKategori ?? $kategori ?? '');
            if ($altKategori && $this->matchesCategory($altKategori, $kategoriIds)) {
                $score += 50;
            } elseif ($kategori && $this->matchesCategory($kategori, $kategoriIds)) {
                $score += 30;
            }
        } else {
            $score += 10; // Genel paket
        }

        // Yayın tipi eşleşmesi
        $yayinTipiIds = $pack->yayin_tipi_ids ?? [];
        if (!empty($yayinTipiIds) && $yayinTipi) {
            if ($this->matchesYayinTipi($yayinTipi, $yayinTipiIds)) {
                $score += 20;
            }
        }

        // Özellik eşleşmesi
        $packFeatureCount = $pack->items()->count();
        if ($packFeatureCount > 0) {
            $score += min($packFeatureCount, 20); // En fazla 20 puan özellik sayısından
        }

        return $score;
    }

    /**
     * İlanın özellik atamalarını al.
     *
     * @return array<int, string>
     */
    private function getIlanOzellikleri(Ilan $ilan): array
    {
        $ozellikler = \Illuminate\Support\Facades\DB::table('feature_assignments')
            ->join('ozellikler', 'feature_assignments.feature_id', '=', 'ozellikler.id')
            ->where('feature_assignments.assignable_type', Ilan::class)
            ->where('feature_assignments.assignable_id', $ilan->getKey())
            ->pluck('ozellikler.slug', 'ozellikler.id')
            ->toArray();

        return $ozellikler;
    }

    /**
     * Kategori eşleşmesi.
     *
     * @param array<int> $kategoriIds
     */
    private function matchesCategory(string $kategori, array $kategoriIds): bool
    {
        $kategoriLower = mb_strtolower($kategori);

        foreach ($kategoriIds as $id) {
            $k = \App\Models\IlanKategori::find($id);
            if ($k && mb_strtolower($k->name) === $kategoriLower) {
                return true;
            }
            // Alt kategori kontrolü
            if ($k) {
                $parentName = $k->parent?->name;
                if ($parentName && mb_strtolower($parentName) === $kategoriLower) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Yayın tipi eşleşmesi.
     *
     * @param array<int> $yayinTipiIds
     */
    private function matchesYayinTipi(string $yayinTipi, array $yayinTipiIds): bool
    {
        $yayinTipiLower = mb_strtolower($yayinTipi);

        foreach ($yayinTipiIds as $id) {
            $yt = \App\Models\YayinTipiSablonu::find($id);
            if ($yt && mb_strtolower($yt->name) === $yayinTipiLower) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kategori ID'lerinden isim çıkar.
     */
    private function kategoriIdToName(array $ids, string $fallback): string
    {
        foreach ($ids as $id) {
            $k = \App\Models\IlanKategori::find($id);
            if ($k) return $k->name;
        }
        return $fallback;
    }
}
