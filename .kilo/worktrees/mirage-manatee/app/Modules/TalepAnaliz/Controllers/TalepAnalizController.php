<?php

namespace App\Modules\TalepAnaliz\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\TalepTopluAnalizJob;
use App\Models\Talep;
use App\Modules\TalepAnaliz\Services\AIAnalizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TalepAnalizController extends Controller
{
    protected AIAnalizService $aiAnalizService;

    public function __construct(AIAnalizService $analizService)
    {
        // $this->middleware('auth');
        // $this->middleware('role:admin,danisman');
        $this->aiAnalizService = $analizService;
    }

    public function index()
    {
        $talepler = Talep::with('kullanici', 'il', 'ilce')->latest()->paginate(10);

        return view('admin.talepler.analiz_index', compact('talepler')); // View yolu düzeltildi
    }

    public function analizEt(Request $request, $id)
    {
        $talep = Talep::findOrFail($id);
        $sonuc = $this->aiAnalizService->analizEt($talep);

        return view('admin.talepler.analiz_detay', compact('talep', 'sonuc')); // View yolu düzeltildi
    }

    /**
     * Toplu talep analizi
     *
     * Context7 Standardı: C7-TALEP-TOPLU-ANALIZ-2025-11-05
     *
     * Birden fazla talebi queue'da analiz eder
     * Progress tracking ile ilerleme takibi yapar
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function topluAnalizEt(Request $request)
    {
        $request->validate([
            'talep_ids' => 'required|array|min:1|max:100',
            'talep_ids.*' => 'required|exists:talepler,id',
        ]);

        $talepIds = $request->talep_ids;
        $jobId = 'talep_analiz_'.Str::uuid()->toString();

        // Job'ı queue'ya ekle
        TalepTopluAnalizJob::dispatch($talepIds, $jobId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Toplu analiz başlatıldı',
                'job_id' => $jobId,
                'total' => count($talepIds),
                'progress_url' => route('admin.talep-analiz.progress', $jobId),
            ]);
        }

        return redirect()->route('admin.talepler.analiz.index')
            ->with('success', 'Toplu analiz başlatıldı. Job ID: '.$jobId)
            ->with('job_id', $jobId);
    }

    /**
     * Analiz progress'ini getir
     *
     * GET /admin/talepler/analiz/progress/{jobId}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress(string $jobId)
    {
        $progress = Cache::get("talep_toplu_analiz_{$jobId}_progress");
        $results = Cache::get("talep_toplu_analiz_{$jobId}_results");

        if (! $progress) {
            return response()->json([
                'success' => false,
                'message' => 'Job bulunamadı veya süresi dolmuş',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'results' => $results,
        ]);
    }

    /**
     * Analiz sonuçlarını getir
     *
     * GET /admin/talepler/analiz/results/{jobId}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResults(string $jobId)
    {
        $results = Cache::get("talep_toplu_analiz_{$jobId}_results");

        if (! $results) {
            return response()->json([
                'success' => false,
                'message' => 'Sonuçlar bulunamadı veya süresi dolmuş',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function testSayfasi()
    {
        return view('admin.talepler.analiz_test'); // View yolu düzeltildi
    }

    /**
     * Talep analiz raporu oluştur
     *
     * Context7 Standardı: C7-TALEP-RAPOR-2025-11-05
     *
     * PDF ve Excel rapor oluşturur
     * TalepRaporController'a yönlendirir
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function raporOlustur(Request $request, $id)
    {
        $raporController = app(\App\Http\Controllers\Admin\TalepRaporController::class);

        return $raporController->raporOlustur($request, $id);
    }
}
