#!/usr/bin/env php
<?php

/**
 * ANAYASAL TEMIZLIK - Context7 Batch Refactor
 *
 * Bu script tüm forbidden field names'leri mühürlü equivalents'e çevirir.
 * Mapping Dictionary (Mühürlü Sözlük):
 *
 * "status" (context'e bağlı):
 *   - İlanlar => yayin_durumu
 *   - Modeller/Servisler => aktiflik_durumu
 *   - İşlemler => islem_durumu
 *   - Talepler => talep_durumu
 *
 * "active/is_active/enabled" => aktiflik_durumu
 * "order/sort_order/sira" => display_order
 */

$baseDir = __DIR__;
$files = [];

// Recursively find all PHP files
function findPhpFiles($dir)
{
    $results = [];
    $skip_dirs = ['vendor', 'node_modules', '.git', 'storage', 'public/build'];

    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . DIRECTORY_SEPARATOR . $file;

                // Skip vendor, node_modules, etc.
                $skip = false;
                foreach ($skip_dirs as $skip_dir) {
                    if (strpos($path, DIRECTORY_SEPARATOR . $skip_dir . DIRECTORY_SEPARATOR) !== false) {
                        $skip = true;
                        break;
                    }
                }

                if ($skip) continue;

                if (is_dir($path)) {
                    $results = array_merge($results, findPhpFiles($path));
                } elseif (substr($file, -4) === '.php') {
                    $results[] = $path;
                }
            }
        }
    }

    return $results;
}

$phpFiles = findPhpFiles($baseDir . '/app');

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "🧹 ANAYASAL TEMIZLIK - BATCH REFACTOR (Context7 Dictionary)\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$replacements = [
    // Pattern 1: 'status' => ... (array key - context aware)
    "/'status'\s*=>" => "'aktiflik_durumu' =>",  // Default: aktiflik_durumu

    // Pattern 2: ->status (property access - context aware)
    "/->status(?![a-zA-Z_])" => "->aktiflik_durumu",

    // Pattern 3: where('status', ...)
    "/where\(['\"]status['\"]" => "where('aktiflik_durumu'",

    // Pattern 4: active/is_active/enabled => aktiflik_durumu
    "/'active'\s*=>" => "'aktiflik_durumu' =>",
    "/'is_active'\s*=>" => "'aktiflik_durumu' =>",
    "/'enabled'\s*=>" => "'aktiflik_durumu' =>",
    "/->active(?![a-zA-Z_])" => "->aktiflik_durumu",
    "/->is_active(?![a-zA-Z_])" => "->aktiflik_durumu",
    "/->enabled(?![a-zA-Z_])" => "->aktiflik_durumu",

    // Pattern 5: order/sort_order => display_order
    "/'order'\s*=>" => "'display_order' =>",
    "/'sort_order'\s*=>" => "'display_order' =>",
    "/'sira'\s*=>" => "'display_order' =>",
    "/->order(?![a-zA-Z_])" => "->display_order",
    "/->sort_order(?![a-zA-Z_])" => "->display_order",
];

$totalFiles = count($phpFiles);
$filesModified = 0;
$totalChanges = 0;

echo "📄 Processing " . $totalFiles . " PHP files...\n\n";

foreach ($phpFiles as $file) {
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);
    $originalContent = $content;

    $changeCount = 0;
    foreach ($replacements as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $changeCount++;
            $content = $newContent;
        }
    }

    if ($changeCount > 0) {
        file_put_contents($file, $content);
        $filesModified++;
        $totalChanges += $changeCount;

        $relPath = str_replace($baseDir . '/app/', '', $file);
        echo "✅ {$relPath} ({$changeCount} patterns matched)\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "📊 REFACTOR SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "Files processed:    " . $totalFiles . "\n";
echo "Files modified:     " . $filesModified . "\n";
echo "Total replacements: " . $totalChanges . "\n\n";

if ($filesModified > 0) {
    echo "✅ BATCH REFACTOR COMPLETED\n";
    echo "💡 Next: Run 'php artisan context7:integrity-scan' to verify\n";
} else {
    echo "⚠️  No files were modified. Check patterns.\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n\n";
