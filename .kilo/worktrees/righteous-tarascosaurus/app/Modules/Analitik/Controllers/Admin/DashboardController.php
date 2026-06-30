<?php

namespace App\Modules\Analitik\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.analitik.dashboard');
    }

    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Analitik dashboard data',
            ],
        ]);
    }
}
