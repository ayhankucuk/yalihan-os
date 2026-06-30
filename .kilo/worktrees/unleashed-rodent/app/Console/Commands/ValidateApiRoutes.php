<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * API Route Validator
 *
 * Context7 Standard: C7-API-ROUTE-VALIDATOR-2025-12-03
 *
 * Route çakışmalarını ve sorunları otomatik tespit eder.
 *
 * @version 1.0.0
 * @since 2025-12-03
 */
class ValidateApiRoutes extends Command
{
    protected $signature = 'api:validate-routes';
    protected $description = 'Validate API routes for conflicts and issues';

    public function handle()
    {
        $this->info('🔍 Validating API routes...');

        $routes = Route::getRoutes();
        $apiRoutes = [];
        $conflicts = [];
        $issues = [];

        // Collect all API routes
        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'admin/')) {
                $method = implode('|', $route->methods());
                $key = "{$method}:{$uri}";

                if (isset($apiRoutes[$key])) {
                    $conflicts[] = [
                        'uri' => $uri,
                        'method' => $method,
                        'existing' => $apiRoutes[$key],
                        'duplicate' => $route->getActionName(),
                    ];
                } else {
                    $apiRoutes[$key] = $route->getActionName();
                }

                // Check for issues
                if (str_contains($uri, '//')) {
                    $issues[] = "Double slash in URI: {$uri}";
                }

                if (str_ends_with($uri, '/') && $uri !== '/') {
                    $issues[] = "Trailing slash in URI: {$uri}";
                }
            }
        }

        // Report results
        $this->newLine();

        if (empty($conflicts) && empty($issues)) {
            $this->info('✅ No conflicts or issues found!');
            $this->info("   Total API routes: " . count($apiRoutes));
            return 0;
        }

        if (!empty($conflicts)) {
            $this->error('❌ Route conflicts found:');
            foreach ($conflicts as $conflict) {
                $this->line("   {$conflict['method']} {$conflict['uri']}");
                $this->line("      Existing: {$conflict['existing']}");
                $this->line("      Duplicate: {$conflict['duplicate']}");
                $this->newLine();
            }
        }

        if (!empty($issues)) {
            $this->warn('⚠️  Issues found:');
            foreach ($issues as $issue) {
                $this->line("   {$issue}");
            }
        }

        return 1;
    }
}
