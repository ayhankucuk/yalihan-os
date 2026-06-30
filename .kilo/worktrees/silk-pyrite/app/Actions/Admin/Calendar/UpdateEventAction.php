<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Event;

class UpdateEventAction
{
    public function handle(Event $event, array $data): Event
    {
        $event->update($data);
        return $event->fresh();
    }
}
