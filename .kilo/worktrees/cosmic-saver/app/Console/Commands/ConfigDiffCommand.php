<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class ConfigDiffCommand extends Command
{
    protected $signature = 'config:diff';
    protected $description = 'Detect drifts between the current config and the sealed snapshot.json';

    public function handle(): int
    {
        if (!file_exists(base_path('config/snapshot.json'))) {
            $this->error('❌ Snapshot file not found. Run php artisan config:snapshot first.');
            return self::FAILURE;
        }

        $current = [
            'ai' => config('ai'),
            'database' => [
                'default' => config('database.default'),
            ],
        ];

        $snapshot = json_decode(
            file_get_contents(base_path('config/snapshot.json')),
            true
        );

        $drifts = [];
        foreach ($current as $key => $value) {
            if (!isset($snapshot[$key]) || $snapshot[$key] !== $value) {
                $drifts[] = $key;
            }
        }

        if (!empty($drifts)) {
            $this->error('🚨 CONFIG DRIFT DETECTED IN: ' . implode(', ', $drifts));
            foreach ($drifts as $key) {
                $this->warn("Key: [{$key}]");
                $this->line('Snapshot: ' . json_encode($snapshot[$key] ?? 'MISSING'));
                $this->line('Current:  ' . json_encode($current[$key] ?? 'MISSING'));
            }
            return self::FAILURE;
        }

        $this->info('✅ Config is stable. No drift detected against the baseline.');
        return self::SUCCESS;
    }
}
