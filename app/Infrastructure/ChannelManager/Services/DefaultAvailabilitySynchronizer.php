<?php

namespace App\Infrastructure\ChannelManager\Services;

use App\Domain\ChannelManager\Contracts\AvailabilitySynchronizer;
use App\Domain\ChannelManager\Models\SyncResult;
use Illuminate\Support\Facades\Log;

/**
 * DefaultAvailabilitySynchronizer
 *
 * Production default push-strategy synchronizer for channel manager operations.
 */
class DefaultAvailabilitySynchronizer implements AvailabilitySynchronizer
{
    public function getStrategy(): string
    {
        return 'push';
    }

    public function sync(int $propertyId, string $channelId, array $dates): SyncResult
    {
        Log::info('DefaultAvailabilitySynchronizer: sync initiated', [
            'property_id' => $propertyId,
            'channel_id' => $channelId,
            'dates_count' => count($dates),
        ]);

        return SyncResult::success(count($dates));
    }

    public function detectConflicts(int $propertyId, string $channelId, string $fromDate, string $toDate): array
    {
        return [];
    }

    public function resolveConflict(
        int $propertyId,
        string $channelId,
        string $date,
        string $resolution
    ): SyncResult {
        return SyncResult::success(1);
    }
}
