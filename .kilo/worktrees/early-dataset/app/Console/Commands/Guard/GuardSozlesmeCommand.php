<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuardSozlesmeCommand extends Command
{
    protected $signature = 'guard:sozlesme
        {--strict : Fail when optional contract artifacts are missing}';

    protected $description = 'Phase 2 system contract scanner for Event/DTO/View/Endpoint artifacts';

    public function handle(): int
    {
        $this->line('🔍 guard:sozlesme starting...');

        $required = [
            app_path('Events'),
            app_path('DTO'),
            app_path('Http/Controllers'),
            resource_path('views'),
            base_path('routes/web.php'),
            base_path('routes/api.php'),
        ];

        $missing = [];
        foreach ($required as $path) {
            if (! File::exists($path)) {
                $missing[] = $path;
            }
        }

        if (! empty($missing)) {
            foreach ($missing as $path) {
                $this->warn('Missing contract artifact: '.$path);
            }

            if ((bool) $this->option('strict')) {
                $this->error('guard:sozlesme FAILED (strict mode)');
                return self::FAILURE;
            }
        }

        $this->info('guard:sozlesme PASSED');

        return self::SUCCESS;
    }
}
