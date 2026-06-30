<?php

namespace App\Actions\Admin\Dashboard;

use App\Models\DashboardWidget;
use App\Services\Cache\CacheHelper;

class UpdateDashboardWidgetAction
{
    public function handle(int $widgetId, array $data, int $userId): DashboardWidget
    {
        $widget = DashboardWidget::forUser($userId)->findOrFail($widgetId);
        $widget->update($data);

        CacheHelper::forget('dashboard', 'data', ['user_id' => $userId]);

        return $widget->fresh();
    }
}
