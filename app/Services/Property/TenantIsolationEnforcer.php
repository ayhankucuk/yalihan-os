<?php

namespace App\Services\Property;

use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TenantIsolationEnforcer — RESERVATION_CORE Phase 2 E04
 *
 * Enforces the fundamental invariant:
 *   reservation.tenant_id = property.tenant_id = availability.tenant_id
 *
 * Uyumsuzluk durumunda:
 * - Reject: İşlem reddedilir
 * - Log: Sistem loglarına yazılır
 * - Audit Evidence: Audit tablosuna kayıt düşülür
 *
 * Sistem sessizce tenant düzeltmesi YAPMAZ.
 *
 * SAAB E04 Zorunlu Kurallar:
 * 1. Cross-tenant erişim kesinlikle yasak
 * 2. Mismatch durumunda reject + log + audit
 * 3. Hiçbir koşulda sessiz düzeltme yapılmaz
 */
class TenantIsolationEnforcer
{
    public const EVENT_CROSS_TENANT_PROJECT = 'cross_tenant_project_attempt';
    public const EVENT_CROSS_TENANT_CANCEL = 'cross_tenant_cancel_attempt';
    public const EVENT_CROSS_TENANT_REBUILD = 'cross_tenant_rebuild_attempt';
    public const EVENT_TENANT_MISMATCH = 'tenant_mismatch_detected';

    /**
     * Verify tenant/property/availability alignment.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @return TenantVerificationResult
     */
    public function verifyPropertyOwnership(int $tenantId, int $propertyId): TenantVerificationResult
    {
        // Get property
        $ilan = Ilan::withoutGlobalScopes()->find($propertyId);

        if (!$ilan) {
            return TenantVerificationResult::failure(
                'Property not found',
                'PROPERTY_NOT_FOUND',
                $tenantId,
                null,
                $propertyId,
                null
            );
        }

        $propertyTenantId = $ilan->tenant_id;

        // null tenant_id means legacy data without tenant isolation
        if ($propertyTenantId === null) {
            return TenantVerificationResult::success($tenantId, $propertyTenantId, $propertyId);
        }

        if ((int) $propertyTenantId !== $tenantId) {
            $this->logAndAuditViolation(
                self::EVENT_TENANT_MISMATCH,
                $tenantId,
                $propertyId,
                null,
                "Property {$propertyId} belongs to tenant {$propertyTenantId}, not {$tenantId}"
            );

            return TenantVerificationResult::failure(
                "Cross-tenant violation: property belongs to tenant {$propertyTenantId}",
                'CROSS_TENANT_PROPERTY_ACCESS',
                $tenantId,
                $propertyTenantId,
                $propertyId,
                null
            );
        }

        return TenantVerificationResult::success($tenantId, $propertyTenantId, $propertyId);
    }

    /**
     * Verify reservation ownership against property.
     *
     * @param PropertyReservation $reservation
     * @return TenantVerificationResult
     */
    public function verifyReservationPropertyAlignment(PropertyReservation $reservation): TenantVerificationResult
    {
        $result = $this->verifyPropertyOwnership(
            $reservation->tenant_id,
            $reservation->property_id
        );

        if (!$result->isValid()) {
            $this->logAndAuditViolation(
                self::EVENT_TENANT_MISMATCH,
                $reservation->tenant_id,
                $reservation->property_id,
                $reservation->id,
                "Reservation {$reservation->id} tenant mismatch with property"
            );
        }

        return $result;
    }

    /**
     * Verify availability row ownership against reservation.
     *
     * @param PropertyAvailability $availability
     * @param int $expectedTenantId
     * @return TenantVerificationResult
     */
    public function verifyAvailabilityReservationAlignment(
        PropertyAvailability $availability,
        int $expectedTenantId
    ): TenantVerificationResult {
        if ((int) $availability->tenant_id !== $expectedTenantId) {
            $this->logAndAuditViolation(
                self::EVENT_TENANT_MISMATCH,
                $expectedTenantId,
                $availability->property_id,
                $availability->reservation_id,
                "Availability row tenant_id {$availability->tenant_id} does not match expected {$expectedTenantId}"
            );

            return TenantVerificationResult::failure(
                "Cross-tenant availability access: row tenant_id {$availability->tenant_id} != {$expectedTenantId}",
                'CROSS_TENANT_AVAILABILITY_ACCESS',
                $expectedTenantId,
                $availability->tenant_id,
                $availability->property_id,
                $availability->reservation_id
            );
        }

        return TenantVerificationResult::success(
            $expectedTenantId,
            $availability->tenant_id,
            $availability->property_id
        );
    }

    /**
     * Enforce projection operation is tenant-scoped.
     *
     * @param int $requestingTenantId
     * @param int $targetTenantId
     * @param string $operation
     * @param int|null $propertyId
     * @param int|null $reservationId
     * @return bool
     * @throws CrossTenantAccessException
     */
    public function enforceProjectionAccess(
        int $requestingTenantId,
        int $targetTenantId,
        string $operation,
        ?int $propertyId = null,
        ?int $reservationId = null
    ): bool {
        if ($requestingTenantId !== $targetTenantId) {
            $this->logAndAuditViolation(
                self::EVENT_CROSS_TENANT_PROJECT,
                $requestingTenantId,
                $propertyId,
                $reservationId,
                "Tenant {$requestingTenantId} attempted to project into tenant {$targetTenantId}"
            );

            throw new CrossTenantAccessException(
                "Cross-tenant projection denied: requesting tenant {$requestingTenantId}, target tenant {$targetTenantId}",
                $requestingTenantId,
                $targetTenantId,
                $propertyId,
                $reservationId
            );
        }

        return true;
    }

