<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Seeding alt_kategori_yayin_tipi pivot table...\n\n";

// Alt kategoriler (child categories)
$altKategoriler = [
    7  => 'Daire',
    8  => 'Villa',
    9  => 'Müstakil Ev',
    10 => 'Dubleks',
    11 => 'Ofis',
    12 => 'Dükkan',
    13 => 'Fabrika',
    14 => 'Depo',
    15 => 'Arsa (Konut/Villa)',
    16 => 'Sanayi & Ticari İmar',
    17 => 'Tarla',
    18 => 'Zeytinlik',
    19 => 'Bağ & Bahçe',
    26 => 'Villa (Yazlık)',
    27 => 'Rezidans',
    28 => 'Daire (Yazlık)',
];

// Temel yayın tipleri
$yayinTipleri = [
    1 => 'Satılık',
    2 => 'Kiralık',
    3 => 'Günlük Kiralama',
];

$count = 0;
foreach ($altKategoriler as $katId => $katName) {
    foreach ($yayinTipleri as $ytId => $ytName) {
        $skip = false;

        // Arsa alt kategorileri (15-19): Sadece Satılık
        if (in_array($katId, [15,16,17,18,19]) && $ytId != 1) $skip = true;

        // Yazlık alt kategorileri (26-28): Kiralık + Günlük (Satılık değil)
        if (in_array($katId, [26,27,28]) && $ytId == 1) $skip = true;

        // Konut/İşyeri alt kategorileri (7-14): Satılık + Kiralık (Günlük yok)
        if (in_array($katId, [7,8,9,10,11,12,13,14]) && $ytId == 3) $skip = true;

        if ($skip) continue;

        $exists = DB::table('alt_kategori_yayin_tipi')
            ->where('alt_kategori_id', $katId)
            ->where('yayin_tipi_id', $ytId)
            ->exists();

        if (!$exists) {
            DB::table('alt_kategori_yayin_tipi')->insert([
                'alt_kategori_id' => $katId,
                'yayin_tipi_id' => $ytId,
                'aktiflik_durumu' => true,
                'display_order' => $count,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✅ {$katName} ↔ {$ytName}\n";
            $count++;
        }
    }
}

echo "\n--------------------------------------------------\n";
echo "Pivot records created: {$count}\n";
echo "Total pivot records: " . DB::table('alt_kategori_yayin_tipi')->count() . "\n";
echo "--------------------------------------------------\n";
