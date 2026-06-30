<?php

namespace App\Actions\Admin\Dashboard;

use App\Models\DashboardWidget;
use App\Services\Cache\CacheHelper;

class DeleteDashboardWidgetAction
{
    public function handle(int $widgetId, int $userId): bool
    {
        $widget = DashboardWidget::forUser($userId)->findOrFail($widgetId);
        $deleted = (bool) $widget->delete();

        CacheHelper::forget('dashboard', 'data', ['user_id' => $userId]);

        return $deleted;
    }
}
