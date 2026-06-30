<?php

namespace App\Actions\Admin\Calendar;

use App\Models\IlanTakvimSync;

class DeleteCalendarSyncAction
{
    public function handle(IlanTakvimSync $sync): bool
    {
        return $sync->delete();
    }
}
