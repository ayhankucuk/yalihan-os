<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ValidateBladeAlpineScope extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blade:validate-alpine-scope
                            {--auto-fix : Otomatik düzeltme önerisi}
                            {--strict : Strictmode - hata bulunca exit code 1}
                            {--file= : Belirli bir dosya kontrolü}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Blade @include + Alpine.js scope standartlarını valide et';

    protected $errors = [];
    protected $warnings = [];

    public function handle()
    {
        $this->info('🔍 Blade @include + Alpine.js Scope Validation');
        $this->line('');

        $file = $this->option('file');

        if ($file) {
            $this->validateFile(resource_path("views/{$file}"));
        } else {
            $this->validateDirectory(resource_path('views'));
        }

        $this->reportResults();

        if ($this->option('strict') && !empty($this->errors)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Dizini recursive olarak kontrol et
     */
    protected function validateDirectory($directory)
    {
        if (!is_dir($directory)) {
            $this->error("Dizin bulunamadı: $directory");
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getFilename(), '.blade.php') !== false) {
                $this->validateFile($file->getRealPath());
            }
        }
    }

    /**
     * Dosyayı kontrol et
     */
    protected function validateFile($filePath)
    {
        if (!file_exists($filePath)) {
            $this->error("Dosya bulunamadı: $filePath");
            return;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $relativePath = str_replace(base_path() . '/', '', $filePath);

        // @include kullanan satırları bul
        $includeMatches = [];
        foreach ($lines as $lineNum => $line) {
            if (preg_match('/@include\s*\(/', $line)) {
                $includeMatches[$lineNum] = $line;
            }
        }

        if (empty($includeMatches)) {
            return;
        }

        // Her include bloğu içinde Alpine directives kontrol et
        foreach ($includeMatches as $lineNum => $includeLine) {
            $this->checkIncludeBlock($filePath, $lines, $lineNum, $relativePath);
        }
    }

    /**
     * @include bloğunu kontrol et
     */
    protected function checkIncludeBlock($filePath, $lines, $startLine, $relativePath)
    {
        $blockContent = '';
        $inBlock = true;
        $blockEnd = $startLine;

        // Include komutunu ve sonrasını oku
        for ($i = $startLine; $i < count($lines) && $inBlock; $i++) {
            $line = $lines[$i];
            $blockContent .= $line . "\n";
            $blockEnd = $i;

            // Blok sonunu tespit et
            if ($i > $startLine && (preg_match('/^\s*}/', $line) || preg_match('/@endif/', $line) || preg_match('/@endforeach/', $line))) {
                $inBlock = false;
            }
        }

        // Alpine directives kontrol et
        if (preg_match_all('/(x-show|x-if|x-bind)\s*=\s*["\']([^"\']+)["\']/', $blockContent, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[2] as $idx => $match) {
                $expression = $match[0];
                $directive = $matches[1][$idx][0];

                // $parent prefix var mı kontrol et
                if (!preg_match('/\$parent\./', $expression)) {
                    // Ignore complex expressions or global stores (e.g. $store, comparisons, dotted paths)
                    if (preg_match('/[\\$\.\=\!\<\>\(\)\+\-\*\/]/', $expression)) {
                        // treat as warning for non-component files, otherwise ignore
                        if (strpos($relativePath, '/components/') === false && strpos($relativePath, 'category-fields') === false && strpos($relativePath, '.stubs') === false) {
                            $this->warnings[] = "Possible scope access in $relativePath: {$directive} -> {$expression}";
                        }
                        continue;
                    }

                    // Ancak eğer basit değişken ise hata kaydı (ör: selectedCategory)
                    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $expression)) {
                        $lineDisplay = $startLine + 1;
                        $errorMsg = "Blade @include içinde Alpine.js '{$directive}' direktivinde \$parent prefix eksik";

                        // If file is not a component-like path, downgrade to warning to avoid false-positives on layouts
                        if (strpos($relativePath, '/components/') === false && strpos($relativePath, 'category-fields') === false && strpos($relativePath, '.stubs') === false) {
                            $this->warnings[] = "Potential missing \$parent in $relativePath on line $lineDisplay: {$directive} -> {$expression}";
                            continue;
                        }

                        $this->errors[] = [
                            'file' => $relativePath,
                            'line' => $lineDisplay,
                            'message' => $errorMsg,
                            'expression' => $expression,
                            'directive' => $directive,
                        ];

                        // Auto-fix önerisini oluştur
                        $suggestion = preg_replace('/(^[A-Za-z_][A-Za-z0-9_]*)/', '\\$parent.$1', $expression);
                        if ($this->option('auto-fix')) {
                            $this->suggestions[] = [
                                'file' => $filePath,
                                'line' => $startLine,
                                'from' => $expression,
                                'to' => $suggestion,
                            ];
                        }
                    } else {
                        // Non-simple expressions ignored but warned for non-component files
                        if (strpos($relativePath, '/components/') === false && strpos($relativePath, 'category-fields') === false && strpos($relativePath, '.stubs') === false) {
                            $this->warnings[] = "Complex expression ignored in $relativePath: {$directive} -> {$expression}";
                        }
                    }
                }
            }
        }
    }

    /**
     * Sonuçları göster
     */
    protected function reportResults()
    {
        if (empty($this->errors) && empty($this->warnings)) {
            $this->info('');
            $this->info('✅ Tüm Blade @include + Alpine.js scope kontrolleri başarılı');
            return;
        }

        $this->line('');
        $this->error('═══════════════════════════════════════════════════════════');

        if (!empty($this->errors)) {
            $this->error('❌ ' . count($this->errors) . ' hata bulundu:');
            $this->line('');

            foreach ($this->errors as $error) {
                $this->error("  📄 {$error['file']}:{$error['line']}");
                $this->line("     Directive: {$error['directive']}");
                $this->line("     Expression: {$error['expression']}");
                $this->line("     {$error['message']}");
                $this->line("     ✓ Çözüm: \$parent.{$error['expression']}");
                $this->line('');
            }
        }

        if (!empty($this->warnings)) {
            $this->warn('⚠️  ' . count($this->warnings) . ' uyarı:');
            $this->line('');

            foreach ($this->warnings as $warning) {
                $this->warn("  {$warning}");
            }
            $this->line('');
        }

        $this->error('═══════════════════════════════════════════════════════════');
    }
}
