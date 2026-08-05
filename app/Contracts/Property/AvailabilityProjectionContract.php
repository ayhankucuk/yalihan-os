<?php

namespace App\Contracts\Property;

/**
 * AvailabilityProjectionContract
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Canonical write path for reservation → availability projection.
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 * ASLA: Reservation → PropertyAvailability::save()
 *
 * Bu contract, Reservation lifecycle event'lerinden (confirm, cancel, complete, no_show)
 * PropertyAvailability tablosuna yazma yetkisini tekelleştirir.
 *
 * Idempotency: Aynı event birden fazla kez çağrıldığında aynı sonucu üretir.
 * Deterministic: Aynı reservation her zaman aynı availability kayıtlarını üretir.
 * Tenant-safe: Cross-tenant erişim engellenir.
 *
 * @since Phase 2
 */
interface AvailabilityProjectionContract
{
    /*=======================================================================
     * Projection Identity
     *=======================================================================*/

    /**
     * Bir rezervasyon için oluşturulmuş idempotency anahtarını döndürür.
     *
     * Format: reservation:{reservation_id}:availability
     * Gün bazlı: reservation:{reservation_id}:{date}
     *
     * @param int $reservationId
     * @param string $date
     * @return string
     */
    public function getProjectionKey(int $reservationId, string $date): string;

    /*=======================================================================
     * Projection Operations
     *=======================================================================*/

    /**
     * Bir rezervasyonu onayla ve availability kayıtlarını oluştur.
     *
     * Idempotent: Rezervasyon zaten confirmed ise tekrar çağrı hata vermez.
     * Deterministic: Aynı rezervasyon her zaman aynı date range'i bloklar.
     *
     * @param int $reservationId
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array ['success' => bool, 'blocked_days' => int, 'dates' => string[]]
     * @throws \Exception Cross-tenant violation veya invalid state transition
     */
    public function projectConfirm(
        int $reservationId,
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array;

    /**
     * Bir rezervasyonu iptal et ve availability kayıtlarını serbest bırak.
     *
     * Idempotent: Rezervasyon zaten cancelled ise tekrar çağrı hata vermez.
     * Yalnızca source_system='internal' ve reservation_id=$reservationId kayıtları freed edilir.
     * External source kayıtları (Airbnb, Booking) korunur.
     *
     * @param int $reservationId
     * @param int $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return array ['success' => bool, 'freed_days' => int, 'dates' => string[]]
     * @throws \Exception Cross-tenant violation
     */
    public function projectCancel(
        int $reservationId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): array;

    /**
     * Mevcut projection kayıtlarını doğrula ve döndür.
     *
     * Tenant-safe: Yalnızca authorized tenant'ın kayıtlarını döndürür.
     *
     * @param int $reservationId
     * @param int $tenantId
     * @return array Date-keyed availability records
     * @throws \Exception Cross-tenant violation
     */
    public function getProjection(int $reservationId, int $tenantId): array;

    /**
     * Projection kayıtlarının mevcut durumunu kontrol et.
     *
     * @param int $reservationId
     * @param int $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return bool True = tüm tarihler blocked, False = eksik veya free
     */
    public function isProjectionComplete(int $reservationId, int $tenantId, string $startDate, string $endDate): bool;

    /*=======================================================================
     * Tenant Invariant Validation
     *=======================================================================*/

    /**
     * Tenant/property eşleşmesini doğrula.
     *
     * Kritik invariant:
     * - availability.tenant_id = reservation.tenant_id
     * - availability.property_id = reservation.property_id
     *
     * @param int $tenantId
     * @param int $propertyId
     * @return bool
     */
    public function validateTenantPropertyMatch(int $tenantId, int $propertyId): bool;

    /**
     * Cross-tenant erişim kontrolü.
     *
     * Tenant A'nın Tenant B'nin availability kayıtlarına erişmesini engeller.
     *
     * @param int $requestingTenantId
     * @param int $targetTenantId
     * @return bool True = erişim reddedildi
     */
    public function isCrossTenantAccess(int $requestingTenantId, int $targetTenantId): bool;
}
