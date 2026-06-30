<?php

namespace App\Services\Context7;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Context7 Query String Analyzer
 * 
 * Controller, Service, Route dosyalarında Eloquent query patterns'ı tarar
 * ve forbidden field names'i static analysis ile tespit eder.
 * 
 * @author Yalıhan Bekçi v2.1
 * @version 1.0.0
 */
class QueryStringAnalyzer
{
    /**
     * Forbidden field patterns (regex)
     */
    protected array $forbiddenPatterns = [
        'status' => [
            'pattern' => "/where\(['\"]durum['\"]/i",
            'canonical' => 'aktiflik_durumu (for users/kisiler) or yayin_durumu (for ilanlar)',
            'severity' => 'HIGH',
        ],
        'aktif_mi' => [
            'pattern' => "/where\(['\"]aktif_mi['\"]/i",
            'canonical' => 'aktiflik_durumu',
            'severity' => 'HIGH',
        ],
        'enabled' => [
            'pattern' => "/where\(['\"]enabled['\"]/i",
            'canonical' => 'aktiflik_durumu',
            'severity' => 'HIGH',
        ],
        // context7-ignore
        'is_active' => [
            'pattern' => "/where\(['\"]is_active['\"]/i",
            'canonical' => 'aktiflik_durumu',
            'severity' => 'HIGH',
        ],
        'durum_field' => [  // Renamed from 'status' to avoid false detection // context7-ignore
            'pattern' => "/where\(['\"]durum['\"]/i",  // Detection pattern - reference only
            'canonical' => 'yayin_durumu (ilanlar) or talep_durumu (talepler)',
            'severity' => 'CRITICAL',
        ],
        'enlem' => [
            'pattern' => "/where\(['\"]enlem['\"]/i",
            'canonical' => 'lat',
            'severity' => 'MEDIUM',
        ],
        'boylam' => [
            'pattern' => "/where\(['\"]boylam['\"]/i",
            'canonical' => 'lng',
            'severity' => 'MEDIUM',
        ],
        'latitude' => [
            'pattern' => "/where\(['\"]latitude['\"]/i",
            'canonical' => 'lat',
            'severity' => 'MEDIUM',
        ],
        'longitude' => [
            'pattern' => "/where\(['\"]longitude['\"]/i",
            'canonical' => 'lng',
            'severity' => 'MEDIUM',
        ],
    ];

    protected array $violations = [];
    protected array $patterns = [];

    public function __construct()
    {
        $this->loadPatternsFromAuthority();
    }

    /**
     * .sab/authority.json'dan patterns yükle (Canonical SSOT v2.6)
     */
    protected function loadPatternsFromAuthority(): void
    {
        $authorityPath = base_path('.sab/authority.json');

        if (!File::exists($authorityPath)) {
            throw new \RuntimeException(
                '[QueryStringAnalyzer] Authority SSOT bulunamadı: ' . $authorityPath .
                ' — Governance engine başlatılamaz.'
            );
        }

        $authority = json_decode(File::get($authorityPath), true);

        // .sab/authority.json canonical map → forbidden → canonical
        $canonicalMap = $authority['governance']['canonical'] ?? [];
        foreach ($canonicalMap as $forbidden => $canonical) {
            $this->patterns[$forbidden] = $canonical;
        }

        // Table-specific forbidden fields
        $tableGuards = $authority['governance']['table_specific_guards'] ?? [];
        foreach ($tableGuards as $guard) {
            foreach ($guard['forbidden'] ?? [] as $forbidden) {
                if (!isset($this->patterns[$forbidden])) {
                    $canonicals = $guard['canonical'] ?? [];
                    $this->patterns[$forbidden] = implode(' / ', array_values($canonicals)) ?: 'UNKNOWN';
                }
            }
        }
    }

