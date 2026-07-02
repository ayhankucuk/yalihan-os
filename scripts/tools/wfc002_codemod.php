#!/usr/bin/env php
<?php

/**
 * WFC-002 Codemod: yayin_tipi → yayin_tipi_id Migration
 * 
 * Strategy: DUAL SUPPORT (Conservative)
 * - Accept both yayin_tipi and yayin_tipi_id
 * - Prefer yayin_tipi_id (canonical)
 * - Log deprecation warnings for yayin_tipi usage
 * - Maintain backward compatibility
 */

class WFC002Codemod
{
    private array $stats = [
        'files_scanned' => 0,
        'files_modified' => 0,
        'replacements' => 0,
        'patterns_found' => [],
    ];

    private bool $dryRun = false;
    private bool $verbose = false;

    // P0 Files from audit
    private array $targetFiles = [
        'app/Http/Controllers/Admin/IlanController.php',
        'app/Http/Controllers/Admin/AI/IlanAIController.php',
        'app/Http/Controllers/Admin/FeatureController.php',
        'app/Http/Controllers/Frontend/DynamicFormController.php',
        'app/Http/Controllers/Admin/YayinTipiYoneticisiController.php',
    ];

    public function __construct(bool $dryRun = false, bool $verbose = false)
    {
        $this->dryRun = $dryRun;
        $this->verbose = $verbose;
    }

    public function run(): void
    {
        $this->printHeader();

        foreach ($this->targetFiles as $relPath) {
            $this->processFile($relPath);
        }

        $this->printSummary();
    }

    private function processFile(string $relPath): void
    {
        $fullPath = base_path($relPath);

        if (!file_exists($fullPath)) {
            $this->warn("⚠️  File not found: {$relPath}");
            return;
        }

        $this->stats['files_scanned']++;
        $content = file_get_contents($fullPath);
        $originalContent = $content;

        // Apply transformations
        $content = $this->transformRequestInput($content, $relPath);
        $content = $this->transformRequestGet($content, $relPath);
        $content = $this->transformRequestProperty($content, $relPath);

        if ($content !== $originalContent) {
            $this->stats['files_modified']++;
            
            if (!$this->dryRun) {
                file_put_contents($fullPath, $content);
                $this->success("✅ Modified: {$relPath}");
            } else {
                $this->info("🔍 [DRY RUN] Would modify: {$relPath}");
            }
        } else {
            $this->verbose("   No changes needed: {$relPath}");
        }
    }

    /**
     * Transform: $request->input('yayin_tipi')
     * To: $request->input('yayin_tipi_id') ?? $request->input('yayin_tipi')
     */
    private function transformRequestInput(string $content, string $file): string
    {
        $pattern = '/\$request\s*->\s*input\s*\(\s*[\'"]yayin_tipi[\'"]\s*(?:,\s*[^)]+)?\)/';
        
        if (preg_match($pattern, $content)) {
            $this->recordPattern('request->input(yayin_tipi)', $file);
            
            $replacement = function ($matches) {
                $original = $matches[0];
                
                // Extract default value if exists
                if (preg_match('/,\s*([^)]+)\)$/', $original, $defaultMatch)) {
                    $default = trim($defaultMatch[1]);
                    return "(\$request->input('yayin_tipi_id', \$request->input('yayin_tipi')) ?? {$default})";
                }
                
                return "(\$request->input('yayin_tipi_id') ?? \$request->input('yayin_tipi'))";
            };
            
            $content = preg_replace_callback($pattern, $replacement, $content);
            $this->stats['replacements']++;
        }

        return $content;
    }

