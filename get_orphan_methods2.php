<?php
require 'vendor/autoload.php';

$framework = ['__construct','__destruct','middleware','getMiddleware','callAction','__call',
    'authorize','authorizeForUser','authorizeResource','dispatchSync','validateWith','validate',
    'validateWithBag','adminMenu','toResponse','render','redirectIfUnauthorized','checkOwnership'];

$orphans = file('/tmp/orphan_52.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
sort($orphans);

$controllerBase = '/Users/macbookpro/dev/yalihan2026/app/Http/Controllers';
$results = [];

foreach ($orphans as $fullClass) {
    $fullClass = trim($fullClass, '"');
    $shortName = class_basename($fullClass);

    // Convert namespace to file path
    $relative = str_replace('App\\Http\\Controllers\\', '', $fullClass);
    $parts = explode('\\', $relative);
    $dir = $controllerBase . '/' . implode('/', $parts) . '.php';
    $dir = str_replace($controllerBase . '/', '', $dir);
    $parts2 = explode('/', $dir);
    $filePath = $controllerBase . '/' . implode('/', $parts2);

    $methods = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        // Extract method names using regex
        if (preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $matches)) {
            foreach ($matches[1] as $method) {
                if (!in_array($method, $framework)) {
                    $methods[] = $method;
                }
            }
        }
    }

    $results[$shortName] = [
        'class' => $fullClass,
        'methods' => $methods,
        'dir' => $relative,
        'file_exists' => file_exists($filePath),
    ];
}

file_put_contents('/tmp/orphan_methods.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Done: " . count($results) . " controllers\n";
