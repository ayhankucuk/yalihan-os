<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GovernanceSafeguardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // SAB v3 DLP Safeguard: Block destructive commands in production
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\CommandStarting::class, function (\Illuminate\Console\Events\CommandStarting $event) {
            $destructiveCommands = ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'];

            if (in_array($event->command, $destructiveCommands) && app()->environment('production')) {
                \Illuminate\Support\Facades\Log::critical("SAB v3 DLP: Blocked destructive command in production: " . $event->command);
                throw new \RuntimeException("SAB v3 DLP: Destructive database commands ({$event->command}) are strictly forbidden in production!");
            }
        });
    }
}
