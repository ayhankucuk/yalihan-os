<?php

namespace App\Services\Governance;

use App\Services\Sab\SabAutomationGuardService;
use App\Services\AI\CodeReviewService;
use Illuminate\Support\Facades\File;

/**
 * SabScanRunner - The Single Authority for performing governance scans.
 * Normalizes findings from different guard layers.
 */
class SabScanRunner
{
    protected SabAutomationGuardService $guardService;
    protected CodeReviewService $codeReviewService;
    protected SabBaselineManager $baselineManager;
    protected \App\Services\Governance\Ast\AstScannerService $astScanner;

    public function __construct(
        SabAutomationGuardService $guardService,
        CodeReviewService $codeReviewService,
        SabBaselineManager $baselineManager,
        \App\Services\Governance\Ast\AstScannerService $astScanner
    ) {
        $this->guardService = $guardService;
        $this->codeReviewService = $codeReviewService;
        $this->baselineManager = $baselineManager;
        $this->astScanner = $astScanner;
    }

    public function getBaselineManager(): SabBaselineManager
    {
        return $this->baselineManager;
    }

    /**
     * Run the full integrity scan.
     */
    public function scan(string $path = 'app'): array
    {
        $violations = [];

        // 1. Core Guard Scan (Architectural - Whole System)
        $guardIssues = $this->guardService->runAllGuards();
        foreach ($guardIssues as $issue) {
            $violations[] = $this->normalizeViolation($issue, 'core_guard');
        }

        // 2. Code Review Scan & AST Scan (File-by-file pattern matching)
        $files = $this->getFilesToScan($path);
        foreach ($files as $file) {
            $filePath = $file->getRelativePathname();
            $rootRelativePath = $path . '/' . $filePath;
            $absolutePath = base_path($rootRelativePath);
            
            // Grep-based legacy scan
            $fileIssues = $this->codeReviewService->reviewFile($absolutePath);
            foreach ($fileIssues as $issue) {
                $issue['file'] = $rootRelativePath;
                $violations[] = $this->normalizeViolation($issue, 'code_review');
            }

            // AST-based modern scan (Report-Only in P3A)
            $astIssues = $this->astScanner->scanFile($absolutePath);
            foreach ($astIssues as $issue) {
                $issue['file'] = $rootRelativePath;
                $violations[] = $this->normalizeViolation($issue, 'ast_analyzer');
            }
        }
        
        return $violations;
    }

    /**
     * Get files to scan, excluding SAB internal components.
     */
    private function getFilesToScan(string $path): array
    {
        if (!File::isDirectory(base_path($path))) {
            return [];
        }

        $files = File::allFiles(base_path($path));

        $excludedPaths = [
            'Rules/PHPStan',
            'Models/BaseModel.php',
            'Services/AI/CodeReviewService.php',
            'Services/Bekci/Scanners/',
            'Console/Commands/Sab/',
        ];

        return array_filter($files, function ($file) use ($excludedPaths) {
            $relativePath = $file->getRelativePathname();
            foreach ($excludedPaths as $excluded) {
                if (str_contains($relativePath, $excluded)) {
                    return false;
                }
            }
            // Only scan PHP and Blade files
            return in_array($file->getExtension(), ['php']);
        });
    }

    /**
     * Phase 3: Normalize violation object schema.
     */
    private function normalizeViolation(array $issue, string $origin): array
    {
        $file = $issue['file'] ?? 'unknown';
        $type = $issue['type'] ?? ''; // Preserve original type for baseline matching
        
        $v = [
            'rule' => $issue['guard'] ?? ($type ?: 'unknown'),
            'type' => $type,
            'severity' => strtoupper($issue['severity'] ?? 'LOW'),
            'file' => $file,
            'line' => $issue['line'] ?? 0,
            'message' => $issue['message'] ?? 'No message',
            'suggestion' => $issue['fix'] ?? '',
            'source' => 'yalihan-bekci', // Unified Source
            'origin' => $origin,
            'is_report_only' => $issue['is_report_only'] ?? false,
        ];

        // 🛡️ Baseline Semantic Integration
        // We calculate fingerprint using the original 'type' field to match baseline logic
        $v['fingerprint'] = $this->baselineManager->generateFingerprint($file, $v);
        $v['is_baseline'] = $this->baselineManager->isIgnored($file, $v);

        return $v;
    }
}
