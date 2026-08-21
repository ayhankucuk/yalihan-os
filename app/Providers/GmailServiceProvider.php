<?php

namespace App\Providers;

use App\Services\Email\GmailMultiMailboxOrchestrator;
use App\Services\Email\GmailOAuthService;
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
 * Container singletons:
 *   GmailWorkspaceMailboxService  — PRIMARY: @yalihanemlak.com.tr (DWD/Service Account)
 *   GmailOAuthService           — SECONDARY: yalihanemlak@gmail.com (User OAuth)
 *   GmailMultiMailboxOrchestrator — Tum mailbox'lari yonetir
 *   GmailPollingCommand          — Orchestrator kullanir
 */
class GmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── PRIMARY: Workspace / Service Account ─────────────────────────
        $this->app->singleton(GmailWorkspaceMailboxService::class, function ($app) {
            $cfg = config('services.gmail.workspace', []);

            $clientId     = $cfg['client_id']     ?? '';
            $clientEmail  = $cfg['client_email']  ?? '';
            $privateKey   = $cfg['private_key']   ?? '';
            $delegatedUser = $cfg['delegated_user'] ?? 'ayhan@yalihanemlak.com.tr';
            $mailboxLabel = 'workspace';

            // Credentials file overwrite
            if (empty($clientId) && ! empty($cfg['credentials_file'])) {
                $creds = $this->loadCredentialsFile($cfg['credentials_file']);
                if ($creds) {
                    $clientId    = $creds['client_id']    ?? '';
                    $clientEmail = $creds['client_email'] ?? '';
                    $privateKey  = $creds['private_key']  ?? '';
                }
            }

            return new GmailWorkspaceMailboxService(
                clientId:     $clientId,
                clientEmail:  $clientEmail,
                privateKey:   $privateKey,
                delegatedUser: $delegatedUser,
                mailboxLabel: $mailboxLabel,
                tenantId:     null,
            );
        });

        // ── SECONDARY: Personal Gmail / User OAuth ────────────────────
        $this->app->singleton(GmailOAuthService::class, function ($app) {
            $cfg = config('services.gmail.personal', []);

            $clientId    = $cfg['client_id'] ?? '';
            $clientEmail = $cfg['client_email'] ?? '';
            $privateKey  = $cfg['private_key'] ?? '';

            if (empty($clientId) && ! empty($cfg['credentials_file'])) {
                $creds = $this->loadCredentialsFile($cfg['credentials_file']);
                if ($creds) {
                    $clientId    = $creds['client_id']    ?? '';
                    $clientEmail = $creds['client_email'] ?? '';
                    $privateKey  = $creds['private_key']  ?? '';
                }
            }

            if (empty($clientId)) {
                // SECONDARY disabled — return null placeholder
                return new GmailOAuthService('', '', '');
            }

            return new GmailOAuthService(
                clientId:    $clientId,
                clientEmail: $clientEmail,
                privateKey:  $privateKey,
            );
        });

        // ── Multi-mailbox Orchestrator ─────────────────────────────────
        $this->app->singleton(GmailMultiMailboxOrchestrator::class, function ($app) {
            $primary   = $app->make(GmailWorkspaceMailboxService::class);
            $secondary = $app->make(GmailOAuthService::class);

            return new GmailMultiMailboxOrchestrator(
                primaryMailbox:   $primary->isEnabled() ? $primary : null,
                secondaryMailbox: $secondary->isEnabled() ? $secondary : null,
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
