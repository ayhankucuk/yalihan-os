<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PropertyType\PropertyTypeService;
use Illuminate\Support\Facades\File;

echo "Scanning Property Types...\n";

// Use the service
$service = app(PropertyTypeService::class);
$mainCategories = $service->getMainCategories();

$report = $mainCategories->map(function($mainCat) use ($service) {
    try {
        $subCats = $service->getSubCategories($mainCat->id);

        $subCategoriesData = $subCats->map(function($sub) use ($service) {
            $yayinTipleri = $service->getYayinTipleri($sub->id);
            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'slug' => $sub->slug,
                'durum_etiketi' => $sub->aktiflik_durumu ? 'Active' : 'Passive',
                'publication_types' => $yayinTipleri->map(fn($yt) => [
                    'id' => $yt->id,
                    'name' => $yt->ad ?? $yt->name, // Handle legacy naming
                    'durum_etiketi' => $yt->aktiflik_durumu ? 'Active' : 'Passive'
                ])->values()
            ];
        })->values();

        // Also get publication types for main category directly (if any)
        $mainYayinTipleri = $service->getYayinTipleri($mainCat->id);

        return [
            'id' => $mainCat->id,
            'name' => $mainCat->name,
            'slug' => $mainCat->slug,
            'durum_etiketi' => $mainCat->aktiflik_durumu ? 'Active' : 'Passive',
            'publication_types' => $mainYayinTipleri->map(fn($yt) => [
                'id' => $yt->id,
                'name' => $yt->ad ?? $yt->name,
                'durum_etiketi' => $yt->aktiflik_durumu ? 'Active' : 'Passive'
            ])->values(),
            'sub_categories' => $subCategoriesData
        ];
    } catch (\Exception $e) {
        return [
            'id' => $mainCat->id,
            'name' => $mainCat->name,
            'error' => $e->getMessage()
        ];
    }
});

$outputPath = __DIR__ . '/../../storage/app/property_type_scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Scanned types for " . $mainCategories->count() . " main categories.\n";
echo "Report saved to: " . $outputPath . "\n";
