<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Chaos;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Chaos Mode Service
 *
 * Provides failure injection hooks for resilience testing.
 * ✅ Production Safe: Automatically disabled if PROPERTYHUB_CHAOS_ENABLED is false.
 */
class ChaosModeService
{
    public const TYPE_SIGNATURE_MISMATCH = 'signature_mismatch';
    public const TYPE_REDIS_OUTAGE = 'redis_outage';
    public const TYPE_DB_DEADLOCK = 'db_deadlock';
    public const TYPE_PARTIAL_ROLLBACK = 'partial_rollback';

    private array $activeChaos = [];

    public function __construct()
    {
        // Load default chaos if enabled in config
        if (Config::get('propertyhub.chaos_enabled', false)) {
            $this->activeChaos = Config::get('propertyhub.active_chaos', []);
        }
    }

    /**
     * Check if a specific chaos type is active.
     */
    public function isSet(string $type): bool
    {
        if (!Config::get('propertyhub.chaos_enabled', false)) {
            return false;
        }

        return in_array($type, $this->activeChaos);
    }

    /**
     * Set chaos type programmatically (useful for tests).
     */
    public function set(string $type): void
    {
        if (!Config::get('propertyhub.chaos_enabled', false)) {
            Log::warning("Chaos injection attempted but chaos is disabled globally.");
            return;
        }

        if (!in_array($type, $this->activeChaos)) {
            $this->activeChaos[] = $type;
            Log::channel('governance_security')->warning("CHAOS INJECTED: {$type}");
        }
    }

    /**
     * Clear all active chaos.
     */
    public function clear(): void
    {
        $this->activeChaos = [];
        Log::channel('governance_security')->info("CHAOS CLEARED");
    }

    /**
     * Trigger a chaos event if set.
     */
    public function trigger(string $type, \Closure $failure): void
    {
        if ($this->isSet($type)) {
            $failure();
        }
    }
}
