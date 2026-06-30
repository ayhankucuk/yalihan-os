<?php

namespace App\Console\Commands;

use App\Services\CalendarSyncService;
use Illuminate\Console\Command;

class CalendarSyncCommand extends Command
{
    protected $signature = 'calendar:sync {--platform=*}';

    protected $description = 'Takvim senkronizasyonlarını çalıştırır';

    public function handle(CalendarSyncService $service)
    {
        $platforms = $this->option('platform');
        $results = $service->syncAllCalendars();
        $this->info(json_encode($results));

        return self::SUCCESS;
    }
}
