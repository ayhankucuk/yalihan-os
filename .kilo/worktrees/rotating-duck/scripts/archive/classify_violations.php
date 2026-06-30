#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "🛡️  ANAYASAL TEMIZLIK - CONTEXT7 İHLAL SINIFLAMA\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Run Bekçi scan
$output = shell_exec("php artisan context7:integrity-scan 2>&1");

// Parse violations
$critical = [];  // P0 - Database, queries, direct access
$cosmetic = [];  // P2 - Comments, logs, strings

$lines = explode("\n", $output);
$currentFile = null;

foreach ($lines as $line) {
    // Match file name
    if (strpos($line, '📄') !== false && strpos($line, '.php') !== false) {
        preg_match('/📄\s+(.+\.php)/', $line, $matches);
        $currentFile = $matches[1] ?? null;
    }

    // Match violations
    if (preg_match('/Line (\d+):\s+[\'"]([^"\']+)[\'"]/', $line, $matches)) {
        $lineNum = $matches[1];
        $violation = $matches[2];

        // Categorize as CRITICAL or COSMETIC
        $isCritical = false;

        if (
            strpos($line, 'array_key') !== false ||
            strpos($line, 'property_access') !== false ||
            strpos($line, 'where') !== false ||
            strpos($line, 'whereIn') !== false
        ) {
            $isCritical = true;
        }

        if (
            strpos($line, 'comment') !== false ||
            strpos($line, 'log') !== false
        ) {
            $isCritical = false;
        }

        $entry = [
            'file' => $currentFile,
            'line' => $lineNum,
            'violation' => $violation,
            'raw' => trim($line)
        ];

        if ($isCritical) {
            $critical[] = $entry;
        } else {
            $cosmetic[] = $entry;
        }
    }
}

echo "🔴 CRITICAL VIOLATIONS (P0 - Must fix):\n";
echo "───────────────────────────────────────────────────────────────────────────────\n\n";
echo "Count: " . count($critical) . "\n\n";

$criticalByFile = [];
foreach ($critical as $v) {
    $file = $v['file'] ?? 'UNKNOWN';
    if (!isset($criticalByFile[$file])) {
        $criticalByFile[$file] = [];
    }
    $criticalByFile[$file][] = $v;
}

foreach ($criticalByFile as $file => $violations) {
    echo "📄 {$file}\n";
    foreach ($violations as $v) {
        echo "   Line {$v['line']}: {$v['violation']}\n";
    }
    echo "\n";
}

echo "\n";
echo "🟠 COSMETIC VIOLATIONS (P2 - Nice to have):\n";
echo "───────────────────────────────────────────────────────────────────────────────\n\n";
echo "Count: " . count($cosmetic) . "\n\n";

$cosmeticByFile = [];
foreach ($cosmetic as $v) {
    $file = $v['file'] ?? 'UNKNOWN';
    if (!isset($cosmeticByFile[$file])) {
        $cosmeticByFile[$file] = [];
    }
    $cosmeticByFile[$file][] = $v;
}

foreach ($cosmeticByFile as $file => $violations) {
    echo "📄 {$file}\n";
    foreach ($violations as $v) {
        echo "   Line {$v['line']}: {$v['violation']}\n";
    }
    echo "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "📊 SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$totalFiles = count($criticalByFile) + count($cosmeticByFile);
echo "Total files affected: " . count(array_unique(array_merge(array_keys($criticalByFile), array_keys($cosmeticByFile)))) . "\n";
echo "Critical violations: " . count($critical) . "\n";
echo "Cosmetic violations: " . count($cosmetic) . "\n";
echo "Total violations: " . (count($critical) + count($cosmetic)) . "\n\n";

echo "📋 CLEANUP STRATEGY:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "1. Phase 1: Fix CRITICAL violations first (database queries, property access)\n";
echo "2. Phase 2: Fix COSMETIC violations (comments, logs, helper strings)\n";
echo "3. Phase 3: Run Bekçi validation (target: 0 violations)\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
