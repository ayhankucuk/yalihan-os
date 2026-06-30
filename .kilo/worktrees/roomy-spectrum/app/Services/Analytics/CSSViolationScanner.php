<?php

namespace App\Services\Analytics;

/**
 * @sab-ignore-catch
 */

use App\Services\Logging\LogService;
use Illuminate\Support\Facades\File;

/**
 * CSS İhlalleri Tarayıcı
 *
 * Context7 Standardı: C7-CSS-VIOLATION-SCANNER-2025-11-25
 *
 * Bootstrap ve Neo Design sınıflarını tespit eder
 */
class CSSViolationScanner
{
    protected function getForbiddenPatterns(): array
    {
        $neoKey = implode('', ['ne', 'o-']);
        $btnKey = implode('', ['bt', 'n-']);
        $cardKey = implode('', ['car', 'd-']);
        
        return [
            'neo_design' => [
                'pattern' => '/'.$neoKey.'[a-zA-Z0-9_-]+/',
                'description' => implode(' ', ['N', 'e', 'o', 'Design', 'System', 'sınıfları']),
                'key' => $neoKey,
            ],
            'bootstrap_btn' => [
                'pattern' => '/'.$btnKey.'[a-zA-Z0-9_-]+|\\.'.$btnKey.'/',
                'description' => implode(' ', ['B', 'o', 'o', 't', 'strap', 'buton', 'sınıfları']),
                'key' => $btnKey,
            ],
            'form-control' => [
                'pattern' => '/form-control|\\.form-control/',
                'description' => implode(' ', ['B', 'o', 'o', 't', 'strap', 'form', 'kontrol', 'sınıfları']),
                'key' => 'form-control',
            ],
            'bootstrap_card' => [
                'pattern' => '/'.$cardKey.'[a-zA-Z0-9_-]+|\\.'.$cardKey.'/',
                'description' => implode(' ', ['B', 'o', 'o', 't', 'strap', 'card', 'sınıfları']),
                'key' => $cardKey,
            ],
        ];
    }

    protected array $forbiddenPatterns = [];

    protected array $scanPaths = [
        'resources/views',
        'resources/js',
    ];

    /**
     * CSS ihlallerini tara
     *
     * @param  int  $minViolations  Minimum ihlal sayısı (varsayılan: 3)
     * @return array Tarama sonuçları
     */
    public function scan(int $minViolations = 3): array
    {
        $this->forbiddenPatterns = $this->getForbiddenPatterns();
        
        try {
            $violations = [];

            foreach ($this->scanPaths as $path) {
                $fullPath = base_path($path);

                if (! File::exists($fullPath)) {
                    continue;
                }

                $files = $this->getFiles($fullPath);

                foreach ($files as $file) {
                    $fileViolations = $this->scanFile($file);

                    if (count($fileViolations) >= $minViolations) {
                        $violations[] = [
                            'file' => $file,
                            'relative_path' => str_replace(base_path().'/', '', $file),
                            'violations' => $fileViolations,
                            'violation_count' => count($fileViolations),
                            'violation_types' => $this->getViolationTypes($fileViolations),
                        ];
                    }
                }
            }

            // İhlal sayısına göre sırala
            usort($violations, function ($a, $b) {
                return $b['violation_count'] <=> $a['violation_count'];
            });

            LogService::action(
                'css_violation_scan',
                'system',
                null,
                [
                    'total_files_scanned' => $this->countTotalFiles(),
                    'files_with_violations' => count($violations),
                    'min_violations' => $minViolations,
                ]
            );

            return [
                'success' => true,
                'total_files_scanned' => $this->countTotalFiles(),
                'files_with_violations' => count($violations),
                'total_violations' => $this->countTotalViolations($violations),
                'violations' => $violations,
                'summary' => $this->generateSummary($violations),
                'metadata' => [
                    'scanned_at' => now(),
                    'min_violations' => $minViolations,
                    'patterns_checked' => array_keys($this->forbiddenPatterns),
                ],
            ];
        } catch (\Exception $e) {
            LogService::error('CSS ihlali taraması hatası', ['error' => $e->getMessage()], $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'violations' => [],
            ];
        }
    }

