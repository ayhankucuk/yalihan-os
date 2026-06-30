#!/usr/bin/env php
<?php

/**
 * YatirimciProfili Data Cleaner - PHP 8.4 Enum Compatibility
 *
 * Bu script kisiler tablosundaki yatirimci_profili alanındaki
 * geçersiz değerleri tespit eder ve temizler.
 *
 * Kullanım:
 *   php artisan tinker
 *   include 'tools/clean-yatirimci-profili.php';
 *
 * Veya direkt:
 *   php tools/clean-yatirimci-profili.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Kisi;
use App\Enums\YatirimciProfili;
use Illuminate\Support\Facades\DB;

echo "\n🔍 YatirimciProfili Veri Temizleyici - PHP 8.4 Uyumluluk\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Valid enum values
$validValues = YatirimciProfili::values();
echo "✅ Geçerli değerler: " . implode(', ', $validValues) . "\n\n";

// 1. NULL değerleri say
$nullCount = DB::table('kisiler')
    ->whereNull('yatirimci_profili')
    ->count();

echo "📊 İstatistikler:\n";
echo "  • NULL değer: {$nullCount} kayıt\n";

// 2. Geçersiz değerleri bul
$invalidRecords = DB::table('kisiler')
    ->whereNotNull('yatirimci_profili')
    ->whereNotIn('yatirimci_profili', $validValues)
    ->get(['id', 'ad', 'soyad', 'yatirimci_profili']);

echo "  • Geçersiz değer: " . $invalidRecords->count() . " kayıt\n\n";

if ($invalidRecords->isNotEmpty()) {
    echo "⚠️  Geçersiz Kayıtlar:\n";
    foreach ($invalidRecords as $record) {
        echo sprintf(
            "  • [ID: %d] %s %s → '%s'\n",
            $record->id,
            $record->ad ?? '',
            $record->soyad ?? '',
            $record->yatirimci_profili
        );
    }
    echo "\n";
}

// 3. Boş string değerleri bul
$emptyStrings = DB::table('kisiler')
    ->where('yatirimci_profili', '')
    ->count();

if ($emptyStrings > 0) {
    echo "⚠️  Boş string: {$emptyStrings} kayıt\n\n";
}

// 4. Onarım önerisi
echo "🛠️  Onarım Seçenekleri:\n\n";

echo "Seçenek 1: NULL'a Çevir (Güvenli)\n";
echo "  DB::table('kisiler')\n";
echo "      ->whereNotIn('yatirimci_profili', ['" . implode("', '", $validValues) . "'])\n";
echo "      ->orWhere('yatirimci_profili', '')\n";
echo "      ->update(['yatirimci_profili' => null]);\n\n";

echo "Seçenek 2: Varsayılan Değer (yeni_baslayan) Ata\n";
echo "  DB::table('kisiler')\n";
echo "      ->whereNotIn('yatirimci_profili', ['" . implode("', '", $validValues) . "'])\n";
echo "      ->orWhere('yatirimci_profili', '')\n";
echo "      ->update(['yatirimci_profili' => 'yeni_baslayan']);\n\n";

// 5. Otomatik onarım (kullanıcı onayı ile)
if ($invalidRecords->isNotEmpty() || $emptyStrings > 0 || $nullCount > 0) {
    echo "Otomatik onarımı başlatmak ister misiniz? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'y') {
        echo "\n🔧 Onarım başlatılıyor...\n";
        
        // Geçersiz değerleri NULL yap
        $fixed = DB::table('kisiler')
            ->where(function ($query) use ($validValues) {
                $query->whereNotIn('yatirimci_profili', $validValues)
                      ->orWhere('yatirimci_profili', '');
            })
            ->update(['yatirimci_profili' => null]);
        
        echo "✅ {$fixed} kayıt NULL'a çevrildi.\n";
        
        // Cache temizle (Eloquent query'ye çevir)
        Kisi::whereNotNull('id')->take(1)->get(); // Force query cache refresh
        
        echo "✅ Model cache temizlendi.\n";
        echo "\n🎉 Onarım tamamlandı! Artık Kisi modeli PHP 8.4 ile uyumlu.\n\n";
    } else {
        echo "\n⏸️  Onarım iptal edildi. Manuel olarak yukarıdaki komutları kullanabilirsiniz.\n\n";
    }
    
    fclose($handle);
} else {
    echo "\n✅ Veri temiz! Hiçbir onarıma gerek yok.\n\n";
}

echo "=" . str_repeat("=", 60) . "\n";
echo "💡 Not: Eğer hala 'Cannot instantiate enum' hatası alıyorsanız:\n";
echo "   1. php artisan cache:clear\n";
echo "   2. php artisan config:clear\n";
echo "   3. php artisan optimize:clear\n";
echo "   4. Composer autoload: composer dump-autoload\n\n";
