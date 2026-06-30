<?php

namespace Tests\Unit\Governance;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

/**
 * Service Governance Test Suite
 *
 * Purpose: Enforce service layer governance rules
 * Phase: 4B - Service Governance Alignment
 *
 * Invariants:
 *   1. Tenant-owned services MUST NOT query models directly
 *   2. All cache keys MUST include tenant context (or be explicitly exempt)
 *   3. Services MUST use Repository Kernel for data access
 *   4. Cross-tenant access MUST have @governance annotation
 */
class ServiceGovernanceTest extends TestCase
{
    private array $whitelist = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Load whitelist
        $whitelistPath = base_path('scripts/governance/service_governance_whitelist.txt');
        if (File::exists($whitelistPath)) {
            $this->whitelist = array_filter(
                array_map('trim', file($whitelistPath)),
                fn($line) => !empty($line) && !str_starts_with($line, '#')
            );
        }
    }

    /**
     * @test
     * @group governance
     * @group service-layer
     */
    public function tenant_owned_services_must_not_query_models_directly()
    {
        $violations = $this->scanForDirectModelAccess('app/Services/CRM');

        $this->assertEmpty(
            $violations,
            "Direct model access detected in tenant-owned services:\n" .
            $this->formatViolations($violations)
        );
    }

    /**
     * @test
     * @group governance
     * @group cache-isolation
     */
    public function cache_keys_must_include_tenant_context()
    {
        $violations = $this->scanForTenantAgnosticCache('app/Services/CRM');

        $this->assertEmpty(
            $violations,
            "Tenant-agnostic cache keys detected:\n" .
            $this->formatViolations($violations)
        );
    }

    /**
     * @test
     * @group governance
     * @group repository-injection
     */
    public function crm_services_must_inject_repositories()
    {
        $violations = $this->scanForMissingRepositoryInjection('app/Services/CRM');

        $this->assertEmpty(
            $violations,
            "Services using models without repository injection:\n" .
            $this->formatViolations($violations)
        );
    }

    /**
     * @test
     * @group governance
     * @group annotations
     */
    public function cross_tenant_services_must_have_governance_annotation()
    {
        $violations = [];

        $crossTenantServices = [
            'app/Services/Cortex/MatchingEngine.php',
            'app/Services/Matching/DemandMatchingEngine.php',
        ];

        foreach ($crossTenantServices as $service) {
            $path = base_path($service);
            if (!File::exists($path)) {
                continue;
            }

            $content = File::get($path);

            if (!str_contains($content, '@governance INTENTIONAL_CROSS_TENANT') &&
                !str_contains($content, '@governance PUBLIC_CORPUS')) {
                $violations[] = $service;
            }
        }

        $this->assertEmpty(
            $violations,
            "Cross-tenant services missing @governance annotation:\n" .
            implode("\n", array_map(fn($v) => "  - $v", $violations))
        );
    }

    /**
     * @test
     * @group governance
     * @group aggregation
     */
    public function aggregation_services_must_have_boundary_annotation()
    {
        $violations = [];

        $aggregationServices = [
            'app/Services/CRM/CRMOrchestratorService.php',
            'app/Services/Analytics/KPIService.php',
        ];

        foreach ($aggregationServices as $service) {
            $path = base_path($service);
            if (!File::exists($path)) {
                continue;
            }

            $content = File::get($path);

            // Check for aggregation boundary annotation or tenant isolation
            if (!str_contains($content, '@governance AGGREGATION_BOUNDARY') &&
                !str_contains($content, '@tenant-isolation required')) {
                $violations[] = $service;
            }
        }

        $this->assertEmpty(
            $violations,
            "Aggregation services missing boundary annotation:\n" .
            implode("\n", array_map(fn($v) => "  - $v", $violations))
        );
    }

    /**
     * Scan for direct model access in services
     */
    private function scanForDirectModelAccess(string $directory): array
    {
        $violations = [];
        $basePath = base_path($directory);

        if (!File::isDirectory($basePath)) {
            return [];
        }

        $files = File::allFiles($basePath);

        foreach ($files as $file) {
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            // Skip whitelisted files
            if ($this->isWhitelisted($relativePath)) {
                continue;
            }

            $content = File::get($file->getPathname());

            // Skip if has governance annotation
            if ($this->hasGovernanceAnnotation($content)) {
                continue;
            }

            // Check for direct model access patterns
            $patterns = [
                '/\b\w+::where\s*\(/',
                '/\b\w+::find\s*\(/',
                '/\b\w+::query\s*\(/',
                '/\b\w+::first\s*\(/',
                '/\b\w+::get\s*\(/',
                '/\b\w+::all\s*\(/',
                '/\b\w+::create\s*\(/',
                '/\b\w+::update\s*\(/',
            ];

            // Exclude allowed patterns
            $excludePatterns = [
                'Repository::',
                'Builder::',
                'Query::',
                'DB::',
                'Cache::',
                '// @governance-exempt',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    // Check if it's an excluded pattern
                    $isExcluded = false;
                    foreach ($excludePatterns as $exclude) {
                        if (str_contains($content, $exclude)) {
                            $isExcluded = true;
                            break;
                        }
                    }

                    if (!$isExcluded) {
                        $violations[] = [
                            'file' => $relativePath,
                            'issue' => 'Direct model access detected',
                            'pattern' => $pattern,
                        ];
                        break;
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for tenant-agnostic cache keys
     */
    private function scanForTenantAgnosticCache(string $directory): array
    {
        $violations = [];
        $basePath = base_path($directory);

        if (!File::isDirectory($basePath)) {
            return [];
        }

        $files = File::allFiles($basePath);

        foreach ($files as $file) {
            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            if ($this->isWhitelisted($relativePath)) {
                continue;
            }

            $content = File::get($file->getPathname());

            // Check for cache operations
            if (preg_match('/Cache::(remember|rememberForever|put)\s*\(/', $content)) {
                // Check if tenant context is present
                $hasTenantContext =
                    str_contains($content, 'tenant:') ||
                    str_contains($content, 'tenantId') ||
                    str_contains($content, 'tenant_id') ||
                    str_contains($content, '@governance PUBLIC_CORPUS');

                if (!$hasTenantContext) {
                    $violations[] = [
                        'file' => $relativePath,
                        'issue' => 'Cache key without tenant context',
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for missing repository injection
     */
    private function scanForMissingRepositoryInjection(string $directory): array
    {
        $violations = [];
        $basePath = base_path($directory);

        if (!File::isDirectory($basePath)) {
            return [];
        }

        $files = File::allFiles($basePath);

        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), 'Service.php')) {
                continue;
            }

            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            if ($this->isWhitelisted($relativePath)) {
                continue;
            }

            $content = File::get($file->getPathname());

            // Check if uses models but doesn't inject repository
            if (preg_match('/use App\\\\Models\\\\/', $content)) {
                if (!str_contains($content, 'Repository')) {
                    $violations[] = [
                        'file' => $relativePath,
                        'issue' => 'Uses models but no repository injection',
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Check if file is whitelisted
     */
    private function isWhitelisted(string $file): bool
    {
        foreach ($this->whitelist as $pattern) {
            if (preg_match('#' . str_replace('*', '.*', $pattern) . '#', $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content has governance annotation
     */
    private function hasGovernanceAnnotation(string $content): bool
    {
        return str_contains($content, '@governance INTENTIONAL_CROSS_TENANT') ||
               str_contains($content, '@governance PUBLIC_CORPUS') ||
               str_contains($content, '@governance AGGREGATION_BOUNDARY');
    }

    /**
     * Format violations for output
     */
    private function formatViolations(array $violations): string
    {
        if (empty($violations)) {
            return '';
        }

        $output = [];
        foreach ($violations as $violation) {
            $file = $violation['file'] ?? 'unknown';
            $issue = $violation['issue'] ?? 'unknown issue';
            $output[] = "  - $file: $issue";
        }

        return implode("\n", $output);
    }
}
