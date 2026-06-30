<?php

namespace App\Console\Commands\Guard;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GuardGhostSchemaCommand extends Command
{
    protected $signature = 'guard:ghost-schema';

    protected $description = 'Alias command for schema drift and ghost field audit';

    public function handle(): int
    {
        $exitCode = Artisan::call('guard:schema');
        $this->output->write(Artisan::output());

        return $exitCode;
    }
}
