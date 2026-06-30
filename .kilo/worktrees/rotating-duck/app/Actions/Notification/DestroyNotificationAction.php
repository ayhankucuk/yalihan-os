<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class DestroyNotificationAction
{
    public function handle(Notification $notification): void
    {
        $notification->delete();
    }
}
