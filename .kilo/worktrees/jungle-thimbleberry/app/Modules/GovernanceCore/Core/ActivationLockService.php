<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Activation Lock Service
 *
 * Enforces strict sequential activation of configuration versions.
 * Combines Redis Mutex with Pessimistic DB Row Locking.
 * Part of the Sprint 13 Zero-Trust protocol.
 */
class ActivationLockService
{
    private const LOCK_KEY = 'governance.activation_mutex';
    private const LOCK_TTL = 30; // 30 seconds

    /**
     * Execute a closure within a strict activation lock.
     *
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function executeLocked(callable $callback): mixed
    {
        // 1. Distributed Mutex (Redis)
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (!$lock->get()) {
            Log::channel('governance_security')->warning("CONCURRENT ACTIVATION ATTEMPT: Mutex already held.");
            throw new \RuntimeException("Yalıhan Governance Error: Activation in progress by another operator.");
        }

        try {
            // 2. Pessimistic DB Row Locking
            return DB::transaction(function () use ($callback) {
                // Resolve the state column name (yonetim_durumu or governance_state)
                $stateColumn = Schema::hasColumn('property_config_versions', 'yonetim_durumu')
                    ? 'yonetim_durumu'
                    : 'governance_state';

                // Lock the active version record to prevent any other process from swapping it
                DB::table('property_config_versions')
                    ->where($stateColumn, VersionStateMachine::DURUM_AKTIF)
                    ->lockForUpdate()
                    ->get();

                return $callback();
            });
        } finally {
            $lock->release();
        }
    }
}
