<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== UPS TEMPLATE FIX: Adding missing Arsa fields ===" . PHP_EOL . PHP_EOL;

// Get template for Arsa + Satılık (ID: 2, junction: 60)
$template = DB::table('ups_templates')->where('id', 2)->first();

if (!$template) {
    die("❌ Template ID 2 not found!" . PHP_EOL);
}

echo "✅ Found template: {$template->id} (junction: {$template->yayin_tipi_sablonu_id})" . PHP_EOL;

$templateJson = json_decode($template->template_json, true);

echo "📝 Current field_visibility (step2): " . count($templateJson['field_visibility']['step2'] ?? []) . " fields" . PHP_EOL . PHP_EOL;

// Add missing fields to step2
$step2Fields = $templateJson['field_visibility']['step2'] ?? [];

// Remove duplicates first
$step2Fields = array_unique($step2Fields);

// Add critical Arsa fields if missing
$requiredFields = ['ada_no', 'parsel_no', 'kaks', 'gabari'];

foreach ($requiredFields as $field) {
    if (!in_array($field, $step2Fields)) {
        $step2Fields[] = $field;
        echo "  + Adding: {$field}" . PHP_EOL;
    }
}

// Update template
$templateJson['field_visibility']['step2'] = $step2Fields;

// Also add to optional if not in required
foreach (['ada_no', 'parsel_no', 'kaks', 'gabari'] as $field) {
    if (!in_array($field, $templateJson['required'] ?? []) && !in_array($field, $templateJson['optional'] ?? [])) {
        $templateJson['optional'][] = $field;
    }
}

DB::table('ups_templates')
    ->where('id', 2)
    ->update([
        'template_json' => json_encode($templateJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        'updated_at' => now(),
    ]);

echo PHP_EOL . "✅ Template updated! New step2 field count: " . count($step2Fields) . PHP_EOL;
echo PHP_EOL . "🧪 Test context API:" . PHP_EOL;
echo "   curl \"http://127.0.0.1:8002/api/v1/wizard/context?kategori_id=3&yayin_tipi_id=1\" | jq '.context.template.field_visibility.step2'" . PHP_EOL;
