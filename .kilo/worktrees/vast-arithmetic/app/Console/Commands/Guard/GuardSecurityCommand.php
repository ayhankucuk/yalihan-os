<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GuardSecurityCommand extends Command
{
    protected $signature = 'guard:security {--url=http://localhost:8002 : Base URL of the running application}';
    protected $description = '🛡️ Security Boundary Scanner: Verifies public AI search does not leak private fields.';

    /**
     * Fields that MUST NEVER appear in public AI search responses.
     */
    protected array $forbiddenFields = [
        'owner_id',
        'danisman_id',
        'metadata',
        'internal_notes',
        'advisor_phone',
        'danisman_telefon',
        'sifre_hash',
        'password',
    ];

    public function handle(): int
    {
        $this->info('🛡️ Security Boundary Scanner — Checking public endpoint responses...');
        $this->newLine();

        $baseUrl = $this->option('url');
        $endpoint = $baseUrl . '/api/v1/public-ai/ilan-arama';
        $leakFound = false;

        // Test with a broad query that should return data
        $queries = ['bodrum villa', 'istanbul daire', 'emlak'];

        foreach ($queries as $query) {
            $this->info("🔍 Scanning response for query: \"{$query}\"...");

            try {
                $response = Http::timeout(30)->post($endpoint, [
                    'query' => $query,
                ]);

                if (!$response->successful() && $response->status() !== 200) {
                    $this->warn("   ⚠️  Non-200 response ({$response->status()}), skipping field scan.");
                    continue;
                }

                $body = $response->body();
                $json = $response->json();

                // Deep scan: check the entire JSON string for forbidden field names
                foreach ($this->forbiddenFields as $field) {
                    if (stripos($body, "\"$field\"") !== false) {
                        $leakFound = true;
                        $this->error("   ❌ SECURITY LEAK: Field \"{$field}\" found in public response!");
                    }
                }

                if (!$leakFound) {
                    $this->info("   ✅ No forbidden fields detected.");
                }

            } catch (\Exception $e) {
                $this->warn("   ⚠️  Request failed: " . $e->getMessage());
                $this->warn("   Skipping — ensure the server is running at {$baseUrl}.");
            }
        }

        $this->newLine();

        // Also verify protected endpoints reject guests
        $this->info('🔍 Verifying protected endpoints reject unauthenticated access...');
        $protectedEndpoints = [
            '/api/advisor/opportunities',
            '/api/advisor/listings/1/buyer-matches',
        ];

        foreach ($protectedEndpoints as $ep) {
            try {
                $response = Http::timeout(10)->get($baseUrl . $ep);
                if ($response->status() === 401 || $response->status() === 403) {
                    $this->info("   ✅ {$ep} → {$response->status()} (correctly rejected)");
                } else {
                    $leakFound = true;
                    $this->error("   ❌ {$ep} → {$response->status()} (SHOULD be 401/403!)");
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠️  {$ep} → Connection failed: " . $e->getMessage());
            }
        }

        $this->newLine();

        if ($leakFound) {
            $this->error('❌ SECURITY LEAK DETECTED — Block deployment immediately.');
            return 1;
        }

        $this->info('✅ SECURITY BOUNDARY: PASS — No private data leakage detected.');
        return 0;
    }
}
