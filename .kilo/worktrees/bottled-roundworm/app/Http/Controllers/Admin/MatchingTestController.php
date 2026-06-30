<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Talep;
use App\Services\Matching\DemandMatchingEngine;
use Illuminate\Http\Request;

/**
 * 🎯 Eşleşme Test Controller
 * 
 * Matching Engine'i test etmek ve sonuçları görselleştirmek için
 */
class MatchingTestController extends AdminController
{
    protected DemandMatchingEngine $matchingEngine;

    public function __construct(DemandMatchingEngine $matchingEngine)
    {
        $this->matchingEngine = $matchingEngine;
    }

    /**
     * Bir talep için eşleşmeleri göster
     */
    public function showMatches($talepId)
    {
        $talep = Talep::with(['kisi', 'altKategori'])->findOrFail($talepId);
        
        // 🎯 Eşleşme Motoru'nu çalıştır
        $eslesenIlanlar = $this->matchingEngine->matchDemand($talep, 20);

        return view('admin.matching.show', compact('talep', 'eslesenIlanlar'));
    }

    /**
     * Toplu eşleştirme testi
     */
    public function testBulkMatching()
    {
        $istatistikler = $this->matchingEngine->matchAllPendingDemands();

        return response()->json([
            'success' => true,
            'message' => '🎯 Toplu eşleştirme tamamlandı',
            'data' => $istatistikler,
        ]);
    }
}
