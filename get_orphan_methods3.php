<?php
// Parse the orphan_52.txt which stores class names without backslashes
// Format: AppHttpControllers{AIR}{ClassName} where {AIR} is Admin/AI/Api/etc
// We detect subdirs by checking which files exist in Admin/

$baseDir = '/Users/macbookpro/dev/yalihan2026/app/Http/Controllers';
$orphansRaw = file('/tmp/orphan_52.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
sort($orphansRaw);

$framework = ['__construct','__destruct','middleware','getMiddleware','callAction','__call',
    'authorize','authorizeForUser','authorizeResource','dispatchSync','validateWith','validate',
    'validateWithBag','adminMenu','toResponse','render','redirectIfUnauthorized','checkOwnership'];

$results = [];

foreach ($orphansRaw as $line) {
    $raw = trim($line, '"');
    // Strip leading AppHttpControllers
    $remainder = substr($raw, 20); // strlen('AppHttpControllers') = 20
    // $remainder = 'AIAISearchController', 'AdminDashboardController', etc.

    // Determine directory: AI, Admin, Api, Frontend, Owner
    // Priority: check Admin subdirs first (longer names), then top-level dirs
    $dirs = ['Admin', 'AI', 'Api', 'Frontend', 'Owner'];
    $foundDir = null;
    $className = null;

    foreach ($dirs as $dir) {
        if (strpos($remainder, $dir) === 0) {
            $foundDir = $dir;
            $className = substr($remainder, strlen($dir));
            break;
        }
    }

    if (!$foundDir || !$className) {
        $results[$raw] = ['methods' => [], 'dir' => 'UNKNOWN', 'file' => ''];
        continue;
    }

    $file = "$baseDir/$foundDir/$className.php";
    $methods = [];

    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $matches)) {
            foreach ($matches[1] as $m) {
                if (!in_array($m, $framework)) {
                    $methods[] = $m;
                }
            }
        }
    }

    $results[$raw] = [
        'methods' => $methods,
        'dir' => $foundDir,
        'file' => $file,
        'class' => "App\\Http\\Controllers\\$foundDir\\$className",
    ];
}

file_put_contents('/tmp/orphan_methods2.json', json_encode($results, JSON_PRETTY_PRINT));

// Summary
$withMethods = 0; $without = 0;
foreach ($results as $r) { if (!empty($r['methods'])) $withMethods++; else $without++; }
echo "Total: " . count($results) . ", with methods: $withMethods, without: $without\n";

// Show first 10 with methods
$count = 0;
foreach ($results as $name => $r) {
    if (!empty($r['methods'])) {
        echo "$name => " . json_encode($r['methods']) . "\n";
        if (++$count >= 20) break;
    }
}
