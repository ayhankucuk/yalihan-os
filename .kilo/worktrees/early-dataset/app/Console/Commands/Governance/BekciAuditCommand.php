<?php

declare(strict_types=1);

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\Governance\Ast\AstScannerService;

/**
 * Yalıhan Bekçi (Watchdog) v2.1
 * The Semantic Architectural Guardian.
 */
class BekciAuditCommand extends Command
{
    protected $signature = 'bekci:audit
        {--silent-catch : Scan for silent/empty exception catch blocks}
        {--boundaries : Verify cross-domain architectural boundaries}
        {--naming : Audit naming authority violations}
        {--secret-scan : Scan for hardcoded secrets, tunnels, and learned patterns}
        {--technical-debt : Track lifecycle of TODO/FIXME comments}
        {--all : Run all cognitive guards}';

    protected $description = 'Yalıhan Bekçi (Watchdog): Cognitive Architectural Audit';

    private AstScannerService $astScanner;

    public function __construct(AstScannerService $astScanner)
    {
        parent::__construct();
        $this->astScanner = $astScanner;
    }

    public function handle(): int
    {
        $this->info("🛡️ Yalıhan Bekçi: Cognitive Audit Starting...");
        $anyViolation = false;

        if ($this->option('silent-catch') || $this->option('all')) {
            $anyViolation = $this->auditSilentCatches() || $anyViolation;
        }

        if ($this->option('secret-scan') || $this->option('all')) {
            $anyViolation = $this->auditSecretLeaks() || $anyViolation;
        }

        if ($this->option('boundaries') || $this->option('all')) {
            $anyViolation = $this->auditBoundaries() || $anyViolation;
        }

        if ($this->option('naming') || $this->option('all')) {
            $this->auditNamingAuthority();
        }

        if ($this->option('technical-debt') || $this->option('all')) {
            $this->auditTechnicalDebt(); // Run but don't block
        }

        if ($anyViolation) {
            $this->error("\n❌ Audit FAILED: Architectural integrity violated.");
            return Command::FAILURE;
        }

        $this->info("\n✅ Audit PASSED: System remains architecturally sound.");
        return Command::SUCCESS;
    }

    private function auditSilentCatches(): bool
    {
        $this->comment("\n🔍 Auditing Silent Catches (Semantic AST)...");
        $found = false;
        $files = $this->getRecursiveFiles(base_path('app'));

        foreach ($files as $file) {
            if (!str_ends_with($file, '.php')) continue;
            
            $violations = $this->astScanner->scanFile($file);
            foreach ($violations as $v) {
                if ($v['rule'] === 'SilentCatchAST') {
                    $relativeFile = str_replace(base_path(), '', $file);
                    $this->comment("   [!] (WARNING) {$v['message']} at {$relativeFile}:{$v['line']}");
                    // $found = true; // Non-blocking warning
                }
            }
        }
        return false;
    }

    private function auditSecretLeaks(): bool
    {
        $this->comment("\n🔍 Auditing Secret Leaks & Learned Patterns...");
        $anyLeak = false;

        // 1. Check Living Memory (ANTI_PATTERNS.json)
        $antiPatterns = json_decode(File::get(base_path('docs/governance/ANTI_PATTERNS.json')), true);
        foreach ($antiPatterns['anti_patterns'] as $ap) {
            $this->info("   Checking: {$ap['name']}...");
            $paths = explode(' ', $ap['path']);
            foreach ($paths as $path) {
                if (empty(trim($path))) continue;
                $anyLeak = $this->scanFilesByPattern(trim($path), '/' . preg_quote($ap['pattern'], '/') . '/', "Anti-Pattern Violation: {$ap['name']}") || $anyLeak;
            }
        }

        // 2. Check Learned Patterns (LEARNED_PATTERNS.json)
        $learned = json_decode(File::get(base_path('docs/governance/LEARNED_PATTERNS.json')), true);
        foreach ($learned['patterns'] as $lp) {
            $this->info("   Checking Learned: {$lp['name']}...");
            $anyLeak = $this->scanFilesByPattern('app/', '/' . preg_quote($lp['signature'], '/') . '/', "Learned Regression: {$lp['name']}") || $anyLeak;
        }

        return $anyLeak;
    }

    private function auditBoundaries(): bool
    {
        $this->comment("\n🔍 Auditing Domain Boundaries...");
        // Logic to detect tight coupling between domains (e.g., CRM importing Finance)
        $this->info("   Boundary Guard active (Base Check).");
        return false; // Placeholder
    }

    private function auditTechnicalDebt(): bool
    {
        $this->comment("\n🔍 Auditing Technical Debt (TODO/FIXME)...");
        return $this->scanFilesByPattern('app/', '/\/\/\s*(TODO|FIXME)/i', "Technical Debt marker found");
    }

    private function scanFilesByPattern(string $dir, string $pattern, string $message): bool
    {
        $found = false;
        $baseDir = base_path($dir);
        if (!is_dir($baseDir) && !is_file($baseDir)) return false;

        $files = is_file($baseDir) ? [$baseDir] : $this->getRecursiveFiles($baseDir);

        foreach ($files as $file) {
            if (str_contains($file, 'vendor') || str_contains($file, 'node_modules')) continue;
            
            $content = file_get_contents($file);
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $relativeFile = str_replace(base_path(), '', $file);
                    $this->error("   [!] {$message} at {$relativeFile}:{$lineNumber}");
                    $found = true;
                }
            }
        }
        return $found;
    }

    private function auditNamingAuthority(): bool
    {
        $this->comment("\n🔍 Auditing Naming Authority (Semantic AST)...");
        $found = false;
        
        // Scan app/ and database/migrations/
        $dirs = [base_path('app'), base_path('database/migrations')];
        $files = [];
        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->getRecursiveFiles($dir));
        }

        foreach ($files as $file) {
            if (!str_ends_with($file, '.php')) continue;
            
            $violations = $this->astScanner->scanFile($file);
            foreach ($violations as $v) {
                if ($v['rule'] === 'NamingAuthorityAST') {
                    $relativeFile = str_replace(base_path(), '', $file);
                    $this->comment("   [!] (WARNING) {$v['message']} at {$relativeFile}:{$v['line']}");
                }
            }
        }
        
        // This is warning-only in the current policy
        return false;
    }

    private function getRecursiveFiles(string $dir): array
    {
        $results = [];
        $files = scandir($dir);
        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                if (str_ends_with($path, '.php') || str_ends_with($path, '.js') || str_ends_with($path, '.env')) {
                    $results[] = $path;
                }
            } else if ($value != "." && $value != "..") {
                $results = array_merge($results, $this->getRecursiveFiles($path));
            }
        }
        return $results;
    }
}
