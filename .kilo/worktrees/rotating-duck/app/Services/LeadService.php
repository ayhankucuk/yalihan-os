<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadMessage;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * Lead Service - CRM Lead Management
 *
 * Handles creation, updates, and tracking of leads from social media
 * Integrates with NLP parsing results to create structured database records
 *
 * Usage:
 * $leadService = app(LeadService::class);
 * $lead = $leadService->createOrUpdateFromWebhook('instagram', 'user_123', 'Bodrum da 3+1 daire', $nlpResult);
 */
class LeadService
{
    use GuardsAgentWrites;
    public function __construct(
        protected \App\Services\CRM\LeadAuthorityService $leadAuthority
    ) {}
    /**
     * Create or update lead from webhook message
     *
     * This is the main integration point between:
     * - Webhooks (WhatsApp, Instagram, Facebook)
     * - NLP Processing (Intent, Entity extraction)
     * - Database storage (Lead + Message history)
     *
     * @param string $platform Platform name (whatsapp, instagram, facebook, telegram)
     * @param string $platformUserId Platform-specific user ID
     * @param string $messageText Original message from user
     * @param array $nlpResult Parsed NLP result from NLPProcessor
     * @param array $platformData Optional platform-specific data (phone, username, etc)
     * @return Lead Created or updated lead model
     */
    public function createOrUpdateFromWebhook(
        string $platform,
        string $platformUserId,
        string $messageText,
        array $nlpResult,
        array $platformData = []
    ): Lead {
        $this->blockAgentWrite(__FUNCTION__);

        try {
            // DELEGATION: Route to centralized LeadAuthorityService
            $lead = $this->leadAuthority->registerLeadFromExternalSource(
                $platform,
                $platformUserId,
                $messageText,
                $nlpResult,
                $platformData
            );

            // Additional legacy structure: Store the raw message text specifically in lead_messages
            // (LeadAuthority handles basic activity, but LeadService manages granular message history)
            $lead->messages()->create([
                'message_text' => $messageText,
                'message_type' => 'incoming',
                'intent' => $nlpResult['intent'] ?? null,
                'confidence' => $nlpResult['confidence'] ?? 0.0,
                'entities' => json_encode($nlpResult['entities'] ?? []),
                'sentiment' => $nlpResult['sentiment'] ?? 'neutral',
                'sent_at' => now(),
            ]);

            return $lead;

        } catch (\Exception $e) {
            Log::error('Error in LeadService: createOrUpdateFromWebhook', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);
            throw $e;
        }
    }

    /**
     * Add message to existing lead
     *
     * Used when a customer sends multiple messages to same lead
     *
     * @param Lead $lead
     * @param string $messageText
     * @param array $nlpResult NLP parsing result
     * @return LeadMessage
     */
    public function addMessageToLead(Lead $lead, string $messageText, array $nlpResult): LeadMessage
    {
        $message = $lead->messages()->create([
            'message_text' => $messageText,
            'message_type' => 'incoming',
            'intent' => $nlpResult['intent'] ?? null,
            'confidence' => $nlpResult['confidence'] ?? 0.0,
            'entities' => json_encode($nlpResult['entities'] ?? []),
            'sentiment' => $nlpResult['sentiment'] ?? 'neutral',
            'sent_at' => now(),
        ]);

        // DELEGATION: Sync intelligence via Authority
        $this->leadAuthority->syncIntelligenceFromNlp($lead, $nlpResult);

        return $message;
    }

