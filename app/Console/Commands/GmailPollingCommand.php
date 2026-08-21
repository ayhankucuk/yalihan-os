<?php

namespace App\Console\Commands;

use App\Services\Email\GmailMultiMailboxOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GmailPollingCommand
 *
 * Wave 2 — Gmail Communications Intelligence
 *
 * Periyodik olarak Gmail'i kontrol eder ve yeni mailleri webhook'a iletir.
 * Ayhan'in Gmail hesabindan gelen yeni mesajlari tespit eder.
 *
 * Kullanim:
 *   php artisan gmail:poll                  # Tek seferlik kontrol
 *   php artisan gmail:poll --daemon          # Sürekli daemon modu (production)
 *
 * Cron ile 5 dakikada bir calistirmak icin:
 *   * /5 * * * * cd /path-to-project && php artisan gmail:poll >> /dev/null 2>&1
 */
class GmailPollingCommand extends Command
{
    protected $signature = 'gmail:poll
        {--daemon : Sürekli calisma modu (production)}
        {--dry-run : Yeni email yokmus gibi davran, sadece log}';

    protected $description = 'Gmail inbox polling — yeni mailleri Yalihan OS\'a iletir (Wave 2)';

    public function __construct(
        private readonly GmailMultiMailboxOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('services.gmail.enabled')) {
            $this->warn('Gmail integration disabled (GMAIL_ENABLED=false)');
            return Command::SUCCESS;
        }

        $this->info('Gmail multi-mailbox polling basladi...');

        if ($this->option('daemon')) {
            return $this->runDaemon();
        }

        return $this->runOnce();
    }

    private function runOnce(): int
    {
        try {
            $result = $this->orchestrator->pollAll();

            if ($result['total'] === 0) {
                $this->info('Yeni email yok.');
                return Command::SUCCESS;
            }

            $this->info("{$result['total']} yeni email islendi.");

            foreach ($result['by_mailbox'] as $mailbox => $count) {
                $this->line("  - [{$mailbox}] {$count} yeni email");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Polling hatasi: ' . $e->getMessage());
            Log::error('[GmailPollingCommand] Polling failed', [
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }

    private function runDaemon(): int
    {
        $this->info('Daemon modu baslatildi. Durdurmak icin Ctrl+C.');
        $interval = (int) config('services.gmail.poll_interval_minutes', 5) * 60;

        while (true) {
            try {
                $result = $this->orchestrator->pollAll();

                if ($result['total'] > 0) {
                    $this->info(date('H:i:s') . " — {$result['total']} yeni email.");
                }
            } catch (\Throwable $e) {
                $this->error(date('H:i:s') . ' — Hata: ' . $e->getMessage());
                Log::error('[GmailPollingCommand] Daemon polling error', [
                    'error' => $e->getMessage(),
                ]);
            }

            sleep($interval);
        }
    }
}
