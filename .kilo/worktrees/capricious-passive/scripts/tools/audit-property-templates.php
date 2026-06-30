<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CATEGORY & PUBLISH TYPE INVENTORY ===" . PHP_EOL . PHP_EOL;

// Get all categories
$categories = DB::table('ilan_kategorileri')
    ->select('id', 'name')
    ->orderBy('id')
    ->get();

echo "Categories ({$categories->count()}):" . PHP_EOL;
foreach ($categories as $cat) {
    echo "  {$cat->id}: {$cat->name}" . PHP_EOL;
}

// Get all publish types
$publishTypes = DB::table('yayin_tipleri')
    ->select('id', 'name')
    ->orderBy('id')
    ->get();

echo PHP_EOL . "Publish Types ({$publishTypes->count()}):" . PHP_EOL;
foreach ($publishTypes as $pt) {
    echo "  {$pt->id}: {$pt->name}" . PHP_EOL;
}

// Get existing templates
echo PHP_EOL . "=== EXISTING TEMPLATES ===" . PHP_EOL . PHP_EOL;

$templates = DB::table('property_hub_templates as t')
    ->join('ilan_kategorileri as k', 't.kategori_id', '=', 'k.id')
    ->join('yayin_tipleri as yt', 't.yayin_tipi_id', '=', 'yt.id')
    ->select('k.name as kategori_name', 'yt.name as yayin_tipi_name', 't.kategori_id', 't.yayin_tipi_id', 't.template_data')
    ->orderBy('k.name')
    ->orderBy('yt.name')
    ->get();

echo "Total Templates: {$templates->count()}" . PHP_EOL . PHP_EOL;

$audit = [];
foreach ($templates as $t) {
    $data = json_decode($t->template_data, true);
    $fieldCount = isset($data['fields']) ? count($data['fields']) : 0;

    echo "{$t->kategori_name} + {$t->yayin_tipi_name}";
    echo " (kat_id={$t->kategori_id}, yayin_id={$t->yayin_tipi_id}): ";
    echo "{$fieldCount} fields" . PHP_EOL;

    $audit[] = [
        'category' => $t->kategori_name,
        'publish_type' => $t->yayin_tipi_name,
        'kategori_id' => $t->kategori_id,
        'yayin_tipi_id' => $t->yayin_tipi_id,
        'field_count' => $fieldCount,
        'has_fields' => $fieldCount > 0,
    ];
}

// Save audit to JSON
$auditData = [
    'generated_at' => date('Y-m-d H:i:s'),
    'total_categories' => $categories->count(),
    'total_publish_types' => $publishTypes->count(),
    'total_templates' => $templates->count(),
    'templates' => $audit,
];

$auditPath = __DIR__ . '/../../.precheck/property-templates.audit.json';
@mkdir(dirname($auditPath), 0755, true);
file_put_contents($auditPath, json_encode($auditData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo PHP_EOL . "✅ Audit saved to: .precheck/property-templates.audit.json" . PHP_EOL;
