<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class FormValidationController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Form Validation endpoint - to be implemented']);
    }
}
