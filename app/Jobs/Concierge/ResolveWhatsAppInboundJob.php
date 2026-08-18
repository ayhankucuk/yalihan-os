<?php

namespace App\Jobs\Concierge;

use App\Services\Concierge\GuestConciergeRouter;
use App\Services\Concierge\GuestConciergePilotGate;
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
 * MICRO PILOT READINESS — PILOT-GATE-01
 *
 * Pipeline:
 *   WhatsAppWebhookController (thin)
 *     → ResolveWhatsAppInboundJob (tenant-agnostic)
 *       → GuestConciergeRouter.resolve()
 *         → PILOT-GATE-01: allowlist check
 *           → ProcessGuestMessageJob (tenant-aware)
 *             → Guest Concierge pipeline
 *
 * Responsibilities:
 * - Route to GuestConciergeRouter
 * - PILOT-GATE-01: verify routing decision is in pilot allowlist
 * - Dispatch ProcessGuestMessageJob with routing context
 *
 * GC-D1: This job performs tenant-agnostic resolution.
 * GC-D11: Tenant resolution from guest/lead, NOT from phone alone.
 * PILOT-GATE-01: fail-closed allowlist enforcement.
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

    public function handle(
        GuestConciergeRouter $router,
        GuestConciergePilotGate $pilotGate,
    ): void {
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

        // 2. PILOT-GATE-01: Check allowlist
        // FAIL-CLOSED: enabled=true + empty allowlist = BLOCKED
        // PILOT-GATE-01 invariant: only allowlisted tenants/reservations enter Concierge pipeline
        if (!$pilotGate->isAllowed($decision)) {
            Log::warning('[ResolveWhatsAppInboundJob] PILOT-GATE-01: Not in pilot allowlist — blocked', [
                'phone' => $this->senderPhone,
                'decision' => $decision->decision,
                'tenant_id' => $decision->tenantId,
                'reservation_id' => $decision->reservationId,
                'gate_status' => $pilotGate->getStatus(),
            ]);
            // WhatsApp acknowledgment already sent (200 OK).
            // No further processing — message silently blocked from Concierge pipeline.
            return;
        }

        // 3. PILOT-GATE-01: Dispatch tenant-aware processing job
        Log::info('[ResolveWhatsAppInboundJob] PILOT-GATE-01: ALLOWED — dispatching to pipeline', [
            'phone' => $this->senderPhone,
            'tenant_id' => $decision->tenantId,
            'reservation_id' => $decision->reservationId,
        ]);

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
