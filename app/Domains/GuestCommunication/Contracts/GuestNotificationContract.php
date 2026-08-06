<?php

namespace App\Domains\GuestCommunication\Contracts;

/**
 * GuestNotificationContract
 *
 * GuestCommunication WAVE 1
 *
 * Guest Communication capability için notification contract.
 * NotificationContract'ı extend eder.
 */
interface GuestNotificationContract
{
    /**
     * Get guest communication type
     */
    public function getCommunicationType(): string;

    /**
     * Get reservation ID for this notification
     */
    public function getReservationId(): int;

    /**
     * Get property ID for this notification
     */
    public function getPropertyId(): int;

    /**
     * Get tenant ID for multi-tenancy
     */
    public function getTenantId(): int;
}