    /**
     * Transform: $request->get('yayin_tipi')
     * To: $request->get('yayin_tipi_id', $request->get('yayin_tipi'))
     */
    private function transformRequestGet(string $content, string $file): string
    {
        $pattern = '/\$request\s*->\s*get\s*\(\s*[\'"]yayin_tipi[\'"]\s*(?:,\s*[^)]+)?\)/';
        
        if (preg_match($pattern, $content)) {
            $this->recordPattern('request->get(yayin_tipi)', $file);
            
            $replacement = function ($matches) {
                $original = $matches[0];
                
                // Extract default value if exists
                if (preg_match('/,\s*([^)]+)\)$/', $original, $defaultMatch)) {
                    $default = trim($defaultMatch[1]);
                    return "(\$request->get('yayin_tipi_id', \$request->get('yayin_tipi')) ?? {$default})";
                }
                
                return "(\$request->get('yayin_tipi_id') ?? \$request->get('yayin_tipi'))";
            };
            
            $content = preg_replace_callback($pattern, $replacement, $content);
            $this->stats['replacements']++;
        }

        return $content;
    }

    /**
     * Transform: $request->yayin_tipi
     * To: $request->yayin_tipi_id ?? $request->yayin_tipi
     */
    private function transformRequestProperty(string $content, string $file): string
    {
        // Match property access: $request->yayin_tipi (but not yayin_tipi_id)
        $pattern = '/\$request\s*->\s*yayin_tipi\b(?!_)/';
        
        if (preg_match($pattern, $content)) {
            $this->recordPattern('request->yayin_tipi', $file);
            
            $replacement = '(\$request->yayin_tipi_id ?? \$request->yayin_tipi)';
            $content = preg_replace($pattern, $replacement, $content);
            $this->stats['replacements']++;
        }

        return $content;
    }


    private function recordPattern(string $pattern, string $file): void
    {
        if (!isset($this->stats['patterns_found'][$pattern])) {
            $this->stats['patterns_found'][$pattern] = [];
        }
        $this->stats['patterns_found'][$pattern][] = basename($file);
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║         WFC-002 Codemod: yayin_tipi → yayin_tipi_id         ║\n";
        echo "║                  Strategy: DUAL SUPPORT                      ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        if ($this->dryRun) {
            echo "🔍 [DRY RUN MODE] - No files will be modified\n\n";
        }
    }

    private function printSummary(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                      CODEMOD SUMMARY                         ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Files Scanned:   {$this->stats['files_scanned']}\n";
        echo "Files Modified:  {$this->stats['files_modified']}\n";
        echo "Replacements:    {$this->stats['replacements']}\n";
        echo "\n";

        if (!empty($this->stats['patterns_found'])) {
            echo "Patterns Found:\n";
            foreach ($this->stats['patterns_found'] as $pattern => $files) {
                echo "  • {$pattern}: " . count($files) . " file(s)\n";
                foreach ($files as $file) {
                    echo "    - {$file}\n";
                }
            }
        }

        echo "\n";
        
        if ($this->dryRun) {
            echo "✓ Dry run complete. Run without --dry-run to apply changes.\n";
        } else {
            echo "✓ Codemod complete! Next steps:\n";
            echo "  1. Review changes: git diff\n";
            echo "  2. Run tests: php artisan test\n";
            echo "  3. Run Bekçi: php artisan bekci:wizard-contract\n";
            echo "  4. Verify Quality Gate: bash scripts/quality-gate.sh\n";
        }
        echo "\n";
    }

    private function success(string $msg): void
    {
        echo "\033[32m{$msg}\033[0m\n";
    }

    private function info(string $msg): void
    {
        echo "\033[36m{$msg}\033[0m\n";
    }

    private function warn(string $msg): void
    {
        echo "\033[33m{$msg}\033[0m\n";
    }

    private function verbose(string $msg): void
    {
        if ($this->verbose) {
            echo "{$msg}\n";
        }
    }
}

// Bootstrap Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Parse CLI arguments
$dryRun = in_array('--dry-run', $argv);
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);

if (in_array('--help', $argv) || in_array('-h', $argv)) {
    echo "WFC-002 Codemod Usage:\n";
    echo "  php scripts/wfc002_codemod.php [options]\n\n";
    echo "Options:\n";
    echo "  --dry-run    Preview changes without modifying files\n";
    echo "  --verbose    Show detailed output\n";
    echo "  --help       Show this help message\n";
    exit(0);
}

// Run codemod
$codemod = new WFC002Codemod($dryRun, $verbose);
$codemod->run();
