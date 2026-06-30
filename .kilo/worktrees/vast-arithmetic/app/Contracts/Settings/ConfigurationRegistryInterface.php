<?php

namespace App\Contracts\Settings;

use Illuminate\Database\Eloquent\Collection;

/**
 * 🛡️ SAB SEALED: Configuration Registry Interface
 * 
 * Provides a canonical, unified read-only boundary for the Settings domain.
 * Eliminates Cache Split Brain by enforcing a Single Source of Truth for reads.
 */
interface ConfigurationRegistryInterface
{
    /**
     * Get a setting value by key, with an optional default.
     * Must be cached behind the scenes.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all settings belonging to a specific group.
     */
    public function getGroup(string $group): Collection;

    /**
     * Get a list of all groups and their counts.
     */
    public function getGroups(): array;
}
