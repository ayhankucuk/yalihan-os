<?php

namespace App\Actions\Setting;

use App\Models\Setting;

class BulkStoreSettingAction
{
    public function handle(array $settingsData): array
    {
        $created = [];
        foreach ($settingsData as $settingData) {
            $created[] = Setting::create($settingData);
        }

        Setting::clearCache();

        return $created;
    }
}
