<?php

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Log;

/**
 * N1-B: Notification Dispatcher
 * Responsible for flow stabilization, auditing, and async enforcement.
 * @sab-ignore-catch
 */
class NotificationDispatcher
{
    public function __construct(
        protected NotificationRetryService $retryService
    ) {}

    /**
     * Dispatch the notification through the normalized flow.
     */
    public function dispatch(NotificationContract $notification): bool
    {
        try {
            // 1. Audit Log (Pre-send)
            $audit = $this->logOutbound($notification);

            // 2. Async Policy Enforcement
            // Forced async for standard channels (Email, WhatsApp, Webhook)
            if ($notification->isAsync()) {
                SendNotificationJob::dispatch($notification, $audit->id);
                return true;
            }

            // 3. Sync Send (Limited to internal/admin alerts or explicit sync)
            return $this->routeToAdapter($notification, $audit->id);

        } catch (\Throwable $e) {
            Log::error("[NotificationDispatcher] Dispatch failed: " . $e->getMessage(), [
                'recipient' => $notification->getRecipient(),
                'template' => $notification->getTemplateKey()
            ]);
            return false;
        }
    }

    /**
     * Create an audit record for delivery traceability.
     */
    protected function logOutbound(NotificationContract $notification): OutboundNotification
    {
        return OutboundNotification::create([
            'channel' => $notification->getChannel(),
            'recipient' => $notification->getRecipient(),
            'template_key' => $notification->getTemplateKey(),
            'payload_data' => $notification->getData(),
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
        ]);
    }

    /**
     * Route the notification to the correct channel adapter.
     */
    public function routeToAdapter(NotificationContract $notification, int $auditId): bool
    {
        try {
            // N1-B Flow Stabilization: Channel Routing
            $adapter = app()->make(match($notification->getChannel()) {
                'email'     => \App\Services\Notification\Adapters\EmailAdapter::class,
                'whatsapp'  => \App\Services\Notification\Adapters\WhatsAppAdapter::class,
                'telegram'  => \App\Services\Notification\Adapters\TelegramAdapter::class,
                'instagram' => \App\Services\Notification\Adapters\InstagramAdapter::class,
                'webhook'   => \App\Services\Notification\Adapters\WebhookAdapter::class,
                default    => null,
            });

            if (!$adapter) {
                Log::warning("[NotificationDispatcher] Unsupported channel or adapter missing: " . $notification->getChannel());
                $this->retryService->markAsFailed(OutboundNotification::find($auditId), "Unsupported channel: " . $notification->getChannel());
                return false;
            }

            $audit = OutboundNotification::find($auditId);
            $this->retryService->markAsProcessing($audit);

            $success = $adapter->send($notification, $auditId);

            if ($success) {
                $this->retryService->markAsSent($audit);
            }

            return $success;

        } catch (\Throwable $e) {
            $audit = OutboundNotification::find($auditId);
            if ($audit) {
                $this->retryService->markAsFailed($audit, $e->getMessage());
            }
            return false;
        }
    }
}
