<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class UpdateSettingAction
{
    public function handle(Setting $setting, array $data): Setting
    {
        $setting->update($data);
        Cache::forget('settings');
        Cache::forget('settings.' . $setting->group);
        return $setting;
    }
}
