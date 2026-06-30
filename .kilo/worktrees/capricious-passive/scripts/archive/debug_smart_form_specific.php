<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Category\FeatureCategoryService;

// Test Smart Form Logic specifically for 6/6
echo "\n--- Testing Smart Form Logic (6/6) ---\n";
try {
    $kategoriId = 6;
    $yayinTipiId = 6;
    echo "Testing with Kategori ID: $kategoriId, YayinTipi ID: $yayinTipiId\n";
    
    $service = app(FeatureCategoryService::class);
    $result = $service->getFeaturesByPublicationType($kategoriId, $yayinTipiId);
    
    echo "Smart Form Service Successful. Result Count: " . $result->count() . "\n";
} catch (\Exception $e) {
    echo "ERROR in Smart Form Logic: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