    /**
     * Send reply to lead via platform
     *
     * Stores the reply in lead_messages with message_type='outgoing'
     * Calls appropriate platform controller to send via WhatsApp/Instagram/Facebook
     *
     * @param Lead $lead
     * @param string $replyText
     * @param string $platform Must match lead platform
     * @return bool Process success
     */
    public function sendReplyToLead(Lead $lead, string $replyText, string $platform = null): bool
    {
        try {
            $platform = $platform ?? $lead->platform;

            // 1. Store reply in message history
            $lead->messages()->create([
                'message_text' => $replyText,
                'message_type' => 'outgoing',
                'sent_at' => now(),
            ]);

            // 2. Log activity
            $lead->activities()->create([
                'activity_type' => 'reply_sent',
                'description' => "Auto-reply sent via {$platform}: " . mb_substr($replyText, 0, 100, 'UTF-8'),
                'activity_date' => now(),
            ]);

            // 3. Send via appropriate platform
            match($platform) {
                'whatsapp' => $this->sendViaWhatsApp($lead, $replyText),
                'instagram' => $this->sendViaInstagram($lead, $replyText),
                'facebook' => $this->sendViaFacebook($lead, $replyText),
                'telegram' => $this->sendViaTelegram($lead, $replyText),
                default => throw new \Exception("Unknown platform: {$platform}"),
            };

            return true;

        } catch (\Exception $e) {
            Log::error('Error sending reply to lead', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id,
                'platform' => $platform,
            ]);
            return false;
        }
    }

    /**
     * Update lead CRM process status
     *
     * P0-A FIX: string $status parametresi Lead::CRM_STRING_MAP üzerinden int'e çevrilir.
     * Setter FAIL-FAST yapar; geçersiz string exception fırlatır.
     *
     * @param Lead $lead
     * @param int|string $status Lead::CRM_* sabiti veya 'new'|'contacted'|'qualified'|'lost'|'won'
     * @param string|null $note Optional note about status change
     * @return Lead
     */
    public function updateLeadStatus(Lead $lead, int|string $status, string $note = null): Lead
    {
        $this->blockAgentWrite(__FUNCTION__);
        $this->leadAuthority->updateStatus($lead, $status);

        if ($note) {
            $lead->update(['notes' => $lead->notes . "\n\n" . $note]);
        }

        return $lead;
    }

    /**
     * Assign lead to sales agent
     *
     * @param Lead $lead
     * @param int $agentId User ID of sales agent
     * @return Lead
     */
    public function assignToAgent(Lead $lead, int $agentId): Lead
    {
        // DELEGATION: Update via Authority
        $this->leadAuthority->assignLeadToAgent($lead, $agentId);

        return $lead;
    }

    /**
     * Mark lead as qualified (high intent + sufficient info)
     *
     * @param Lead $lead
     * @return bool
     */
    public function qualifyLead(Lead $lead): bool
    {
        // DELEGATION: Evaluate and qualify via Authority
        return $this->leadAuthority->evaluateAndQualify($lead);
    }

    /**
     * Get lead summary for display
     *
     * @param Lead $lead
     * @return array
     */
    public function getLeadSummary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name ?? 'Unknown',
            'platform' => $lead->platform_badge,
            'crm_status' => $lead->crm_durumu,
            'intent' => $lead->intent,
            'confidence' => $lead->confidence_percent . '%',
            'location' => $lead->interested_location_id ? $lead->interested_location_id . ' (ID)' : 'Not specified',
            'property_type' => $lead->interested_property_type ?? 'Not specified',
            'budget' => $this->formatBudget($lead->budget_min, $lead->budget_max),
            'messages' => $lead->messages()->count(),
            'created' => $lead->created_at->format('d.m.Y H:i'),
            'last_contact' => $lead->last_contacted_at?->format('d.m.Y H:i') ?? 'Never',
        ];
    }

    // ========== PRIVATE HELPERS ==========

    /**
     * Extract customer name from NLP or platform data
     */
    private function extractName(array $nlpResult, array $platformData): ?string
    {
        // Try platform data first (from profile)
        if (!empty($platformData['name'])) {
            return $platformData['name'];
        }

        // Try from NLP entities
        if (!empty($nlpResult['entities']['person_name'])) {
            return $nlpResult['entities']['person_name'];
        }

        // Try from platform username
        if (!empty($platformData['username'])) {
            return $platformData['username'];
        }

        return null;
    }

    /**
     * Format budget range for display
     */
    private function formatBudget(?int $min, ?int $max): string
    {
        if (!$min && !$max) {
            return 'Not specified';
        }

        $minStr = $min ? $this->formatCurrency($min) : '?';
        $maxStr = $max ? $this->formatCurrency($max) : '?';

        return "{$minStr} - {$maxStr}";
    }

    /**
     * Format currency for display
     */
    private function formatCurrency(int $value): string
    {
        if ($value >= 1000000) {
            return number_format($value / 1000000, 1) . 'M TL';
        } elseif ($value >= 1000) {
            return number_format($value / 1000, 0) . 'K TL';
        }
        return number_format($value, 0) . ' TL';
    }

    /**
     * Send reply via WhatsApp
     */
    private function sendViaWhatsApp(Lead $lead, string $message): void
    {
        // Use WhatsAppWebhookController::sendMessage
        \App\Http\Controllers\Api\WhatsAppWebhookController::sendMessage(
            $lead->platform_phone,
            $message
        );
    }

    /**
     * Send reply via Instagram
     */
    private function sendViaInstagram(Lead $lead, string $message): void
    {
        \App\Http\Controllers\Api\InstagramWebhookController::sendMessage(
            $lead->platform_user_id,
            $message
        );
    }

    /**
     * Send reply via Facebook
     */
    private function sendViaFacebook(Lead $lead, string $message): void
    {
        \App\Http\Controllers\Api\FacebookWebhookController::sendMessage(
            $lead->platform_user_id,
            $message
        );
    }

    /**
     * Send reply via Telegram
     * @deprecated Phase 12: Telegram notifications are now handled asynchronously via listeners
     */
    private function sendViaTelegram(Lead $lead, string $message): void
    {
        // Nullified - handled via Event + Listener (NotificationService decoupling)
    }
}
