<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class MarkAsReadNotificationAction
{
    public function handle(Notification $notification): void
    {
        $notification->markAsRead();
    }
}
