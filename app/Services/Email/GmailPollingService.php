<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GmailPollingService
 *
 * Wave 2 — Gmail Communications Intelligence
 *
 * Gmail hesabini poll eder, yeni mesajlari tespit eder ve
 * webhook endpoint'ine POST eder.
 *
 * İş akışı:
 *   1. OAuth token al (service account JWT)
 *   2. history.list ile değişiklikleri tespit et
 *   3. Yeni mesajların metadata + body al
 *   4. /api/v1/webhook/email/inbound endpoint'ine POST et
 *
 * SAAB Kural: Token alinamazsa sessiz fail — webhook'a gidemezse log + retry.
 */
class GmailPollingService
{
    public function __construct(
        private readonly ?GmailOAuthService $oauthService = null,
    ) {}

    /**
     * Tek polling döngüsü — yeni mesajları al ve webhook'a ilet.
     *
     * @return array{new_messages: int, processed: array}
     */
    public function poll(): array
    {
        $oauth = $this->getOAuthService();
        if ($oauth === null) {
            Log::warning('[GmailPollingService] OAuth service unavailable — skipping poll');
            return ['new_messages' => 0, 'processed' => []];
        }

        $token = $oauth->getAccessToken();
        if ($token === null) {
            Log::warning('[GmailPollingService] Failed to get OAuth access token');
            return ['new_messages' => 0, 'processed' => []];
        }

        // ── history.list — son değişiklikleri al ──────────────────────
        $lastHistoryId = config('services.gmail.history_id', '');
        $historyResult = $lastHistoryId
            ? $oauth->getHistory($lastHistoryId, $token)
            : $this->getInitialHistory($oauth, $token);

        if ($historyResult === null) {
            Log::warning('[GmailPollingService] Failed to get history');
            return ['new_messages' => 0, 'processed' => []];
        }

        $newHistoryId = $historyResult['historyId'] ?? null;
        $historyItems = $historyResult['history'] ?? [];

        if (empty($historyItems)) {
            return ['new_messages' => 0, 'processed' => []];
        }

        // ── Yeni mesaj ID'lerini topla ────────────────────────────────
        $messageIds = [];
        foreach ($historyItems as $item) {
            foreach ($item['messagesAdded'] ?? [] as $msgAdded) {
                $messageIds[$msgAdded['message']['id']] = true;
            }
        }
        $messageIds = array_keys($messageIds);

        if (empty($messageIds)) {
            return ['new_messages' => 0, 'processed' => []];
        }

        Log::info('[GmailPollingService] Found new messages', [
            'count'      => count($messageIds),
            'history_id' => $newHistoryId,
        ]);

        // ── Her mesajı webhook'a ilet ─────────────────────────────────
        $processed = [];
        $baseUrl = config('app.url');

        foreach ($messageIds as $messageId) {
            $result = $this->dispatchMessage($oauth, $messageId, $token, $baseUrl);
            $processed[] = $result;

            // Rate limit — 1 saniye bekle
            usleep(1_000_000);
        }

        // ── Son history ID'yi kaydet ─────────────────────────────────
        if ($newHistoryId) {
            config(['services.gmail.history_id' => $newHistoryId]);
            $this->saveHistoryId($newHistoryId);
        }

        return [
            'new_messages' => count($messageIds),
            'processed'   => $processed,
        ];
    }

