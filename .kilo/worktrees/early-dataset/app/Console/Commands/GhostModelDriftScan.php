<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class GhostModelDriftScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:drift-scan {--model= : Tek bir modeli (FQCN) tarar} {--json : Raporu JSON formatında döndürür}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Model $fillable ve Database şeması arasındaki '
        . 'senkronizasyonu denetler (Ghost Field tespiti)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    /**
     * Models that are base/abstract classes and do not require their own DB table.
     * These are excluded from the "Table missing" warning.
     */
    private const SKIP_MODELS = [
        'App\\Models\\BaseModel',
    ];

    public function handle(): int
    {
        $specificModel = $this->option('model');
        
        if ($specificModel) {
            $this->info("🔍 Starting Targeted Drift Scan for: {$specificModel}");
            if (!class_exists($specificModel)) {
                $this->error("❌ Model class not found: {$specificModel}");
                return 1;
            }
            $models = [$specificModel];
        } else {
            $this->info('🔍 Starting Model Drift Scan (Ghost Detection)...');
            $models = $this->getModels();
        }
        
        $violations = [];

        foreach ($models as $modelClass) {
            try {
                // Skip base/abstract models that don't require their own table
                if (in_array($modelClass, self::SKIP_MODELS, true)) {
                    continue;
                }

                // Modeli instantiate ederek hem dinamik table adını hem de fillable metotlarını alıyoruz
                $model = new $modelClass();
                $table = $model->getTable();

                if (!Schema::hasTable($table)) {
                    // Bazı modeller pivot tablo veya özel tablo kullanıyor olabilir, tablo yoksa uyarı veriyoruz
                    $this->warn("⚠️  Table missing for model: {$modelClass} (Table: {$table})");
                    continue;
                }

                $fillable = $model->getFillable();
                $columns = Schema::getColumnListing($table);

                // Ghost Field: fillable içinde olup DB'de olmayan alanlar
                $ghostFields = array_diff($fillable, $columns);

                if (!empty($ghostFields)) {
                    $violations[$modelClass] = [
                        'table' => $table,
                        'ghost_fields' => array_values($ghostFields)
                    ];
                }
            } catch (\Exception $e) {
                $this->error("❌ Error scanning model {$modelClass}: " . $e->getMessage());
            }
        }

        if (empty($violations)) {
            $this->info('✅ No ghost fields detected. All models are in sync with database.');
            return 0;
        }

        if ($this->option('json')) {
            $this->line(json_encode($violations, JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ GHOST FIELDS DETECTED!');
            foreach ($violations as $class => $data) {
                $this->line("<fg=yellow>{$class}</> (Table: <fg=cyan>{$data['table']}</>)");
                foreach ($data['ghost_fields'] as $field) {
                    $this->line("  - <fg=red>{$field}</> (Missing in DB)");
                }
            }
        }

        return 1;
    }

    /**
     * App/Models dizinindeki tüm sınıfları bulur.
     *
     * @return array
     */
    protected function getModels(): array
    {
        if (!File::isDirectory(app_path('Models'))) {
            return [];
        }

        $modelFiles = File::allFiles(app_path('Models'));
        $models = [];

        foreach ($modelFiles as $file) {
            $path = $file->getRelativePathName();
            if (File::extension($path) !== 'php') {
                continue;
            }

            // Namespace ve class adını oluştur
            $class = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $path);

            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                // Abstract olmayan ve Eloquent Model'den türeyen sınıfları al
                if (!$reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }
}
