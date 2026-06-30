<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\UpsTemplate;
use Illuminate\Http\Request;

/**
 * @deprecated 2026-04-05 Legacy UPS analytics stub. Minimal implementation.
 * ⚠️ QUARANTINE: Do not add new methods. Route active (admin.php L1602).
 * Target: migrate analytics to PropertyHub dashboard or dedicated analytics controller.
 */
class UpsAnalyticsController extends Controller
{
    /**
     * UPS Analytics Dashboard
     * Displays usage stats, adoption rates, and template performance.
     */
    public function index()
    {
        // Basic stats for now
        $stats = [
            'total_templates' => UpsTemplate::count(),
            'total_listings' => Ilan::count(),
            'active_templates' => UpsTemplate::aktif()->count(),
        ];

        return view('admin.ups.analytics.index', compact('stats'));
    }
}
