<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GmailMultiMailboxOrchestrator
 *
 * Wave 2 Phase 2 — Multi-mailbox Gmail integration
 *
 * Tum Gmail mailbox'larini poll eder ve her birinden gelen
 * mesajlari /api/v1/webhook/email/inbound endpoint'ine iletir.
 *
 * Mimari:
 *   PRIMARY:   @yalihanemlak.com.tr → GmailWorkspaceMailboxService (DWD/Service Account)
 *   SECONDARY: yalihanemlak@gmail.com → GmailOAuthService (User OAuth)
 *
 * Tenant isolation: Her tenant'in kendi mailbox'leri vardir.
 * Idempotency: external_message_id + mailbox benzersiz — iki mailbox'ta
 *   ayni Message-ID olamaz (Gmail global unique).
 */
class GmailMultiMailboxOrchestrator
{
    /** @var array<string, GmailWorkspaceMailboxService|GmailOAuthService> */
    private array $mailboxServices = [];

    public function __construct(
        private readonly ?GmailWorkspaceMailboxService $primaryMailbox = null,
        private readonly ?GmailApiOAuthService $secondaryMailbox = null,
    ) {}

    /**
     * Tum etkin mailbox'lerden yeni mesajlari al ve webhook'a ilet.
     *
     * @return array{total: int, by_mailbox: array<string, int>}
     */
    public function pollAll(): array
    {
        $results = [];
        $total = 0;

        // ── PRIMARY: Workspace / Service Account ──────────────────────
        if ($this->primaryMailbox !== null && $this->primaryMailbox->isEnabled()) {
            $result = $this->pollMailbox(
                service: $this->primaryMailbox,
                mailboxLabel: 'workspace',
                webhookPayloadFn: fn($meta, $body) => [
                    'source_mailbox' => 'yalihanemlak.com.tr',
                    'source_type'   => 'workspace',
                    'gmail_labels'   => $meta['label_ids'] ?? [],
                ],
            );
            $results['workspace'] = $result;
            $total += $result;
        }

        // ── SECONDARY: Personal Gmail / User OAuth ─────────────────
        if ($this->secondaryMailbox !== null && $this->secondaryMailbox->isEnabled()) {
            $result = $this->pollMailbox(
                service: $this->secondaryMailbox,
                mailboxLabel: 'gmail_com',
                webhookPayloadFn: fn($meta, $body) => [
                    'source_mailbox' => 'gmail.com/yalihanemlak',
                    'source_type'   => 'personal',
                ],
            );
            $results['personal'] = $result;
            $total += $result;
        }

        Log::info('[GmailMultiMailboxOrchestrator] Poll complete', [
            'total'      => $total,
            'by_mailbox'  => $results,
        ]);

        return ['total' => $total, 'by_mailbox' => $results];
    }

    /**
     * Tek bir mailbox'u poll et.
     *
     * @param callable $webhookPayloadFn Adds mailbox-specific fields to webhook payload
     * @return int Number of messages processed
     */
    private function pollMailbox(
        GmailWorkspaceMailboxService|GmailApiOAuthService $service,
        string $mailboxLabel,
        callable $webhookPayloadFn,
    ): int {
        $lastHistoryId = $service instanceof GmailWorkspaceMailboxService
            ? $service->getLastHistoryId()
            : null;

        $messages = method_exists($service, 'fetchNewMessages')
            ? $service->fetchNewMessages($lastHistoryId)
            : $this->fetchViaHistoryList($service, $lastHistoryId);

        if (empty($messages)) {
            return 0;
        }

        Log::info("[GmailMultiMailbox] Found messages", [
            'mailbox' => $mailboxLabel,
            'count'   => count($messages),
        ]);

        $processed = 0;

        foreach ($messages as $meta) {
            $messageId = $meta['message_id'] ?? $meta['id'] ?? null;
            if (! $messageId) {
                continue;
            }

            $body = method_exists($service, 'fetchMessageBody')
                ? $service->fetchMessageBody($messageId)
                : ($meta['body_text'] ?? null);

            $payload = array_merge([
                'message_id'   => $meta['message_id_header'] ?? $meta['message_id'] ?? $messageId,
                'from'        => $meta['from'] ?? null,
                'subject'    => $meta['subject'] ?? '(no subject)',
                'body_text'  => $body ?? $meta['snippet'] ?? '',
                'headers'    => [
                    'Message-ID' => $meta['message_id_header'] ?? $meta['message_id'] ?? $messageId,
                    'From'      => $meta['from'] ?? null,
                    'Subject'   => $meta['subject'] ?? null,
                ],
            ], $webhookPayloadFn($meta, $body));

            $dispatched = $this->dispatchToWebhook($payload, $mailboxLabel);

            if ($dispatched) {
                $processed++;
            }

            // Rate limit: 1 saniye bekle
            usleep(1_000_000);
        }

        return $processed;
    }

    /**
     * OAuth (GmailApiOAuthService) icin history.list cagir.
     *
     * @return list<array>
     */
    private function fetchViaHistoryList(GmailApiOAuthService $oauth, ?string $historyId): array
    {
        if ($historyId === null) {
            return [];
        }
        $token = $oauth->getAccessToken();
        if ($token === null) {
            return [];
        }
        return $oauth->fetchHistory($historyId, $token);
    }

    /**
     * Webhook endpoint'ine POST gonder.
     *
     * Idempotency server-side saglanir — ayni messageId iki kez gelirse
     * endpoint 200 + skipped:true dondurur.
     *
     * @return bool True = basarili, False = basarisiz
     */
    private function dispatchToWebhook(array $payload, string $mailboxLabel): bool
    {
        $baseUrl = config('app.url');
        $tenantId = (int) config('services.gmail.oauth.default_tenant_id', 5);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Mailbox-Source' => $mailboxLabel,
                    'X-Tenant-Id' => (string) $tenantId,
                ])
                ->post($baseUrl . '/api/v1/webhook/email/inbound', $payload);

            $status = $response->status();
            $body = $response->json();

            Log::info('[GmailMultiMailbox] Webhook response', [
                'mailbox'      => $mailboxLabel,
                'status'       => $status,
                'skipped'      => $body['skipped'] ?? false,
                'severity'    => $body['severity'] ?? null,
                'hermes_log_id' => $body['hermes_log_id'] ?? null,
            ]);

            return $status === 200;
        } catch (\Throwable $e) {
            Log::error('[GmailMultiMailbox] Webhook dispatch failed', [
                'mailbox' => $mailboxLabel,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