    /**
     * Enforce cancel operation is tenant-scoped.
     *
     * @param int $requestingTenantId
     * @param int $targetTenantId
     * @param int|null $propertyId
     * @param int|null $reservationId
     * @throws CrossTenantAccessException
     */
    public function enforceCancelAccess(
        int $requestingTenantId,
        int $targetTenantId,
        ?int $propertyId = null,
        ?int $reservationId = null
    ): void {
        if ($requestingTenantId !== $targetTenantId) {
            $this->logAndAuditViolation(
                self::EVENT_CROSS_TENANT_CANCEL,
                $requestingTenantId,
                $propertyId,
                $reservationId,
                "Tenant {$requestingTenantId} attempted to cancel reservation in tenant {$targetTenantId}"
            );

            throw new CrossTenantAccessException(
                "Cross-tenant cancel denied: requesting tenant {$requestingTenantId}, target tenant {$targetTenantId}",
                $requestingTenantId,
                $targetTenantId,
                $propertyId,
                $reservationId
            );
        }
    }

    /**
     * Enforce rebuild operation is tenant-scoped.
     *
     * @param int $requestingTenantId
     * @param int $targetTenantId
     * @param int|null $propertyId
     * @throws CrossTenantAccessException
     */
    public function enforceRebuildAccess(
        int $requestingTenantId,
        int $targetTenantId,
        ?int $propertyId = null
    ): void {
        if ($requestingTenantId !== $targetTenantId) {
            $this->logAndAuditViolation(
                self::EVENT_CROSS_TENANT_REBUILD,
                $requestingTenantId,
                $propertyId,
                null,
                "Tenant {$requestingTenantId} attempted to rebuild projections in tenant {$targetTenantId}"
            );

            throw new CrossTenantAccessException(
                "Cross-tenant rebuild denied: requesting tenant {$requestingTenantId}, target tenant {$targetTenantId}",
                $requestingTenantId,
                $targetTenantId,
                $propertyId,
                null
            );
        }
    }

    /**
     * Log violation and create audit evidence.
     */
    private function logAndAuditViolation(
        string $eventType,
        int $requestingTenantId,
        ?int $propertyId,
        ?int $reservationId,
        string $message
    ): void {
        // System log
        Log::channel('security')->warning('Tenant Isolation Violation', [
            'event_type' => $eventType,
            'requesting_tenant_id' => $requestingTenantId,
            'property_id' => $propertyId,
            'reservation_id' => $reservationId,
            'message' => $message,
            'ip' => request()->ip() ?? 'CLI',
            'user_agent' => request()->userAgent() ?? 'CLI',
            'timestamp' => now()->toIso8601String(),
        ]);

        // Audit evidence in database
        $this->createAuditEvidence($eventType, $requestingTenantId, $propertyId, $reservationId, $message);
    }

    /**
     * Create audit evidence record.
     */
    private function createAuditEvidence(
        string $eventType,
        int $requestingTenantId,
        ?int $propertyId,
        ?int $reservationId,
        string $message
    ): void {
        DB::table('cross_tenant_violation_audit')->insert([
            'event_type' => $eventType,
            'requesting_tenant_id' => $requestingTenantId,
            'property_id' => $propertyId,
            'reservation_id' => $reservationId,
            'message' => $message,
            'ip_address' => request()->ip() ?? 'CLI',
            'user_agent' => request()->userAgent() ?? 'CLI',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get audit trail for a tenant.
     */
    public function getAuditTrail(int $tenantId, int $limit = 100): array
    {
        return DB::table('cross_tenant_violation_audit')
            ->where('requesting_tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }
}

/**
 * Result of tenant verification.
 */
class TenantVerificationResult
{
    public function __construct(
        public bool $isValid,
        public int $requestingTenantId,
        public ?int $actualTenantId,
        public ?int $propertyId,
        public ?int $reservationId,
        public ?string $errorCode,
        public ?string $errorMessage
    ) {}

    public static function success(int $requestingTenantId, ?int $actualTenantId, int $propertyId): self
    {
        return new self(
            isValid: true,
            requestingTenantId: $requestingTenantId,
            actualTenantId: $actualTenantId,
            propertyId: $propertyId,
            reservationId: null,
            errorCode: null,
            errorMessage: null
        );
    }

    public static function failure(
        string $message,
        string $errorCode,
        int $requestingTenantId,
        ?int $actualTenantId,
        ?int $propertyId,
        ?int $reservationId
    ): self {
        return new self(
            isValid: false,
            requestingTenantId: $requestingTenantId,
            actualTenantId: $actualTenantId,
            propertyId: $propertyId,
            reservationId: $reservationId,
            errorCode: $errorCode,
            errorMessage: $message
        );
    }
}

/**
 * Exception thrown when cross-tenant access is attempted.
 */
class CrossTenantAccessException extends \Exception
{
    public function __construct(
        string $message,
        public int $requestingTenantId,
        public int $targetTenantId,
        public ?int $propertyId = null,
        public ?int $reservationId = null
    ) {
        parent::__construct($message);
    }
}
