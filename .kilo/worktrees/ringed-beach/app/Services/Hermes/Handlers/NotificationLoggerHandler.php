<?php

namespace App\Services\Hermes\Handlers;

use App\Services\Hermes\Contracts\HandlerInterface;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Example notification handler - logs notifications to database.
 * This demonstrates a working handler pattern.
 */
class NotificationLoggerHandler implements HandlerInterface
{
    public function __construct(
        private readonly LogService $logService
    ) {}

    /**
     * Event names this handler subscribes to
     */
    public static function handles(): array
    {
        return [
            'ilan.created',
            'ilan.updated',
            'ilan.deleted',
            'lead.assigned',
            'lead.created',
            'lead.updated',
        ];
    }

    /**
     * Handle event - log notification
     */
    public function handle(string $eventName, array $payload): void
    {
        $tenantId = $payload['tenant_id'] ?? null;

        Log::info('Hermes: Notification logged', [
            'event' => $eventName,
            'tenant_id' => $tenantId,
            'payload_keys' => array_keys($payload),
        ]);

        // Example: Could store in notification_logs table
        // NotificationLog::create([...]);
    }

    /**
     * Handler is enabled
     */
    public function isEnabled(): bool
    {
        return config('hermes.handlers.notification_logger.enabled', true);
    }
}
