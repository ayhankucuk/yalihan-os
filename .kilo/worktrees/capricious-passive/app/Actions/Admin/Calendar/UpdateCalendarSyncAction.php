<?php

namespace App\Actions\Admin\Calendar;

use App\Models\IlanTakvimSync;

class UpdateCalendarSyncAction
{
    public function handle(IlanTakvimSync $sync, array $data): IlanTakvimSync
    {
        if (isset($data['sync_status'])) {
            $data['is_sync_active'] = (int) $data['sync_status'];
            unset($data['sync_status']);
        }

        $sync->update($data);
        return $sync->fresh();
    }
}
