<?php

namespace App\Domain\ChannelManager\Contracts;

/**
 * AvailabilitySynchronizer — Contract for availability sync operations
 *
 * Sprint 13 E01: Domain Foundation
 *
 * This contract defines the sync strategy interface.
 * Implementations can use push, pull, or hybrid (push+pull) strategies.
 */
interface AvailabilitySynchronizer
{
    /**
     * Get the synchronizer strategy name
     */
    public function getStrategy(): string; // 'push' | 'pull' | 'hybrid'

    /**
     * Sync availability for a property on a channel
     *
     * @param int $propertyId
     * @param string $channelId
     * @param array $dates Array of ['date' => 'Y-m-d', 'available' => bool]
     * @return SyncResult
     */
    public function sync(int $propertyId, string $channelId, array $dates): SyncResult;

    /**
     * Detect conflicts between local and remote availability
     *
     * @param int $propertyId
     * @param string $channelId
     * @param string $fromDate Y-m-d
     * @param string $toDate Y-m-d
     * @return array Array of conflict details
     */
    public function detectConflicts(int $propertyId, string $channelId, string $fromDate, string $toDate): array;

    /**
     * Resolve a detected conflict
     *
     * @param int $propertyId
     * @param string $channelId
     * @param string $date Y-m-d
     * @param string $resolution 'local_wins' | 'remote_wins' | 'manual'
     * @return SyncResult
     */
    public function resolveConflict(
        int $propertyId,
        string $channelId,
        string $date,
        string $resolution
    ): SyncResult;
}
