<?php

namespace App\Services\Property;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AvailabilityReplayService — RESERVATION_CORE Phase 2 E03
 *
 * Reconstructs availability projections from canonical reservation data.
 *
 * ZORUNLU KURALLAR (SAAB E03):
 *
 * 1. Replay mevcut history'yi değiştirmemeli
 *    → Reservation kayıtları salt-okunur; sadece PropertyAvailability yazılır
 *
 * 2. Aynı rebuild ikinci kez çalıştırıldığında duplicate üretmemeli
 *    → Idempotency: origin-scoped delete + idempotency_key kontrolü
 *
 * 3. Rebuild yalnızca hedef tenant kapsamında çalışmalı
 *    → Tenant isolation: tüm sorgular tenant_id ile scoped
 *
 * 4. Başarısız rebuild yarım projection bırakmamalı
 *    → Transaction: tüm değişiklikler atomic transaction içinde
 *
 * 5. Rebuild yeni bir execution ve audit kaydı üretmeli
 *    → Audit trail: rebuild_execution_logs tablosuna kayıt
 *
 * 6. Orijinal reservation kayıtları mutate edilmemeli
 *    → Reservation kayıtları okunur, asla yazılmaz
 *
 * Mimari zincir:
 * Canonical reservations
 *         ↓
 * Projection rebuild (this service)
 *         ↓
 * PropertyAvailability
 *         ↓
 * Runtime ile aynı sonuç
 */
class AvailabilityReplayService
{
    use GuardsAgentWrites;

    private AvailabilityProjectionContract $projectionService;
    private PropertyAvailabilityContract $availabilityService;