    /**
     * Tek bir Gmail mesajini webhook'a ilet.
     *
     * @return array{from: string, subject: string, status: string, hermes_log_id: int|null}
     */
    private function dispatchMessage(
        GmailOAuthService $oauth,
        string $messageId,
        string $token,
        string $baseUrl,
    ): array {
        // ── Metadata al ───────────────────────────────────────────────
        $meta = $oauth->getMessageMetadata($messageId, $token);
        if ($meta === null) {
            Log::warning('[GmailPollingService] Could not fetch metadata', ['message_id' => $messageId]);
            return [
                'from'    => 'unknown',
                'subject' => 'unknown',
                'status'  => 'metadata_failed',
                'hermes_log_id' => null,
            ];
        }

        $from = $meta['from'] ?? 'unknown';
        $subject = $meta['subject'] ?? '(no subject)';
        $gmailMsgId = $meta['message_id_header'] ?? $meta['message_id'] ?? $messageId;

        // ── Body al ───────────────────────────────────────────────────
        $body = $oauth->getMessageBody($messageId, $token) ?? '';

        // ── Webhook endpoint'ine POST ─────────────────────────────────
        try {
            $response = Http::timeout(15)
                ->post($baseUrl . '/api/v1/webhook/email/inbound', [
                    'message_id'  => $gmailMsgId,
                    'from'       => $from,
                    'subject'     => $subject,
                    'body_text'   => $body,
                    'headers'     => [
                        'Message-ID' => $gmailMsgId,
                        'From'       => $from,
                        'Subject'     => $subject,
                    ],
                ]);

            $statusCode = $response->status();
            $body2 = $response->json();

            if ($statusCode === 200) {
                Log::info('[GmailPollingService] Email dispatched', [
                    'message_id' => $messageId,
                    'severity'  => $body2['severity'] ?? 'unknown',
                    'hermes_id'  => $body2['hermes_log_id'] ?? null,
                ]);

                return [
                    'from'          => $from,
                    'subject'       => $subject,
                    'status'        => 'dispatched',
                    'hermes_log_id' => $body2['hermes_log_id'] ?? null,
                ];
            }

            Log::warning('[GmailPollingService] Webhook returned non-200', [
                'message_id' => $messageId,
                'status'     => $statusCode,
            ]);

            return [
                'from'    => $from,
                'subject' => $subject,
                'status'  => "webhook_error_{$statusCode}",
                'hermes_log_id' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[GmailPollingService] Webhook dispatch failed', [
                'message_id' => $messageId,
                'error'      => $e->getMessage(),
            ]);

            return [
                'from'    => $from,
                'subject' => $subject,
                'status'  => 'dispatch_failed',
                'hermes_log_id' => null,
            ];
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function getOAuthService(): ?GmailOAuthService
    {
        // If no service injected, build from config
        if ($this->oauthService !== null) {
            return $this->oauthService;
        }

        $clientId = config('services.gmail.client_id');
        $clientEmail = config('services.gmail.client_email');
        $privateKey = config('services.gmail.private_key');

        if (empty($clientId) || empty($clientEmail) || empty($privateKey)) {
            return null;
        }

        return new GmailOAuthService($clientId, $clientEmail, $privateKey);
    }

    /**
     * İlk çalıştırma: history ID olmadığında en son mesajlari al.
     */
    private function getInitialHistory(GmailOAuthService $oauth, string $token): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
                    'maxResults' => 20,
                    'q'          => 'is:unread', // Sadece okunmamışlar
                ]);

            if (! $response->successful()) {
                return null;
            }

            $messages = $response->json('messages', []);
            $historyId = $response->json('historyId');

            // Convert message list to history format
            $history = [];
            foreach ($messages as $msg) {
                $history[] = [
                    'messagesAdded' => [['message' => ['id' => $msg['id']]]],
                ];
            }

            return [
                'history'    => $history,
                'historyId'  => $historyId,
            ];
        } catch (\Throwable $e) {
            Log::error('[GmailPollingService] getInitialHistory failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function saveHistoryId(string $historyId): void
    {
        // Environment'e yazmak yerine settings/DB'ye yazılabilir.
        // Basitlik için .env güncellemesi yapıyoruz (deployment ortamında önerilmez).
        // Production için: SettingsAuthorityService kullan.
        try {
            $envFile = base_path('.env');
            $envContent = file_get_contents($envFile);

            if (preg_match('/^GMAIL_HISTORY_ID=/m', $envContent)) {
                $envContent = preg_replace(
                    '/^GMAIL_HISTORY_ID=.*/m',
                    "GMAIL_HISTORY_ID={$historyId}",
                    $envContent
                );
            } else {
                $envContent .= "\nGMAIL_HISTORY_ID={$historyId}\n";
            }

            file_put_contents($envFile, $envContent);
            config(['services.gmail.history_id' => $historyId]);
        } catch (\Throwable $e) {
            Log::warning('[GmailPollingService] Could not save history_id to .env', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
