<?php

namespace App\Actions\Admin\Intelligence;

use App\Models\MarketIntelligenceSetting;

class ToggleMarketSettingAction
{
    public function handle(MarketIntelligenceSetting $setting): MarketIntelligenceSetting
    {
        $setting->aktiflik_durumu = !$setting->aktiflik_durumu;
        $setting->save();
        return $setting->fresh();
    }
}
