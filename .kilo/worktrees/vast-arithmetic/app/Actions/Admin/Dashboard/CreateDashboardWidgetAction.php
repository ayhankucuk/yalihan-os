<?php

namespace App\Actions\Admin\Dashboard;

use App\Models\DashboardWidget;
use App\Services\Cache\CacheHelper;

class CreateDashboardWidgetAction
{
    public function handle(array $data, int $userId): DashboardWidget
    {
        $widget = DashboardWidget::create(array_merge($data, ['user_id' => $userId]));

        CacheHelper::forget('dashboard', 'data', ['user_id' => $userId]);

        return $widget;
    }
}
