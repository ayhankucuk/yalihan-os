<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuardDocsCoverageCommand extends Command
{
    protected $signature = 'guard:docs-coverage
                            {--strict : Yeni eksikleri FAIL olarak değerlendir (baseline sonrası)}
                            {--baseline : Mevcut durumu baseline olarak kaydet}';

    protected $description = '🛡️ Docs Coverage Guard: Checks that admin routes, critical services, and models are documented.';

    /**
     * Docs files to check coverage against.
     */
    protected array $coverageMap = [
        'routes' => [
            'source' => 'routes/admin.php',
            'docs' => [
                'docs/architecture/pages.md',
                'docs/architecture/ui-to-route-map.md',
            ],
            'pattern' => '/Route::(get|post|put|patch|delete|resource|apiResource)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        ],
        'services' => [
            'source_dirs' => [
                'app/Services/Wizard',
                'app/Services/Governance',
                'app/Services/AI',
                'app/Services/Feature',
                'app/Services/Cortex',
                'app/Services/Ups',
            ],
            'docs' => [
                'docs/architecture/service-ownership.md',
                'docs/architecture/flows.md',
            ],
        ],
        'models' => [
            'source_dir' => 'app/Models',
            'docs' => [
                'docs/architecture/models.md',
            ],
        ],
    ];

    /**
     * Baseline file path (relative to base_path).
     */
    protected string $baselineFile = '.sab/docs-coverage-baseline.json';

    public function handle(): int
    {
        $this->info('🛡️ Docs Coverage Guard — Checking documentation coverage...');
        $this->newLine();

        // Handle baseline generation
        if ($this->option('baseline')) {
            return $this->generateBaseline();
        }

        $gaps = [];
        $warnings = [];

        // 1. Check route coverage
        $this->info('📍 Route coverage kontrol ediliyor...');
        $routeGaps = $this->checkRouteCoverage();
        if (!empty($routeGaps)) {
            foreach ($routeGaps as $gap) {
                $warnings[] = $gap;
                $this->warn("   ⚠️ Route docs'ta yok: {$gap['item']}");
            }
        } else {
            $this->info('   ✅ Tüm admin route\'lar docs\'ta mevcut');
        }

        // 2. Check service coverage
        $this->info('🔧 Service coverage kontrol ediliyor...');
        $serviceGaps = $this->checkServiceCoverage();
        if (!empty($serviceGaps)) {
            foreach ($serviceGaps as $gap) {
                $warnings[] = $gap;
                $this->warn("   ⚠️ Service docs'ta yok: {$gap['item']}");
            }
        } else {
            $this->info('   ✅ Tüm kritik servisler docs\'ta mevcut');
        }

        // 3. Check model coverage
        $this->info('📦 Model coverage kontrol ediliyor...');
        $modelGaps = $this->checkModelCoverage();
        if (!empty($modelGaps)) {
            foreach ($modelGaps as $gap) {
                $warnings[] = $gap;
                $this->warn("   ⚠️ Model docs'ta yok: {$gap['item']}");
            }
        } else {
            $this->info('   ✅ Tüm modeller docs\'ta mevcut');
        }

        // Load baseline and determine new gaps
        $baseline = $this->loadBaseline();
        $newGaps = $this->findNewGaps($warnings, $baseline);

        $this->newLine();
        $this->info("📊 Toplam: " . count($warnings) . " gap | " . count($newGaps) . " yeni gap");

        if (!empty($newGaps)) {
            $this->newLine();
            $this->error('🆕 Yeni (baseline dışı) gap\'ler:');
            foreach ($newGaps as $gap) {
                $this->error("   → [{$gap['type']}] {$gap['item']}");
            }
        }

        $this->newLine();

        // Determine exit code
        if (!empty($newGaps) && $this->option('strict')) {
            $this->error('❌ DOCS COVERAGE: FAIL — ' . count($newGaps) . ' new gap(s) (strict mode).');
            $this->error('   Yeni route/service/model docs\'a eklenmelidir.');
            return 1;
        }

        if (!empty($warnings)) {
            $baselineCount = count($warnings) - count($newGaps);
            $this->warn("⚠️ DOCS COVERAGE: WARN — {$baselineCount} baseline gap(s), " . count($newGaps) . " new gap(s).");
            if (empty($baseline)) {
                $this->warn('   Baseline oluşturmak için: php artisan guard:docs-coverage --baseline');
            }
            return 0;
        }

        $this->info('✅ DOCS COVERAGE: PASS — Tüm route/service/model docs\'ta mevcut.');
        return 0;
    }

    /**
     * Check admin routes are documented in pages.md or ui-to-route-map.md.
     */
    protected function checkRouteCoverage(): array
    {
        $gaps = [];
        $config = $this->coverageMap['routes'];
        $sourcePath = base_path($config['source']);

        if (!File::exists($sourcePath)) {
            return $gaps;
        }

        // Load docs content
        $docsContent = '';
        foreach ($config['docs'] as $docFile) {
            $docPath = base_path($docFile);
            if (File::exists($docPath)) {
                $docsContent .= File::get($docPath) . "\n";
            }
        }

        // Extract admin route URLs from source
        $sourceContent = File::get($sourcePath);
        preg_match_all($config['pattern'], $sourceContent, $matches);

        if (!empty($matches[2])) {
            // Deduplicate and check significant routes only
            $routes = array_unique($matches[2]);
            foreach ($routes as $route) {
                // Skip parameter-only routes, very short routes, and closures
                if (strlen($route) < 3 || str_starts_with($route, '{')) {
                    continue;
                }

                // Check if route URL appears in docs
                if (stripos($docsContent, $route) === false) {
                    $gaps[] = [
                        'type' => 'route',
                        'item' => "/admin/{$route}",
                        'source' => $config['source'],
                    ];
                }
            }
        }

        return $gaps;
    }

    /**
     * Check critical services are documented.
     */
    protected function checkServiceCoverage(): array
    {
        $gaps = [];
        $config = $this->coverageMap['services'];

        // Load docs content
        $docsContent = '';
        foreach ($config['docs'] as $docFile) {
            $docPath = base_path($docFile);
            if (File::exists($docPath)) {
                $docsContent .= File::get($docPath) . "\n";
            }
        }

        foreach ($config['source_dirs'] as $serviceDir) {
            $dirPath = base_path($serviceDir);
            if (!File::isDirectory($dirPath)) {
                continue;
            }

            $files = File::allFiles($dirPath);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $file->getFilenameWithoutExtension();

                // Skip interfaces, traits, abstract base classes
                if (str_ends_with($className, 'Interface') ||
                    str_ends_with($className, 'Trait') ||
                    str_starts_with($className, 'Abstract') ||
                    str_starts_with($className, 'Base')) {
                    continue;
                }

                if (stripos($docsContent, $className) === false) {
                    $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                    $gaps[] = [
                        'type' => 'service',
                        'item' => $className,
                        'source' => $relativePath,
                    ];
                }
            }
        }

        return $gaps;
    }

    /**
     * Check models are documented in models.md.
     */
    protected function checkModelCoverage(): array
    {
        $gaps = [];
        $config = $this->coverageMap['models'];

        // Load docs content
        $docsContent = '';
        foreach ($config['docs'] as $docFile) {
            $docPath = base_path($docFile);
            if (File::exists($docPath)) {
                $docsContent .= File::get($docPath) . "\n";
            }
        }

        $modelDir = base_path($config['source_dir']);
        if (!File::isDirectory($modelDir)) {
            return $gaps;
        }

        // Only check top-level models (not subdirectories — those are documented separately)
        $files = File::files($modelDir);
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $file->getFilenameWithoutExtension();

            // Skip base/abstract/trait/test
            if (in_array($className, ['BaseModel', 'TestEntity', 'NameAttribute'])) {
                continue;
            }

            if (stripos($docsContent, $className) === false) {
                $gaps[] = [
                    'type' => 'model',
                    'item' => $className,
                    'source' => "app/Models/{$className}.php",
                ];
            }
        }

        return $gaps;
    }

    /**
     * Load existing baseline.
     */
    protected function loadBaseline(): array
    {
        $path = base_path($this->baselineFile);
        if (!File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);
        return $data['gaps'] ?? [];
    }

    /**
     * Find gaps that are NOT in the baseline (new gaps).
     */
    protected function findNewGaps(array $currentGaps, array $baselineGaps): array
    {
        if (empty($baselineGaps)) {
            return []; // No baseline = all gaps are baseline, none are "new"
        }

        $baselineKeys = array_map(fn($g) => $g['type'] . ':' . $g['item'], $baselineGaps);

        return array_filter($currentGaps, function ($gap) use ($baselineKeys) {
            return !in_array($gap['type'] . ':' . $gap['item'], $baselineKeys);
        });
    }

    /**
     * Generate baseline from current state.
     */
    protected function generateBaseline(): int
    {
        $this->info('📦 Baseline oluşturuluyor...');

        $allGaps = array_merge(
            $this->checkRouteCoverage(),
            $this->checkServiceCoverage(),
            $this->checkModelCoverage()
        );

        $baselinePath = base_path($this->baselineFile);
        $baselineDir = dirname($baselinePath);

        if (!File::isDirectory($baselineDir)) {
            File::makeDirectory($baselineDir, 0755, true);
        }

        $data = [
            'generated_at' => now()->toIso8601String(),
            'total_gaps' => count($allGaps),
            'gaps' => $allGaps,
        ];

        File::put($baselinePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✅ Baseline kaydedildi: {$this->baselineFile}");
        $this->info("   Toplam: " . count($allGaps) . " gap (baseline olarak kabul edildi)");
        return 0;
    }
}
