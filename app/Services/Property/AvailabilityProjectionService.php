<?php

namespace App\Services\Property;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AvailabilityProjectionService
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Canonical write path for reservation → availability projection.
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 * ASLA: Reservation → PropertyAvailability::save()
 *
 * Idempotency: Aynı event birden fazla kez çağrıldığında aynı sonucu üretir.
 * Deterministic: Aynı reservation her zaman aynı availability kayıtlarını üretir.
 * Tenant-safe: Cross-tenant erişim engellenir.
 */
class AvailabilityProjectionService implements AvailabilityProjectionContract
{
    use GuardsAgentWrites;

    /**
     * Tenant isolation constants
     */
    private const TIER_RESERVATION = 10;

    /**
     * Projection source identifier
     */
    private const SOURCE = 'reservation_projection';

    /*=======================================================================
     * Projection Identity
     *=======================================================================*/

    public function getProjectionKey(int $reservationId, string $date): string
    {
        return "reservation:{$reservationId}:{$date}";
    }

    /*=======================================================================
     * Projection Operations
     *=======================================================================*/

    /**
     * Confirm: Project availability blocks for a reservation.
     *
     * Idempotent: If reservation is already confirmed, returns success without side effects.
     * Deterministic: Same reservation always produces same date blocks.
     *
     * @throws \Exception Cross-tenant violation
     */
    public function projectConfirm(
        int $reservationId,
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        // Validate tenant/property match
        $this->validateTenantPropertyMatchOrFail($tenantId, $propertyId);

        return DB::transaction(function () use ($reservationId, $tenantId, $propertyId, $startDate, $endDate) {
            $dates = $this->generateDateRange($startDate, $endDate);
            $now = now();

            // Check for external blocks (Airbnb, Booking, etc.)
            $externalBlock = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', false)
                ->where('source_system', '!=', 'internal')
                ->where('tenant_id', '!=', $tenantId)
                ->first();

            if ($externalBlock) {
                throw new \Exception(
                    "Cannot confirm: External block exists on {$externalBlock->date}"
                );
            }

            // Idempotency: Check if already projected by this reservation
            $existingBlocks = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('reservation_id', $reservationId)
                ->where('source_system', 'internal')
                ->count();

            if ($existingBlocks === count($dates)) {
                // Already projected — idempotent return
                return [
                    'success' => true,
                    'blocked_days' => $existingBlocks,
                    'dates' => $dates,
                    'idempotent' => true,
                ];
            }

            // Upsert: Insert missing dates, update existing
            $existing = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', true)
                ->where('source_system', 'internal')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($r) => $r->date);

            $blockedCount = 0;
            foreach ($dates as $dateStr) {
                if (isset($existing[$dateStr])) {
                    $existing[$dateStr]->update([
                        'is_available' => false,
                        'block_reason' => 'reservation',
                        'priority_tier' => self::TIER_RESERVATION,
                        'reservation_id' => $reservationId,
                        'source_system' => 'internal',
                        'origin' => 'reservation',
                        'idempotency_key' => $this->getProjectionKey($reservationId, $dateStr),
                        'projection_generated_at' => $now,
                        'projection_source' => self::SOURCE,
                    ]);
                    $blockedCount++;
                } else {
                    PropertyAvailability::create([
                        'tenant_id' => $tenantId,
                        'property_id' => $propertyId,
                        'date' => $dateStr,
                        'is_available' => false,
                        'block_reason' => 'reservation',
                        'priority_tier' => self::TIER_RESERVATION,
                        'reservation_id' => $reservationId,
                        'source_system' => 'internal',
                        'origin' => 'reservation',
                        'idempotency_key' => $this->getProjectionKey($reservationId, $dateStr),
                        'projection_generated_at' => $now,
                        'projection_source' => self::SOURCE,
                    ]);
                    $blockedCount++;
                }
            }

            return [
                'success' => true,
                'blocked_days' => $blockedCount,
                'dates' => $dates,
                'idempotent' => $existingBlocks > 0,
            ];
        });
    }

    /**
     * Cancel: Release availability blocks for a cancelled reservation.
     *
     * Idempotent: If reservation is already cancelled, returns success without side effects.
     * Only releases internal source blocks with matching reservation_id.
     * External source blocks (Airbnb, Booking) are preserved.
     *
     * @throws \Exception Cross-tenant violation
     */
    public function projectCancel(
        int $reservationId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        return DB::transaction(function () use ($reservationId, $tenantId, $startDate, $endDate) {
            $dates = $this->generateDateRange($startDate, $endDate);
            $now = now();

            // Tenant-safe: Scope to tenant
            $query = PropertyAvailability::where('tenant_id', $tenantId)
                ->whereIn('date', $dates)
                ->where('reservation_id', $reservationId)
                ->where('source_system', 'internal');

            $freedRows = $query->update([
                'is_available' => true,
                'block_reason' => null,
                'priority_tier' => 0, // HOLD_PENDING sentinel
                'reservation_id' => null,
                'origin' => null,
                'idempotency_key' => null,
                'projection_generated_at' => $now,
                'projection_source' => self::SOURCE,
            ]);

            return [
                'success' => true,
                'freed_days' => $freedRows,
                'dates' => $dates,
                'idempotent' => true, // Cancel is always idempotent
            ];
        });
    }

    /**
     * Get current projection for a reservation.
     *
     * @throws \Exception Cross-tenant violation
     */
    public function getProjection(int $reservationId, int $tenantId): array
    {
        $records = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('reservation_id', $reservationId)
            ->where('source_system', 'internal')
            ->get()
            ->keyBy(fn($r) => $r->date);

        $result = [];
        foreach ($records as $date => $record) {
            $result[$date] = [
                'date' => $date,
                'is_available' => $record->is_available,
                'block_reason' => $record->block_reason,
                'reservation_id' => $record->reservation_id,
                'source_system' => $record->source_system,
                'origin' => $record->origin,
            ];
        }

        return $result;
    }

    /**
     * Check if projection is complete for all dates.
     */
    public function isProjectionComplete(
        int $reservationId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): bool {
        $dates = $this->generateDateRange($startDate, $endDate);

        $blocked = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('reservation_id', $reservationId)
            ->whereIn('date', $dates)
            ->where('is_available', false)
            ->where('source_system', 'internal')
            ->count();

        return $blocked === count($dates);
    }

    /*=======================================================================
     * Tenant Invariant Validation
     *=======================================================================*/

    /**
     * Validate tenant/property match.
     *
     * @throws \Exception Cross-tenant violation
     */
    public function validateTenantPropertyMatch(int $tenantId, int $propertyId): bool
    {
        $ilan = Ilan::find($propertyId);

        if (!$ilan) {
            throw new \Exception("Property {$propertyId} not found");
        }

        // Cross-tenant violation: property belongs to different tenant
        // Skip check if property has no tenant_id (legacy data)
        if ($ilan->tenant_id !== null && (int) $ilan->tenant_id !== $tenantId) {
            throw new \Exception(
                "Cross-tenant violation: property {$propertyId} belongs to tenant {$ilan->tenant_id}, not {$tenantId}"
            );
        }

        return true;
    }

    /**
     * Check for cross-tenant access attempt.
     */
    public function isCrossTenantAccess(int $requestingTenantId, int $targetTenantId): bool
    {
        return $requestingTenantId !== $targetTenantId;
    }

    /*=======================================================================
     * Private Helpers
     *=======================================================================*/

    /**
     * Generate date range array.
     */
    private function generateDateRange(string $startDate, string $endDate): array
    {
        $dates = [];
        $current = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Validate tenant/property match or throw exception.
     */
    private function validateTenantPropertyMatchOrFail(int $tenantId, int $propertyId): void
    {
        if (!$this->validateTenantPropertyMatch($tenantId, $propertyId)) {
            throw new \Exception("Tenant/property match validation failed");
        }
    }
}
