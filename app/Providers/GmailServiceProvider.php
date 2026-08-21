<?php

namespace App\Providers;

use App\Services\Email\GmailApiOAuthService;
use App\Services\Email\GmailMultiMailboxOrchestrator;
use App\Services\Email\GmailWorkspaceMailboxService;
use Illuminate\Support\ServiceProvider;

/**
 * GmailServiceProvider
 *
 * Wave 2 Phase 2 — Multi-mailbox Gmail Integration
 *
 * SAAB Kural: Tum Gmail servisleri bu provider uzerinden resolve edilir.
 * Direkt "new GmailXxx()" YASAK.
 *
 * Auth yontemi: OAuth 2.0 Authorization Code Flow (keyless)
 */
class GmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── OAuth 2.0 (PRIMARY) ─────────────────────────────────────
        // Config lazy-loading — primitive resolve on request time
        $this->app->singleton(GmailApiOAuthService::class, function () {
            $cfg = config('services.gmail.oauth', []);

            return new GmailApiOAuthService(
                clientId:    $cfg['client_id'] ?? '',
                clientSecret: $cfg['client_secret'] ?? '',
                redirectUri: $cfg['redirect_uri'] ?? '',
            );
        });

        // ── Workspace / Service Account (gelecek faz — signJwt) ─────
        $this->app->singleton(GmailWorkspaceMailboxService::class, function () {
            $cfg = config('services.gmail.workspace', []);

            $clientId     = $cfg['client_id'] ?? '';
            $clientEmail = $cfg['client_email'] ?? '';
            $privateKey  = $cfg['private_key'] ?? '';
            $delegatedUser = $cfg['delegated_user'] ?? 'ayhan@yalihanemlak.com.tr';

            if (empty($clientId) && ! empty($cfg['credentials_file'])) {
                $creds = $this->loadCredentialsFile($cfg['credentials_file']);
                if ($creds) {
                    $clientId    = $creds['client_id'] ?? '';
                    $clientEmail = $creds['client_email'] ?? '';
                    $privateKey = $creds['private_key'] ?? '';
                }
            }

            return new GmailWorkspaceMailboxService(
                clientId:     $clientId,
                clientEmail:  $clientEmail,
                privateKey:   $privateKey,
                delegatedUser: $delegatedUser,
                mailboxLabel: 'workspace',
            );
        });

        // ── Multi-mailbox Orchestrator ─────────────────────────────────
        $this->app->singleton(GmailMultiMailboxOrchestrator::class, function ($app) {
            $oauthService = $app->make(GmailApiOAuthService::class);
            $workspaceService = $app->make(GmailWorkspaceMailboxService::class);

            return new GmailMultiMailboxOrchestrator(
                primaryMailbox:   $oauthService->isEnabled() ? $oauthService : null,
                secondaryMailbox: $workspaceService->isEnabled() ? $workspaceService : null,
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\GmailPollingCommand::class,
            ]);
        }
    }

    private function loadCredentialsFile(string $path): ?array
    {
        $fullPath = file_exists($path) ? $path : storage_path("app/{$path}");

        if (! file_exists($fullPath)) {
            return null;
        }

        $data = json_decode(file_get_contents($fullPath), true);

        return is_array($data) ? $data : null;
    }
}
