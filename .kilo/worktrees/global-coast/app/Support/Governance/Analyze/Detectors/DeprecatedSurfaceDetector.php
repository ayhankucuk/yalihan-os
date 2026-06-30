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
 * Detects deprecated class/interface/trait declarations that still have
 * active callers via `use` imports in the codebase.
 *
 * v1 scope: app/ directory, PHP files only.
 * Detection: @deprecated in a docblock → scan for `use FQCN` references.
 * One finding per deprecated class that has ≥ 1 active caller.
 *
 * Out of scope: method-level @deprecated, runtime reflection, vendor/.
 * Self-protection: skips app/Support/Governance/Analyze/ namespace.
 */
final class DeprecatedSurfaceDetector implements Detector
{
    private const SCAN_ROOT = 'app';

    public function slug(): string
    {
        return 'deprecated';
    }

    public function title(): string
    {
        return 'Deprecated Surface Detector';
    }

    public function detect(AnalysisContext $context): array
    {
        $root = $context->repoRoot . DIRECTORY_SEPARATOR . self::SCAN_ROOT;
        if (! is_dir($root)) {
            return [];
        }

        $findings = [];
        $allFiles = $this->collectPhpFiles($root);

        foreach ($allFiles as $path) {
            // Self-protect
            if (str_contains($path, DIRECTORY_SEPARATOR . 'Governance' . DIRECTORY_SEPARATOR . 'Analyze' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = @file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            // Only process files that declare a @deprecated class/interface/trait
            if (! preg_match('/@deprecated\b/i', $contents)) {
                continue;
            }

            $fqcn = $this->extractFqcn($contents);
            if ($fqcn === null) {
                continue;
            }

            // Search other files for `use {FQCN}` or `use {FQCN};`
            $callers = [];
            foreach ($allFiles as $callerPath) {
                if ($callerPath === $path) {
                    continue;
                }
                // Self-protect callers too
                if (str_contains($callerPath, DIRECTORY_SEPARATOR . 'Governance' . DIRECTORY_SEPARATOR . 'Analyze' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $callerContents = @file_get_contents($callerPath);
                if ($callerContents === false) {
                    continue;
                }

                // Use `use FQCN` (with optional alias or semicolon)
                $pattern = '/^\\s*use\\s+' . preg_quote($fqcn, '/') . '(\\s|;|\\\\|,)/m';
                if (preg_match($pattern, $callerContents)) {
                    $callers[] = $callerPath;
                }
            }

            if (empty($callers)) {
                continue;
            }

            $rel = $this->relative($context->repoRoot, $path);
            $shortClass = $this->shortName($fqcn);
            $id = 'DEPRECATED_SURFACE_' . strtoupper($shortClass) . '_' . md5($fqcn);

            $evidence = [
                new Evidence(file: $rel, line: 1, snippet: "@deprecated — {$fqcn}"),
            ];
            foreach ($callers as $callerPath) {
                $evidence[] = new Evidence(
                    file: $this->relative($context->repoRoot, $callerPath),
                    line: $this->findUseLine($callerPath, $fqcn),
                    snippet: "use {$fqcn}"
                );
            }

            $findings[] = new Finding(
                id: substr($id, 0, 96),
                title: sprintf('@deprecated class "%s" has %d active caller(s)', $shortClass, count($callers)),
                tur: FindingType::DEPRECATED_SURFACE,
                risk: RiskLevel::MEDIUM,
                confidence: Confidence::HIGH,
                layer: 'governance',
                summary: sprintf(
                    'Class "%s" is marked @deprecated but is still imported by %d file(s). '
                    . 'Callers should be migrated to the canonical replacement before this class is removed.',
                    $fqcn,
                    count($callers)
                ),
                evidence: $evidence,
                safeAction: 'Identify the canonical replacement for this deprecated class, migrate callers, then remove the deprecated declaration. Never add new callers.',
                detector: $this->slug(),
                impact: [
                    'deprecated surface growth',
                    'risk of removal breakage',
                    'governance drift',
                ],
                tags: ['deprecated', 'governance', 'surface'],
            );
        }

        return $findings;
    }

    /** @return list<string> */
    private function collectPhpFiles(string $root): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extract the fully-qualified class name from namespace + class declaration.
     * Returns null if no recognizable declaration is found.
     */
    private function extractFqcn(string $contents): ?string
    {
        $namespace = '';
        if (preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $contents, $m)) {
            $namespace = $m[1];
        }

        if (preg_match('/^(?:abstract\s+)?(?:class|interface|trait)\s+(\w+)/m', $contents, $m)) {
            $shortName = $m[1];

            return $namespace !== '' ? $namespace . '\\' . $shortName : $shortName;
        }

        return null;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    private function findUseLine(string $path, string $fqcn): int
    {
        $lines = @file($path);
        if ($lines === false) {
            return 1;
        }

        $quoted = preg_quote($fqcn, '/');
        foreach ($lines as $idx => $line) {
            if (preg_match('/^\\s*use\\s+' . $quoted . '/', $line)) {
                return $idx + 1;
            }
        }

        return 1;
    }

    private function relative(string $root, string $abs): string
    {
        if (str_starts_with($abs, $root . DIRECTORY_SEPARATOR)) {
            return substr($abs, strlen($root) + 1);
        }

        return $abs;
    }
}
