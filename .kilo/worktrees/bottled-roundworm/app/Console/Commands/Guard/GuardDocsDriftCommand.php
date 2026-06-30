<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuardDocsDriftCommand extends Command
{
    protected $signature = 'guard:docs-drift';
    protected $description = '🛡️ Docs Drift Guard: Detects verified-wrong command, route, and service references in documentation.';

    /**
     * Verified drift entries: [wrong_reference => correct_explanation]
     *
     * Each entry represents a known-wrong reference that has been
     * confirmed via runtime verification. Only add entries here
     * after the drift has been verified (not speculative).
     */
    protected array $verifiedDrift = [
        // Komut adları
        'sab:integrity-scan'        => 'Doğru komut: sab:integrity-scan',
        'php artisan context7:scan' => 'Doğru komut: php artisan sab:integrity-scan',

        // CI workflow isimleri (eski/yanlış referanslar)
        'sab-guard.yml'             => 'Doğru CI workflow: gold-line.yml',
        'postseal-guard.yml'        => 'Doğru CI workflow: gold-line.yml',
    ];

    /**
     * Files/directories to exclude from scanning.
     * These may legitimately contain drift references (e.g., changelog documenting the fix).
     */
    protected array $excludePatterns = [
        'CHANGELOG.md',
        'docs/adr/',
        'docs/archive/',
    ];

    /**
     * Docs directory to scan.
     */
    protected string $docsDir = 'docs';

    public function handle(): int
    {
        $this->info('🛡️ Docs Drift Guard — Scanning for verified-wrong references...');
        $this->newLine();

        $docsPath = base_path($this->docsDir);

        if (!File::isDirectory($docsPath)) {
            $this->error("❌ Directory not found: {$this->docsDir}");
            return 1;
        }

        $files = File::allFiles($docsPath);
        $mdFiles = collect($files)->filter(fn($f) => $f->getExtension() === 'md');

        $violations = [];
        $scanned = 0;

        foreach ($mdFiles as $file) {
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            // Skip excluded files/directories
            $excluded = false;
            foreach ($this->excludePatterns as $excludePattern) {
                if (str_contains($relativePath, $excludePattern)) {
                    $excluded = true;
                    break;
                }
            }
            if ($excluded) {
                continue;
            }

            $content = File::get($file->getPathname());
            $scanned++;

            foreach ($this->verifiedDrift as $wrongRef => $correction) {
                if (stripos($content, $wrongRef) !== false) {
                    // Find the line number for context
                    $lines = explode("\n", $content);
                    $lineNumbers = [];
                    foreach ($lines as $i => $line) {
                        if (stripos($line, $wrongRef) !== false) {
                            $lineNumbers[] = $i + 1;
                        }
                    }

                    $violations[] = [
                        'file' => $relativePath,
                        'wrong_ref' => $wrongRef,
                        'correction' => $correction,
                        'lines' => $lineNumbers,
                    ];

                    $lineStr = implode(', ', $lineNumbers);
                    $this->error("❌ DRIFT: {$relativePath} (L{$lineStr})");
                    $this->error("   Yanlış: '{$wrongRef}'");
                    $this->error("   Doğru:  {$correction}");
                }
            }
        }

        $this->newLine();
        $this->info("📊 Tarama: {$scanned} dosya | " . count($this->verifiedDrift) . " drift kuralı kontrol edildi");
        $this->newLine();

        if (!empty($violations)) {
            $this->error('❌ DOCS DRIFT: FAIL — ' . count($violations) . ' verified drift(s) found.');
            $this->error('   Yanlış referanslar düzeltilmeli. Doğru değerler yukarıda belirtilmiştir.');
            return 1;
        }

        $this->info('✅ DOCS DRIFT: PASS — Doğrulanmış yanlış referans bulunamadı.');
        return 0;
    }
}