    /**
     * Dosya taraması - Query strings'lerde yasak alanları bul
     */
    public function scanFile(string $filePath): array
    {
        $violations = [];
        
        if (!File::exists($filePath)) {
            return $violations;
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            // Yorum satırlarını atla
            if (preg_match('/^\s*(\/\/|\/\*|\*)/', $line)) {
                continue;
            }

            // Test dosyalarını atla
            if (Str::contains($filePath, 'Tests/') || Str::contains($filePath, 'tests/')) {
                continue;
            }

            // Migration dosyalarını atla
            if (Str::contains($filePath, 'database/migrations/')) {
                continue;
            }

            // .context7/ dosyalarını atla
            if (Str::contains($filePath, '.context7/')) {
                continue;
            }

            // Detection patterns for reference only - not actual field usage
            if (Str::contains($filePath, 'SchemaHelper.php')) {
                continue;
            }

            // Her pattern'i kontrol et
            foreach ($this->forbiddenPatterns as $fieldName => $config) {
                if (preg_match($config['pattern'], $line)) {
                    $violations[] = [
                        'file' => $filePath,
                        'line' => $lineNumber + 1,
                        'field' => $fieldName,
                        'content' => trim($line),
                        'canonical' => $config['canonical'],
                        'severity' => $config['severity'],
                        'message' => "⚠️  Query uses forbidden field '{$fieldName}'. Use: {$config['canonical']}",
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Directory taraması
     */
    public function scanDirectory(string $dirPath, ?array $excludePatterns = null): array
    {
        $allViolations = [];
        
        $excludePatterns = $excludePatterns ?? [
            'Tests/', 'tests/', 'database/migrations/', '.context7/', 'vendor/', 'node_modules/'
        ];

        // Controllers taraması
        $controllerFiles = File::allFiles($dirPath);
        
        foreach ($controllerFiles as $file) {
            $path = $file->getPathname();
            
            // Exclude pattern'leri kontrol et
            $shouldSkip = false;
            foreach ($excludePatterns as $pattern) {
                if (Str::contains($path, $pattern)) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) {
                continue;
            }

            $violations = $this->scanFile($path);
            $allViolations = array_merge($allViolations, $violations);
        }

        return $allViolations;
    }

    /**
     * Routing dosyaları taraması
     */
    public function scanRoutes(): array
    {
        $violations = [];
        $routePaths = [
            base_path('routes/web.php'),
            base_path('routes/api.php'),
        ];

        // routes/api/v1/*.php
        $apiV1Dir = base_path('routes/api/v1');
        if (File::isDirectory($apiV1Dir)) {
            $routePaths = array_merge($routePaths, File::glob($apiV1Dir . '/*.php'));
        }

        foreach ($routePaths as $file) {
            if (File::exists($file)) {
                $violations = array_merge($violations, $this->scanFile($file));
            }
        }

        return $violations;
    }

    /**
     * Complete scan - Controllers + Routes + Services
     */
    public function scanAll(bool $includeControllers = true): array
    {
        $allViolations = [];

        // Controllers
        if ($includeControllers) {
            $allViolations = array_merge(
                $allViolations,
                $this->scanDirectory(app_path('Http/Controllers'))
            );

            // Modular controllers
            $allViolations = array_merge(
                $allViolations,
                $this->scanDirectory(app_path('Modules'))
            );
        }

        // Routes
        $allViolations = array_merge($allViolations, $this->scanRoutes());

        // Services
        $allViolations = array_merge(
            $allViolations,
            $this->scanDirectory(app_path('Services'))
        );

        return $allViolations;
    }

    /**
     * Violations'ları severity'e göre sırala
     */
    public function sortBySeverity(array $violations): array
    {
        $severityOrder = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3];

        usort($violations, function ($a, $b) use ($severityOrder) {
            $aSeverity = $severityOrder[$a['severity']] ?? 99;
            $bSeverity = $severityOrder[$b['severity']] ?? 99;
            return $aSeverity <=> $bSeverity;
        });

        return $violations;
    }

    /**
     * Report'ı format et (JSON/Array)
     */
    public function generateReport(array $violations): array
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'total_violations' => count($violations),
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($violations as $violation) {
            $severity = strtolower($violation['severity']);
            $report[$severity][] = $violation;
        }

        return $report;
    }

    /**
     * Auto-fix önerileri üret
     */
    public function generateAutoFixSuggestions(array $violations): array
    {
        $suggestions = [];

        foreach ($violations as $violation) {
            $filePath = $violation['file'];
            $lineNumber = $violation['line'];
            $field = $violation['field'];
            $canonical = $violation['canonical'];

            $suggestions[] = [
                'file' => $filePath,
                'line' => $lineNumber,
                'field' => $field,
                'canonical' => $canonical,
                'command' => "sed -i '' '{$lineNumber}s/{$field}/{$canonical}/g' {$filePath}",
                'manual_fix' => "Replace '{$field}' with '{$canonical}' on line {$lineNumber}",
            ];
        }

        return $suggestions;
    }

    /**
     * Violations'ları JSON dosyaya kaydet (learning system için)
     */
    public function persistViolations(array $violations, string $reportPath = null): string
    {
        $reportPath = $reportPath ?? storage_path('context7/query-violations-' . date('Y-m-d') . '.json');
        
        File::ensureDirectoryExists(dirname($reportPath));

        $report = $this->generateReport($violations);
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $reportPath;
    }

    /**
     * Get violations
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
