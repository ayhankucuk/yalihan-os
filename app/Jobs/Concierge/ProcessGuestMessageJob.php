<?php

namespace App\Jobs\Concierge;

use App\Models\GuestMessage;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Services\Concierge\AuthorityResult;
use App\Services\Concierge\GuestConciergeAuthorityPolicy;
use App\Services\Concierge\GuestConciergeHermes;
use App\Services\Concierge\IntentClassification;
use App\Services\Concierge\PropertyFactSheet;
use App\Services\Concierge\RoutingDecision;
use App\Services\Concierge\WhatsAppOutboundService;
use App\Services\Reservation\OperationalGorevService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RestoreTenantContext;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessGuestMessageJob — Tenant-aware guest message processing.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Pipeline:
 *   ResolveWhatsAppInboundJob
 *     → ProcessGuestMessageJob (tenant-aware, RestoreTenantContext)
 *       → GuestMessage::create() (append-only audit)
 *         → Hermes.classifyIntent()
 *           → AuthorityPolicy.canAnswer() / canCreateGorev()
 *             → Answer / Action / Escalate
 *               → WhatsAppOutboundService::send()
 *
 * Tenant Safety:
 * - TenantContextService::setTenant() called via RestoreTenantContext middleware
 * - All DB queries MUST use tenant-scoped queries
 * - tenantId comes from RoutingDecision (not from phone number)
 *
 * GC-D11: Tenant-aware, tenant context restored from job payload
 */
class ProcessGuestMessageJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [60, 120];
    public int $timeout = 60;

    public ?int $tenantId;
    public ?int $userId;

    public function __construct(
        public readonly string $senderPhone,
        public readonly ?string $senderName,
        public readonly string $messageText,
        public readonly ?string $messageId,
        public readonly string $messageType,
        public readonly RoutingDecision $routingDecision,
    ) {
        $this->tenantId = $routingDecision->tenantId;
        $this->userId = null;
        $this->onQueue('concierge');
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function middleware(): array
    {
        return [
            new RestoreTenantContext(
                app(TenantContextService::class)
            ),
        ];
    }

    public function handle(
        GuestConciergeHermes $hermes,
        GuestConciergeAuthorityPolicy $policy,
        WhatsAppOutboundService $outboundService,
        OperationalGorevService $gorevService,
    ): void {
        // ── 0. Idempotency Check ──────────────────────────────────────
        if ($this->messageId !== null) {
            $alreadyProcessed = GuestMessage::query()
                ->where('external_message_id', $this->messageId)
                ->exists();

            if ($alreadyProcessed) {
                Log::info('[ProcessGuestMessageJob] Message already processed, skipping', [
                    'message_id' => $this->messageId,
                ]);
                return;
            }
        }

        // ── 1. Load Property Facts ────────────────────────────────────
        $factSheet = $this->buildFactSheet();
        if ($factSheet === null) {
            // No ilan context — still process but with empty facts
            $factSheet = PropertyFactSheet::empty();
        }

        // ── 2. Classify Intent ────────────────────────────────────────
        $classification = $hermes->classifyIntent($this->messageText, $factSheet);

        Log::info('[ProcessGuestMessageJob] Intent classified', [
            'intent' => $classification->intent,
            'confidence' => $classification->confidence,
            'routing_decision' => $this->routingDecision->decision,
        ]);

        // ── 3. Authority Check ────────────────────────────────────────
        // First check if this intent can be answered
        $canAnswerResult = $policy->canAnswer($classification, $factSheet);

        if ($canAnswerResult->isDenied()) {
            // If answer denied, check if it should create an ACTION (Gorev)
            $canActionResult = $policy->canCreateGorev($classification);

            if ($canActionResult->isAllowed()) {
                $this->handleAction($classification, $outboundService, $gorevService);
                return;
            }

            // Neither answer nor action allowed — escalate
            Log::info('[ProcessGuestMessageJob] Both answer and action denied, escalating', [
                'answer_reason' => $canAnswerResult->denialReason,
                'action_reason' => $canActionResult->denialReason,
                'intent' => $classification->intent,
            ]);
            $this->escalate($classification, $policy, $outboundService, $factSheet);
            return;
        }

        // ── 4. Build Answer ─────────────────────────────────────────
        $answerText = $hermes->draftAnswer($this->messageText, $factSheet, $classification);

        // ── 5. Create Audit Record ───────────────────────────────────
        $guestMessage = $this->createAuditRecord(
            intent: $classification->intent,
            confidence: $classification->confidence,
            requiredFactKeys: $classification->requiredFactKeys,
            responseMode: 'ANSWER',
            responseText: $answerText,
            escalated: false,
        );

        // ── 6. Send WhatsApp Response ───────────────────────────────
        $outboundService->send(
            to: $this->senderPhone,
            message: $answerText,
        );

        Log::info('[ProcessGuestMessageJob] Answer sent', [
            'intent' => $classification->intent,
            'message_id' => $this->messageId,
            'guest_message_id' => $guestMessage->id,
        ]);
    }

    /**
     * Handle ACTION mode: create Gorev.
     *
     * Called only after GuestConciergeAuthorityPolicy::canCreateGorev() confirmed authority.
     */
    protected function handleAction(
        IntentClassification $classification,
        WhatsAppOutboundService $outboundService,
        OperationalGorevService $gorevService,
    ): void {
        // Load reservation and ilan from routing context
        $reservation = $this->loadReservation();
        $ilan = $this->loadIlan();

        if ($reservation === null || $ilan === null) {
            Log::warning('[ProcessGuestMessageJob] Cannot create Gorev — missing context', [
                'reservation_id' => $this->routingDecision->reservationId,
                'ilan_id' => $this->routingDecision->ilanId,
                'tenant_id' => $this->tenantId,
            ]);
            $this->escalate($classification, new GuestConciergeAuthorityPolicy(), $outboundService, PropertyFactSheet::empty());
            return;
        }

        // Create Gorev
        $gorev = $gorevService->createOperationalTask(
            reservation: $reservation,
            ilan: $ilan,
            taskType: $this->mapIntentToGorevType($classification->intent),
            title: $this->buildGorevTitle($classification->intent, $ilan),
            description: $this->buildGorevDescription($classification->intent, $this->messageText, $reservation, $ilan),
            priority: 'normal',
            deadlineOffset: 0,
            creatorUserId: 0,  // System user
        );

        // Draft confirmation
        $confirmation = $gorevService->createTaskConfirmation(
            gorev: $gorev,
            guestName: $this->routingDecision->guestName ?? 'Misafir',
        );

        // Create audit record
        $guestMessage = $this->createAuditRecord(
            intent: $classification->intent,
            confidence: $classification->confidence,
            requiredFactKeys: $classification->requiredFactKeys,
            responseMode: 'ACTION',
            responseText: $confirmation,
            escalated: false,
            gorevId: $gorev?->id,
        );

        // Send confirmation
        $outboundService->send(to: $this->senderPhone, message: $confirmation);

        // Return so main handler doesn't escalate
        return;
    }

    /**
     * Handle ACTION failure: convert existing ACTION record to ESCALATE.
     *
     * When handleAction() creates an ACTION record but Gorev creation fails,
     * we convert that record to ESCALATE and send the escalation message.
     * This prevents duplicate audit records (the bug this fixes).
     *
     * @param GuestMessage $existingMessage The ACTION record to convert
     */
    protected function convertActionToEscalate(
        GuestMessage $existingMessage,
        IntentClassification $classification,
        GuestConciergeAuthorityPolicy $policy,
        WhatsAppOutboundService $outboundService,
        PropertyFactSheet $factSheet,
    ): void {
        $escalationMessage = $policy->getEscalationMessage(
            $classification->intent,
            $policy->getEscalationReason($classification, $factSheet)
        );

        // Convert existing ACTION record to ESCALATE (no duplicate record)
        $existingMessage->update([
            'response_mode' => 'ESCALATE',
            'response_text' => $escalationMessage,
            'escalated' => true,
            'escalation_reason' => $policy->getEscalationReason($classification, $factSheet),
            'gorev_id' => null,  // Gorev wasn't created
        ]);

        $outboundService->send(to: $this->senderPhone, message: $escalationMessage);

        Log::warning('[ProcessGuestMessageJob] Action record converted to escalation', [
            'guest_message_id' => $existingMessage->id,
            'original_response_mode' => 'ACTION',
        ]);
    }

    /**
     * Handle ESCALATE mode.
     */
    protected function escalate(
        IntentClassification $classification,
        GuestConciergeAuthorityPolicy $policy,
        WhatsAppOutboundService $outboundService,
        PropertyFactSheet $factSheet,
    ): void {
        $escalationMessage = $policy->getEscalationMessage(
            $classification->intent,
            $policy->getEscalationReason($classification, $factSheet)
        );

        // Create audit record
        $guestMessage = $this->createAuditRecord(
            intent: $classification->intent,
            confidence: $classification->confidence,
            requiredFactKeys: $classification->requiredFactKeys,
            responseMode: 'ESCALATE',
            responseText: $escalationMessage,
            escalated: true,
            escalationReason: $policy->getEscalationReason($classification, $factSheet),
        );

        // Send acknowledgment to guest
        $outboundService->send(to: $this->senderPhone, message: $escalationMessage);

        // TODO: Notify Ayhan (future P2 — Telegram notification)
        Log::warning('[ProcessGuestMessageJob] Message escalated to human', [
            'intent' => $classification->intent,
            'reason' => $policy->getEscalationReason($classification, $factSheet),
            'phone' => $this->senderPhone,
            'guest_message_id' => $guestMessage->id,
        ]);
    }

    /**
     * Build PropertyFactSheet for the message context.
     */
    protected function buildFactSheet(): ?PropertyFactSheet
    {
        if ($this->routingDecision->ilanId === null) {
            return null;
        }

        $ilan = Ilan::query()
            ->where('id', $this->routingDecision->ilanId)
            ->when($this->tenantId, fn($q) => $q->where('tenant_id', $this->tenantId))
            ->first();

        if ($ilan === null) {
            return null;
        }

        // TODO: Load WiFi credentials from AccessCredentialService in P2
        // For P1, WiFi credentials will be empty
        $wifiCredentials = [];

        return PropertyFactSheet::build(
            ilan: $ilan,
            reservationId: $this->routingDecision->reservationId,
            wifiCredentials: $wifiCredentials,
        );
    }

    protected function loadReservation(): ?PropertyReservation
    {
        if ($this->routingDecision->reservationId === null) {
            return null;
        }

        // CountryScope is bypassed by GuestConciergeRouter (withoutGlobalScopes).
        // BelongsToTenant TenantScope: bypass — tenantId comes from RoutingDecision
        // payload (not from TenantContextService), so we use explicit WHERE.
        return PropertyReservation::query()
            ->withoutGlobalScopes()
            ->where('id', $this->routingDecision->reservationId)
            ->when($this->tenantId, fn($q) => $q->where('tenant_id', $this->tenantId))
            ->first();
    }

    protected function loadIlan(): ?Ilan
    {
        if ($this->routingDecision->ilanId === null) {
            return null;
        }

        // BelongsToTenant TenantScope bypassed — tenantId comes from RoutingDecision
        // payload (not from TenantContextService auth context).
        return Ilan::query()
            ->withoutGlobalScopes()
            ->where('id', $this->routingDecision->ilanId)
            ->when($this->tenantId, fn($q) => $q->where('tenant_id', $this->tenantId))
            ->first();
    }

    protected function createAuditRecord(
        string $intent,
        float $confidence,
        array $requiredFactKeys,
        string $responseMode,
        ?string $responseText,
        bool $escalated,
        ?int $gorevId = null,
        ?string $escalationReason = null,
    ): GuestMessage {
        return GuestMessage::create([
            'tenant_id' => $this->tenantId,
            'ilan_id' => $this->routingDecision->ilanId,
            'channel' => 'whatsapp',
            'sender_phone' => $this->senderPhone,
            'sender_name' => $this->senderName,
            'external_message_id' => $this->messageId,
            'message_text' => $this->messageText,
            'message_type' => $this->messageType,
            'routing_decision' => $this->routingDecision->decision,
            'reservation_id' => $this->routingDecision->reservationId,
            'intent' => $intent,
            'confidence' => $confidence,
            'required_fact_keys' => $requiredFactKeys,
            'response_mode' => $responseMode,
            'response_text' => $responseText,
            'gorev_id' => $gorevId,
            'escalated' => $escalated,
            'escalation_reason' => $escalationReason,
        ]);
    }

    protected function mapIntentToGorevType(string $intent): string
    {
        return match ($intent) {
            'TECHNICAL_ISSUE' => 'teknik_destek',
            'CLEANING_REQUEST' => 'temizlik',
            default => 'diger',
        };
    }

    protected function buildGorevTitle(string $intent, Ilan $ilan): string
    {
        $ilanAdi = $ilan->baslik ?? $ilan->title ?? "Mülk #{$ilan->id}";
        $guestName = $this->routingDecision->guestName ?? 'Misafir';

        return match ($intent) {
            'TECHNICAL_ISSUE' => "Teknik Destek: {$ilanAdi} — {$guestName}",
            'CLEANING_REQUEST' => "Temizlik Talebi: {$ilanAdi} — {$guestName}",
            default => "Concierge Talebi: {$ilanAdi}",
        };
    }

    protected function buildGorevDescription(
        string $intent,
        string $messageText,
        PropertyReservation $reservation,
        Ilan $ilan,
    ): string {
        $ilanAdi = $ilan->baslik ?? $ilan->title ?? "Mülk #{$ilan->id}";
        $guestName = $this->routingDecision->guestName ?? 'Misafir';
        $description = "## Concierge Otomatik Görev\n\n";
        $description .= "**Niyet:** {$intent}\n";
        $description .= "**Mülk:** {$ilanAdi}\n";
        $description .= "**Misafir:** {$guestName}\n";
        $description .= "**Telefon:** {$this->senderPhone}\n\n";
        $description .= "**Misafir Mesajı:**\n{$messageText}\n";

        return $description;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessGuestMessageJob] Job failed', [
            'phone' => $this->senderPhone,
            'message_id' => $this->messageId,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}
