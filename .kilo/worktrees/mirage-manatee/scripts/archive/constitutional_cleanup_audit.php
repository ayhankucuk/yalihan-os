#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "📋 ANAYASAL TEMIZLIK - CONTEXT7 VIOLATIONS AUDIT\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Komut çalıştır
$output = shell_exec("cd /Users/macbookpro/Projects/yalihanai && php artisan context7:integrity-scan 2>&1");

echo $output;

// Parse violations
$violations = [];
$lines = explode("\n", $output);

foreach ($lines as $line) {
    if (preg_match('/🔴|❌|🟠/', $line)) {
        $violations[] = trim($line);
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "📊 VIOLATION CATEGORIZATION\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$critical = [];
$cosmetic = [];

foreach ($violations as $violation) {
    if (strpos($violation, "'status'") !== false) {
        $critical[] = $violation;
    } elseif (strpos($violation, "'active'") !== false || strpos($violation, "'is_active'") !== false) {
        $critical[] = $violation;
    } elseif (strpos($violation, "undefined_model") !== false) {
        $cosmetic[] = $violation;
    } else {
        $cosmetic[] = $violation;
    }
}

echo "🔴 CRITICAL (Must fix for production):\n";
echo "   Count: " . count($critical) . "\n\n";

echo "🟠 COSMETIC (Refactoring for code quality):\n";
echo "   Count: " . count($cosmetic) . "\n\n";

echo "📈 Summary:\n";
echo "   • Total violations: " . count($violations) . "\n";
echo "   • Critical: " . count($critical) . "\n";
echo "   • Cosmetic: " . count($cosmetic) . "\n";
echo "   • Estimated fix time: 2-3 hours (batch refactor)\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "💡 RECOMMENDATION:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "Run: php artisan context7:integrity-scan --auto-fix\n";
echo "This will automatically refactor legacy field names.\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
