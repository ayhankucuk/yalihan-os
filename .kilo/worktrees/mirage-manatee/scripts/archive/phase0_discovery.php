#!/usr/bin/env php
<?php
/**
 * PHASE 0: DISCOVERY - Template System Structural Mapping
 * Konut, Arsa, Ticari grupları için kategori-yayın tipi matris analizi
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        PHASE 0: TEMPLATE SYSTEM DISCOVERY                    ║\n";
echo "║        3 Şubat 2026 - Platinum Sealing Context              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// 1. KATEGORİLER
// ============================================================================

echo "📊 [1/4] KATEGORİ TARAMASI\n";
echo str_repeat("─", 65) . "\n";

$kategoriler = DB::table('ilan_kategoriler')
    ->where('aktiflik_durumu', 1)
    ->select('id', 'adi', 'parent_id')
    ->orderBy('id')
    ->get();

$anaKategoriler = $kategoriler->filter(fn($k) => !$k->parent_id);
$altKategoriler = $kategoriler->filter(fn($k) => $k->parent_id);

echo "✅ Ana Kategoriler: " . count($anaKategoriler) . "\n";
foreach ($anaKategoriler as $k) {
    echo "   └─ [{$k->id}] {$k->adi}\n";
}

echo "\n✅ Alt Kategoriler: " . count($altKategoriler) . "\n";
$altGrouped = $altKategoriler->groupBy('parent_id');
foreach ($altGrouped as $parentId => $alts) {
    $parent = $anaKategoriler->where('id', $parentId)->first();
    echo "   {$parent->adi} altı:\n";
    foreach ($alts as $alt) {
        echo "      └─ [{$alt->id}] {$alt->adi}\n";
    }
}

echo "\n🔢 TOPLAM KATEGORİ: " . count($kategoriler) . "\n\n";

// ============================================================================
// 2. YAYIN TİPLERİ
// ============================================================================

echo "📊 [2/4] YAYIN TİPİ TARAMASI\n";
echo str_repeat("─", 65) . "\n";

$yayinTipleriRaw = DB::table('yayin_tipi_sablonlari')
    ->select('id', 'adi')
    ->distinct()
    ->orderBy('id')
    ->get();

echo "✅ Unique Yayın Tipleri: " . count($yayinTipleriRaw) . "\n";
foreach ($yayinTipleriRaw as $y) {
    echo "   └─ [{$y->id}] {$y->adi}\n";
}

echo "\n";

// ============================================================================
// 3. FEATURE POOL
// ============================================================================

echo "📊 [3/4] FEATURE POOL TARAMASI\n";
echo str_repeat("─", 65) . "\n";

$ozellikler = DB::table('ozellikler')
    ->where('aktiflik_durumu', 1)
    ->select('id', 'slug', 'adi')
    ->orderBy('slug')
    ->get();

echo "✅ Toplam Features: " . count($ozellikler) . "\n\n";

// Konut Features
$konutSlugs = ['brut-alan', 'oda-sayisi', 'banyo-sayisi', 'bulundugu-kat', 'kat-sayisi', 'asansor', 'balkon-sayisi', 'cephe-yonu', 'tapu-durumu', 'yapim-yili'];
$konutOzellikler = $ozellikler->whereIn('slug', $konutSlugs);
echo "🏠 Konut Features Sample: " . count($konutOzellikler) . " found\n";
foreach ($konutOzellikler->take(5) as $o) {
    echo "   └─ {$o->slug}\n";
}

// Arsa Features
$arsaSlugs = ['arsa-alani', 'imar-statusu', 'tapu-durumu', 'arsa-egimi'];
$arsaOzellikler = $ozellikler->whereIn('slug', $arsaSlugs);
echo "\n🏞️  Arsa Features Sample: " . count($arsaOzellikler) . " found\n";
foreach ($arsaOzellikler as $o) {
    echo "   └─ {$o->slug}\n";
}

// Ticari Features
$ticariSlugs = ['isletme-ruhsati', 'ticaret-unvani', 'vitrin', 'giris-yuksekligi'];
$ticariOzellikler = $ozellikler->whereIn('slug', $ticariSlugs);
echo "\n💼 Ticari Features Sample: " . count($ticariOzellikler) . " found\n";
foreach ($ticariOzellikler as $o) {
    echo "   └─ {$o->slug}\n";
}

echo "\n";

// ============================================================================
// 4. CURRENT ASSIGNMENTS
// ============================================================================

echo "📊 [4/4] CURRENT ASSIGNMENT STATE\n";
echo str_repeat("─", 65) . "\n";

$assignments = DB::table('ilan_ozellik_atamasi')->count();
$templates = DB::table('yayin_tipi_sablonlari')
    ->distinct('kategori_id', 'yayin_tipi_id')
    ->count();

echo "✅ Toplam Atama (ilan_ozellik_atamasi): {$assignments}\n";
echo "✅ Toplam Unique Kategori-YayinTipi: {$templates}\n";
echo "✅ Ortalama Atama/Template: " . ($templates > 0 ? round($assignments / $templates, 2) : 0) . "\n\n";

// ============================================================================
// STRATEGIC MATRIX SUMMARY
// ============================================================================

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        STRATEGIC MATRIX - ATAMA PLANI                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Konut Grubu
echo "🏠 KONUT GRUBU (Residential Spine)\n";
echo "─────────────────────────────────────────────────────────────────\n";
$konutKategoriler = $kategoriler->filter(
    fn($k) =>
    in_array(strtolower($k->adi), ['villa', 'rezidans', 'daire', 'taş ev', 'malikane', 'tiny house'])
);
$kiralamaTipleri = $yayinTipleriRaw->filter(
    fn($y) =>
    str_contains(strtolower($y->adi), ['günlük', 'haftalık', 'aylık', 'sezonluk'])
);
$konutMatrix = count($konutKategoriler) * count($kiralamaTipleri);
echo "Kategoriler: " . count($konutKategoriler) . "\n";
echo "Yayın Tipleri: " . count($kiralamaTipleri) . "\n";
echo "Planlanan Matrix: {$konutMatrix} (37 feature/template)\n";
echo "Beklenen Toplam Atama: " . ($konutMatrix * 37) . "\n\n";

// Arsa Grubu
echo "🏞️  ARSA GRUBU (Land Core)\n";
echo "─────────────────────────────────────────────────────────────────\n";
$arsaKategoriler = $kategoriler->filter(
    fn($k) =>
    in_array(strtolower($k->adi), ['arsa', 'tarla', 'zeytinlik', 'bağ', 'bahçe', 'zeytinli tarla'])
);
$satisKiralamaTipleri = $yayinTipleriRaw->filter(
    fn($y) =>
    in_array(strtolower($y->adi), ['satılık', 'kiralık', 'kat karşılığı'])
);
$arsaMatrix = count($arsaKategoriler) * count($satisKiralamaTipleri);
echo "Kategoriler: " . count($arsaKategoriler) . "\n";
echo "Yayın Tipleri: " . count($satisKiralamaTipleri) . "\n";
echo "Planlanan Matrix: {$arsaMatrix} (6 feature/template)\n";
echo "Beklenen Toplam Atama: " . ($arsaMatrix * 6) . "\n\n";

// Ticari Grubu
echo "💼 TİCARİ GRUBU (Business & Dev)\n";
echo "─────────────────────────────────────────────────────────────────\n";
$ticariKategoriler = $kategoriler->filter(
    fn($k) =>
    in_array(strtolower($k->adi), ['işyeri', 'turizm', 'otel', 'kamp', 'proje', 'karma proje'])
);
$devrenTipleri = $yayinTipleriRaw->filter(
    fn($y) =>
    str_contains(strtolower($y->adi), 'devren')
);
$ticariMatrix = count($ticariKategoriler) * count($devrenTipleri);
echo "Kategoriler: " . count($ticariKategoriler) . "\n";
echo "Yayın Tipleri: " . count($devrenTipleri) . "\n";
echo "Planlanan Matrix: {$ticariMatrix} (8 feature/template)\n";
echo "Beklenen Toplam Atama: " . ($ticariMatrix * 8) . "\n\n";

// Total
$totalPlanned = ($konutMatrix * 37) + ($arsaMatrix * 6) + ($ticariMatrix * 8);
echo "═══════════════════════════════════════════════════════════════\n";
echo "📈 TOPLAM PLANLANAN ATAMA: {$totalPlanned}\n";
echo "📊 CURRENT ATAMA: {$assignments}\n";
echo "🎯 FARK: " . ($totalPlanned - $assignments) . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ PHASE 0 DISCOVERY COMPLETE\n\n";
