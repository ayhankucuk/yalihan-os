<?php

namespace App\Services\AI;

/**
 * EmailExtractionResult
 *
 * LLM extraction sonucu — immutable value object.
 * EmailIntelligenceService tarafindan dondurulur.
 *
 * SORUMLULUK:
 *   LLM sadece signal cikarir. Severity/P0-P2 karari
 *   CommunicationSeverityPolicy tarafindan deterministic olarak verilir.
 */
readonly class EmailExtractionResult
{
    public function __construct(
        public string $intent,
        public string $language,
        public string $sourcePlatform,
        public ?string $guestName,
        public ?string $reservationRef,
        public string $messageSummary,
        public string $sentiment,
        public bool $isUrgent,
        public array $extractedFields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            intent:           $data['intent']          ?? 'unknown',
            language:         $data['language']         ?? 'unknown',
            sourcePlatform:   $data['source_platform'] ?? ($data['platform'] ?? 'unknown'),
            guestName:        $data['guest_name']       ?? null,
            reservationRef:    $data['reservation_ref']  ?? null,
            messageSummary:   $data['message_summary'] ?? '',
            sentiment:         $data['sentiment']        ?? 'neutral',
            isUrgent:         (bool) ($data['is_urgent'] ?? false),
            extractedFields:  $data['extracted_fields'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'intent'            => $this->intent,
            'language'          => $this->language,
            'source_platform'   => $this->sourcePlatform,
            'guest_name'        => $this->guestName,
            'reservation_ref'  => $this->reservationRef,
            'message_summary'  => $this->messageSummary,
            'sentiment'        => $this->sentiment,
            'is_urgent'       => $this->isUrgent,
            'extracted_fields' => $this->extractedFields,
        ];
    }
}
