# Add to app/Console/Kernel.php schedule() method

protected function schedule(Schedule $schedule)
{
    // === ASA AUTOMATED SYSTEM AUDITOR SCHEDULE ===
    
    // Daily quick maintenance (midnight UTC)
    $schedule->exec('bash yalihan-bekci/tools/asa-scheduled.sh')
        ->daily()
        ->at('00:00')
        ->appendOutputTo(storage_path('logs/asa-daily-maintenance.log'));
    
    // Dark Mode Auto-Fix (Sunday 23:00 UTC)
    $schedule->exec('bash scripts/auto-fix/dark-mode-variants.sh')
        ->weekly()
        ->sundays()
        ->at('23:00')
        ->appendOutputTo(storage_path('logs/dark-mode-autofix.log'));
    
    // Context7 Weekly Audit (Monday 02:00 UTC)
    $schedule->command('context7:weekly-audit')
        ->weekly()
        ->mondays()
        ->at('02:00')
        ->appendOutputTo(storage_path('logs/context7-weekly-audit.log'));
    
    // Cache refresh (hourly)
    $schedule->command('config:cache')
        ->hourly()
        ->appendOutputTo(storage_path('logs/cache-refresh.log'));
}
