<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CreateSettingAction
{
    public function handle(array $data): Setting
    {
        $setting = Setting::create($data);
        Cache::forget('settings');
        Cache::forget('settings.' . $setting->group);
        return $setting;
    }
}
