<?php

namespace App\Console\Commands\SabGuard;

use Illuminate\Console\Command;
use App\Services\Sab\SabAutomationGuardService;

class SabScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ SAB Architecture Scanner: Perform a quick check on core constraints (Context7, ThinController, CQRS, ServiceLayer, etc.).';

    protected SabAutomationGuardService $guardService;

    public function __construct(SabAutomationGuardService $guardService)
    {
        parent::__construct();
        $this->guardService = $guardService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🚀 Starting SAB Guard Automation Scanner...");

        $issues = $this->guardService->runAllGuards();

        if (empty($issues)) {
            $this->info("✨ Architecture is 100% SAB Compliant. No drift detected.");
            return 0;
        }

        $this->error("🚨 SAB Architecture Violations Detected!");

        $headers = ['File', 'Line', 'Guard', 'Severity', 'Message', 'Required Fix'];
        $rows = [];

        foreach ($issues as $issue) {
            $rows[] = [
                $issue['file'],
                $issue['line'],
                $issue['guard'],
                $this->formatSeverity($issue['severity']),
                $issue['message'],
                $issue['fix'] ?? 'Manual review required'
            ];
        }

        $this->table($headers, $rows);
        $this->warn("Total Violations: " . count($issues));

        return 0; // Scan just reports, doesn't halt (unlike guard)
    }

    private function formatSeverity(string $severity): string
    {
        return match ($severity) {
            'critical', 'error' => '<error>' . strtoupper($severity) . '</error>',
            'high' => '<fg=red>HIGH</fg=red>',
            'warning', 'medium' => '<comment>' . strtoupper($severity) . '</comment>',
            default => '<info>LOW</info>',
        };
    }
}
