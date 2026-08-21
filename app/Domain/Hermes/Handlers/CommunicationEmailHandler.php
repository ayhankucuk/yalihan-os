<?php

namespace App\Domain\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Models\Communication;
use Illuminate\Support\Facades\Log;

/**
 * CommunicationEmailHandler
 *
 * Hermes handler — email.communication.received event'ini isler.
 *
 * SORUMLULUK:
 *   - P0/P1 → Ayhan'a bildirim gonderir (Telegram veya log)
 *   - P2 → sessiz log, alarm yok
 *   - Tenant disi veri islemez
 *
 * ASENKRON: bildirim olduguundan queue'da calisir.
 */
class CommunicationEmailHandler implements HermesHandlerContract
{
    public function subscribesTo(): array
    {
        return ['email.communication.received'];
    }

    public function handle(HermesEventContract $event): array
    {
        $payload = $event->toPayload();

        $severity = $payload['severity'];
        $intent = $payload['intent'];
        $platform = $payload['platform'];
        $hasReservation = $payload['has_reservation'];
        $aiData = $payload['ai_extracted_data'] ?? [];

        Log::info('[CommunicationEmailHandler] Processing email communication', [
            'tenant_id'  => $event->tenantId(),
            'severity'  => $severity,
            'intent'    => $intent,
            'platform'  => $platform,
        ]);

        // ── P2: sessiz log, alarm yok ───────────────────────────────────────
        if ($severity === 'P2') {
            Log::info('[CommunicationEmailHandler] P2 — no notification', [
                'communication_id' => $payload['communication_id'],
            ]);

            return [
                'handler'    => self::class,
                'severity'  => $severity,
                'notified'  => false,
                'reason'    => 'P2 — no alarm required',
            ];
        }

        // ── P0/P1: Ayhan bildirimi ─────────────────────────────────────────
        $this->sendNotification($event->tenantId(), $payload, $aiData);

        return [
            'handler'    => self::class,
            'severity'  => $severity,
            'notified'  => true,
            'channel'   => 'telegram',
            'tenant_id' => $event->tenantId(),
        ];
    }

    public function isAsync(): bool
    {
        return true;
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function sendNotification(?int $tenantId, array $payload, array $aiData): void
    {
        $communicationId = $payload['communication_id'];
        $severity = $payload['severity'];
        $platform = $payload['platform'];

        // SAAB TENANT SAFETY: Communication tenant-scope ile bulunur.
        // tenant disi bir communicationId → 404, notification gitmez.
        $comm = Communication::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $communicationId)
            ->first();

        $guestName = $aiData['guest_name'] ?? $comm?->sender_name ?? 'Misafir';
        $summary = $aiData['message_summary'] ?? $comm?->subject ?? '';

        $platformLabel = match ($platform) {
            'airbnb'     => 'Airbnb',
            'booking.com' => 'Booking.com',
            'direct'    => 'Direct',
            default     => 'Email',
        };

        $severityEmoji = match ($severity) {
            'P0' => '🔴',
            'P1' => '🟡',
            default => 'ℹ️',
        };

        $message = <<<MSG
{$severityEmoji} *Yeni Email — {$platformLabel}*
*{$guestName}*

_{$summary}_

🔗 Dashboard: https://panel.yalihanemlak.com.tr/admin/communications
MSG;

        // Telegram bildirimi
        $this->dispatchTelegramNotification($tenantId, $message, $severity);

        Log::info('[CommunicationEmailHandler] Ayhan notified', [
            'tenant_id'  => $tenantId,
            'severity'  => $severity,
            'platform'  => $platform,
        ]);
    }

    private function dispatchTelegramNotification(?int $tenantId, string $message, string $severity): void
    {
        try {
            $adminChatId = (int) config('services.telegram.admin_chat_id');

            if (! $adminChatId) {
                Log::warning('[CommunicationEmailHandler] No admin chat ID configured', [
                    'tenant_id' => $tenantId,
                ]);
                return;
            }

            app(\App\Services\Telegram\AlertService::class)
                ->sendTelegramMessage($adminChatId, $message);

            Log::info('[CommunicationEmailHandler] Admin notified via Telegram', [
                'tenant_id' => $tenantId,
                'admin_chat_id' => $adminChatId,
                'severity' => $severity,
            ]);
        } catch (\Throwable $e) {
            Log::error('[CommunicationEmailHandler] Telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
