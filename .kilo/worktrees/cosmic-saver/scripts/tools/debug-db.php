<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $connection = config('database.default');
    $dbName = config("database.connections.{$connection}.database");
    echo "🔍 Environment DB_DATABASE: " . getenv('DB_DATABASE') . "\n";
    echo "🔍 Configured DB: {$dbName}\n";
    
    DB::connection($connection)->getPdo();
    echo "✅ PDO Connection Established.\n";
    
    DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0');
    echo "✅ SET FOREIGN_KEY_CHECKS=0 executed.\n";
    
} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
