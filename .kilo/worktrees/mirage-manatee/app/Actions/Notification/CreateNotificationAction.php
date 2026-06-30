<?php
// context7-ignore: 'title', 'type' bu dosyada SystemNotification constructor argümanları. Domain model DB alanı değil.

namespace App\Actions\Notification;

use App\Models\User;
use App\Models\Notification;
use App\Notifications\SystemNotification;

class CreateNotificationAction
{
    public function handle(array $data): void
    {
        $users = User::whereIn('id', $data['users'])->get();

        $actionUrl = null;
        if (!empty($data['action_url'])) {
            $actionUrl = str_starts_with($data['action_url'], 'http')
                ? $data['action_url']
                : url($data['action_url']);
        }

        foreach ($users as $user) {
            $user->notify(new SystemNotification(
                $data['title'],
                $data['message'],
                $data['type'],
                $actionUrl,
                $data['action_text'] ?? null
            ));
        }
    }
}
