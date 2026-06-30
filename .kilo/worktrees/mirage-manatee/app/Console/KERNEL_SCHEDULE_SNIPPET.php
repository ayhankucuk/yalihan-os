// app/Console/Kernel.php schedule section

protected function schedule(Schedule $schedule)
{
    // Context7 Weekly Audit (Every Monday 02:00 UTC)
    $schedule->command('context7:weekly-audit')
        ->weekly()
        ->mondays()
        ->at('02:00')
        ->appendOutputTo(storage_path('logs/context7-weekly-audit.log'));

    // Dark Mode Auto-Fix (Every Sunday 23:00 UTC - before audit)
    $schedule->exec('bash scripts/auto-fix/dark-mode-variants.sh')
        ->weekly()
        ->sundays()
        ->at('23:00')
        ->appendOutputTo(storage_path('logs/dark-mode-autofix.log'));

    // Cache Context7 metadata (Hourly)
    $schedule->command('config:cache')
        ->hourly()
        ->appendOutputTo(storage_path('logs/cache-refresh.log'));
}
