<?php

namespace App\Domain\Workforce\Support;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;

/**
 * PhotoIntelligence — Sprint 7.3
 *
 * Workspace'teki fotoğrafları analiz eder.
 * AI Vision yoksa kural-tabanlı öneri üretir.
 */
class PhotoIntelligence
{
    /**
     * Fotoğraf envanteri çıkarır ve analiz sonucu döndürür.
     *
     * @return array{
     *     toplam_foto: int,
     *     kapak_onerilen: string|null,
     *     envanter: array<string, string>,
     *     eksikler: array<string>,
     *     kapak_ozellikleri: array<string>,
     *     puan: int,
     *     source: string
     * }
     */
    public function analyze(PortfolioDriveWorkspace $workspace): array
    {
        $ilan = $workspace->ilan;
        $fotos = $this->getWorkspacePhotos($workspace);

        $toplamFoto = count($fotos);
        $envanter = $this->categorizePhotos($fotos, $ilan);
        $eksikler = $this->detectMissingCategories($envanter, $ilan);
        $kapak = $this->recommendCover($fotos, $envanter, $ilan);
        $puan = $this->scorePhotoQuality($toplamFoto, $envanter, $eksikler);

        return [
            'toplam_foto' => $toplamFoto,
            'kapak_onerilen' => $kapak,
            'envanter' => $envanter,
            'eksikler' => $eksikler,
            'kapak_ozellikleri' => $this->describeCover($kapak, $envanter),
            'puan' => $puan,
            'source' => 'workspace',
        ];
    }

