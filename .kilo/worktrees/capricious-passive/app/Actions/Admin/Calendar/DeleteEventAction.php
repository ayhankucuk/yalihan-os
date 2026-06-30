<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Event;

class DeleteEventAction
{
    public function handle(Event $event): bool
    {
        return $event->delete();
    }
}
