<?php

namespace App\Domain\Workforce\Publishing;

use App\Models\Ilan;
use App\Models\User;

/**
 * PublishingAudit — Sprint 7.4
 *
 * Immutable audit record for publishing operations.
 */
final class PublishingAudit
{
    public function __construct(
        public readonly string $packageId,
        public readonly Ilan $ilan,
        public readonly int $userId,
        public readonly string $action, // created|dispatched|channel_success|channel_failed|retry|completed|rolled_back
        public readonly array $channelResults,
        public readonly ?string $error = null,
        public readonly ?array $metadata = [],
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function toArray(): array
    {
        return [
            'package_id' => $this->packageId,
            'ilan_id' => $this->ilan->getKey(),
            'user_id' => $this->userId,
            'action' => $this->action,
            'channel_results' => $this->channelResults,
            'error' => $this->error,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}

/**
 * NotificationHook — Sprint 7.4
 *
 * Yayınlama sonuclarini bildirir.
 * Su an: log tabanli. E-posta/SMS/Telegram sonra eklenebilir.
 */
class NotificationHook
{
    /**
     * Bildirim gonder.
     *
     * @param array<ChannelExecution> $executions
     */
    public function notify(
        Ilan $ilan,
        int $userId,
        array $executions,
        ?string $packageId = null,
    ): void {
        $succeeded = count(array_filter($executions, fn($e) => $e->isSuccess()));
        $failed = count(array_filter($executions, fn($e) => $e->isFailed()));
        $total = count($executions);

        $message = $this->buildMessage($ilan, $succeeded, $failed, $total);

        // Log kanala bildirim gonder
        \Illuminate\Support\Facades\Log::channel('workforce')->info('[PublishingNotification] ' . $message, [
            'ilan_id' => $ilan->getKey(),
            'ilan_baslik' => $ilan->baslik,
            'package_id' => $packageId,
            'user_id' => $userId,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'total' => $total,
            'channels' => array_map(fn($e) => $e->channel->value, $executions),
        ]);
    }

    private function buildMessage(Ilan $ilan, int $succeeded, int $failed, int $total): string
    {
        if ($succeeded === $total && $total > 0) {
            return "İlan #{$ilan->getKey()} ({$ilan->baslik}) {$total} kanala basariyla yayinlandi.";
        }
        if ($failed === $total && $total > 0) {
            return "İlan #{$ilan->getKey()} ({$ilan->baslik}) {$total} kanala yayinlanamadi. Lutfen kontrol edin.";
        }
        if ($succeeded > 0 && $failed > 0) {
            return "İlan #{$ilan->getKey()} ({$ilan->baslik}) {$succeeded}/{$total} kanala yayinlandi. {$failed} kanal basarisiz oldu.";
        }
        return "İlan #{$ilan->getKey()} yayinlama tamamlandi.";
    }
}
