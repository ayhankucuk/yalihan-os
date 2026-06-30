<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class DanismanController extends Controller
{
    public function index(Request $request)
    {
        return ResponseService::success([], 'Danışman endpoint - to be implemented');
    }

    public function show($id)
    {
        return ResponseService::success([
            'id' => (int) $id,
        ], 'Danışman detayı');
    }
}
