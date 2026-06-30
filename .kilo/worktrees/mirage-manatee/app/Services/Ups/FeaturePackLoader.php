<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeaturePack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FeaturePackLoader
 *
 * Global pazarlar için hazırlanan özellik paketlerini (JSON tabanlı)
 * sisteme dinamik olarak yükleyen ve yöneten "Plug-and-Play" servisi.
 */
class FeaturePackLoader
{
    private string $packDirectory;

    public function __construct()
    {
        // Önce production klasörüne bak, yoksa POC klasörüne düş.
        $prodPath = config('ups.packs_path', base_path('config/ups/packs'));
        $this->packDirectory = File::exists($prodPath) ? $prodPath : base_path('docs/technical/geo_packs');
    }

    /**
     * Tüm uygun feature paketlerini bulur ve listeler.
     */
    public function listAvailablePacks(): array
    {
        if (!File::exists($this->packDirectory)) {
            Log::warning("FeaturePackLoader: Pack directory not found: {$this->packDirectory}");
            return [];
        }

        $packs = [];
        $files = File::files($this->packDirectory);

        foreach ($files as $file) {
            $data = $this->parseFile($file);
            if ($data) {
                $packs[] = $data;
            }
        }

        return $packs;
    }

    /**
     * Belirli bir dasyayı tipine göre parse eder.
     */
    private function parseFile(\Symfony\Component\Finder\SplFileInfo $file): ?array
    {
        $extension = $file->getExtension();
        $content = $file->getContents();

        if ($extension === 'json') {
            return json_decode($content, true);
        }

        if ($extension === 'md') {
            // Markdown içindeki JSON bloğunu bul (POC desteği)
            // Use a more robust regex to capture everything between ```json and ```
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                return json_decode($matches[1], true);
            }
        }

        return null;
    }

    /**
     * Belirtilen bölgeye (Region) ait paketi getirir.
     */
    public function getPackByRegion(string $regionCode): ?array
    {
        return collect($this->listAvailablePacks())
            ->first(fn($p) => strtoupper($p['region_code'] ?? '') === strtoupper($regionCode));
    }

    /**
     * Paketi ve içindeki özellikleri veritabanına mühürler (Sync).
     */
    public function syncPackToDatabase(string $packSlug): bool
    {
        $packData = collect($this->listAvailablePacks())->first(fn($p) => ($p['pack_slug'] ?? '') === $packSlug);

        if (!$packData) {
            Log::error("FeaturePackLoader: Pack not found: {$packSlug}");
            return false;
        }

        try {
            DB::beginTransaction();

            // 1. FeaturePack Kaydı
            $packModel = FeaturePack::updateOrCreate(
                ['slug' => $packData['pack_slug']],
                [
                    'name' => $packData['name'],
                    'description' => $packData['description'] ?? '',
                    'aktiflik_durumu' => true,
                    'region_code' => $packData['region_code'] ?? null,
                ]
            );

            // 2. Özellikleri (Features) Oluştur
            foreach ($packData['features'] as $featureDef) {
                $feature = Feature::updateOrCreate(
                    ['slug' => $featureDef['slug']],
                    [
                        'name' => $featureDef['name'],
                        'type' => $featureDef['type'] ?? 'text', // context7-ignore
                        'options' => $featureDef['options'] ?? null,
                        'validation_rules' => $featureDef['validation'] ?? null,
                        'display_order' => $featureDef['display_order'] ?? 0,
                        'aktiflik_durumu' => true,
                    ]
                );

                // Pakete bağla (Using model helper for encapsulation)
                $packModel->addFeature($feature, $featureDef['display_order'] ?? 0);
            }

            DB::commit();
            Log::info("FeaturePackLoader: Successfully synced '{$packSlug}' to database.");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("FeaturePackLoader Sync Failed: " . $e->getMessage());
            return false;
        }
    }
}
