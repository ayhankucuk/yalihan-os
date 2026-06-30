<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class CustomerProfileController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Customer Profile endpoint - to be implemented']);
    }

    public function show(Request $request, $customerId)
    {
        return response()->json([
            'message' => 'Customer Profile endpoint - to be implemented',
            'customer_id' => $customerId,
        ]);
    }
}
