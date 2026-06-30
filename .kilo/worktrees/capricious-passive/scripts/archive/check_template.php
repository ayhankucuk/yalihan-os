<?php

use App\Models\MasterTemplate;
use App\Models\Feature;
use App\Models\FeatureCategory;

// Check Master Template
$template = MasterTemplate::where('slug', 'yazlik-kiralik-master')->first();
$templateByName = MasterTemplate::where('name', 'Yazlık Kiralama (Master)')->first();

if ($template || $templateByName) {
    echo "✅ Master Template Found!\n";
    $t = $template ?? $templateByName;
    echo "Name: " . $t->name . "\n";
    echo "Active: " . ($t->aktiflik_durumu ? 'Yes' : 'No') . "\n";
    echo "Feature Count: " . count($t->feature_ids ?? []) . "\n";
} else {
    echo "❌ Master Template NOT Found!\n";
}

// Check Categories
$foundCategories = FeatureCategory::whereIn('name', [
    'Genel Bilgiler', 'Mutfak Olanakları', 'Yatak Düzeni',
    'Banyo & Hijyen', 'Havuz & Bahçe', 'İklimlendirme & Teknoloji', 'Kurallar & Politikalar'
])->count();

echo "Found Categories: " . $foundCategories . "/7\n";
