<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX: Add Ada No, Parsel No to ui_ipuclari ===" . PHP_EOL . PHP_EOL;

$template = DB::table('ups_templates')->where('id', 2)->first();
$templateJson = json_decode($template->template_json, true);

echo "Current ui_ipuclari: " . count($templateJson['ui_ipuclari'] ?? []) . " fields" . PHP_EOL;

// Add missing fields to ui_ipuclari (this is what WizardContext uses!)
$uiIpuclari = [
    [
        'slug' => 'arsa_tipi',
        'label' => 'Arsa Tipi',
        'hint' => 'Arsanızın tipini seçin (İmar içi, tarla, zeytinlik vb.)',
        'placeholder' => 'Arsa tipini seçiniz'
    ],
    [
        'slug' => 'imar_durumu',
        'label' => 'İmar Durumu',
        'hint' => 'Arsanızın imar durumunu belirtiniz',
        'placeholder' => 'İmar durumunu seçiniz'
    ],
    [
        'slug' => 'tapu_durumu',
        'label' => 'Tapu Durumu',
        'hint' => 'Tapu tipini seçiniz (Kat mülkiyeti, arsa tapusu vb.)',
        'placeholder' => 'Tapu durumunu seçiniz'
    ],
    [
        'slug' => 'ada_no',
        'label' => 'Ada No',
        'hint' => 'Arsanızın bulunduğu adanın numarasını giriniz',
        'placeholder' => 'Örn: 123'
    ],
    [
        'slug' => 'parsel_no',
        'label' => 'Parsel No',
        'hint' => 'Arsanızın parsel numarasını giriniz',
        'placeholder' => 'Örn: 45'
    ],
    [
        'slug' => 'kaks',
        'label' => 'KAKS',
        'hint' => 'Kat Alanı Kat Sayısı oranı',
        'placeholder' => 'Örn: 1.20',
        'birim' => null
    ],
    [
        'slug' => 'gabari',
        'label' => 'Gabari',
        'hint' => 'Maksimum bina yüksekliği',
        'placeholder' => 'Örn: 12.50',
        'birim' => 'metre'
    ]
];

$templateJson['ui_ipuclari'] = $uiIpuclari;

DB::table('ups_templates')
    ->where('id', 2)
    ->update([
        'template_json' => json_encode($templateJson, JSON_UNESCAPED_UNICODE),
        'updated_at' => now(),
    ]);

echo "✅ ui_ipuclari updated with " . count($uiIpuclari) . " fields" . PHP_EOL;
echo PHP_EOL . "🧪 Test:" . PHP_EOL;
echo "curl \"http://127.0.0.1:8002/api/v1/wizard/context?kategori_id=3&yayin_tipi_id=1\" | jq '.context.template.fields | length'" . PHP_EOL;
