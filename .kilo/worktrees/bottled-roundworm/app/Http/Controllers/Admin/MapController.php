<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class MapController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Map endpoint - to be implemented']);
    }

    public function nearbyPreview(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }
}
