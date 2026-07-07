<?php

/**
 * Neo Design System & Dark Mode Scanner
 * 
 * Bu script tüm view dosyalarını tarar ve şunları raporlar:
 * 1. Yasaklı Bootstrap/Legacy Class'ları (btn-primary, card, vb.)
 * 2. Dark Mode desteği olmayan hardcoded renkleri (bg-white olup dark: olmayanlar)
 */

$directories = [
    __DIR__ . '/../../resources/views',
    __DIR__ . '/../../resources/js',
];

$forbiddenClasses = [
    'btn-primary', 'btn-secondary', 'btn-danger', 'btn-success', 'btn-info', 'btn-warning',
    'card-body', 'card-header', 'card-footer',
    'form-control', 'form-group', 'form-check',
    'alert-success', 'alert-danger', 'alert-warning', 'alert-info',
    'badge-primary', 'badge-secondary',
    'table-striped', 'table-bordered'
];

// Tailwind renkleri için basit kontrol (light mode renkleri)
$lightModeColors = [
    'bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-gray-200',
    'text-gray-900', 'text-gray-800', 'text-black'
];

function scanDirectory($dir) {
    global $forbiddenClasses, $lightModeColors;
    
    $results = [
        'legacy_violations' => [],
        'dark_mode_violations' => []
    ];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($files as $file) {
        if ($file->isDir()) continue;
        if (!in_array($file->getExtension(), ['php', 'blade.php', 'js', 'vue'])) continue;

        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);
        $relativePath = str_replace(realpath(__DIR__ . '/..') . '/', '', $file->getPathname());

        foreach ($lines as $lineNum => $line) {
            // Skip comments
            if (strpos(trim($line), '//') === 0 || strpos(trim($line), '/*') === 0) continue;

            // 1. Check Legacy Classes
            foreach ($forbiddenClasses as $class) {
                if (preg_match('/class=["\'].*?\b' . preg_quote($class, '/') . '\b.*?["\']/', $line)) {
                    $results['legacy_violations'][] = [
                        'file' => $relativePath,
                        'line' => $lineNum + 1,
                        'violation' => $class
                    ];
                }
            }

            // 2. Check Dark Mode Missing
            // Logic: Eğer satırda class="..." varsa ve içinde bg-white/text-black geçip 'dark:' geçmiyorsa
            if (preg_match('/class=["\']([^"\']*)["\']/', $line, $matches)) {
                $classContent = $matches[1];
                foreach ($lightModeColors as $color) {
                    if (strpos($classContent, $color) !== false && strpos($classContent, 'dark:') === false) {
                        $results['dark_mode_violations'][] = [
                            'file' => $relativePath,
                            'line' => $lineNum + 1,
                            'violation' => "Found '$color' but missing 'dark:' variant"
                        ];
                        break; // Bir satırda bir hata yeterli
                    }
                }
            }
        }
    }

    return $results;
}

echo "\n🔍 YalıhanAI Design System Scanner\n";
echo "=================================\n\n";

$allResults = [
    'legacy_violations' => [],
    'dark_mode_violations' => []
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "Scanning: $dir...\n";
        $res = scanDirectory($dir);
        $allResults['legacy_violations'] = array_merge($allResults['legacy_violations'], $res['legacy_violations']);
        $allResults['dark_mode_violations'] = array_merge($allResults['dark_mode_violations'], $res['dark_mode_violations']);
    }
}

// Raporlama
echo "\n🚫 LEGACY/BOOTSTRAP VIOLATIONS (" . count($allResults['legacy_violations']) . ")\n";
echo "----------------------------------------\n";
if (empty($allResults['legacy_violations'])) {
    echo "✅ No legacy violations found!\n";
} else {
    foreach ($allResults['legacy_violations'] as $v) {
        echo "❌ {$v['file']}:{$v['line']} -> Forbidden class '{$v['violation']}'\n";
    }
}

echo "\n🌑 DARK MODE MISSING (" . count($allResults['dark_mode_violations']) . ")\n";
echo "----------------------------------------\n";
if (empty($allResults['dark_mode_violations'])) {
    echo "✅ No dark mode issues found!\n";
} else {
    // Limit output if too many
    $limit = 50;
    $count = 0;
    foreach ($allResults['dark_mode_violations'] as $v) {
        echo "⚠️  {$v['file']}:{$v['line']} -> {$v['violation']}\n";
        $count++;
        if ($count >= $limit) {
            echo "... and " . (count($allResults['dark_mode_violations']) - $limit) . " more.\n";
            break;
        }
    }
}

echo "\nDone.\n";
