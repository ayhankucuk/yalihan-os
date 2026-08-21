<?php

namespace App\Services\Communication;

use App\Models\Kisi;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\Log;

/**
 * ReservationResolver
 *
 * Gelen email'deki bilgilerden PropertyReservation eslesmesi yapar.
 *
 * Eslesme stratejisi (priority order):
 *   1. Reservation reference (Airbnb/Booking ref) — kesin eslesme
 *   2. Guest email (Kisi.eposta)                — aktif rezervasyonlarda arar
 *   3. Tenant disiysa → null
 *
 * Tenant isolation: her query tenant_id scope icinde calisir.
 * Tenant disi misafir verisi islenmez.
 */
class ReservationResolver
{
    /**
     * @return array{
     *     reservation_id: int|null,
     *     ilan_id: int|null,
     *     guest_name: string|null,
     *     confidence: string,
     *     match_type: string
     * }
     */
    public function resolve(
        string $email,
        ?string $reservationRef,
        int $tenantId,
    ): array {
        // ── 1. Reservation reference ile dene ──────────────────────────────
        if ($reservationRef) {
            $match = $this->matchByReference($reservationRef, $tenantId);
            if ($match !== null) {
                Log::info('[ReservationResolver] Matched by reference', [
                    'reservation_id' => $match['reservation_id'],
                    'ref' => $reservationRef,
                ]);
                return $match;
            }
        }

        // ── 2. Email ile dene ─────────────────────────────────────────────
        if ($email) {
            $match = $this->matchByEmail($email, $tenantId);
            if ($match !== null) {
                Log::info('[ReservationResolver] Matched by email', [
                    'reservation_id' => $match['reservation_id'],
                    'email' => $email,
                ]);
                return $match;
            }
        }

        Log::info('[ReservationResolver] No match found', [
            'email'     => $email,
            'ref'       => $reservationRef,
            'tenant_id' => $tenantId,
        ]);

        return [
            'reservation_id' => null,
            'ilan_id'       => null,
            'guest_name'    => null,
            'confidence'    => 'none',
            'match_type'    => 'none',
        ];
    }

    private function matchByReference(string $ref, int $tenantId): ?array
    {
        // property_reservations'da reservation_reference alani kontrol et
        // Yoksa: reservation_reference olmayan sistemler icin ek anahtarlar
        $reservation = PropertyReservation::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($ref) {
                $q->where('reservation_reference', $ref)
                  ->orWhere('airbnb_confirmation_code', $ref)
                  ->orWhere('booking_confirmation_code', $ref);
            })
            ->orderBy('id', 'desc')
            ->first();

        if (! $reservation) {
            return null;
        }

        return $this->buildMatch($reservation, 'reference');
    }

    private function matchByEmail(string $email, int $tenantId): ?array
    {
        // Kisi.eposta ile Kisi'yi bul → aktif rezervasyonlarda ara
        $kisi = Kisi::query()
            ->where('tenant_id', $tenantId)
            ->where('eposta', $email)
            ->first();

        if (! $kisi) {
            return null;
        }

        // Aktif veya gelecek rezervasyonlarda ara
        $reservation = PropertyReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('guest_email', $email)
            ->whereIn('reservation_state', [
                'confirmed', 'checked_in', 'pending',
            ])
            ->where('start_date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        if (! $reservation) {
            return null;
        }

        return $this->buildMatch($reservation, 'email');
    }

    private function buildMatch(PropertyReservation $reservation, string $matchType): array
    {
        return [
            'reservation_id' => $reservation->id,
            'ilan_id'       => $reservation->property_id,
            'guest_name'    => $reservation->guest_name,
            'confidence'    => $matchType === 'reference' ? 'high' : 'medium',
            'match_type'    => $matchType,
        ];
    }
}
