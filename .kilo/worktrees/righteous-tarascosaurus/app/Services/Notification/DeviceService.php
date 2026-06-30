<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class DeviceService
{
    /**
     * Register or update a user device
     */
    public function register(User $user, array $data): UserDevice
    {
        return UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $data['device_id'],
            ],
            [
                'fcm_token' => $data['fcm_token'],
                'platform' => $data['platform'] ?? 'ios',
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Unregister a device
     */
    public function unregister(User $user, string $deviceId): bool
    {
        return UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->delete() > 0;
    }

    /**
     * Prune inactive devices
     */
    public function prune(int $days = 90): int
    {
        return UserDevice::where('last_active_at', '<', now()->subDays($days))
            ->delete();
    }
}
