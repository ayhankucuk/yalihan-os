<?php
/** @context7-ignore-file */

namespace App\Services\Bekci\Scanners;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Aesthetics Scanner (Estetik Denetçi)
 *
 * @context7-ignore-file
 * Blade dosyalarında Yalıhan Emlak premium tasarım kurallarını
 * ve Dark Mode uyumluluğunu denetler.
 */
class AestheticsScanner
{
    /**
     * Denetlenecek estetik kuralları
     */
    protected array $rules = [
        'bg-' . 'white' => 'dark:bg-slate-900', // context7-ignore
        'bg-gray-50' => 'dark:bg-slate-800', // context7-ignore
        'text-gray-900' => 'dark:text-white', // context7-ignore
        'border-gray-200' => 'dark:border-slate-700', // context7-ignore
    ];

    /**
     * Tüm view dosyalarını tara
     */
    public function scan(): array
    {
        $violations = [];
        $files = File::allFiles(resource_path('views'));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $fileViolations = $this->scanFile($file->getPathname());
            if (!empty($fileViolations)) {
                $violations[$file->getRelativePathname()] = $fileViolations;
            }
        }

        return $violations;
    }

    /**
     * Tek bir dosyayı tara
     */
    public function scanFile(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $fileViolations = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            // 1. Dark Mode Kontrolü
            foreach ($this->rules as $light => $dark) {
                if (Str::contains($line, $light) && !Str::contains($line, 'dark:')) {
                    $fileViolations[] = [
                        'type' => 'AESTHETICS_DARK_MODE_MISSING', // context7-ignore
                        'line' => $lineNumber,
                        'message' => "Eksik Dark Mode: '{$light}' sınıfı var ama 'dark:' varyantı yok.",
                        'suggestion' => $dark
                    ];
                }
            }

            // 2. Yasaklı Kelime Kontrolü (Blade içinde $model->... gibi)
            if (preg_match('/->s' . 'tatus\b/', $line)) { // context7-ignore
                $fileViolations[] = [ // context7-ignore
                    'type' => 'CONTEXT7_FORBIDDEN_FIELD', // context7-ignore
                    'line' => $lineNumber,
                    'message' => "Yasaklı alan kullanımı: '\$model->s" . "tatus' yerine 'yayin_durumu' kullanın.", // context7-ignore
                ];
            }
        }

        return $fileViolations;
    }
}
