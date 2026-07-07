<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$framework = ['__construct','__destruct','middleware','getMiddleware','callAction','__call',
    'authorize','authorizeForUser','authorizeResource','dispatchSync','validateWith','validate',
    'validateWithBag','adminMenu','toResponse','render'];

$orphans = file('/tmp/orphan_52.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
sort($orphans);

$results = [];
foreach ($orphans as $class) {
    $class = trim($class, '"');
    if (!class_exists($class)) {
        $results[$class] = ['category' => 'Delete', 'reason' => 'Class does not exist'];
        continue;
    }
    $rc = new ReflectionClass($class);
    $publicMethods = [];
    foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->getDeclaringClass()->getName() !== $class) continue;
        if (in_array($m->name, $framework)) continue;
        $publicMethods[] = $m->name;
    }
    $shortName = class_basename($class);

    $results[$shortName] = [
        'class' => $class,
        'methods' => $publicMethods,
        'dir' => str_replace('App\\Http\\Controllers\\', '', $class),
    ];
}

file_put_contents('/tmp/orphan_methods.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Done: " . count($results) . " controllers\n";
