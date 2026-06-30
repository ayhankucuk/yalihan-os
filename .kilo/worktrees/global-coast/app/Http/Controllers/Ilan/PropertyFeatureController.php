<?php

namespace App\Http\Controllers\Ilan;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyFeatureController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Property Feature endpoint - to be implemented']);
    }

    public function show($propertyId)
    {
        return response()->json([
            'success' => true,
            'property_id' => $propertyId,
            'features' => [],
        ]);
    }
}
