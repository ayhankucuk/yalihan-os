<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuardDocsAuthorityCommand extends Command
{
    protected $signature = 'guard:docs-authority';
    protected $description = '🛡️ Docs Authority Guard: Ensures architecture docs are marked as READ-ONLY reflection, not authority.';

    /**
     * Required header patterns — at least one must exist in the first 10 lines.
     */
    protected array $requiredHeaders = [
        'Authority Rule',
        'REFERENCE ONLY',
        'NOT SSOT',
        'NOT Authority',
        'READ-ONLY',
    ];

    /**
     * Forbidden patterns — docs MUST NOT claim to be authority.
     */
    protected array $forbiddenPatterns = [
        'source of truth = docs',
        'source of truth = this document',
        'source of truth = documentation',
        'this is the authority',
        'this is the canonical source',
        'canonical source = docs',
        'authoritative source = docs',
    ];

    /**
     * Docs directory to scan.
     */
    protected string $docsDir = 'docs/architecture';

    public function handle(): int
    {
        $this->info('🛡️ Docs Authority Guard — Scanning architecture documentation...');
        $this->newLine();

        $docsPath = base_path($this->docsDir);

        if (!File::isDirectory($docsPath)) {
            $this->error("❌ Directory not found: {$this->docsDir}");
            return 1;
        }

        $files = File::allFiles($docsPath);
        $mdFiles = collect($files)->filter(fn($f) => $f->getExtension() === 'md');

        if ($mdFiles->isEmpty()) {
            $this->warn('⚠️ No markdown files found in ' . $this->docsDir);
            return 0;
        }

        $violations = [];
        $warnings = [];
        $passed = 0;

        foreach ($mdFiles as $file) {
            $content = File::get($file->getPathname());
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
            $lines = explode("\n", $content);
            $headerLines = implode("\n", array_slice($lines, 0, min(10, count($lines))));

            // Check 1: Required authority header in first 10 lines
            $hasHeader = false;
            foreach ($this->requiredHeaders as $header) {
                if (stripos($headerLines, $header) !== false) {
                    $hasHeader = true;
                    break;
                }
            }

            if (!$hasHeader) {
                $violations[] = [
                    'file' => $relativePath,
                    'type' => 'MISSING_AUTHORITY_HEADER',
                    'detail' => 'İlk 10 satırda Authority Rule / REFERENCE ONLY header\'ı bulunamadı',
                ];
                $this->error("❌ MISSING HEADER: {$relativePath}");
                $this->error("   İlk 10 satırda 'Authority Rule' veya 'REFERENCE ONLY' olmalı");
            } else {
                $passed++;
                $this->info("   ✅ {$relativePath}");
            }

            // Check 2: Forbidden authority claims in entire file
            foreach ($this->forbiddenPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $violations[] = [
                        'file' => $relativePath,
                        'type' => 'FORBIDDEN_AUTHORITY_CLAIM',
                        'detail' => "Yasak ifade bulundu: '{$pattern}'",
                    ];
                    $this->error("❌ FORBIDDEN CLAIM in {$relativePath}");
                    $this->error("   Pattern: '{$pattern}'");
                }
            }
        }

        $this->newLine();
        $total = $mdFiles->count();
        $this->info("📊 Tarama: {$total} dosya | {$passed} PASS | " . count($violations) . " violation(s)");
        $this->newLine();

        if (!empty($violations)) {
            $this->error('❌ DOCS AUTHORITY: FAIL — ' . count($violations) . ' violation(s) found.');
            $this->error('   Tüm docs/architecture dosyaları READ-ONLY reflection olmalıdır.');
            return 1;
        }

        $this->info('✅ DOCS AUTHORITY: PASS — Tüm dosyalarda authority header mevcut, yasak ifade yok.');
        return 0;
    }
}
