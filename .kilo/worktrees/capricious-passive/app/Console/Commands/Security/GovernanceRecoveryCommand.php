<?php

namespace App\Console\Commands\Security;

use App\Domain\Core\Security\GlobalHardlockManagerContract;
use Illuminate\Console\Command;

/**
 * Class GovernanceRecoveryCommand
 * @package App\Console\Commands\Security
 * @description Phase 20: Kriptografik kurtarma anahtarı ile hardlock kilitlerini çözen Artisan yönetim komutu.
 */
class GovernanceRecoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'governance:recover {tenant_id : The tenant ID to recover or 0 for SYSTEM} {--token= : Kriptografik kurtarma anahtarı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover and lift the secure cryptographic hardlock for a tenant or the system.';

    /**
     * Execute the console command.
     */
    public function handle(GlobalHardlockManagerContract $hardlockManager): int
    {
        $tenantId = (int)$this->argument('tenant_id');
        $token = $this->option('token');

        if (!$token) {
            $this->error('🚨 Error: --token is required to lift the hardlock.');
            return 1;
        }

        $this->info("🔑 Attempting to lift hardlock for ID [{$tenantId}]...");

        $success = $hardlockManager->releaseHardlock($tenantId, $token);

        if ($success) {
            $this->info("✅ Success: Hardlock lifted successfully.");
            return 0;
        }

        $this->error("🚨 Failure: Invalid recovery token.");
        return 1;
    }
}
