<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mahalle;
use App\Models\IlanKategori;
use App\Models\IlanKategoriYayinTipi;
use App\Services\Category\FeatureCategoryService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "\n--- Mahalle Table Config ---\n";
try {
    $columns = Schema::getColumnListing('mahalleler');
    echo "Columns: " . implode(', ', $columns) . "\n";
} catch (\Exception $e) {
    echo "Schema Error: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Mahalle Query ---\n";
try {
    $ilce = \App\Models\Ilce::first();
    if ($ilce) {
        $ilceId = $ilce->id;
        echo "Testing with Ilce ID: $ilceId\n";
        
        $neighborhoods = Mahalle::where('ilce_id', $ilceId)
            // Use the exact select from LocationController
            ->select(['id', 'mahalle_adi', 'mahalle_adi as name', 'lat', 'lng'])
            ->limit(1)
            ->get();
            
        echo "Query Successful. Count: " . $neighborhoods->count() . "\n";
        if ($neighborhoods->count() > 0) {
            print_r($neighborhoods->first()->toArray());
        }
    } else {
        echo "No Ilce found.\n";
    }
} catch (\Exception $e) {
    echo "ERROR in Mahalle Query: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Smart Form Logic ---\n";
try {
    // Find a valid Kategori/YayinTipi pair
    $pair = IlanKategoriYayinTipi::first();
    
    if ($pair) {
        $kategoriId = $pair->kategori_id;
        $yayinTipiId = $pair->id;
        echo "Testing with Kategori ID: $kategoriId, YayinTipi ID: $yayinTipiId\n";
        
        $service = app(FeatureCategoryService::class);
        $result = $service->getFeaturesByPublicationType($kategoriId, $yayinTipiId);
        
        echo "Smart Form Service Successful. Result Count: " . $result->count() . "\n";
    } else {
        echo "No IlanKategoriYayinTipi found.\n";
    }
} catch (\Exception $e) {
    echo "ERROR in Smart Form Logic: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