    /**
     * Workspace fotoğraf dosyalarını al.
     *
     * @return array<int, array{filename: string, path: string}>
     */
    private function getWorkspacePhotos(PortfolioDriveWorkspace $workspace): array
    {
        // Drive folder'dan dosya listesi metadata_json'de olabilir
        $meta = $workspace->metadata_json ?? [];
        $photos = $meta['photos'] ?? $meta['files'] ?? [];

        if (!empty($photos)) {
            return array_map(fn($f) => is_array($f) ? $f : ['filename' => $f, 'path' => $f], $photos);
        }

        // Workspace folder path'inden dosya okuma
        $folderPath = $workspace->root_folder_name ?? 'workspace_' . $workspace->getKey();
        $fullPath = storage_path("app/drive/{$folderPath}/fotograf");

        if (!is_dir($fullPath)) {
            return [];
        }

        $files = glob($fullPath . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
        $result = [];

        foreach ($files as $i => $path) {
            $result[$i] = [
                'filename' => basename($path),
                'path' => $path,
            ];
        }

        return $result;
    }

    /**
     * Fotoğrafları kategorilere ayır.
     *
     * @param array<int, array{filename: string, path: string}> $fotos
     * @return array<string, string> category => filename
     */
    private function categorizePhotos(array $fotos, ?Ilan $ilan): array
    {
        $categories = [];

        foreach ($fotos as $foto) {
            $name = mb_strtolower($foto['filename']);
            $assigned = false;

            // KAPAK — genelde IMG_0001 veya cover/kapak
            if (!$assigned && (str_contains($name, 'cover') || str_contains($name, 'kapak') || str_contains($name, 'ana') || str_contains($name, '1.jpg') || str_contains($name, '01.jpg'))) {
                $categories['kapak'] = $foto['filename'];
                $assigned = true;
            }

            // SALON
            if (!$assigned && (str_contains($name, 'salon') || str_contains($name, 'living') || str_contains($name, 'oturma'))) {
                $categories['salon'] = $foto['filename'];
                $assigned = true;
            }

            // MUTFAK
            if (!$assigned && (str_contains($name, 'mutfak') || str_contains($name, 'kitchen'))) {
                $categories['mutfak'] = $foto['filename'];
                $assigned = true;
            }

            // YATAK ODASI
            if (!$assigned && (str_contains($name, 'yatak') || str_contains($name, 'bedroom') || str_contains($name, 'y.o') || str_contains($name, 'oda'))) {
                $categories['yatak_odasi'] = $foto['filename'];
                $assigned = true;
            }

            // BANYO
            if (!$assigned && (str_contains($name, 'banyo') || str_contains($name, 'bath'))) {
                $categories['banyo'] = $foto['filename'];
                $assigned = true;
            }

            // CEPHE / DIŞ
            if (!$assigned && (str_contains($name, 'cephe') || str_contains($name, 'dis') || str_contains($name, 'facade') || str_contains($name, 'outdoor') || str_contains($name, 'harici'))) {
                $categories['dis_cephe'] = $foto['filename'];
                $assigned = true;
            }

            // HAVUZ
            if (!$assigned && ($ilan?->havuz_var || str_contains($name, 'havuz') || str_contains($name, 'pool'))) {
                $categories['havuz'] = $foto['filename'];
                $assigned = true;
            }

            // MANZARA
            if (!$assigned && (str_contains($name, 'manzara') || str_contains($name, 'view') || str_contains($name, 'panorama'))) {
                $categories['manzara'] = $foto['filename'];
                $assigned = true;
            }

            // BALKON / TERAS
            if (!$assigned && (str_contains($name, 'balkon') || str_contains($name, 'terrace') || str_contains($name, 'balcony'))) {
                $categories['balkon'] = $foto['filename'];
                $assigned = true;
            }
        }

        return $categories;
    }

    /**
     * Eksik kategorileri tespit eder.
     *
     * @param array<string, string> $envanter
     * @return array<string>
     */
    private function detectMissingCategories(array $envanter, ?Ilan $ilan): array
    {
        $eksikler = [];
        $kategori = $ilan?->alt_kategori ?? $ilan?->kategori ?? '';

        // Her gayrimenkul için asgari gerekli kategoriler
        $gerekli = ['kapak', 'salon'];

        // Banyo — konutsa gerekli
        if (str_contains(mb_strtolower($kategori), 'daire') || str_contains(mb_strtolower($kategori), 'villa') || str_contains(mb_strtolower($kategori), 'konut')) {
            $gerekli[] = 'banyo';
        }

        // Mutfak — villa/daire
        if (str_contains(mb_strtolower($kategori), 'villa') || str_contains(mb_strtolower($kategori), 'daire')) {
            $gerekli[] = 'mutfak';
        }

        // Havuz — yazlık / villa
        if ($ilan?->havuz_var) {
            $gerekli[] = 'havuz';
        }

        foreach ($gerekli as $cat) {
            if (!isset($envanter[$cat])) {
                $eksikler[] = $cat;
            }
        }

        return $eksikler;
    }

    /**
     * En iyi kapak fotoğrafını önerir.
     */
    private function recommendCover(array $fotos, array $envanter, ?Ilan $ilan): ?string
    {
        // Zaten atanmış kapak varsa onu döndür
        if (!empty($envanter['kapak'])) {
            return $envanter['kapak'];
        }

        // Salon veya ilk fotoğraf
        if (!empty($envanter['salon'])) {
            return $envanter['salon'];
        }

        // Manzara varsa — ideal kapak
        if (!empty($envanter['manzara'])) {
            return $envanter['manzara'];
        }

        // Hiç fotoğraf yoksa null
        if (empty($fotos)) {
            return null;
        }

        // İlk fotoğraf
        return $fotos[0]['filename'];
    }

    /**
     * Kapak fotoğrafı özelliklerini tanımla.
     */
    private function describeCover(?string $filename, array $envanter): array
    {
        if (!$filename) return [];

        $labels = [];

        if (isset($envanter['salon'])) $labels[] = 'Salon görünüşü';
        if (isset($envanter['manzara'])) $labels[] = 'Manzara';
        if (isset($envanter['havuz'])) $labels[] = 'Havuz';
        if (isset($envanter['dis_cephe'])) $labels[] = 'Dış cephe';
        if (isset($envanter['yatak_odasi'])) $labels[] = 'İç mekan';

        return $labels ?: ['İlk fotoğraf'];
    }

    /**
     * Fotoğraf kalitesi puanı (0-100).
     *
     * @param array<string, string> $envanter
     * @param array<string> $eksikler
     */
    private function scorePhotoQuality(int $toplamFoto, array $envanter, array $eksikler): int
    {
        if ($toplamFoto == 0) return 0;
        if ($toplamFoto < 5) return 30;
        if ($toplamFoto < 10) return 60;
        if ($toplamFoto < 20) return 80;

        $envanterScore = min(100, count($envanter) * 15);
        $eksikScore = max(0, 100 - count($eksikler) * 25);

        return (int) min(100, ($envanterScore + $eksikScore) / 2);
    }
}
