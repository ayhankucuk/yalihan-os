<?php

namespace App\Services\Communication;

use App\Models\Communication;
use App\Models\PropertyReservation;
use Illuminate\Support\Collection;

/**
 * CommunicationExceptionEvaluatorService
 *
 * Email Intelligence Wave 1 — Guest Communication exception evaluator.
 *
 * AYRI SERVIS: OperationalExceptionEvaluatorService'i BUYUTMEZ.
 * PropertyReservation state anomalies   → OperationalExceptionEvaluatorService (mevcut)
 * Email misafir mesajlari anomalies    → CommunicationExceptionEvaluatorService (bu)
 *
 * SORUMLULUK:
 *   - PropertyReservation ile iliskili unresolved P0/P1 Communications'u degerlendirir
 *   - Her rezervasyon icin aktif communication exception'larini dondurur
 *   - Read-only: side-effect yok, DB yazmaz, event tetiklemez
 *
 * @see OperationalExceptionEvaluatorService — mevcut rezervasyon state evaluator
 */
class CommunicationExceptionEvaluatorService
{
    /**
     * Tek rezervasyon icin aktif communication exception'larini dondurur.
     *
     * @return array<int, CommunicationExceptionDTO>
     */
    public function evaluate(PropertyReservation $reservation, ?\Carbon\Carbon $referenceNow = null): array
    {
        $referenceNow ??= now();

        $communications = Communication::query()
            ->where('reservation_id', $reservation->id)
            ->whereIn('severity', ['P0', 'P1'])
            ->whereNull('resolved_at')
            ->where('reply_durumu', 'bekliyor')
            // Cross-DB: P0 before P1 using CASE WHEN (SQLite + MySQL compatible)
            ->orderByRaw("CASE WHEN severity = 'P0' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return $communications->map(fn ($comm) => new CommunicationExceptionDTO(
            communicationId: $comm->id,
            reservationId: $reservation->id,
            severity: $comm->severity,
            platform: $comm->platform ?? 'unknown',
            intent: $comm->ai_extracted_data['intent'] ?? 'unknown',
            messageSummary: $comm->ai_extracted_data['message_summary'] ?? $comm->message,
            senderName: $comm->sender_name,
            createdAt: $comm->created_at,
            guestName: $comm->ai_extracted_data['guest_name'] ?? null,
            extractedFields: $comm->ai_extracted_data['extracted_fields'] ?? [],
        ))->values()->all();
    }

    /**
     * Bir rezervasyon koleksiyonu icin communication exception'larini dondurur.
     *
     * @param iterable<PropertyReservation> $reservations
     * @return array<int, array<int, CommunicationExceptionDTO>>
     */
    public function evaluateCollection(iterable $reservations, ?\Carbon\Carbon $referenceNow = null): array
    {
        $results = [];
        foreach ($reservations as $reservation) {
            $exceptions = $this->evaluate($reservation, $referenceNow);
            if (count($exceptions) > 0) {
                $results[$reservation->id] = $exceptions;
            }
        }
        return $results;
    }

    /**
     * Tenant icin tum aktif P0/P1 communications dondurur.
     */
    public function getActiveForTenant(int $tenantId): Collection
    {
        return Communication::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('severity', ['P0', 'P1'])
            ->whereNull('resolved_at')
            ->where('reply_durumu', 'bekliyor')
            ->where('channel', 'email')
            // Cross-DB: P0 before P1 using CASE WHEN (SQLite + MySQL compatible)
            ->orderByRaw("CASE WHEN severity = 'P0' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->with(['reservation.ilan', 'tenant'])
            ->get();
    }
}

/**
 * CommunicationExceptionDTO
 *
 * Read-only value object — communication exception temsil eder.
 */
readonly class CommunicationExceptionDTO
{
    public function __construct(
        public int $communicationId,
        public int $reservationId,
        public string $severity,
        public string $platform,
        public string $intent,
        public string $messageSummary,
        public ?string $senderName,
        public \Carbon\Carbon $createdAt,
        public ?string $guestName,
        public array $extractedFields = [],
    ) {}

    public function isP0(): bool { return $this->severity === 'P0'; }
    public function isP1(): bool { return $this->severity === 'P1'; }

    public function platformLabel(): string
    {
        return match ($this->platform) {
            'airbnb'      => 'Airbnb',
            'booking.com' => 'Booking.com',
            'direct'     => 'Direct',
            default       => 'Email',
        };
    }
}
