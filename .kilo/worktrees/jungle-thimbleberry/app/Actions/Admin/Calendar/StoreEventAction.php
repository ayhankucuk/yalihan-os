<?php

namespace App\Actions\Admin\Calendar;

use App\Models\Event;

class StoreEventAction
{
    public function handle(array $data): Event
    {
        return Event::create($data);
    }
}
