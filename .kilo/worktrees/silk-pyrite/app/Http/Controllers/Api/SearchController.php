<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        return ResponseService::success([], 'Search endpoint - to be implemented');
    }

    public function getCompatiblePropertyTypes(Request $request)
    {
        return ResponseService::success([
            'property_types' => [],
        ], 'Uyumlu tipler');
    }

    public function getDefaultPropertyTypes(Request $request)
    {
        return ResponseService::success([
            'property_types' => [],
        ], 'Varsayılan tipler');
    }
}
