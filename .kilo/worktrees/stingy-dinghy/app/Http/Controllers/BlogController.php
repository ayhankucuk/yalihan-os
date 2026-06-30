<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Blog endpoint - to be implemented']);
    }
}
