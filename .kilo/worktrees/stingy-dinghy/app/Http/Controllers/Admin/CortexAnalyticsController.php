<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CortexAnalyticsController extends Controller
{
    /**
     * Display the Cortex Analytics Dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // We can pass initial data or config here if needed.
        // For now, the dashboard relies on Alpine.js fetching data from API.

        return view('admin.analytics.cortex-dashboard');
    }
}
