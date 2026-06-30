<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * API Documentation Generator
 *
 * Context7 Standard: C7-API-DOCS-GENERATOR-2025-12-03
 *
 * Otomatik API dokümantasyonu oluşturur.
 *
 * @version 1.0.0
 * @since 2025-12-03
 */
class GenerateApiDocs extends Command
{
    protected $signature = 'api:generate-docs {--output=docs/api-endpoints.md}';
    protected $description = 'Generate API documentation from routes';

    public function handle()
    {
        $outputPath = $this->option('output');
        $this->info('📚 Generating API documentation...');

        $routes = Route::getRoutes();
        $apiRoutes = [];
        $categories = [];

        // Collect and categorize routes
        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'admin/')) {
                $method = implode('|', $route->methods());
                $action = $route->getActionName();

                // Categorize by prefix
                $category = 'other';
                if (str_contains($uri, 'location')) $category = 'location';
                elseif (str_contains($uri, 'categories')) $category = 'categories';
                elseif (str_contains($uri, 'kisiler') || str_contains($uri, 'users')) $category = 'live_search';
                elseif (str_contains($uri, 'tkgm')) $category = 'tkgm';
                elseif (str_contains($uri, 'properties')) $category = 'properties';
                elseif (str_contains($uri, 'ai')) $category = 'ai';
                elseif (str_contains($uri, 'admin')) $category = 'admin';

                if (!isset($categories[$category])) {
                    $categories[$category] = [];
                }

                $categories[$category][] = [
                    'method' => $method,
                    'uri' => $uri,
                    'action' => $action,
                    'name' => $route->getName(),
                ];
            }
        }

        // Generate markdown
        $markdown = $this->generateMarkdown($categories);

        // Save to file
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($outputPath, $markdown);

        $this->info("✅ Documentation generated: {$outputPath}");
        $this->info("   Total routes documented: " . array_sum(array_map('count', $categories)));
    }

    private function generateMarkdown($categories)
    {
        $md = "# API Endpoints Documentation\n\n";
        $md .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        $md .= "**Context7 Standard:** C7-API-DOCS-2025-12-03\n\n";
        $md .= "---\n\n";

        $categoryNames = [
            'location' => '📍 Location API',
            'categories' => '📂 Categories API',
            'live_search' => '🔍 Live Search API',
            'tkgm' => '🏛️ TKGM API',
            'properties' => '🏠 Properties API',
            'ai' => '🤖 AI API',
            'admin' => '👤 Admin API',
            'other' => '📦 Other API',
        ];

        foreach ($categories as $category => $routes) {
            $md .= "## {$categoryNames[$category]}\n\n";

            foreach ($routes as $route) {
                $method = str_replace('|', ' / ', $route['method']);
                $md .= "### `{$method}` `{$route['uri']}`\n\n";

                if ($route['name']) {
                    $md .= "**Route Name:** `{$route['name']}`\n\n";
                }

                $md .= "**Controller:** `{$route['action']}`\n\n";
                $md .= "---\n\n";
            }
        }

        return $md;
    }
}
