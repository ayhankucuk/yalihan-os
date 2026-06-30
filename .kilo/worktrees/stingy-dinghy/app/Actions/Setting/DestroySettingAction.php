<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class DestroySettingAction
{
    public function handle(Setting $setting): void
    {
        $group = $setting->group;
        $setting->delete();
        Cache::forget('settings');
        Cache::forget('settings.' . $group);
    }
}
