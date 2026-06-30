<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class TalepController extends Controller
{
    public function index(Request $request)
    {
        return ResponseService::success([], 'Talep endpoint - to be implemented');
    }
}
