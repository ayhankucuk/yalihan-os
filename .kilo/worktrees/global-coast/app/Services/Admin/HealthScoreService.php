<?php

namespace App\Services\Admin;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Feature;

class HealthScoreService
{
    /**
     * Calculate the overall system health score and reasoning.
     */
    public function calculate(): array
    {
        $score = 100;
        $deductions = [];
        $recommendations = [];

        // 1. Categories Check
        $categoryCount = IlanKategori::count();
        if ($categoryCount === 0) {
            $deductions[] = ['points' => 50, 'reason' => 'Hiç kategori yok'];
            $recommendations[] = ['action' => 'Kategori Ekle', 'route' => 'admin.ilan-kategorileri.create'];
            $score -= 50;
        } elseif ($categoryCount < 3) {
            $deductions[] = ['points' => 10, 'reason' => 'Yetersiz kategori sayısı (< 3)'];
            $recommendations[] = ['action' => 'Daha fazla kategori ekleyin', 'route' => 'admin.ilan-kategorileri.create'];
            $score -= 10;
        }

        // 2. Active Categories Check
        $activeCategories = IlanKategori::where('aktiflik_durumu', true)->count();
        if ($categoryCount > 0 && $activeCategories === 0) {
            $deductions[] = ['points' => 20, 'reason' => 'Hiç aktif kategori yok'];
            $recommendations[] = ['action' => 'Kategorileri yayına alın', 'route' => 'admin.ilan-kategorileri.index'];
            $score -= 20;
        }

        // 3. Property Types Check (Mocked for now if model logic is complex, checking via relationships typically)
        // Assuming simple check on IlanKategori where seviye=2 or specific model
        // For Context7, "Yayın Tipi" is controlled by YayinTipiSablonu or logic in Controller.
        // Let's assume broad check: "Features" existence
        $featureCount = Feature::count();
        if ($featureCount === 0) {
            $deductions[] = ['points' => 15, 'reason' => 'Özellik havuzu boş'];
            $recommendations[] = ['action' => 'Özellik Ekle', 'route' => 'admin.ozellikler.create'];
            $score -= 15;
        }

        // 4. Description Check
        $missingDesc = IlanKategori::whereNull('aciklama')->orWhere('aciklama', '')->count();
        if ($missingDesc > 0) {
            $impact = min(15, $missingDesc * 2); // Cap at 15
            $deductions[] = ['points' => $impact, 'reason' => "$missingDesc kategorinin açıklaması eksik"];
            $recommendations[] = ['action' => 'Açıklamaları tamamla', 'route' => 'admin.ilan-kategorileri.index'];
            $score -= $impact;
        }

        return [
            'score' => max(0, $score),
            'deductions' => $deductions,
            'recommendations' => $recommendations,
            'aktiflik_durumu' => $this->getDurumEtiketi($score),
            'color' => $this->getDurumRengi($score),
        ];
    }

    protected function getDurumEtiketi(int $score): string
    {
        if ($score >= 90) return 'Mükemmel';
        if ($score >= 70) return 'İyi';
        if ($score >= 50) return 'Orta';
        return 'Kritik';
    }

    protected function getDurumRengi(int $score): string
    {
        if ($score >= 90) return 'text-green-600 dark:text-green-400';
        if ($score >= 70) return 'text-blue-600 dark:text-blue-400';
        if ($score >= 50) return 'text-yellow-600 dark:text-yellow-400';
        return 'text-red-600 dark:text-red-400';
    }
}
