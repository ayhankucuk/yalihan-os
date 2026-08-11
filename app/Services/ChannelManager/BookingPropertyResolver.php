<?php

namespace App\Services\ChannelManager;

use App\Infrastructure\ChannelManager\Booking\BookingPropertyRef;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingPropertyResolver — Resolves Booking HotelCode to canonical Ilan + Tenant.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 *
 * Mapping chain:
 *   Booking HotelCode (BasicPropertyInfo.HotelCode)
 *       ↓
 *   IlanTakvimSync (platform='booking_com', external_listing_id = HotelCode)
 *       ↓
 *   Ilan (canonical property)
 *       ↓
 *   Tenant context validation (tenant isolation)
 *       ↓
 *   BookingPropertyRef { ilanId, tenantId, hotelCode }
 *
 * BW1-06: Resolves to correct ilan
 * BW1-07: Unknown HotelCode → null (no exception)
 * BW1-08: Cross-tenant mapping blocked
 */
class BookingPropertyResolver
{
    /**
     * Resolve a Booking HotelCode to a canonical property reference.
     *
     * @param int    $tenantId   Calling tenant context
     * @param string $hotelCode  Booking.com BasicPropertyInfo.HotelCode
     *
     * @return BookingPropertyRef|null  null if HotelCode unknown or tenant mismatch
     */
    public function resolve(int $tenantId, string $hotelCode): ?BookingPropertyRef
    {
        // Step 1: Find sync record by HotelCode
        // DB::table bypasses Eloquent global scopes
        $sync = DB::table('ilan_takvim_sync')
            ->where('external_listing_id', $hotelCode)
            ->where('platform', 'booking_com')
            ->where('is_sync_active', 1)
            ->first(['ilan_id']);

        if ($sync === null) {
            Log::debug('BookingPropertyResolver: unknown HotelCode', [
                'hotel_code' => $hotelCode,
            ]);
            return null;
        }

        // Step 2: Verify tenant via ilan lookup
        $ilanTenantId = DB::table('ilanlar')
            ->where('id', $sync->ilan_id)
            ->value('tenant_id');

        // Tenant isolation: ilan must belong to calling tenant
        if ((int) $ilanTenantId !== $tenantId) {
            Log::warning('BookingPropertyResolver: cross-tenant access blocked', [
                'hotel_code'       => $hotelCode,
                'ilan_tenant_id'   => (int) $ilanTenantId,
                'request_tenant_id' => $tenantId,
            ]);
            return null;
        }

        Log::debug('BookingPropertyResolver: resolved', [
            'hotel_code' => $hotelCode,
            'ilan_id'    => $sync->ilan_id,
            'tenant_id'  => $tenantId,
        ]);

        return new BookingPropertyRef(
            ilanId: (int) $sync->ilan_id,
            tenantId: $tenantId,
            hotelCode: $hotelCode,
        );
    }
}
