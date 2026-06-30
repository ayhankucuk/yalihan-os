<?php

namespace App\Modules\Analitik\Controllers\API;

use App\Http\Controllers\Controller;

class IstatistikApiController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'İstatistikler API']);
    }

    public function genel()
    {
        return response()->json(['message' => 'Genel istatistikler']);
    }

    public function ilan()
    {
        return response()->json(['message' => 'İlan istatistikleri']);
    }

    public function satis()
    {
        return response()->json(['message' => 'Satış istatistikleri']);
    }

    public function finans()
    {
        return response()->json(['message' => 'Finans istatistikleri']);
    }

    public function musteri()
    {
        return response()->json(['message' => 'Müşteri istatistikleri']);
    }

    public function trends()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'trendler' => [],
            ],
        ]);
    }
}
