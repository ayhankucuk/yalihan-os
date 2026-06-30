<?php

namespace App\Actions\Notification;

use Illuminate\Support\Facades\Auth;

class MarkAllAsReadNotificationAction
{
    public function handle(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }
}
