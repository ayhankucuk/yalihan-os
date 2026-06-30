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
 * Detects duplicate route name declarations — the primary marker of
 * route authority conflict. Same name registered in multiple locations
 * is ambiguous runtime behavior.
 *
 * v1 scope: route file scan via regex on ->name('x').
 * Out of scope: url()->route() caller graph, dynamic name binding.
 */
final class RouteAuthorityDetector implements Detector
{
    private const ROUTE_FILE_GLOBS = [
        'routes/*.php',
        'routes/**/*.php',
    ];

    public function slug(): string
    {
        return 'routes';
    }

    public function title(): string
    {
        return 'Route Authority Detector';
    }

    public function detect(AnalysisContext $context): array
    {
        $files = $this->collectRouteFiles($context->repoRoot);

        /** @var array<string, list<Evidence>> $byName */
        $byName = [];

        foreach ($files as $absPath) {
            $lines = @file($absPath);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $idx => $raw) {
                if (! preg_match_all(
                    "/->name\\(\\s*['\"]([a-zA-Z0-9_.\\-]+)['\"]\\s*\\)/",
                    $raw,
                    $matches
                )) {
                    continue;
                }

                foreach ($matches[1] as $routeName) {
                    // v1 discipline: flag only fully-qualified leaf names.
                    // Skip:
                    //  - leaf-only names ('index','show','store') — composed by
                    //    Route::name() group prefixes at runtime.
                    //  - trailing-dot names ('api.','admin.') — group prefixes,
                    //    legitimately reused across groups.
                    if (! str_contains($routeName, '.')) {
                        continue;
                    }
                    if (str_ends_with($routeName, '.')) {
                        continue;
                    }

                    $rel = $this->relative($context->repoRoot, $absPath);
                    $byName[$routeName][] = new Evidence(
                        file: $rel,
                        line: $idx + 1,
                        snippet: trim($raw),
                    );
                }
            }
        }

        $findings = [];
        foreach ($byName as $name => $evidences) {
            if (count($evidences) < 2) {
                continue;
            }

            $id = 'ROUTE_DUPLICATE_NAME_' . strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $name));

            $findings[] = new Finding(
                id: $id,
                title: sprintf('Duplicate route name authority: %s', $name),
                tur: FindingType::AUTHORITY_CONFLICT,
                risk: RiskLevel::HIGH,
                confidence: Confidence::HIGH,
                layer: 'route',
                summary: sprintf(
                    "Route name '%s' is declared in %d locations. Runtime behavior is ambiguous and url()->route('%s') may resolve to dead authority.",
                    $name,
                    count($evidences),
                    $name,
                ),
                evidence: array_values($evidences),
                safeAction: 'Identify the canonical active route via route:list, '
                    . 'preserve it, and remove the dead duplicate declarations '
                    . 'after verifying no caller depends on the removed endpoint.',
                detector: $this->slug(),
                impact: [
                    'runtime behavior ambiguity',
                    'dead authority risk',
                    'governance drift',
                ],
                tags: ['routes', 'authority', 'runtime'],
            );
        }

        return $findings;
    }

    /** @return list<string> */
    private function collectRouteFiles(string $repoRoot): array
    {
        $results = [];
        foreach (self::ROUTE_FILE_GLOBS as $glob) {
            $matches = glob($repoRoot . DIRECTORY_SEPARATOR . $glob, GLOB_BRACE) ?: [];
            foreach ($matches as $m) {
                if (is_file($m)) {
                    $results[$m] = true;
                }
            }
        }

        // Deep recursion for subdirectories (admin/, api/, web/)
        $routesDir = $repoRoot . '/routes';
        if (is_dir($routesDir)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($routesDir));
            foreach ($it as $info) {
                if ($info->isFile() && $info->getExtension() === 'php') {
                    $results[$info->getPathname()] = true;
                }
            }
        }

        return array_keys($results);
    }

    private function relative(string $root, string $abs): string
    {
        if (str_starts_with($abs, $root . DIRECTORY_SEPARATOR)) {
            return substr($abs, strlen($root) + 1);
        }

        return $abs;
    }
}
