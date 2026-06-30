<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GuardSecurityLeakCommand extends Command
{
    protected $signature = 'guard:security-leak {--url=http://localhost:8002 : Base URL of the running application}';

    protected $description = 'Alias command for public API security leak audit';

    public function handle(): int
    {
        $exitCode = Artisan::call('guard:security', [
            '--url' => (string) $this->option('url'),
        ]);

        $this->output->write(Artisan::output());

        return $exitCode;
    }
}
