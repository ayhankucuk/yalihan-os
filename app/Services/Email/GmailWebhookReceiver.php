<?php

namespace App\Services\Email;

use App\Contracts\Hermes\HermesEventContract;
use App\Domain\Hermes\Events\EmailCommunicationReceivedEvent;
use App\Models\Communication;
use App\Models\SaaS\Tenant;
use App\Services\AI\EmailExtractionResult;
use App\Services\AI\EmailIntelligenceService;
use App\Services\Communication\ReservationResolver;
use Illuminate\Support\Facades\Log;

/**
 * GmailWebhookReceiver
 *
 * Inbound email webhook payload parser + Hermes pipeline orchestrator.
 *
 * Verilen bir webhook payload'ini sirasiyla isler:
 *   1. Parse: GCP Pub/Sub / Zapier / raw JSON → normalized email data
 *   2. AI Extraction: EmailIntelligenceService → intent, sentiment, platform
 *   3. Severity: CommunicationSeverityPolicy → P0|P1|P2 (deterministic PHP)
 *   4. Persistence: Communication::create()
 *   5. Hermes: email.communication.received event'i
 *
 * Tenant guard + idempotency controller'da saglanir (EmailWebhookController).
 */
class GmailWebhookReceiver
{
    public function __construct(
        private readonly EmailIntelligenceService $llmService,
        private readonly ReservationResolver $reservationResolver,
    ) {}

    /**
     * Parse incoming webhook payload into normalized email data.
     *
     * Supports:
     *   - GCP Pub/Sub push format
     *   - Zapier/Make webhook format
     *   - Laravel test payload format
     */
    public function parse(array $payload): array
    {
        // GCP Pub/Sub format
        if (isset($payload['message']['data'])) {
            try {
                $decoded = base64_decode($payload['message']['data'], true);
                $data = $decoded !== false ? json_decode($decoded, true) : null;
            } catch (\Throwable) {
                $data = null;
            }

            $messageId = $payload['message']['messageId'] ?? null;

            if (is_array($data)) {
                return $this->normalize([
                    'message_id'  => $messageId,
                    'from'        => $data['from'] ?? null,
                    'to'          => $data['to'] ?? null,
                    'subject'     => $data['subject'] ?? null,
                    'body_text'   => $data['body_text'] ?? null,
                    'body_html'   => $data['body_html'] ?? null,
                    'headers'     => $data['headers'] ?? [],
                ]);
            }

            Log::warning('[GmailWebhookReceiver] GCP Pub/Sub data decode failed', [
                'message_id' => $messageId,
            ]);

            return $this->normalize(['message_id' => $messageId]);
        }

        // Zapier / Make / direct format
        if (isset($payload['from']) || isset($payload['sender_email'])) {
            return $this->normalize($payload);
        }

        // Raw Laravel test format
        return $this->normalize([
            'message_id'  => $payload['message_id'] ?? $payload['Message-ID'] ?? null,
            'from'        => $payload['from'] ?? null,
            'to'          => $payload['to'] ?? null,
            'subject'     => $payload['subject'] ?? null,
            'body_text'   => $payload['body_text'] ?? $payload['text'] ?? $payload['message'] ?? null,
            'body_html'   => $payload['body_html'] ?? null,
            'headers'     => $payload['headers'] ?? [],
        ]);
    }

