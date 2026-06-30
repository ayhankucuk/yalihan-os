<?php

namespace App\Services\AI\Notifications;

/**
 * NotificationChannel Interface
 *
 * AI uyarı kanalları için kontrat.
 * LogChannel, EmailChannel, SlackChannel tarafından implement edilir.
 */
interface NotificationChannel
{
    /**
     * Bir uyarıyı ilgili kanala gönderir.
     *
     * @param array $alert severity, message, type, data, timestamp
     * @return bool Başarılıysa true
     */
    public function send(array $alert): bool;
}
