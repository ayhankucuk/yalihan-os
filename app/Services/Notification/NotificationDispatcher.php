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
     * Pilot Safety Gate — kill switch + allowlist kontrolü.
     * true = gönderim yapılabilir
     * false = gönderim engellenir (audit kaydı açık kalır)
     */
    public function canDispatch(?int $tenantId = null, ?int $propertyId = null): bool
    {
        // 1. Kill Switch — acil durdurma
        if (config('feature-flags.notification_kill_switch', false)) {
            Log::channel('security')->warning('[NotificationDispatcher] Kill switch ACTIVE — dispatch blocked', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'timestamp' => now()->toIso8601String(),
            ]);
            return false;
        }

        // 2. Global flag kapalıysa — pilot modda gönderim yok
        if (!config('feature-flags.whatsapp_pilot_global', false)) {
            return false;
        }

        // 3. Allowlist kontrolü — pilot tenant/property ikilisi mi?
        $allowlist = config('feature-flags.pilot_notification_allowlist', [
            'tenant_ids' => [],
            'property_ids' => [],
        ]);

        $allowedTenants = $allowlist['tenant_ids'] ?? [];
        $allowedProperties = $allowlist['property_ids'] ?? [];

        // Boş allowlist = güvenlik kilidi (devrede hiçbir şey açık değil)
        if (empty($allowedTenants) && empty($allowedProperties)) {
            Log::channel('security')->info('[NotificationDispatcher] Allowlist empty — pilot not configured, blocking.');
            return false;
        }

        // Tenant allowlist kontrolü
        if (!empty($allowedTenants) && $tenantId !== null && !in_array($tenantId, $allowedTenants, true)) {
            Log::channel('security')->info('[NotificationDispatcher] Tenant not in allowlist', [
                'tenant_id' => $tenantId,
                'allowed' => $allowedTenants,
            ]);
            return false;
        }

        // Property allowlist kontrolü
        if (!empty($allowedProperties) && $propertyId !== null && !in_array($propertyId, $allowedProperties, true)) {
            Log::channel('security')->info('[NotificationDispatcher] Property not in allowlist', [
                'property_id' => $propertyId,
                'allowed' => $allowedProperties,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Dispatch the notification through the normalized flow.
     */
    public function dispatch(NotificationContract $notification, ?int $tenantId = null, ?int $propertyId = null): bool
    {
        // Pilot safety gate
        if (!$this->canDispatch($tenantId, $propertyId)) {
            // Audit kaydı yine oluşsun — blocked state ile
            $audit = $this->logOutbound($notification, OutboundNotification::STATE_CANCELLED);
            Log::channel('security')->info('[NotificationDispatcher] Notification blocked by pilot gate', [
                'audit_id' => $audit->id,
                'channel' => $notification->getChannel(),
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
            ]);
            return false;
        }

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
    protected function logOutbound(NotificationContract $notification, string $state = OutboundNotification::STATE_PENDING): OutboundNotification
    {
        return OutboundNotification::create([
            'channel' => $notification->getChannel(),
            'recipient' => $notification->getRecipient(),
            'template_key' => $notification->getTemplateKey(),
            'payload_data' => $notification->getData(),
            'gonderim_durumu' => $state,
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
