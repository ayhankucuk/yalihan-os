<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Context7Controller extends Controller
{
    public function sistemDurumu(Request $request)
    {
        $apiKey = config('services.context7.api_key');
        $configured = ! empty($apiKey);

        return response()->json([
            'success' => true,
            'configured' => $configured,
            'connected' => false,
            'transport' => 'http',
        ]);
    }
}
