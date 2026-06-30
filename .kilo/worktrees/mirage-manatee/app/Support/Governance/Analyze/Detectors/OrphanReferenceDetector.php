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
 * Detects concrete service classes in app/Services/ that have no
 * discoverable `use` imports anywhere else in app/.
 *
 * v1 scope: app/Services/ declarations, `use` reference scan in app/.
 * A service class with zero external references is a candidate orphan —
 * it may indicate dead code or an unregistered/unbound service.
 *
 * v1 filters (noise reduction):
 * - Skip abstract classes, interfaces, traits
 * - Skip classes whose short name ends in Interface, Contract, or Abstract
 * - Skip classes that appear as a string literal (e.g. service provider bind)
 *   → future Pack-P2 refinement
 *
 * Out of scope: inheritance references (extends/implements), IoC container
 * resolution by FQCN string, reflection-based resolution.
 * Self-protection: skips app/Support/Governance/Analyze/ namespace.
 */
final class OrphanReferenceDetector implements Detector
{
    private const SERVICE_ROOT = 'app/Services';
    private const SCAN_ROOT = 'app';

    public function slug(): string
    {
        return 'orphan';
    }

    public function title(): string
    {
        return 'Orphan Reference Detector';
    }

    public function detect(AnalysisContext $context): array
    {
        $serviceRoot = $context->repoRoot . DIRECTORY_SEPARATOR . self::SERVICE_ROOT;
        $appRoot = $context->repoRoot . DIRECTORY_SEPARATOR . self::SCAN_ROOT;

        if (! is_dir($serviceRoot) || ! is_dir($appRoot)) {
            return [];
        }

        $findings = [];
        $serviceFiles = $this->collectPhpFiles($serviceRoot);
        $allAppFiles = $this->collectPhpFiles($appRoot);

        foreach ($serviceFiles as $servicePath) {
            // Self-protect
            if (str_contains($servicePath, DIRECTORY_SEPARATOR . 'Governance' . DIRECTORY_SEPARATOR . 'Analyze' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = @file_get_contents($servicePath);
            if ($contents === false) {
                continue;
            }

            // Skip non-concrete declarations
            if (preg_match('/\babstract\s+class\b/i', $contents)) {
                continue;
            }
            if (preg_match('/^interface\s+\w+/im', $contents)) {
                continue;
            }
            if (preg_match('/^trait\s+\w+/im', $contents)) {
                continue;
            }

            $fqcn = $this->extractFqcn($contents);
            if ($fqcn === null) {
                continue;
            }

            $shortName = $this->shortName($fqcn);

            // Skip convention-named non-concrete types
            foreach (['Interface', 'Contract', 'Abstract'] as $suffix) {
                if (str_ends_with($shortName, $suffix)) {
                    continue 2;
                }
            }

            // Count `use FQCN` references in all other app/ files
            $refCount = 0;
            foreach ($allAppFiles as $appPath) {
                if ($appPath === $servicePath) {
                    continue;
                }
                if (str_contains($appPath, DIRECTORY_SEPARATOR . 'Governance' . DIRECTORY_SEPARATOR . 'Analyze' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $appContents = @file_get_contents($appPath);
                if ($appContents === false) {
                    continue;
                }

                $pattern = '/^\\s*use\\s+' . preg_quote($fqcn, '/') . '(\\s|;|\\\\|,)/m';
                if (preg_match($pattern, $appContents)) {
                    $refCount++;
                }
            }

            if ($refCount > 0) {
                continue;
            }

            $rel = $this->relative($context->repoRoot, $servicePath);
            $id = 'ORPHAN_SERVICE_' . strtoupper($shortName) . '_' . md5($fqcn);

            $findings[] = new Finding(
                id: substr($id, 0, 96),
                title: sprintf('Service class "%s" has no discoverable callers', $shortName),
                tur: FindingType::ORPHAN_REFERENCE,
                risk: RiskLevel::LOW,
                confidence: Confidence::MEDIUM,
                layer: 'governance',
                summary: sprintf(
                    'Service class "%s" (%s) has no `use` imports found in app/. '
                    . 'It may be dead code, an unbound IoC service, or resolved via string reference (v1 limitation).',
                    $fqcn,
                    $rel
                ),
                evidence: [
                    new Evidence(file: $rel, line: 1, snippet: "class {$shortName} — 0 external use references"),
                ],
                safeAction: 'Verify whether the class is used via IoC container string binding, '
                    . 'extends/implements, or is truly dead. If dead, schedule removal via a governed deprecation cycle.',
                detector: $this->slug(),
                impact: [
                    'dead code risk',
                    'maintenance overhead',
                    'governance surface bloat',
                ],
                tags: ['orphan', 'governance', 'services'],
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

    private function extractFqcn(string $contents): ?string
    {
        $namespace = '';
        if (preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $contents, $m)) {
            $namespace = $m[1];
        }

        if (preg_match('/^(?:final\s+)?(?:readonly\s+)?class\s+(\w+)/m', $contents, $m)) {
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

    private function relative(string $root, string $abs): string
    {
        if (str_starts_with($abs, $root . DIRECTORY_SEPARATOR)) {
            return substr($abs, strlen($root) + 1);
        }

        return $abs;
    }
}
