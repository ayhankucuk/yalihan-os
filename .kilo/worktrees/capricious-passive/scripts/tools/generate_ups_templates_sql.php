#!/usr/bin/env php
<?php
/**
 * UPS Template SQL Generator
 *
 * Generates idempotent INSERT scripts for ilan_templates
 * from category + publication type combinations
 *
 * Usage: php scripts/generate_ups_templates_sql.php --input=templates.json [--out=output.sql] [--dry-run] [--mode=insert]
 */

// Configuration
$config = [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USERNAME') ?: 'root',
        'pass' => getenv('DB_PASSWORD') ?: '',
        'name' => getenv('DB_DATABASE') ?: 'yalihanai_v2_production',
    ],
    'tables' => [
        'templates' => 'ilan_templates',
        'listings' => 'ilanlar',
        'categories' => 'ilan_kategorileri',
        'publication_types' => 'yayin_tipleri',
    ],
];

// Parse CLI arguments
$options = getopt('', ['input:', 'out::', 'dry-run', 'mode::']);
$inputFile = $options['input'] ?? null;
$outputFile = $options['out'] ?? 'ups_templates_generated.sql';
$dryRun = isset($options['dry-run']);
$mode = $options['mode'] ?? 'insert';

if (!$inputFile) {
    echo "❌ ERROR: --input parameter required\n";
    echo "Usage: php generate_ups_templates_sql.php --input=templates.json [--out=output.sql] [--dry-run]\n";
    exit(1);
}

if (!file_exists($inputFile)) {
    echo "❌ ERROR: Input file not found: $inputFile\n";
    exit(1);
}

// Load input
$inputData = json_decode(file_get_contents($inputFile), true);
if (!$inputData) {
    echo "❌ ERROR: Invalid JSON in input file\n";
    exit(1);
}

// Connect to DB for validation
try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "❌ DB Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Fetch valid columns from ilanlar table
$stmt = $pdo->query("SHOW COLUMNS FROM {$config['tables']['listings']}");
$validColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
$validColumnsSet = array_flip($validColumns);

echo "📋 UPS TEMPLATE SQL GENERATOR\n";
echo "==============================\n\n";
echo "Input: $inputFile\n";
echo "Mode: " . strtoupper($mode) . "\n";
echo "Valid columns in {$config['tables']['listings']}: " . count($validColumns) . "\n\n";

// Validation report
$report = [
    'total' => count($inputData),
    'templates' => [],
    'warnings' => [],
    'errors' => [],
];

// Generate SQL statements
$sqlStatements = [];
$sqlStatements[] = "/* ========================================";
$sqlStatements[] = "   UPS TEMPLATE SQL PACK";
$sqlStatements[] = "   Generated: " . date('Y-m-d H:i:s');
$sqlStatements[] = "   Total Templates: " . count($inputData);
$sqlStatements[] = "   Mode: $mode";
$sqlStatements[] = "   ======================================== */\n";
$sqlStatements[] = "START TRANSACTION;\n";

$templateCodes = [];