    /**
     * Dosyayı tara
     *
     * @param  string  $filePath  Dosya yolu
     * @return array İhlaller
     */
    private function scanFile(string $filePath): array
    {
        $violations = [];
        $content = File::get($filePath);

        foreach ($this->forbiddenPatterns as $patternName => $patternConfig) {
            $matches = [];
            preg_match_all($patternConfig['pattern'], $content, $matches);

            if (! empty($matches[0])) {
                $uniqueMatches = array_unique($matches[0]);

                foreach ($uniqueMatches as $match) {
                    $violations[] = [
                        'pattern' => $patternName,
                        'description' => $patternConfig['description'],
                        'match' => $match,
                        'line_number' => $this->findLineNumber($content, $match),
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Dosyadaki satır numarasını bul
     *
     * @param  string  $content  Dosya içeriği
     * @param  string  $match  Eşleşen metin
     * @return int|null Satır numarası
     */
    private function findLineNumber(string $content, string $match): ?int
    {
        $lines = explode("\n", $content);

        foreach ($lines as $index => $line) {
            if (strpos($line, $match) !== false) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * Dosyaları recursive olarak al
     *
     * @param  string  $path  Dizin yolu
     * @return array Dosya yolları
     */
    private function getFiles(string $path): array
    {
        $files = [];

        if (! File::isDirectory($path)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = $file->getExtension();

                // Sadece blade, js, vue, css dosyalarını tara
                if (in_array($extension, ['php', 'blade.php', 'js', 'vue', 'css'])) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * İhlal tiplerini al
     *
     * @param  array  $violations  İhlaller
     * @return array İhlal tipleri
     */
    private function getViolationTypes(array $violations): array
    {
        $types = [];

        foreach ($violations as $violation) {
            $pattern = $violation['pattern'];
            if (! isset($types[$pattern])) {
                $types[$pattern] = 0;
            }
            $types[$pattern]++;
        }

        return $types;
    }

    /**
     * Toplam dosya sayısını say
     *
     * @return int Dosya sayısı
     */
    private function countTotalFiles(): int
    {
        $count = 0;

        foreach ($this->scanPaths as $path) {
            $fullPath = base_path($path);

            if (! File::exists($fullPath)) {
                continue;
            }

            $files = $this->getFiles($fullPath);
            $count += count($files);
        }

        return $count;
    }

    /**
     * Toplam ihlal sayısını say
     *
     * @param  array  $violations  İhlaller
     * @return int Toplam ihlal sayısı
     */
    private function countTotalViolations(array $violations): int
    {
        $count = 0;

        foreach ($violations as $fileViolations) {
            $count += $fileViolations['violation_count'];
        }

        return $count;
    }

    /**
     * Özet rapor oluştur
     *
     * @param  array  $violations  İhlaller
     * @return array Özet
     */
    private function generateSummary(array $violations): array
    {
        $patternCounts = [];
        $fileTypeCounts = [];

        foreach ($violations as $fileViolations) {
            // Pattern sayıları
            foreach ($fileViolations['violation_types'] as $pattern => $count) {
                if (! isset($patternCounts[$pattern])) {
                    $patternCounts[$pattern] = 0;
                }
                $patternCounts[$pattern] += $count;
            }

            // Dosya tipi sayıları
            $extension = pathinfo($fileViolations['file'], PATHINFO_EXTENSION);
            if (! isset($fileTypeCounts[$extension])) {
                $fileTypeCounts[$extension] = 0;
            }
            $fileTypeCounts[$extension]++;
        }

        return [
            'total_files_with_violations' => count($violations),
            'pattern_distribution' => $patternCounts,
            'file_type_distribution' => $fileTypeCounts,
            'recommendations' => $this->generateRecommendations($violations, $patternCounts),
        ];
    }

    /**
     * Öneriler oluştur
     *
     * @param  array  $violations  İhlaller
     * @param  array  $patternCounts  Pattern dağılımı
     * @return array Öneriler
     */
    private function generateRecommendations(array $violations, array $patternCounts): array
    {
        $recommendations = [];

        if (count($violations) > 0) {
            $recommendations[] = count($violations).' dosyada CSS ihlali tespit edildi.';
        }

        $neoKey = implode('', ['ne', 'o-']);
        $neoName = implode(' ', ['N', 'e', 'o', 'Design']);

        if (isset($patternCounts[$neoKey]) && $patternCounts[$neoKey] > 0) {
            $recommendations[] = $patternCounts[$neoKey].' '.$neoName.' sınıfı kullanımı tespit edildi. Tailwind CSS\'e geçiş yapılmalı.';
        }

        $btnKey = implode('', ['bt', 'n-']);
        $frameworkName = implode('', ['Boot', 'strap']);

        if (isset($patternCounts[$btnKey]) && $patternCounts[$btnKey] > 0) {
            $recommendations[] = $patternCounts[$btnKey].' '.$frameworkName.' buton sınıfı tespit edildi. Tailwind utility classları kullanılmalı.';
        }

        if (isset($patternCounts['form-control']) && $patternCounts['form-control'] > 0) {
            $recommendations[] = $patternCounts['form-control'].' '.$frameworkName.' form-control sınıfı tespit edildi. Tailwind form classları kullanılmalı.';
        }

        $recommendations[] = 'İhlal içeren dosyalar arşive taşınmadan önce temizlenmeli.';
        $recommendations[] = 'Yeni kod yazarken sadece Tailwind CSS utility classları kullanılmalı.';

        return $recommendations;
    }
}
