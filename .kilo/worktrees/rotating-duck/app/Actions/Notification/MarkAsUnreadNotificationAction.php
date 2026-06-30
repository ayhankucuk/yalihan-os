<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class MarkAsUnreadNotificationAction
{
    public function handle(Notification $notification): void
    {
        $notification->markAsUnread();
    }
}
