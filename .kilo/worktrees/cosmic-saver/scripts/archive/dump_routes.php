<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
use Illuminate\Support\Facades\Route;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = Route::getRoutes();

foreach ($routes as $route) {
    echo $route->getName() . " -> " . $route->uri() . "\n";
}
