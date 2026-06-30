<?php

namespace App\Actions\Admin\Calendar;

use App\Models\IlanTakvimSync;

class CreateCalendarSyncAction
{
    public function handle(int $ilanId, array $data): IlanTakvimSync
    {
        return IlanTakvimSync::create([
            'ilan_id' => $ilanId,
            'platform' => $data['platform'],
            'external_listing_id' => $data['external_listing_id'],
            'is_sync_active' => (int) ($data['sync_status'] ?? 1),
            'senkron_durumu' => 'active',
            'last_sync_at' => null,
        ]);
    }
}
