<?php

namespace App\Actions\Admin\Notification;

use App\Models\AdminNotification;

class MarkAllAdminNotificationsReadAction
{
    public function handle(int $userId): int
    {
        return AdminNotification::forUser($userId)
            ->unread()
            ->update(['is_read' => true]);
    }
}