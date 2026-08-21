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
 * Auth Yontemi: OAuth 2.0 Authorization Code Flow
 *   - Ayhan bir kez /auth/google ziyaret eder
 *   - Google consent ekrani gorur, onaylar
 *   - refresh_token encrypt edilip oauth_tokens tablosuna kaydedilir
 *   - access_token 1 saatte bir yenilenir
 *   - Hiçbir secret Git'e yazilmaz
 */
class GmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── OAuth 2.0 (PRIMARY — kullandigimiz yontem) ──────────────────
        $this->app->singleton(GmailApiOAuthService::class, function ($app) {
            $cfg = config('services.gmail.oauth', []);

            $clientId     = $cfg['client_id']     ?? '';
            $clientSecret = $cfg['client_secret'] ?? '';
            $redirectUri  = $cfg['redirect_uri']  ?? '';

            return new GmailApiOAuthService(
                clientId:    $clientId,
                clientSecret: $clientSecret,
                redirectUri: $redirectUri,
            );
        });

        // ── Workspace / Service Account (gelecek faz — signJwt ile) ─────────
        $this->app->singleton(GmailWorkspaceMailboxService::class, function ($app) {
            $cfg = config('services.gmail.workspace', []);

            $clientId      = $cfg['client_id']      ?? '';
            $clientEmail   = $cfg['client_email']   ?? '';
            $privateKey    = $cfg['private_key']    ?? '';
            $delegatedUser = $cfg['delegated_user']  ?? '';
            $mailboxLabel  = 'workspace';

            if (empty($clientId) && ! empty($cfg['credentials_file'])) {
                $creds = $this->loadCredentialsFile($cfg['credentials_file']);
                if ($creds) {
                    $clientId    = $creds['client_id']    ?? '';
                    $clientEmail = $creds['client_email'] ?? '';
                    $privateKey  = $creds['private_key']  ?? '';
                }
            }

            return new GmailWorkspaceMailboxService(
                clientId:      $clientId,
                clientEmail:   $clientEmail,
                privateKey:   $privateKey,
                delegatedUser: $delegatedUser,
                mailboxLabel:  $mailboxLabel,
            );
        });

        // ── Multi-mailbox Orchestrator ─────────────────────────────────────
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
