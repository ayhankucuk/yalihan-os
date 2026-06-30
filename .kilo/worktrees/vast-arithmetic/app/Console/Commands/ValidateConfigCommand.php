<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\Guards\ConfigGuard;
use Throwable;

class ValidateConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate application configuration against governance rules (SSOT Enforcement)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🛡️  Starting Governance Config Validation...');

        try {
            ConfigGuard::validate();
            $this->info('✅ Config validation PASSED. System is compliant.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Config validation FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
