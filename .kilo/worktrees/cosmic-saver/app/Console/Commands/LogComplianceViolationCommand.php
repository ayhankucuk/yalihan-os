<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Console\Command;

class LogComplianceViolationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:log-violation
                           {type : Violation type}
                           {description : Violation description}
                           {--file= : File path where violation occurred}
                           {--line= : Line number where violation occurred}
                           {--context= : Additional context as JSON}
                           {--auto-fixed=false : Whether violation was auto-fixed}
                           {--fix-description= : Description of the fix applied}
                           {--severity=warning : Severity level}
                           {--source=git_hook : Source of the violation detection}';

    /**
     * The console command description.
     */
    protected $description = 'Log a Context7 compliance violation for analytics tracking';

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $type = $this->argument('type');
            $description = $this->argument('description');
            $filePath = $this->option('file');
            $lineNumber = $this->option('line') ? (int) $this->option('line') : null;
            $context = $this->option('context') ? json_decode($this->option('context'), true) : [];
            $autoFixed = filter_var($this->option('auto-fixed'), FILTER_VALIDATE_BOOLEAN);
            $fixDescription = $this->option('fix-description');
            $severity = $this->option('severity');
            $source = $this->option('source');

            $violation = $this->analyticsService->logViolation(
                $type,
                $description,
                $filePath,
                $lineNumber,
                $context,
                $autoFixed,
                $fixDescription,
                $severity,
                $source
            );

            $icon = $autoFixed ? '🔧' : '⚠️';
            $actionText = $autoFixed ? 'auto-fixed' : 'logged';

            $this->info("{$icon} Context7 violation {$actionText}: {$type} - {$description}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to log violation: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
