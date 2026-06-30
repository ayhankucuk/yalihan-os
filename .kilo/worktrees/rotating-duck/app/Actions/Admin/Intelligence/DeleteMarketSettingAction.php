<?php

namespace App\Actions\Admin\Intelligence;

use App\Models\MarketIntelligenceSetting;

class DeleteMarketSettingAction
{
    public function handle(MarketIntelligenceSetting $setting): bool
    {
        return $setting->delete();
    }
}
