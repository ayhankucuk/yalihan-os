<?php

namespace App\Services\Health;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

/**
 * @sab-ignore-thin
 */
class SystemProbeService
{
    /**
     * Get system uptime using Symfony Process (SAB Phase 1A)
     */
    public function getUptime(): string
    {
        // 🛡️ SAB Phase 1A: Replace shell_exec with Symfony Process
        try {
            $process = new Process(['uptime', '-p']);
            $process->setTimeout(2);
            $process->run();

            if (!$process->isSuccessful()) {
                return 'N/A';
            }

            return trim($process->getOutput());
        } catch (\Exception $e) {
            Log::warning('SystemProbeService: Failed to get uptime', ['error' => $e->getMessage()]);
            return 'N/A';
        }
    }

    /**
     * Get CPU load
     */
    public function getCpuLoad(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load) {
                return implode(', ', array_slice($load, 0, 2));
            }
        }
        return 'N/A';
    }
}
