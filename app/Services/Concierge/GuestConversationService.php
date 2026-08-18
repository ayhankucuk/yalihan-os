<?php

namespace App\Services\Concierge;

use App\Models\GuestMessage;
use Illuminate\Support\Facades\Log;

/**
 * GuestConversationService — Orchestrates the guest message processing pipeline.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * This service is the main entry point for processing guest messages.
 * It orchestrates the pipeline: route → classify → authority → answer/action/escalate.
 *
 * Note: The actual processing is done in ProcessGuestMessageJob.
 * This service exists for cases where immediate processing is needed
 * (e.g., in tests or direct API calls).
 */
class GuestConversationService
{
    public function __construct(
        private readonly GuestConciergeRouter $router,
        private readonly GuestConciergeHermes $hermes,
        private readonly GuestConciergeAuthorityPolicy $policy,
        private readonly WhatsAppOutboundService $outboundService,
    ) {}

    /**
     * Process an inbound guest message immediately (synchronous).
     *
     * For production use, prefer the queued job pipeline:
     *   WhatsAppWebhookController → ResolveWhatsAppInboundJob → ProcessGuestMessageJob
     */
    public function processMessage(
        string $phone,
        string $messageText,
        ?string $messageId = null,
        ?string $senderName = null,
    ): ?GuestMessage {
        // 1. Route
        $decision = $this->router->resolve($phone, $senderName);

        // 2. Create audit record
        $guestMessage = GuestMessage::create([
            'tenant_id' => $decision->tenantId,
            'channel' => 'whatsapp',
            'sender_phone' => $phone,
            'sender_name' => $senderName,
            'external_message_id' => $messageId,
            'message_text' => $messageText,
            'message_type' => 'text',
            'routing_decision' => $decision->decision,
            'reservation_id' => $decision->reservationId,
        ]);

        // 3. If no tenant context, escalate
        if (!$decision->hasTenant()) {
            return $this->escalate($guestMessage, 'NO_TENANT', 'Tenant not resolved');
        }

        // 4. Load facts
        $factSheet = $this->loadFactSheet($decision);
        if ($factSheet === null) {
            $factSheet = PropertyFactSheet::empty();
        }

        // 5. Classify intent
        $classification = $this->hermes->classifyIntent($messageText, $factSheet);
        $guestMessage->update([
            'intent' => $classification->intent,
            'confidence' => $classification->confidence,
            'required_fact_keys' => $classification->requiredFactKeys,
        ]);

        // 6. Authority check
        if ($this->policy->mustEscalate($classification)) {
            return $this->escalate(
                $guestMessage,
                $classification->intent,
                $this->policy->getEscalationReason($classification, $factSheet)
            );
        }

        // 7. Answer or Action
        if (in_array($classification->intent, ['WIFI_INFO', 'CHECK_IN_TIME', 'CHECK_OUT_TIME', 'PARKING_INFO', 'HOUSE_RULES'])) {
            return $this->answer($guestMessage, $classification, $factSheet);
        }

        if (in_array($classification->intent, ['TECHNICAL_ISSUE', 'CLEANING_REQUEST'])) {
            return $this->action($guestMessage, $classification);
        }

        // Fallback: escalate
        return $this->escalate($guestMessage, $classification->intent, 'Fallback escalation');
    }

    protected function loadFactSheet(RoutingDecision $decision): ?PropertyFactSheet
    {
        if ($decision->ilanId === null) {
            return null;
        }

        $ilan = \App\Models\Ilan::query()
            ->where('id', $decision->ilanId)
            ->when($decision->tenantId, fn($q) => $q->where('tenant_id', $decision->tenantId))
            ->first();

        if ($ilan === null) {
            return null;
        }

        return PropertyFactSheet::build($ilan, $decision->reservationId);
    }

    protected function answer(
        GuestMessage $guestMessage,
        IntentClassification $classification,
        PropertyFactSheet $factSheet,
    ): GuestMessage {
        $answer = $this->hermes->draftAnswer(
            $guestMessage->message_text,
            $factSheet,
            $classification
        );

        $guestMessage->update([
            'response_mode' => GuestMessage::MODE_ANSWER,
            'response_text' => $answer,
        ]);

        $this->outboundService->send($guestMessage->sender_phone, $answer);

        return $guestMessage;
    }

    protected function action(GuestMessage $guestMessage, IntentClassification $classification): GuestMessage
    {
        $guestMessage->update([
            'response_mode' => GuestMessage::MODE_ACTION,
            'response_text' => 'Talebiniz alındı ve ilgili ekibimize iletildi.',
        ]);

        $this->outboundService->send(
            $guestMessage->sender_phone,
            'Talebiniz alındı ve ekibimize iletildi. En kısa sürede işlem yapılacaktır.'
        );

        return $guestMessage;
    }

    protected function escalate(GuestMessage $guestMessage, string $intent, string $reason): GuestMessage
    {
        $escalationMessage = $this->policy->getEscalationMessage($intent, $reason);

        $guestMessage->update([
            'response_mode' => GuestMessage::MODE_ESCALATE,
            'response_text' => $escalationMessage,
            'escalated' => true,
            'escalation_reason' => $reason,
        ]);

        $this->outboundService->send($guestMessage->sender_phone, $escalationMessage);

        Log::warning('[GuestConversationService] Message escalated', [
            'guest_message_id' => $guestMessage->id,
            'intent' => $intent,
            'reason' => $reason,
        ]);

        return $guestMessage;
    }
}