    /**
     * Full pipeline: AI extract → severity → save → Hermes event.
     *
     * @return array{communication: Communication, hermes_log_id: int}
     */
    public function dispatchHermesEvent(
        Tenant $tenant,
        array $emailData,
        ?string $messageId,
    ): array {
        $emailAddress = $emailData['sender_email'];
        $subject = $emailData['subject'];
        $bodyText = $emailData['body_text'];

        // ── 1. AI: Signal extraction ──────────────────────────────────────────
        $extraction = $this->llmService->extractSignals($emailAddress, $subject, $bodyText);

        Log::info('[GmailWebhookReceiver] AI extraction complete', [
            'intent'  => $extraction->intent,
            'platform' => $extraction->sourcePlatform,
            'urgent'  => $extraction->isUrgent,
        ]);

        // ── 2. Severity: Deterministic PHP policy ─────────────────────────────
        $severity = \App\Policies\CommunicationSeverityPolicy::determineSeverity($extraction);

        Log::info('[GmailWebhookReceiver] Severity determined', [
            'severity' => $severity,
            'intent'  => $extraction->intent,
        ]);

        // ── 3. Reservation matching ──────────────────────────────────────────
        $reservationMatch = $this->reservationResolver->resolve(
            email: $emailAddress,
            reservationRef: $extraction->reservationRef,
            tenantId: $tenant->id,
        );

        Log::info('[GmailWebhookReceiver] Reservation match', [
            'reservation_id' => $reservationMatch['reservation_id'] ?? null,
            'confidence'     => $reservationMatch['confidence'] ?? null,
        ]);

        // ── 4. Persist Communication ─────────────────────────────────────────
        $communication = Communication::create([
            'tenant_id'           => $tenant->id,
            'channel'             => 'email',
            'external_message_id' => $messageId,
            'sender_email'        => $emailAddress,
            'sender_name'         => $extraction->guestName,
            'subject'             => $subject,
            'message'             => $bodyText,
            'platform'            => $extraction->sourcePlatform,
            'severity'            => $severity,
            'ai_extracted_data'  => [
                'intent'           => $extraction->intent,
                'language'         => $extraction->language,
                'source_platform'  => $extraction->sourcePlatform,
                'guest_name'       => $extraction->guestName,
                'reservation_ref'  => $extraction->reservationRef,
                'message_summary'  => $extraction->messageSummary,
                'sentiment'        => $extraction->sentiment,
                'is_urgent'        => $extraction->isUrgent,
                'extracted_fields' => $extraction->extractedFields,
            ],
            'reservation_id'       => $reservationMatch['reservation_id'] ?? null,
            'reply_durumu'        => 'bekliyor',
        ]);

        // ── 5. Hermes event ───────────────────────────────────────────────────
        $event = new EmailCommunicationReceivedEvent(
            tenantId: $tenant->id,
            communicationId: $communication->id,
            severity: $severity,
            intent: $extraction->intent,
            platform: $extraction->sourcePlatform,
            hasReservation: $reservationMatch['reservation_id'] !== null,
            aiExtractedData: $extraction->toArray(),
        );

        $hermesLog = app(\App\Services\Hermes\HermesService::class)->receive($event);

        return [
            'communication'  => $communication,
            'hermes_log_id' => $hermesLog->id,
            'severity'      => $severity,
            'intent'        => $extraction->intent,
        ];
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function normalize(array $input): array
    {
        $from = $this->parseFromHeader($input['from'] ?? '');

        return [
            'message_id'  => $input['message_id'] ?? null,
            'from'        => $input['from'] ?? null,
            'sender_email' => $from['email'],
            'sender_name'  => $from['name'],
            'to'          => $input['to'] ?? null,
            'subject'     => $input['subject'] ?? null,
            'body_text'   => $input['body_text'] ?? $input['text'] ?? $input['message'] ?? null,
            'body_html'   => $input['body_html'] ?? null,
            'headers'     => $input['headers'] ?? [],
        ];
    }

    /**
     * "Ayhan Küçük <ayhan@domain.com>" → ['name' => 'Ayhan Küçük', 'email' => 'ayhan@domain.com']
     */
    private function parseFromHeader(string $from): array
    {
        if (preg_match('/^(.+)\s<([^>]+)>$/', trim($from), $matches)) {
            return ['name' => trim($matches[1]), 'email' => trim($matches[2])];
        }

        return ['name' => null, 'email' => trim($from)];
    }
}
