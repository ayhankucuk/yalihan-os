#!/usr/bin/env php
<?php

/**
 * Context7 Model $fillable Auto-Fixer
 * Veritabanında olmayan $fillable alanlarını kaldırır
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$errors = [];
$fixed = [];

// Get all model files
$modelDirs = [
    'app/Models',
    'app/Modules/Auth/Models',
    'app/Modules/Crm/Models',
    'app/Modules/Emlak/Models',
    'app/Modules/Finans/Models',
    'app/Modules/TakimYonetimi/Models',
];

foreach ($modelDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob("$dir/*.php");
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Skip if no fillable
        if (strpos($content, 'protected $fillable') === false) {
            continue;
        }
        
        // Extract class name
        if (!preg_match('/^\s*class\s+(\w+)/m', $content, $matches)) {
            continue;
        }
        
        $className = $matches[1];
        $namespace = '';
        if (preg_match('/namespace\s+(.*?);/', $content, $nsMatch)) {
            $namespace = $nsMatch[1];
        }
        
        $fullClass = $namespace ? $namespace . '\\' . $className : $className;
        
        // Try to instantiate model
        try {
            $model = new $fullClass();
            $tableName = $model->getTable();
            
            // Get fillable fields
            $fillable = $model->getFillable();
            if (empty($fillable)) continue;
            
            // Get actual database columns
            $columns = Schema::getColumnListing($tableName);
            if (empty($columns)) {
                continue;
            }
            
            // Find missing columns
            $missing = array_diff($fillable, $columns);
            if (empty($missing)) {
                continue;
            }
            
            // Update fillable array in code
            $newFillable = array_diff($fillable, $missing);
            
            // Generate new fillable code
            $fillableStr = "protected \$fillable = [\n";
            foreach ($newFillable as $field) {
                $fillableStr .= "        '$field',\n";
            }
            $fillableStr .= "    ];";
            
            // Find and replace in file
            $oldPattern = '/protected\s+\$fillable\s*=\s*\[.*?\];/s';
            $newContent = preg_replace($oldPattern, $fillableStr, $content);
            
            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
                $fixed[] = "$fullClass: removed " . implode(', ', $missing);
                echo "✅ $fullClass: removed " . implode(', ', $missing) . "\n";
            }
        } catch (\Exception $e) {
            // Model doesn't exist or table doesn't exist - skip
            continue;
        }
    }
}

echo "\n✅ Fixed " . count($fixed) . " models\n";

if (!empty($errors)) {
    echo "\n⚠️ Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
