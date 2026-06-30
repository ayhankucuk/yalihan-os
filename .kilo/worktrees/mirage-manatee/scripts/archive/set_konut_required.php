#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🏠 Setting CORRECT is_required flags for Konut templates...\n\n";

// Step 1: Set ALL to optional first (clean slate)
$updated = DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->whereIn('assignable_id', [1, 2])
    ->update(['is_required' => 0]);

echo "✅ Reset all to optional: {$updated} records\n\n";

// Step 2: Set REQUIRED for Satılık (ID: 1)
$satilikRequired = [
    'brut-alan',      // 1
    'oda-sayisi',     // 11
    'banyo-sayisi',   // 12
    'bulundugu-kat',  // 7
    'kat-sayisi',     // 6
];

$satilikIds = DB::table('features')
    ->whereIn('slug', $satilikRequired)
    ->pluck('id')
    ->toArray();

$satilikSet = DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('assignable_id', 1)
    ->whereIn('feature_id', $satilikIds)
    ->update(['is_required' => 1]);

echo "✅ Satılık REQUIRED: {$satilikSet} features\n";
echo "   " . implode(', ', $satilikRequired) . "\n\n";

// Step 3: Set REQUIRED for Kiralık (ID: 2)
$kiralikRequired = [
    'brut-alan',      // 1
    'oda-sayisi',     // 11
    'banyo-sayisi',   // 12
    'bulundugu-kat',  // 7
    'esyali',         // 22 (Kiralık için critical!)
];

$kiralikIds = DB::table('features')
    ->whereIn('slug', $kiralikRequired)
    ->pluck('id')
    ->toArray();

$kiralikSet = DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('assignable_id', 2)
    ->whereIn('feature_id', $kiralikIds)
    ->update(['is_required' => 1]);

echo "✅ Kiralık REQUIRED: {$kiralikSet} features\n";
echo "   " . implode(', ', $kiralikRequired) . "\n\n";

// Step 4: Verify
echo "📊 VERIFICATION:\n";

$satilikReqCount = DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('assignable_id', 1)
    ->where('is_required', 1)
    ->count();

$kiralikReqCount = DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('assignable_id', 2)
    ->where('is_required', 1)
    ->count();

echo "  Satılık: {$satilikReqCount} required features\n";
echo "  Kiralık: {$kiralikReqCount} required features\n\n";

echo "🎉 Konut is_required flags SET!\n";
