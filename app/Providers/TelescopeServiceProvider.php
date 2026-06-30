<?php

namespace App\Providers;

use App\Telescope\FileTelescopeStorage;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * REPO-GOV-02B: Telescope Service Provider
 *
 * Configures file-based storage for runtime tracing
 * with zero schema impact.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // REPO-GOV-02B: Register file-based storage
        if (config('telescope.driver') === 'file') {
            $this->app->singleton(
                \Laravel\Telescope\Contracts\EntriesRepository::class,
                FileTelescopeStorage::class
            );
        }

        Telescope::filter(function (IncomingEntry $entry) {
            if (config('app.env') === 'local') {
                return true;
            }

            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                // Add authorized emails here
            ]);
        });
    }
}