foreach ($inputData as $idx => $template) {
    $num = $idx + 1;

    // Extract data
    $kategoriId = $template['kategori_id'];
    $kategoriSlug = $template['kategori_slug'];
    $kategoriAdi = $template['kategori_adi'];
    $yayinTipiId = $template['yayin_tipi_id'];
    $yayinSlug = $template['yayin_slug'];
    $yayinAdi = $template['yayin_adi'];

    // Generate template_kodu
    $templateKodu = str_replace('-', '_', "{$kategoriSlug}_{$yayinSlug}");
    $templateAdi = "{$kategoriAdi} {$yayinAdi}";
    $templateCodes[] = $templateKodu;

    // Validate fields
    $required = $template['required'] ?? [];
    $optional = $template['optional'] ?? [];
    $hidden = $template['hidden'] ?? [];
    $groups = $template['groups'] ?? [];

    $unknownFields = [];
    $allFields = array_merge($required, $optional, $hidden);
    foreach ($groups as $group) {
        $allFields = array_merge($allFields, $group['fields'] ?? []);
    }
    $allFields = array_unique($allFields);

    foreach ($allFields as $field) {
        if (!isset($validColumnsSet[$field])) {
            $unknownFields[] = $field;
        }
    }

    // Report entry
    $report['templates'][] = [
        'code' => $templateKodu,
        'name' => $templateAdi,
        'kategori_id' => $kategoriId,
        'yayin_tipi_id' => $yayinTipiId,
        'groups' => count($groups),
        'required' => count($required),
        'optional' => count($optional),
        'hidden' => count($hidden),
        'unknown_fields' => $unknownFields,
    ];

    if (!empty($unknownFields)) {
        $report['warnings'][] = "$templateKodu: Unknown fields - " . implode(', ', $unknownFields);
        // Filter out unknown fields
        $required = array_values(array_diff($required, $unknownFields));
        $optional = array_values(array_diff($optional, $unknownFields));
        $hidden = array_values(array_diff($hidden, $unknownFields));
        foreach ($groups as &$group) {
            $group['fields'] = array_values(array_diff($group['fields'] ?? [], $unknownFields));
        }
    }

    // Build JSON structures
    $groupsJson = 'JSON_ARRAY(' . implode(', ', array_map(function($g) {
        $fields = "JSON_ARRAY('" . implode("','", $g['fields']) . "')";
        return "JSON_OBJECT('key','{$g['key']}','name','{$g['name']}','fields',$fields)";
    }, $groups)) . ')';

    $requiredJson = "JSON_ARRAY('" . implode("','", $required) . "')";
    $optionalJson = "JSON_ARRAY('" . implode("','", $optional) . "')";
    $hiddenJson = "JSON_ARRAY('" . implode("','", $hidden) . "')";

    // Generate SQL
    $sqlStatements[] = "/* $num) $templateAdi (kategori_id=$kategoriId, yayin_tipi_id=$yayinTipiId) */";

    if ($mode === 'update') {
        // UPDATE mode (overwrite if exists)
        $sqlStatements[] = "INSERT INTO {$config['tables']['templates']} (";
        $sqlStatements[] = "  template_kodu, template_adi, kategori_id, yayin_tipi_id,";
        $sqlStatements[] = "  feature_groups, required_fields, optional_fields, hidden_fields,";
        $sqlStatements[] = "  aktiflik_durumu, created_at, updated_at";
        $sqlStatements[] = ") VALUES (";
        $sqlStatements[] = "  '$templateKodu', " . $pdo->quote($templateAdi) . ", $kategoriId, $yayinTipiId,";
        $sqlStatements[] = "  $groupsJson,";
        $sqlStatements[] = "  $requiredJson,";
        $sqlStatements[] = "  $optionalJson,";
        $sqlStatements[] = "  $hiddenJson,";
        $sqlStatements[] = "  1, NOW(), NOW()";
        $sqlStatements[] = ") ON DUPLICATE KEY UPDATE";
        $sqlStatements[] = "  feature_groups = VALUES(feature_groups),";
        $sqlStatements[] = "  required_fields = VALUES(required_fields),";
        $sqlStatements[] = "  optional_fields = VALUES(optional_fields),";
        $sqlStatements[] = "  hidden_fields = VALUES(hidden_fields),";
        $sqlStatements[] = "  updated_at = NOW();";
    } else {
        // INSERT mode (skip if exists)
        $sqlStatements[] = "INSERT INTO {$config['tables']['templates']} (";
        $sqlStatements[] = "  template_kodu, template_adi, kategori_id, yayin_tipi_id,";
        $sqlStatements[] = "  feature_groups, required_fields, optional_fields, hidden_fields,";
        $sqlStatements[] = "  aktiflik_durumu, created_at, updated_at";
        $sqlStatements[] = ")";
        $sqlStatements[] = "SELECT";
        $sqlStatements[] = "  '$templateKodu', " . $pdo->quote($templateAdi) . ", $kategoriId, $yayinTipiId,";
        $sqlStatements[] = "  $groupsJson,";
        $sqlStatements[] = "  $requiredJson,";
        $sqlStatements[] = "  $optionalJson,";
        $sqlStatements[] = "  $hiddenJson,";
        $sqlStatements[] = "  1, NOW(), NOW()";
        $sqlStatements[] = "WHERE NOT EXISTS (";
        $sqlStatements[] = "  SELECT 1 FROM {$config['tables']['templates']} WHERE template_kodu='$templateKodu'";
        $sqlStatements[] = ");";
    }

    $sqlStatements[] = "";
}

// Add postcheck
$sqlStatements[] = "/* POSTCHECK */";
$sqlStatements[] = "SELECT COUNT(*) AS active_templates";
$sqlStatements[] = "FROM {$config['tables']['templates']}";
$sqlStatements[] = "WHERE aktiflik_durumu = 1;\n";

$templateCodesStr = "'" . implode("','", $templateCodes) . "'";
$sqlStatements[] = "SELECT template_kodu, kategori_id, yayin_tipi_id,";
$sqlStatements[] = "       JSON_LENGTH(feature_groups) AS fg_len,";
$sqlStatements[] = "       JSON_LENGTH(required_fields) AS rf_len";
$sqlStatements[] = "FROM {$config['tables']['templates']}";
$sqlStatements[] = "WHERE template_kodu IN ($templateCodesStr);";

$sqlStatements[] = "\nCOMMIT;";

$sql = implode("\n", $sqlStatements);

// Print report
echo "VALIDATION REPORT\n";
echo "=================\n\n";
echo "Templates to generate: {$report['total']}\n\n";

foreach ($report['templates'] as $t) {
    $sonuc = empty($t['unknown_fields']) ? '✅' : '⚠️ ';
    echo "$sonuc {$t['code']}\n";
    echo "   Name: {$t['name']}\n";
    echo "   IDs: kategori={$t['kategori_id']}, yayin={$t['yayin_tipi_id']}\n";
    echo "   Structure: {$t['groups']} groups, {$t['required']} required, {$t['optional']} optional, {$t['hidden']} hidden\n";
    if (!empty($t['unknown_fields'])) {
        echo "   ⚠️  Unknown fields: " . implode(', ', $t['unknown_fields']) . "\n";
    }
    echo "\n";
}

if (!empty($report['warnings'])) {
    echo "WARNINGS:\n";
    foreach ($report['warnings'] as $warning) {
        echo "⚠️  $warning\n";
    }
    echo "\n";
}

if (!empty($report['errors'])) {
    echo "ERRORS:\n";
    foreach ($report['errors'] as $error) {
        echo "❌ $error\n";
    }
    echo "\n";
}

if ($dryRun) {
    echo "🔍 DRY-RUN MODE: SQL not written to file\n\n";
    echo "Generated SQL Preview (first 50 lines):\n";
    echo "======================================\n";
    $lines = explode("\n", $sql);
    echo implode("\n", array_slice($lines, 0, 50));
    echo "\n... (" . (count($lines) - 50) . " more lines)\n";
} else {
    // Write to file
    file_put_contents($outputFile, $sql);
    echo "✅ SQL written to: $outputFile\n";
    echo "   Total lines: " . substr_count($sql, "\n") . "\n";
    echo "   File size: " . number_format(strlen($sql)) . " bytes\n\n";

    echo "To execute:\n";
    echo "  mysql -u {$config['db']['user']} {$config['db']['name']} < $outputFile\n\n";
}

echo "✅ Generation complete\n";
