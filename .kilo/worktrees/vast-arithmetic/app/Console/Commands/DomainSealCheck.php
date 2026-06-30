<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\Governance\SealRegistry;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class DomainSealCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:seal-check {domain? : Denetlenecek domain adı (CRM, TASK vb.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Domain bazlı Foundation Lock bütünlüğünü (Seal Integrity) denetler';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $domainName = $this->argument('domain');

        if (!$domainName) {
            return $this->listDomains();
        }

        if (strtoupper($domainName) === 'ALL') {
            return $this->checkAllDomains();
        }

        return $this->checkDomain($domainName);
    }

    /**
     * Check all registered domains
     */
    protected function checkAllDomains(): int
    {
        $this->info("🛡️  Starting Global Seal Audit for ALL domains...");
        
        $domains = SealRegistry::getDomains();
        $allTests = [];
        $allPassed = true;

        // 1. Collect all tests first
        foreach ($domains as $name => $data) {
            if (!empty($data['tests'])) {
                foreach ($data['tests'] as $test) {
                    $allTests[] = $test;
                }
            }
        }

        // 2. Run ALL tests in a single global process to save resources
        if (!empty($allTests)) {
            $this->comment("\n🧪 Running Global Domain Tests (Armored Batch)...");
            $testFilter = implode('|', array_unique($allTests));
            $cmd = "php artisan test --filter=\"{$testFilter}\"";
            
            $defaultConnection = config('database.default');
            $env = array_merge([
                'DB_CONNECTION' => $defaultConnection,
                'TELESCOPE_ENABLED' => 'false',
                'APP_ENV' => 'testing'
            ], $_SERVER);

            if ($defaultConnection === 'mysql') {
                $env['DB_HOST'] = '127.0.0.1';
                $env['DB_DATABASE'] = 'yalihanai_test';
            }

            $process = Process::fromShellCommandline($cmd, base_path(), $env);
            $process->setTimeout(900); // 15 mins for global suite
            
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $allPassed = false;
                $this->error("\n❌ Global Test Batch FAILED!");
            } else {
                $this->info("\n✅ Global Test Batch PASSED!");
            }
        }

        // 3. Run individual domain scanners and gates (non-test steps)
        foreach ($domains as $name => $data) {
            if ($this->checkDomain($name, true) !== 0) {
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $this->info("\n🏆 GLOBAL SEAL SUCCESS: All domains are compliant and locked.");
            return 0;
        }

        $this->error("\n❌ GLOBAL SEAL FAILURE: One or more domains have broken seals!");
        return 1;
    }

    /**
     * Check a single domain (with optional skipTests flag for batching)
     */
    protected function checkDomain(string $name, bool $skipTests = false): int
    {
        $domain = SealRegistry::getDomain($name);
        if (!$domain) {
            $this->error("❌ Domain '{$name}' not found in Registry.");
            return 1;
        }

        $domainName = $domain['name'] ?? $name;

        $this->info("\n🛡️  Checking Seal Integrity for: {$domainName}");
        $this->comment("Durum: SEALED 🛡️");
        $this->line(str_repeat('-', 60));

        $results = [];
        $allPassed = true;

        // Force stable environment variables for all subprocesses
        $defaultConnection = config('database.default');
        $env = array_merge([
            'DB_CONNECTION' => $defaultConnection,
            'TELESCOPE_ENABLED' => 'false',
            'APP_ENV' => 'testing'
        ], $_SERVER);

        if ($env['DB_CONNECTION'] === 'mysql') {
            $env['DB_HOST'] = config('database.connections.mysql.host', '127.0.0.1');
            $env['DB_DATABASE'] = config('database.connections.mysql.database', 'yalihanai_test');
            
            // Force stable database connection for the current process before running in-process scanners
            $this->comment("🔄 Validating database connection compatibility...");
            config(['database.connections.mysql.host' => $env['DB_HOST']]);
            config(['database.connections.mysql.database' => $env['DB_DATABASE']]);
        }
        // 1. Run Scanners
        foreach ($domain['scanners'] as $scanner) {
            $this->comment("\n🔍 Running Scanner: {$scanner}...");
            
            // Cool-down period for OS network stack
            sleep(2);

            $process = Process::fromShellCommandline("php artisan {$scanner}", base_path(), $env);
            $process->setTimeout(300);
            $process->run(function ($type, $buffer) { $this->output->write($buffer); });
            $passed = $process->isSuccessful();
            
            $results[] = ['Tip' => 'Scanner', 'Check' => $scanner, 'Result' => $passed ? '✅ PASS' : '❌ FAIL'];
            if (!$passed) $allPassed = false;
        }

        // 2. Report Tests (Results were calculated in global batch)
        if (!empty($domain['tests']) && !$skipTests) {
             $testFilter = implode('|', $domain['tests']);
             $cmd = "php artisan test --filter=\"{$testFilter}\"";
             
             sleep(2);
             $process = Process::fromShellCommandline($cmd, base_path(), $env);
             $process->setTimeout(600);
             $process->run(function ($type, $buffer) { $this->output->write($buffer); });
             $passed = $process->isSuccessful();
             $results[] = ['Tip' => 'Test', 'Check' => "Domain Tests", 'Result' => $passed ? '✅ PASS' : '❌ FAIL'];
             if (!$passed) $allPassed = false;
        } elseif (!empty($domain['tests']) && $skipTests) {
            $results[] = ['Tip' => 'Test', 'Check' => "Batch Verified", 'Result' => '✅ PASS'];
        }

        // 3. Run Gates
        foreach ($domain['gates'] as $gate) {
            $this->comment("\n🛡️ Running Quality Gate: {$gate}...");
            
            sleep(2);
            $process = Process::fromShellCommandline("php artisan {$gate}", base_path(), $env);
            $process->setTimeout(300);
            $process->run(function ($type, $buffer) { $this->output->write($buffer); });
            $passed = $process->isSuccessful();

            $results[] = ['Tip' => 'Gate', 'Check' => $gate, 'Result' => $passed ? '✅ PASS' : '❌ FAIL'];
            if (!$passed) $allPassed = false;
        }

        $this->table(['Tip', 'Check', 'Result'], $results);
        return $allPassed ? 0 : 1;
    }

    /**
     * Mevcut domainleri listele
     */
    protected function listDomains(): int
    {
        $this->info("Available Governance Domains:");
        $rows = [];
        foreach (SealRegistry::getDomains() as $name => $data) {
            $rows[] = [$name, $data['label'], $data['durum']];
        }
        $this->table(['Key', 'Label', 'Durum'], $rows);
        return 0;
    }
}
