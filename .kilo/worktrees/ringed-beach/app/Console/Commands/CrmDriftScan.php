<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class CrmDriftScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:drift-scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRM modülü için derinlemesine şema ve isimlendirme sızıntısı (drift) taraması yapar';

    /**
     * Yasaklı legacy alanlar
     */
    private const LEGACY_FIELDS = [
        'kisi_aktiflik_durumu',
        'KisiAktiflikDurumu',
    ];

    /**
     * Kisi modeli için yasaklı isimlendirme kalıpları
     */
    private const FORBIDDEN_PATTERNS = [
        'is_active',
        'active',
        'status',
        'aktif_mi',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️ Starting CRM Foundation Lock Drift Scan...');
        
        $models = $this->getCrmModels();
        $violations = [];
        $criticalCount = 0;

        foreach ($models as $modelClass) {
            $this->comment("Checking {$modelClass}...");
            
            try {
                $model = new $modelClass();
                $table = $model->getTable();
                $fillable = $model->getFillable();
                $columns = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];

                // 1. Legacy Field Check (CRITICAL)
                foreach (self::LEGACY_FIELDS as $legacy) {
                    if (in_array($legacy, $fillable) || in_array($legacy, $columns)) {
                        $violations[$modelClass][] = [
                            'tip' => 'CRITICAL',
                            'field' => $legacy,
                            'message' => 'Legacy field detected! Must be reconciled to aktiflik_durumu.'
                        ];
                        $criticalCount++;
                    }
                }

                // 2. Forbidden Patterns (Only for Kisi related models)
                if (str_contains($modelClass, 'Kisi')) {
                    foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                        if (in_array($pattern, $fillable) || in_array($pattern, $columns)) {
                            $violations[$modelClass][] = [
                                'tip' => 'WARNING',
                                'field' => $pattern,
                                'message' => "Forbidden pattern detected. Use canonical 'aktiflik_durumu'."
                            ];
                        }
                    }
                }

                // 3. Ghost Field Check
                $ghostFields = array_diff($fillable, $columns);
                foreach ($ghostFields as $ghost) {
                    $violations[$modelClass][] = [
                        'tip' => 'GHOST',
                        'field' => $ghost,
                        'message' => 'Field exists in $fillable but missing in database.'
                    ];
                }

            } catch (\Exception $e) {
                $this->error("❌ Error scanning {$modelClass}: " . $e->getMessage());
            }
        }

        return $this->report($violations, $criticalCount);
    }

    /**
     * Sonuçları raporla
     */
    protected function report(array $violations, int $criticalCount): int
    {
        if (empty($violations)) {
            $this->info('✅ CRM Foundation Lock: No drift detected. System compliant.');
            return 0;
        }

        foreach ($violations as $model => $items) {
            $this->line("\n<fg=cyan>{$model}</>");
            foreach ($items as $v) {
                $color = $v['tip'] === 'CRITICAL' ? 'red' : ($v['tip'] === 'WARNING' ? 'yellow' : 'magenta');
                $this->line("  [<fg={$color}>{$v['tip']}</>] <fg=white>{$v['field']}</>: {$v['message']}");
            }
        }

        if ($criticalCount > 0) {
            $this->error("\n❌ BUILD FAILED: {$criticalCount} critical drift(s) detected!");
            return 1;
        }

        $this->warn("\n⚠️  Drift warnings detected, but no critical blockers.");
        return 0;
    }

    /**
     * CRM ile ilgili modelleri bulur
     */
    protected function getCrmModels(): array
    {
        $paths = [
            app_path('Models'),
            app_path('Modules/Crm/Models'),
        ];

        $models = [];
        foreach ($paths as $path) {
            if (!File::isDirectory($path)) continue;

            foreach (File::allFiles($path) as $file) {
                $content = file_get_contents($file->getRealPath());
                if (str_contains($content, 'namespace')) {
                    preg_match('/namespace\s+([^;]+);/s', $content, $matches);
                    $namespace = isset($matches[1]) ? trim($matches[1]) : '';
                    $className = str_replace('.php', '', $file->getFilename());
                    $fullClass = $namespace . '\\' . $className;

                    if (class_exists($fullClass)) {
                        $reflection = new ReflectionClass($fullClass);
                        if (!$reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                            // Filter only CRM related models or all if in Crm module
                            if (str_contains($path, 'Modules/Crm') || str_contains($className, 'Kisi') || str_contains($className, 'Musteri')) {
                                $models[] = $fullClass;
                            }
                        }
                    }
                }
            }
        }

        return array_unique($models);
    }
}
