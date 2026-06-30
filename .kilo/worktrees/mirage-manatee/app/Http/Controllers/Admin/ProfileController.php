<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class ProfileController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Profile endpoint - to be implemented']);
    }
}
