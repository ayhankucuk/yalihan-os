<?php

namespace App\Contracts\Settings;

/**
 * 🛡️ SAB SEALED: Settings Authority Interface
 * 
 * Provides a canonical, unified write-only boundary for the Settings domain.
 * Eliminates Write Split Brain by enforcing a Single Source of Truth for mutations.
 */
interface SettingsAuthorityInterface
{
    /**
     * Set a setting value by key, with auto-type detection.
     */
    public function set(string $key, mixed $value, string $group = 'general', ?string $type = null, ?string $description = null): void;

    /**
     * Bulk update multiple settings.
     */
    public function bulkUpdate(array $settings): void;

    /**
     * Completely flush all settings caches.
     */
    public function flushCache(): void;
}
