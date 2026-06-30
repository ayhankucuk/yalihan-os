<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Detectors;

use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Contracts\Detector;
use App\Support\Governance\Analyze\Enums\Confidence;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use App\Support\Governance\Analyze\Evidence;
use App\Support\Governance\Analyze\Finding;

/**
 * Detects direct env() calls in application code (app/ directory).
 *
 * v1 scope: app/**\/*.php only.
 * Rationale: calling env() outside config/ files bypasses Laravel's config
 * cache (php artisan config:cache) and breaks test isolation, because env()
 * reads from the process environment at runtime while config() reads from
 * the cached array.
 *
 * One Finding per file — all env() occurrences in a file are grouped as
 * multiple Evidence items to reduce finding noise.
 *
 * v1 filters (noise reduction):
 * - Scan root: app/ only. config/ is at project root and is the legitimate
 *   place for env() calls — it is naturally excluded.
 * - Skip lines whose trimmed content starts with //, #, or * (comment lines).
 *   Note: inline comments on code lines and block-comment inner lines may
 *   still match — this is a known v1 heuristic limit (see ADR addendum).
 * - Skip files in app/Support/Governance/Analyze/ (self-protect).
 *
 * Out of scope: env() inside string concatenation fragments, dynamic
 * variable-based calls, or vendor/.
 * Self-protection: skips app/Support/Governance/Analyze/ namespace.
 */
final class EnvironmentBlockerDetector implements Detector
{
    private const SCAN_ROOT = 'app';

    public function slug(): string
    {
        return 'env-blocker';
    }

    public function title(): string
    {
        return 'EnvironmentBlockerDetector';
    }

    public function detect(AnalysisContext $context): array
    {
        $appDir = $context->repoRoot . DIRECTORY_SEPARATOR . self::SCAN_ROOT;

        if (! is_dir($appDir)) {
            return [];
        }

        $findings = [];

        foreach ($this->collectPhpFiles($appDir) as $absPath) {
            // Self-protect: skip governance analyzer namespace
            if (str_contains($absPath, DIRECTORY_SEPARATOR . 'Governance' . DIRECTORY_SEPARATOR . 'Analyze' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $lines = @file($absPath, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }

            $hits = [];

            foreach ($lines as $i => $line) {
                $trimmed = ltrim($line);

                // Skip comment-only lines
                if (
                    str_starts_with($trimmed, '//') ||
                    str_starts_with($trimmed, '#') ||
                    str_starts_with($trimmed, '*')
                ) {
                    continue;
                }

                // Detect direct env( call — regex pattern avoids scanner self-trigger
                if (preg_match('/\benv\(/', $line)) {
                    $hits[] = new Evidence(
                        file: $this->relative($context->repoRoot, $absPath),
                        line: $i + 1,
                        snippet: trim($line),
                    );
                }
            }

            if (count($hits) === 0) {
                continue;
            }

            $rel = $this->relative($context->repoRoot, $absPath);
            $content = implode("\n", $lines);
            $fqcn = $this->extractFqcn($content);
            $label = $fqcn ?? $rel;
            $count = count($hits);
            $id = 'ENV_BLOCKER_' . strtoupper(str_replace([DIRECTORY_SEPARATOR, '/', '.'], '_', $rel)) . '_' . md5($rel);

            $findings[] = new Finding(
                id: substr($id, 0, 96),
                title: sprintf(
                    'Direct env() call in "%s" (%d occurrence%s)',
                    $label,
                    $count,
                    $count > 1 ? 's' : ''
                ),
                tur: FindingType::ENVIRONMENT_BLOCKER,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'service',
                summary: sprintf(
                    'Class/file "%s" calls env() directly. env() bypasses Laravel config cache '
                    . 'and breaks test isolation. Replace with config() accessors backed by proper config keys.',
                    $label
                ),
                evidence: $hits,
                safeAction: 'Replace each env() call with a config() accessor. '
                    . 'Add the corresponding key to the relevant config file (e.g. config/services.php). '
                    . 'Run php artisan config:cache to verify the fix.',
                detector: $this->slug(),
                durum: 'open',
                impact: [
                    'config cache bypassed',
                    'test isolation broken',
                    'environment coupling',
                ],
                tags: ['env-blocker', 'laravel-anti-pattern', 'test-isolation']
            );
        }

        return $findings;
    }

    /** @return list<string> */
    private function collectPhpFiles(string $root): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $root,
            \RecursiveDirectoryIterator::SKIP_DOTS,
        ));

        foreach ($it as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }

    private function extractFqcn(string $contents): ?string
    {
        $namespace = '';
        if (preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $contents, $m)) {
            $namespace = $m[1];
        }

        if (preg_match('/^(?:final\s+)?(?:readonly\s+)?(?:abstract\s+)?class\s+(\w+)/m', $contents, $m)) {
            $shortName = $m[1];

            return $namespace !== '' ? $namespace . '\\' . $shortName : $shortName;
        }

        return null;
    }

    private function relative(string $root, string $abs): string
    {
        if (str_starts_with($abs, $root . DIRECTORY_SEPARATOR)) {
            return substr($abs, strlen($root) + 1);
        }

        return $abs;
    }
}
