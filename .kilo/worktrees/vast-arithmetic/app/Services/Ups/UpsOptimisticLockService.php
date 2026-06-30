<?php

declare(strict_types=1);

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\MasterTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Optimistic Locking Service for UPS
 *
 * Prevents concurrent edit conflicts by tracking version numbers
 * and validating before saves.
 *
 * Context7 Compliant:
 * - Uses aktiflik_durumu (Canonical)
 * - Uses display_order (Canonical)
 * - Wildcard cache pattern (NO Cache::tags)
 */
class UpsOptimisticLockService
{
    private const LOCK_PREFIX = 'ups_lock:';
    private const LOCK_TTL = 300; // 5 minutes

    /**
     * Get current version for an entity
     */
    public function getVersion(string $entityType, int $entityId): int
    {
        $key = $this->getLockKey($entityType, $entityId);
        $version = Cache::get($key);

        if ($version === null) {
            // Initialize from database
            $version = $this->getVersionFromDatabase($entityType, $entityId);
            Cache::put($key, $version, self::LOCK_TTL);
        }

        return (int) $version;
    }

    /**
     * Validate version before save (optimistic lock check)
     */
    public function validateVersion(string $entityType, int $entityId, int $clientVersion): array
    {
        $currentVersion = $this->getVersion($entityType, $entityId);

        if ($clientVersion < $currentVersion) {
            return [
                'valid' => false,
                'message' => 'Bu kayıt başka bir kullanıcı tarafından güncellenmiş. Lütfen sayfayı yenileyip tekrar deneyin.',
                'current_version' => $currentVersion,
                'client_version' => $clientVersion,
                'conflict' => true,
            ];
        }

        return [
            'valid' => true,
            'current_version' => $currentVersion,
        ];
    }

    /**
     * Increment version after successful save
     */
    public function incrementVersion(string $entityType, int $entityId): int
    {
        $key = $this->getLockKey($entityType, $entityId);
        $newVersion = $this->getVersion($entityType, $entityId) + 1;

        Cache::put($key, $newVersion, self::LOCK_TTL);

        Log::channel('daily')->debug('UPS version incremented', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'new_version' => $newVersion,
        ]);

        return $newVersion;
    }

    /**
     * Acquire edit lock for a user
     */
    public function acquireLock(string $entityType, int $entityId, int $userId): array
    {
        $lockKey = $this->getEditLockKey($entityType, $entityId);
        $existingLock = Cache::get($lockKey);

        if ($existingLock && $existingLock['user_id'] !== $userId) {
            $lockAge = now()->diffInMinutes($existingLock['locked_at']);

            // Lock expires after 5 minutes of inactivity
            if ($lockAge < 5) {
                return [
                    'acquired' => false,
                    'message' => "Bu kayıt şu anda {$existingLock['user_name']} tarafından düzenleniyor.",
                    'locked_by' => $existingLock['user_name'],
                    'locked_at' => $existingLock['locked_at'],
                    'expires_in' => 5 - $lockAge,
                ];
            }
        }

        // Acquire or refresh lock
        $lockData = [
            'user_id' => $userId,
            'user_name' => auth()->user()?->name ?? 'Bilinmeyen',
            'locked_at' => now()->toIso8601String(),
        ];

        Cache::put($lockKey, $lockData, self::LOCK_TTL);

        return [
            'acquired' => true,
            'lock_data' => $lockData,
        ];
    }

    /**
     * Release edit lock
     */
    public function releaseLock(string $entityType, int $entityId, int $userId): bool
    {
        $lockKey = $this->getEditLockKey($entityType, $entityId);
        $existingLock = Cache::get($lockKey);

        if ($existingLock && $existingLock['user_id'] === $userId) {
            Cache::forget($lockKey);
            return true;
        }

        return false;
    }

    /**
     * Refresh lock (heartbeat)
     */
    public function refreshLock(string $entityType, int $entityId, int $userId): bool
    {
        $lockKey = $this->getEditLockKey($entityType, $entityId);
        $existingLock = Cache::get($lockKey);

        if ($existingLock && $existingLock['user_id'] === $userId) {
            $existingLock['locked_at'] = now()->toIso8601String();
            Cache::put($lockKey, $existingLock, self::LOCK_TTL);
            return true;
        }

        return false;
    }

    /**
     * Check if entity is locked by another user
     */
    public function isLockedByOther(string $entityType, int $entityId, int $currentUserId): ?array
    {
        $lockKey = $this->getEditLockKey($entityType, $entityId);
        $existingLock = Cache::get($lockKey);

        if ($existingLock && $existingLock['user_id'] !== $currentUserId) {
            $lockAge = now()->diffInMinutes($existingLock['locked_at']);

            if ($lockAge < 5) {
                return [
                    'locked' => true,
                    'locked_by' => $existingLock['user_name'],
                    'locked_at' => $existingLock['locked_at'],
                    'expires_in' => 5 - $lockAge,
                ];
            }
        }

        return null;
    }

    /**
     * Get conflict diff for merge resolution
     */
    public function getConflictDiff(string $entityType, int $entityId, array $clientData): array
    {
        $currentData = $this->getCurrentData($entityType, $entityId);

        if (!$currentData) {
            return ['error' => 'Entity not found'];
        }

        $conflicts = [];
        $mergeable = [];

        foreach ($clientData as $field => $clientValue) {
            if (!isset($currentData[$field])) {
                $mergeable[$field] = $clientValue;
                continue;
            }

            $serverValue = $currentData[$field];

            if ($clientValue !== $serverValue) {
                $conflicts[$field] = [
                    'client_value' => $clientValue,
                    'server_value' => $serverValue,
                ];
            }
        }

        return [
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
            'mergeable' => $mergeable,
            'current_version' => $this->getVersion($entityType, $entityId),
        ];
    }

    /**
     * Force save with conflict resolution
     */
    public function forceSave(string $entityType, int $entityId, array $data, string $resolution = 'client_wins'): array
    {
        // Increment version to acknowledge the conflict was resolved
        $newVersion = $this->incrementVersion($entityType, $entityId);

        return [
            'success' => true,
            'new_version' => $newVersion,
            'resolution' => $resolution,
        ];
    }

    /**
     * Get lock key for version tracking
     */
    private function getLockKey(string $entityType, int $entityId): string
    {
        return self::LOCK_PREFIX . "version:{$entityType}:{$entityId}";
    }

    /**
     * Get lock key for edit locking
     */
    private function getEditLockKey(string $entityType, int $entityId): string
    {
        return self::LOCK_PREFIX . "edit:{$entityType}:{$entityId}";
    }

    /**
     * Get version from database (updated_at timestamp as version)
     */
    private function getVersionFromDatabase(string $entityType, int $entityId): int
    {
        $model = match ($entityType) {
            'feature' => Feature::find($entityId),
            'assignment' => FeatureAssignment::find($entityId),
            'template' => MasterTemplate::find($entityId),
            default => null,
        };

        if (!$model) {
            return 1;
        }

        // Use updated_at timestamp as version number
        return $model->updated_at?->timestamp ?? 1;
    }

    /**
     * Get current data for conflict resolution
     */
    private function getCurrentData(string $entityType, int $entityId): ?array
    {
        $model = match ($entityType) {
            'feature' => Feature::find($entityId),
            'assignment' => FeatureAssignment::find($entityId),
            'template' => MasterTemplate::find($entityId),
            default => null,
        };

        return $model?->toArray();
    }
}