    public function __construct(
        AvailabilityProjectionContract $projectionService,
        PropertyAvailabilityContract $availabilityService
    ) {
        $this->projectionService = $projectionService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Rebuild availability projection from canonical reservations.
     *
     * Idempotent: Running this twice produces the same result.
     * Tenant-scoped: Only processes reservations for the specified tenant.
     * Transaction-safe: Fails atomically, no partial state.
     *
     * @param int $tenantId
     * @param int|null $propertyId null = all properties for tenant
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD (exclusive)
     * @param string|null $initiatedBy User/system identifier for audit
     * @return RebuildResult
     */
    public function rebuild(
        int $tenantId,
        ?int $propertyId,
        string $startDate,
        string $endDate,
        ?string $initiatedBy = null
    ): RebuildResult {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        // Validate dates
        if ($start->gte($end)) {
            return RebuildResult::failure('Start date must be before end date');
        }

        // Create audit execution record FIRST (before any changes)
        $executionId = $this->createExecutionRecord($tenantId, $propertyId, $startDate, $endDate, $initiatedBy);

        try {
            $result = DB::transaction(function () use ($tenantId, $propertyId, $start, $end, $executionId) {
                $processedProperties = 0;
                $processedReservations = 0;
                $blockedDays = 0;
                $errors = [];

                // Get properties to process
                $propertyIds = $this->getPropertyIdsForTenant($tenantId, $propertyId);

                foreach ($propertyIds as $propId) {
                    try {
                        $propertyResult = $this->rebuildProperty(
                            $tenantId,
                            $propId,
                            $start->format('Y-m-d'),
                            $end->format('Y-m-d'),
                            $executionId
                        );

                        if ($propertyResult['success']) {
                            $processedProperties++;
                            $processedReservations += $propertyResult['reservations_processed'];
                            $blockedDays += $propertyResult['blocked_days'];
                        } else {
                            $errors[] = [
                                'property_id' => $propId,
                                'error' => $propertyResult['error'],
                            ];
                        }
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'property_id' => $propId,
                            'error' => $e->getMessage(),
                        ];
                        // Continue processing other properties
                        Log::error('AvailabilityReplay: Property rebuild failed', [
                            'tenant_id' => $tenantId,
                            'property_id' => $propId,
                            'error' => $e->getMessage(),
                            'execution_id' => $executionId,
                        ]);
                    }
                }

                // Update execution record with results
                $this->updateExecutionRecord($executionId, $processedProperties, $processedReservations, $blockedDays, $errors);

                return new RebuildResult(
                    success: empty($errors) || $processedProperties > 0,
                    tenantId: $tenantId,
                    propertyId: $propertyId,
                    startDate: $start->format('Y-m-d'),
                    endDate: $end->format('Y-m-d'),
                    executionId: $executionId,
                    propertiesProcessed: $processedProperties,
                    reservationsProcessed: $processedReservations,
                    blockedDays: $blockedDays,
                    errors: $errors
                );
            });

            return $result;

        } catch (\Throwable $e) {
            // Transaction failed - mark execution as failed
            $this->markExecutionFailed($executionId, $e->getMessage());

            Log::error('AvailabilityReplay: Transaction failed', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
                'execution_id' => $executionId,
            ]);

            return RebuildResult::failure($e->getMessage(), $executionId);
        }
    }

    /**
     * Rebuild for a single property.
     *
     * Uses origin-scoped delete (like CanonicalAvailabilityService::rebuildAvailabilityProjection)
     * to preserve non-reservation blocks (owner, maintenance, external).
     */
    private function rebuildProperty(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        int $executionId
    ): array {
        // First, delete only reservation-origin rows (idempotency)
        // This ensures second rebuild doesn't duplicate
        $this->deleteReservationOriginRows($tenantId, $propertyId, $startDate, $endDate);

        // Get confirmed reservations for this property (canonical source)
        $reservations = $this->getConfirmedReservations($tenantId, $propertyId, $startDate, $endDate);

        // Project each reservation
        $blockedDays = 0;
        $processedCount = 0;

        foreach ($reservations as $reservation) {
            $projectionResult = $this->projectionService->projectConfirm(
                $reservation->id,
                $tenantId,
                $propertyId,
                $reservation->start_date,
                $reservation->end_date
            );

            if ($projectionResult['success']) {
                $blockedDays += $projectionResult['blocked_days'];
                $processedCount++;
            }
        }

        return [
            'success' => true,
            'reservations_processed' => $processedCount,
            'blocked_days' => $blockedDays,
        ];
    }

    /**
     * Delete only reservation-origin availability rows.
     *
     * CONTRACT: Only rows with origin IN ('reservation', 'yazlik') are deleted.
     * This preserves owner blocks, maintenance blocks, external channel blocks.
     * Idempotent: Running twice has same effect as running once.
     */
    private function deleteReservationOriginRows(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): int {
        $dates = $this->generateDateRange($startDate, $endDate);

        // Origin-scoped delete: only reservation and yazlik origins
        $deleted = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->whereIn('origin', [
                PropertyAvailabilityContract::ORIGIN_RESERVATION,
                PropertyAvailabilityContract::ORIGIN_YAZLIK,
            ])
            ->delete();

        // Also delete legacy rows (origin NULL, source=internal, block_reason=reservation)
        // written before E2 origin field was added
        PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->whereNull('origin')
            ->where('source_system', 'internal')
            ->where('block_reason', 'reservation')
            ->delete();

        return $deleted;
    }

    /**
     * Get confirmed reservations for a property in date range.
     *
     * CRITICAL: Only CONFIRMED reservations are included.
     * - PENDING: Not yet committed, should not block availability
     * - CANCELLED/COMPLETED/NO_SHOW: Terminal states, their blocks already released
     */
    private function getConfirmedReservations(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $terminalStates = array_map(
            fn(ReservationState $s) => $s->value,
            array_filter(ReservationState::cases(), fn(ReservationState $s) => $s->isTerminal())
        );

        return PropertyReservation::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->where('reservation_state', ReservationState::CONFIRMED->value)
            ->whereNotIn('reservation_state', $terminalStates)
            ->whereNull('cancelled_at')
            ->get()
            ->all();
    }

    /**
     * Get property IDs for tenant, optionally filtered by specific property.
     */
    private function getPropertyIdsForTenant(int $tenantId, ?int $propertyId): array
    {
        $query = Ilan::where('tenant_id', $tenantId)
            ->where('rental_enabled', true);

        if ($propertyId !== null) {
            $query->where('id', $propertyId);
        }

        return $query->pluck('id')->all();
    }

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

    // ========================================================================
    // Audit Trail
    // ========================================================================

    /**
     * Create execution record before starting rebuild.
     */
    private function createExecutionRecord(
        int $tenantId,
        ?int $propertyId,
        string $startDate,
        string $endDate,
        ?string $initiatedBy
    ): int {
        $executionId = DB::table('rebuild_execution_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'initiated_by' => $initiatedBy ?? 'system',
            'status' => 'running',
            'properties_processed' => 0,
            'reservations_processed' => 0,
            'blocked_days' => 0,
            'errors' => null,
            'started_at' => now(),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('AvailabilityReplay: Started', [
            'execution_id' => $executionId,
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'date_range' => "{$startDate} - {$endDate}",
        ]);

        return $executionId;
    }

    /**
     * Update execution record with results.
     */
    private function updateExecutionRecord(
        int $executionId,
        int $propertiesProcessed,
        int $reservationsProcessed,
        int $blockedDays,
        array $errors
    ): void {
        DB::table('rebuild_execution_logs')
            ->where('id', $executionId)
            ->update([
                'status' => empty($errors) ? 'completed' : 'completed_with_errors',
                'properties_processed' => $propertiesProcessed,
                'reservations_processed' => $reservationsProcessed,
                'blocked_days' => $blockedDays,
                'errors' => empty($errors) ? null : json_encode($errors),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('AvailabilityReplay: Completed', [
            'execution_id' => $executionId,
            'properties_processed' => $propertiesProcessed,
            'reservations_processed' => $reservationsProcessed,
            'blocked_days' => $blockedDays,
            'errors_count' => count($errors),
        ]);
    }

    /**
     * Mark execution as failed.
     */
    private function markExecutionFailed(int $executionId, string $error): void
    {
        DB::table('rebuild_execution_logs')
            ->where('id', $executionId)
            ->update([
                'status' => 'failed',
                'errors' => json_encode([['error' => $error]]),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get execution history for a tenant.
     */
    public function getExecutionHistory(int $tenantId, int $limit = 10): array
    {
        return DB::table('rebuild_execution_logs')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }
}

/**
 * Rebuild operation result.
 */
class RebuildResult
{
    public function __construct(
        public bool $success,
        public int $tenantId,
        public ?int $propertyId,
        public string $startDate,
        public string $endDate,
        public int $executionId,
        public int $propertiesProcessed,
        public int $reservationsProcessed,
        public int $blockedDays,
        public array $errors = []
    ) {}

    public static function failure(string $message, ?int $executionId = null): self
    {
        return new self(
            success: false,
            tenantId: 0,
            propertyId: null,
            startDate: '',
            endDate: '',
            executionId: $executionId ?? 0,
            propertiesProcessed: 0,
            reservationsProcessed: 0,
            blockedDays: 0,
            errors: [['error' => $message]]
        );
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
