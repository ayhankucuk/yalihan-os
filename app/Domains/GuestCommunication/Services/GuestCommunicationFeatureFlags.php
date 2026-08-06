<?php

namespace App\Domains\GuestCommunication\Services;

/**
 * GuestCommunicationFeatureFlags
 *
 * EX-001 WAVE 2 — Feature Flag / Kill Switch
 *
 * Guest Communication otomasyonunu kontrol eden feature flag'ler.
 * Config veya database'den okunabilir.
 */
class GuestCommunicationFeatureFlags
{
    /**
     * Global kill switch - tüm guest communication'ı açar/kapar
     */
    public function isEnabled(): bool
    {
        return (bool) config('guest_communication.enabled', false);
    }

    /**
     * Airbnb kanalını açar/kapar
     */
    public function isAirbnbEnabled(): bool
    {
        return $this->isEnabled()
            && (bool) config('guest_communication.channels.airbnb.enabled', false);
    }

    /**
     * Welcome mesajlarını açar/kapar
     */
    public function isWelcomeEnabled(): bool
    {
        return $this->isEnabled()
            && (bool) config('guest_communication.messages.welcome_enabled', true);
    }

    /**
     * Belirli tenant için pilot modunu kontrol eder
     */
    public function isTenantPilotEnabled(int $tenantId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Pilot allowlist kontrolü
        $pilotTenants = config('guest_communication.pilot.tenants', []);

        // Pilot mode disabled ise sadece allowlist'tekiler çalışır
        if ((bool) config('guest_communication.pilot.strict_mode', true)) {
            return in_array($tenantId, $pilotTenants, true);
        }

        // Pilot mode disabled ise herkes için açık (dikkat!)
        return true;
    }

    /**
     * Belirli property için pilot modunu kontrol eder
     */
    public function isPropertyPilotEnabled(int $propertyId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Pilot allowlist kontrolü
        $pilotProperties = config('guest_communication.pilot.properties', []);

        // Strict mode
        if ((bool) config('guest_communication.pilot.strict_mode', true)) {
            return in_array($propertyId, $pilotProperties, true);
        }

        return true;
    }

    /**
     * Tenant ve property için pilot kontrolü
     */
    public function isPilotEnabled(int $tenantId, int $propertyId): bool
    {
        return $this->isTenantPilotEnabled($tenantId)
            && $this->isPropertyPilotEnabled($propertyId);
    }

    /**
     * Retry mekanizmasını kontrol eder
     */
    public function isRetryEnabled(): bool
    {
        return (bool) config('guest_communication.retry.enabled', true);
    }

    /**
     * Max retry sayısı
     */
    public function getMaxRetries(): int
    {
        return (int) config('guest_communication.retry.max_attempts', 3);
    }

    /**
     * Retry backoff (saniye)
     */
    public function getRetryBackoff(): int
    {
        return (int) config('guest_communication.retry.backoff_seconds', 60);
    }
}
