<?php

namespace App\Modules\Analitik\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RaporApiController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Raporlar API']);
    }

    public function ilanRaporu()
    {
        return response()->json(['message' => 'İlan raporu']);
    }

    public function satisRaporu()
    {
        return response()->json(['message' => 'Satış raporu']);
    }

    public function finansRaporu()
    {
        return response()->json(['message' => 'Finans raporu']);
    }

    public function musteriRaporu()
    {
        return response()->json(['message' => 'Müşteri raporu']);
    }

    public function performansRaporu()
    {
        return response()->json(['message' => 'Performans raporu']);
    }

    public function export(Request $request)
    {
        return response()->json(['message' => 'Export API']);
    }
}
