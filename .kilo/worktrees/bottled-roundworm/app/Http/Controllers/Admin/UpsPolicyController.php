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

/**
 * @deprecated 2026-04-05 Legacy UPS stub. Only index() implemented.
 * ⚠️ QUARANTINE: Do not add new methods. Routes still active (admin.php L811-815).
 * Target: delete after route audit confirms PropertyHub covers all functionality.
 */
class UpsPolicyController extends Controller
{
    public function index()
    {
        return view('admin.ups.policies.index');
    }
}
