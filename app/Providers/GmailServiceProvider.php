<?php

namespace App\Providers;

use App\Services\Email\GmailOAuthService;
use App\Services\Email\GmailPollingService;
use Illuminate\Support\ServiceProvider;

/**
 * GmailServiceProvider
 *
 * Wave 2 — Gmail Communications Intelligence
 *
 * Gmail OAuth + Polling servislerini container'a kaydeder.
 *
 * SAAB Kural: Tum Gmail servisleri bu provider uzerinden resolve edilir.
 * Direkt "new GmailOAuthService()" YASAK.
 */
class GmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GmailOAuthService::class, function ($app) {
            $clientId = config('services.gmail.client_id');
            $clientEmail = config('services.gmail.client_email');
            $privateKey = config('services.gmail.private_key');

            // Try reading from credentials file if env vars are empty
            if (empty($clientId) && empty($clientEmail)) {
                $credentialsFile = config('services.gmail.credentials_file')
                    ?? storage_path('app/gmail-credentials.json');

                if (file_exists($credentialsFile)) {
                    $creds = json_decode(file_get_contents($credentialsFile), true);
                    $clientId = $creds['client_id'] ?? '';
                    $clientEmail = $creds['client_email'] ?? '';
                    $privateKey = $creds['private_key'] ?? '';
                }
            }

            return new GmailOAuthService(
                clientId: $clientId,
                clientEmail: $clientEmail,
                privateKey: $privateKey,
            );
        });

        $this->app->singleton(GmailPollingService::class, function ($app) {
            return new GmailPollingService(
                oauthService: $app->make(GmailOAuthService::class),
            );
        });
    }

    public function boot(): void
    {
        // Register command
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\GmailPollingCommand::class,
            ]);
        }
    }
}
