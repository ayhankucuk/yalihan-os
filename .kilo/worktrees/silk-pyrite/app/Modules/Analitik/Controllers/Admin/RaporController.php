<?php

namespace App\Modules\Analitik\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RaporController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-ilanlar');
    }

    public function index()
    {
        return view('admin.analitik.raporlar.index');
    }

    public function ilanRaporu()
    {
        return view('admin.analitik.raporlar.ilan');
    }

    public function satisRaporu()
    {
        return view('admin.analitik.raporlar.satis');
    }

    public function finansRaporu()
    {
        return view('admin.analitik.raporlar.finans');
    }

    public function musteriRaporu()
    {
        return view('admin.analitik.raporlar.musteri');
    }

    public function performansRaporu()
    {
        return view('admin.analitik.raporlar.performans');
    }

    public function export(Request $request)
    {
        return response()->json(['message' => 'Export feature coming soon']);
    }
}
