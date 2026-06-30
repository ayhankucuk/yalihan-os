<?php

namespace App\Contracts\Notification;

use App\Models\User;

/**
 * Notification Authority Interface
 * 
 * Defines the contract for the central decision engine that maps
 * system events to specific notification channels, templates, and priorities.
 */
interface NotificationAuthorityInterface
{
    /**
     * Notify the system about an event.
     * 
     * @param string $event The event key (e.g., 'booking_requested')
     * @param array $data Data required for the notification (payload, context)
     * @param User|null $actor The user who triggered the event
     * @return void
     */
    public function notify(string $event, array $data = [], ?User $actor = null): void;

    /**
     * Get the notification policy for a specific event.
     * 
     * @param string $event
     * @return array
     */
    public function getPolicyFor(string $event): array;
}
