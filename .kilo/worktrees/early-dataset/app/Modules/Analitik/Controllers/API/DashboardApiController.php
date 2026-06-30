<?php

namespace App\Modules\Analitik\Controllers\API;

use App\Http\Controllers\Controller;

class DashboardApiController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Dashboard API']);
    }

    public function overview()
    {
        return response()->json(['message' => 'Overview data']);
    }

    public function charts()
    {
        return response()->json(['message' => 'Charts data']);
    }

    public function recentActivities()
    {
        return response()->json(['message' => 'Recent activities']);
    }
}
