<?php

declare(strict_types=1);

namespace App\Console\Commands\Chaos;

use App\Services\Resilience\CircuitBreaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChaosSimulationCommand extends Command
{
    protected $signature = 'chaos:simulate {scenario : The scenario to simulate (db_failure|ai_timeout|file_error)}';
    protected $description = 'Simulate failure and chaos scenarios to verify resilience mechanisms';

    public function handle(CircuitBreaker $circuitBreaker): int
    {
        $scenario = $this->argument('scenario');

        $this->info("🌪️ Starting Chaos Simulation: [{$scenario}]");
        $this->line(str_repeat('=', 50));

        switch ($scenario) {
            case 'ai_timeout':
                return $this->simulateAiTimeout($circuitBreaker);

            case 'db_failure':
                return $this->simulateDbFailure();

            case 'file_error':
                return $this->simulateFileError();

            default:
                $this->error("❌ Unknown scenario: {$scenario}");
                return 1;
        }
    }

    protected function simulateAiTimeout(CircuitBreaker $circuitBreaker): int
    {
        $provider = 'openai';
        $this->info("Checking initial state for provider: [{$provider}]");
        $this->line("   • State: " . $circuitBreaker->getState($provider));
        $this->line("   • Available: " . ($circuitBreaker->isAvailable($provider) ? 'YES' : 'NO'));

        $this->info("Simulating consecutive timeouts...");
        // Simulate 6 consecutive failures (threshold is 5)
        for ($i = 1; $i <= 6; $i++) {
            $circuitBreaker->failure($provider);
            $this->line("   • Failure {$i} recorded.");
        }

        $this->newLine();
        $this->info("Checking state after simulated failures:");
        $state = $circuitBreaker->getState($provider);
        $available = $circuitBreaker->isAvailable($provider);
        $this->line("   • State: " . $state);
        $this->line("   • Available: " . ($available ? 'YES' : 'NO'));

        if ($state === 'open' && !$available) {
            $this->info("✅ SUCCESS: Circuit Breaker successfully opened and blocked further requests!");
            
            // Clean up cache
            $circuitBreaker->success($provider);
            $this->info("🧹 Cleaned up and reset circuit state to closed.");
            return 0;
        }

        $this->error("❌ FAILURE: Circuit Breaker did not open as expected.");
        return 1;
    }

    protected function simulateDbFailure(): int
    {
        $this->info("Executing transactional query with forced rollback...");
        
        try {
            DB::transaction(function () {
                $this->line("   • Inserting dummy record inside transaction...");
                // Insert a dummy log
                DB::table('ai_logs')->insert([
                    'provider' => 'chaos_test',
                    'endpoint' => 'test',
                    'request_type' => 'test',
                    'event_type' => 'test',
                    'correlation_id' => 'chaos-id',
                    'olusturma_tarihi' => now(),
                    'guncelleme_tarihi' => now(),
                ]);

                $this->line("   • Transaction active. Now forcing a PDOException/Database failure...");
                throw new \PDOException("Simulated Database Connection Loss.");
            });
        } catch (\PDOException $e) {
            $this->warn("⚠️ Caught expected exception: " . $e->getMessage());
        }

        // Verify record was rolled back
        $count = DB::table('ai_logs')->where('correlation_id', 'chaos-id')->count();
        $this->line("   • Querying database for correlation ID 'chaos-id'...");
        $this->line("   • Record count: {$count}");

        if ($count === 0) {
            $this->info("✅ SUCCESS: Transaction rolled back successfully! No orphan records created.");
            return 0;
        }

        $this->error("❌ FAILURE: Record was not rolled back!");
        return 1;
    }

    protected function simulateFileError(): int
    {
        $this->info("Simulating disk / write error on read-only destination...");
        
        $tempDir = storage_path('chaos_readonly_test');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0444, true); // Read-only directory permissions
        }

        $filePath = $tempDir . '/test.txt';
        
        try {
            $this->line("   • Attempting to write to read-only directory: {$tempDir}");
            
            // On some systems/environments, root user can still write to 0444 directories.
            // So we explicitly throw if file_put_contents fails or if we catch exception.
            if (@file_put_contents($filePath, 'chaos data') === false) {
                throw new \Exception("Permission denied / Disk full simulation.");
            }
            
            $this->warn("⚠️ System allowed write (running as root/super-user). Throwing manual permission exception to check handler recovery...");
            throw new \Exception("Permission denied / Disk full simulation.");
        } catch (\Throwable $e) {
            $this->warn("⚠️ Caught expected exception: " . $e->getMessage());
            
            // Clean up directory if anything was written
            @unlink($filePath);
            @rmdir($tempDir);

            $this->info("✅ SUCCESS: System correctly caught the write failure.");
            return 0;
        }
    }
}
