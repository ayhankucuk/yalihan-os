<?php

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationAuthorityInterface;
use App\DTOs\Notification\GenericNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationAuthorityService implements NotificationAuthorityInterface
{
    public function __construct(
        protected NotificationDispatcher $dispatcher
    ) {}

    /**
     * Map events to notification policies.
     */
    protected function getEventMap(): array
    {
        return [
            'booking_requested' => [
                'channels' => ['email', 'whatsapp'],
                'template' => 'booking_confirmation',
                'priority' => 'high',
                'async' => true,
            ],
            'vip_signal_received' => [
                'channels' => ['telegram', 'webhook'],
                'template' => 'vip_alert',
                'priority' => 'critical',
                'async' => true,
            ],
            'ai_alert' => [
                'channels' => ['email'],
                'template' => 'ai_system_alert',
                'priority' => 'medium',
                'async' => true,
            ],
            'system_log' => [
                'channels' => ['telegram'],
                'template' => 'system_log',
                'priority' => 'low',
                'async' => true,
            ],
            'ai_whatsapp_reply' => [
                'channels' => ['whatsapp'],
                'template' => 'ai_reply',
                'priority' => 'high',
                'async' => true,
            ],
            'ai_instagram_reply' => [
                'channels' => ['instagram'],
                'template' => 'ai_reply',
                'priority' => 'high',
                'async' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function notify(string $event, array $data = [], ?User $actor = null): void
    {
        $policy = $this->getPolicyFor($event);

        if (empty($policy)) {
            Log::warning("NotificationAuthority: No policy found for event '{$event}'");
            return;
        }

        $resolver = app(\App\Services\Notification\TemplateResolver::class);

        foreach ($policy['channels'] as $channel) {
            try {
                $recipients = $this->resolveRecipients($channel, $data, $actor);
                
                // N3: Resolve content from template system
                $resolved = $resolver->resolve(
                    $policy['template'],
                    $channel,
                    $data,
                    $data['language'] ?? 'tr'
                );

                // Merge resolved content back into data for dispatcher/adapters
                $mergedData = array_merge($data, [
                    'subject' => $resolved['subject'],
                    'body' => $resolved['body'],
                    'provider_template_id' => $resolved['provider_template_id'],
                    'template_metadata' => $resolved['metadata'] ?? []
                ]);

                foreach ($recipients as $recipient) {
                    $notification = GenericNotification::make(
                        $channel,
                        $recipient,
                        $policy['template'],
                        $mergedData
                    );

                    $this->dispatcher->dispatch($notification);
                }
            } catch (\Exception $e) {
                Log::error("NotificationAuthority: Failed to dispatch '{$event}' for channel '{$channel}': " . $e->getMessage());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPolicyFor(string $event): array
    {
        return $this->getEventMap()[$event] ?? [];
    }

    /**
     * Resolve recipients based on channel and data.
     */
    protected function resolveRecipients(string $channel, array $data, ?User $actor = null): array
    {
        $recipients = [];

        if ($channel === 'email') {
            if (isset($data['recipients']) && is_array($data['recipients'])) {
                $recipients = array_values($data['recipients']);
            } elseif (isset($data['email'])) {
                $recipients[] = $data['email'];
            } elseif ($actor && $actor->email) {
                $recipients[] = $actor->email;
            } else {
                $recipients[] = config('mail.from.address');
            }
        } elseif ($channel === 'whatsapp') {
            $recipients[] = $data['phone'] ?? $data['whatsapp_id'] ?? '';
        } elseif ($channel === 'telegram') {
            $recipients[] = $data['chat_id'] ?? config('services.telegram.team_channel_id');
        } elseif ($channel === 'instagram') {
            $recipients[] = $data['instagram_id'] ?? '';
        } elseif ($channel === 'webhook') {
            $recipients[] = $data['webhook_url'] ?? config('services.webhooks.default_endpoint');
        }

        return array_unique(array_filter($recipients));
    }
}
