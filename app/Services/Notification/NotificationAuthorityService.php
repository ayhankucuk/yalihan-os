<?php

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationAuthorityInterface;
use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\DTOs\Notification\GenericNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationAuthorityService implements NotificationAuthorityInterface
{
    protected ConfigurationRegistryInterface $configRegistry;

    public function __construct(
        protected NotificationDispatcher $dispatcher,
        ?ConfigurationRegistryInterface $configRegistry = null
    ) {
        $this->configRegistry = $configRegistry ?? app(ConfigurationRegistryInterface::class);
    }

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

        $resolver = app(TemplateResolver::class);

        foreach ($policy['channels'] as $channel) {
            try {
                // N3: Operational channel kill-switch — skip if channel is disabled via admin toggle
                if (! $this->isChannelEnabled($channel)) {
                    Log::info("NotificationAuthority: Skipping '{$channel}' for event '{$event}' — channel disabled via admin toggle.");

                    continue;
                }

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
                    'template_metadata' => $resolved['metadata'] ?? [],
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
                Log::error("NotificationAuthority: Failed to dispatch '{$event}' for channel '{$channel}': ".$e->getMessage());
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

    /**
     * Check if an operational channel is enabled.
     *
     * Missing setting defaults to true (backward-compatible).
     * Technical transports (webhook, instagram) always return true.
     * Unknown channels fail closed (return false).
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => (bool) $this->configRegistry->get('email_notifications', true),
            'whatsapp' => (bool) $this->configRegistry->get('whatsapp_notifications', true),
            'telegram' => (bool) $this->configRegistry->get('telegram_notifications', true),
            'webhook', 'instagram' => true,
            default => false,
        };
    }
}
