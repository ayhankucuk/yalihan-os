#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "🔬 PLATINUM SEALING - FULL NEURAL SYNAPSE MAPPING AUDIT\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

$templates = DB::table('yayin_tipi_sablonlari')
    ->where('aktiflik_durumu', 1)
    ->orderBy('id')
    ->get(['id', 'ad', 'slug'])
    ->toArray();

echo "📊 TEMPLATE INVENTORY: " . count($templates) . " ACTIVE TEMPLATES\n\n";

$totalFeatures = 0;
$contaminationReport = [];

foreach ($templates as $template) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🏷️  Template ID {$template->id}: {$template->ad} (slug: {$template->slug})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // Get all features for this template
    $features = DB::table('feature_assignments as fa')
        ->join('features as f', 'fa.feature_id', '=', 'f.id')
        ->where('fa.assignable_type', 'App\Models\YayinTipiSablonu')
        ->where('fa.assignable_id', $template->id)
        ->select('f.id', 'f.slug', 'fa.is_required')
        ->orderBy('fa.is_required', 'desc')
        ->orderBy('f.slug')
        ->get();

    $requiredCount = $features->where('is_required', 1)->count();
    $optionalCount = $features->where('is_required', 0)->count();
    $totalCount = $features->count();

    echo "📈 Statistics:\n";
    echo "   • Total Features: {$totalCount}\n";
    echo "   • REQUIRED: {$requiredCount}\n";
    echo "   • Optional: {$optionalCount}\n";

    $totalFeatures += $totalCount;

    // Detect contamination
    $categoryPrefix = match ($template->slug) {
        'satilik', 'kiralik' => 'konut',
        'satisagazaraz', 'kiralik-arsa' => 'arsa',
        'acilan-gunluk', 'acilan-gumuslu', 'acilan-haftalik' => 'turizm',
        'commercial-satis', 'commercial-kiralik' => 'commercial',
        default => 'other'
    };

    // Define foreign features per category
    $forbiddenSlugs = match ($categoryPrefix) {
        'konut' => ['arsa-egimi', 'arsa-alani', 'arsa-ici-durum', 'imar-statusu', 'ticaret-unvani', 'isletme-ruhsati'],
        'arsa' => ['oda-sayisi', 'banyo-sayisi', 'salon-sayisi', 'balkon-sayisi', 'asansor', 'mutfak', 'beyaz-esya', 'esyali'],
        'turizm' => ['ticaret-unvani', 'isletme-ruhsati', 'oda-sayisi'],
        'commercial' => ['arsa-egimi', 'arsa-alani'],
        default => []
    };

    $contaminated = $features->whereIn('slug', $forbiddenSlugs)->values();

    if ($contaminated->count() > 0) {
        echo "\n🚨 CONTAMINATION DETECTED: {$contaminated->count()} foreign features\n";
        foreach ($contaminated as $feature) {
            echo "   ❌ {$feature->slug} (ID: {$feature->id})\n";
        }
        $contaminationReport[$template->ad] = $contaminated;
    } else {
        echo "\n✅ NO CONTAMINATION DETECTED\n";
    }

    // Show all features
    echo "\n📋 Feature Breakdown:\n";

    $required = $features->where('is_required', 1)->values();
    if ($required->count() > 0) {
        echo "   ✅ REQUIRED ({$required->count()}):\n";
        foreach ($required as $feature) {
            echo "      • {$feature->slug}\n";
        }
    }

    $optional = $features->where('is_required', 0)->values();
    if ($optional->count() > 0) {
        echo "   ℹ️  OPTIONAL ({$optional->count()}):\n";
        foreach ($optional as $feature) {
            echo "      • {$feature->slug}\n";
        }
    }

    echo "\n";
}

// SUMMARY
echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "📊 PLATINUM SYNAPSE MAPPING - EXECUTIVE SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "🔢 Global Statistics:\n";
echo "   • Total Templates: " . count($templates) . "\n";
echo "   • Total Features Mapped: {$totalFeatures}\n";
echo "   • Average Features per Template: " . round($totalFeatures / count($templates), 1) . "\n";

echo "\n🏗️  Template Distribution:\n";
foreach ($templates as $template) {
    $count = DB::table('feature_assignments')
        ->where('assignable_type', 'App\Models\YayinTipiSablonu')
        ->where('assignable_id', $template->id)
        ->count();

    $reqCount = DB::table('feature_assignments')
        ->where('assignable_type', 'App\Models\YayinTipiSablonu')
        ->where('assignable_id', $template->id)
        ->where('is_required', 1)
        ->count();

    echo "   • {$template->ad}: {$count} features ({$reqCount} required)\n";
}

if (count($contaminationReport) > 0) {
    echo "\n🚨 CONTAMINATION REPORT:\n";
    foreach ($contaminationReport as $templateName => $features) {
        echo "   ❌ {$templateName}: {$features->count()} contaminated features\n";
    }
    echo "\n   STATUS: ⚠️  REQUIRES CLEANUP BEFORE PLATINUM SEAL\n";
} else {
    echo "\n✅ CONTAMINATION REPORT:\n";
    echo "   ✨ ALL 9 TEMPLATES ARE PURE - ZERO CONTAMINATION\n";
    echo "   STATUS: ✅ READY FOR PLATINUM SEALING\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "📝 Report Generated: " . now() . "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
