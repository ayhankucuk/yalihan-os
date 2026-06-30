<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';

use App\Models\IlanKategoriYayinTipi;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$junctions = IlanKategoriYayinTipi::with(['kategori', 'globalTip'])->get();
$data = $junctions->map(function($j) {
    return [
        'kategori_id' => $j->kategori_id,
        'junction_id' => $j->id,
        'name' => ($j->kategori->ad ?? "Cat {$j->kategori_id}") . " + " . ($j->globalTip->ad ?? $j->yayin_tipi ?? "Type {$j->yayin_tipi_id}")
    ];
});

echo json_encode($data, JSON_PRETTY_PRINT);
