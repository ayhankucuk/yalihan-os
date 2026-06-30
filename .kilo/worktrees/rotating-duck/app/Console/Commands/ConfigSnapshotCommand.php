<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\Guards\ConfigGuard;
use Throwable;

class ConfigSnapshotCommand extends Command
{
    protected $signature = 'config:snapshot';
    protected $description = 'Create a snapshot of the current SSOT configuration for drift detection';

    public function handle(): int
    {
        $config = [
            'ai' => config('ai'),
            'database' => [
                'default' => config('database.default'),
            ],
        ];

        file_put_contents(
            base_path('config/snapshot.json'),
            json_encode($config, JSON_PRETTY_PRINT)
        );

        $this->info('✅ Config snapshot created and sealed in config/snapshot.json');
        return self::SUCCESS;
    }
}
