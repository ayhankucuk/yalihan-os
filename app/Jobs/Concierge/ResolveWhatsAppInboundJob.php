<?php

namespace App\Jobs\Concierge;

use App\Services\Concierge\GuestConciergeRouter;
use App\Services\Concierge\RoutingDecision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ResolveWhatsAppInboundJob — Tenant-agnostic inbound resolution.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Pipeline:
 *   WhatsAppWebhookController (thin)
 *     → ResolveWhatsAppInboundJob (tenant-agnostic)
 *       → GuestConciergeRouter.resolve()
 *         → ProcessGuestMessageJob (tenant-aware)
 *           → RestoreTenantContext middleware
 *             → Guest Concierge pipeline
 *
 * Responsibilities:
 * - Extract message from webhook payload
 * - Idempotency check (WhatsApp message ID)
 * - Route to GuestConciergeRouter
 * - Dispatch ProcessGuestMessageJob with routing context
 *
 * GC-D1: This job performs tenant-agnostic resolution.
 * GC-D11: Tenant resolution from guest/lead, NOT from phone alone.
 */
class ResolveWhatsAppInboundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;  // Don't retry — escalate on failure
    public int $timeout = 30;

    public function __construct(
        public readonly string $senderPhone,
        public readonly ?string $senderName,
        public readonly string $messageText,
        public readonly ?string $messageId,
        public readonly string $messageType,
    ) {
        $this->onQueue('concierge');
    }

    public function handle(GuestConciergeRouter $router): void
    {
        Log::info('[ResolveWhatsAppInboundJob] Processing inbound WhatsApp message', [
            'phone' => $this->senderPhone,
            'message_id' => $this->messageId,
        ]);

        // 1. Route the sender
        $decision = $router->resolve($this->senderPhone, $this->senderName);

        Log::info('[ResolveWhatsAppInboundJob] Routing decision', [
            'phone' => $this->senderPhone,
            'decision' => $decision->decision,
            'tenant_id' => $decision->tenantId,
            'reservation_id' => $decision->reservationId,
            'reason' => $decision->reason,
        ]);

        // 2. Dispatch tenant-aware processing job
        // Note: If tenantId is null (UNKNOWN), we still dispatch but ProcessGuestMessageJob
        // will handle the escalation path
        ProcessGuestMessageJob::dispatch(
            senderPhone: $this->senderPhone,
            senderName: $this->senderName,
            messageText: $this->messageText,
            messageId: $this->messageId,
            messageType: $this->messageType,
            routingDecision: $decision,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ResolveWhatsAppInboundJob] Failed to resolve inbound message', [
            'phone' => $this->senderPhone,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
