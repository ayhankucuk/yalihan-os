<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class ValidationController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Validation endpoint - to be implemented']);
    }
}
