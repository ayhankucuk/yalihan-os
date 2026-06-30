<?php

namespace App\Modules\Finans\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finans\Models\FinansalIslem;
use App\Modules\Finans\Services\FinansService;
use App\Modules\Finans\Services\FinansalIslemManager;
use App\DataTransferObjects\Finans\CreateFinansalIslemCommand;
use App\DataTransferObjects\Finans\UpdateFinansalIslemCommand;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Enums\FinansalIslemDurumu;
use Illuminate\Support\Facades\Validator;

/**
 * Financial Transaction Controller
 *
 * Context7 Standardı: C7-FINANS-CONTROLLER-2025-11-25
 * [Refactored: Thin Controller - DB writes moved to Manager]
 *
 * CRUD operations + AI-powered analysis and suggestions
 */
class FinansalIslemController extends Controller
{
    protected FinansService $finansService;
    protected FinansalIslemManager $islemManager;

    public function __construct(FinansService $finansService, FinansalIslemManager $islemManager)
    {
        $this->finansService = $finansService;
        $this->islemManager = $islemManager;
    }

    /**
     * List all financial transactions
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = FinansalIslem::with(['ilan', 'kisi', 'gorev', 'onaylayan']);

            // Filters
            if ($request->has('islem_statusu')) {
                $query->where('islem_statusu', $request->input('islem_statusu'));
            }

            if ($request->has('islem_tipi')) {
                $query->where('islem_tipi', $request->input('islem_tipi'));
            }

            if ($request->has('kisi_id')) {
                $query->where('kisi_id', $request->input('kisi_id'));
            }

            if ($request->has('ilan_id')) {
                $query->where('ilan_id', $request->input('ilan_id'));
            }

            if ($request->has('start_date')) {
                $query->where('tarih', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('tarih', '<=', $request->input('end_date'));
            }

            $islemler = $query->orderBy('tarih', 'desc')
                ->paginate($request->input('per_page', 20));

            // If AJAX request, return JSON
            if ($request->wantsJson() || $request->ajax()) {
                return ResponseService::success($islemler, 'Finansal işlemler başarıyla getirildi');
            }

            // Return view for web requests
            return view('admin.finans.islemler.index', [
                'islemler' => $islemler
            ]);
        } catch (\Exception $e) {
            // If AJAX request, return JSON error
            if ($request->wantsJson() || $request->ajax()) {
                return ResponseService::serverError('Finansal işlemler getirilemedi', $e);
            }

            // Return view with error for web requests
            return view('admin.finans.islemler.index', [
                'islemler' => collect([])
            ])->with('error', 'Finansal işlemler yüklenirken bir hata oluştu.');
        }
    }

    /**
     * Show single financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $islem = FinansalIslem::with(['ilan', 'kisi', 'gorev', 'onaylayan'])->findOrFail($id);

            return ResponseService::success($islem, 'Finansal işlem başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::notFound('Finansal işlem bulunamadı');
        }
    }

    /**
     * Create new financial transaction
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'kisi_id' => 'nullable|exists:kisiler,id',
            'gorev_id' => 'nullable|exists:gorevler,id',
            'islem_tipi' => 'required|string|in:komisyon,odeme,masraf,gelir,gider',
            'miktar' => 'required|numeric|min:0',
            'para_birimi' => 'required|string|max:3',
            'aciklama' => 'nullable|string|max:1000',
            'tarih' => 'required|date',
            'referans_no' => 'nullable|string|max:100',
            'fatura_no' => 'nullable|string|max:100',
            'notlar' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            // If AJAX request, return JSON
            if ($request->wantsJson() || $request->ajax()) {
                return ResponseService::validationError($validator->errors()->toArray());
            }

            // Return back with errors for web requests
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->authorize('create', FinansalIslem::class);

            $cmd = CreateFinansalIslemCommand::fromRequest($request->all());
            $islem = $this->islemManager->createIslem($cmd);

            // If AJAX request, return JSON
            if ($request->wantsJson() || $request->ajax()) {
                return ResponseService::success($islem, 'Finansal işlem başarıyla oluşturuldu');
            }

            // Redirect for web requests
            return redirect()->route('admin.finans.islemler.index')
                ->with('success', 'Finansal işlem başarıyla oluşturuldu');
        } catch (\Exception $e) {
            // If AJAX request, return JSON error
            if ($request->wantsJson() || $request->ajax()) {
                return ResponseService::serverError('Finansal işlem oluşturulamadı', $e);
            }

            // Return back with error for web requests
            return redirect()->back()
                ->with('error', 'Finansal işlem oluşturulurken bir hata oluştu.')
                ->withInput();
        }
    }

    /**
     * Update financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'islem_tipi' => 'sometimes|string|in:komisyon,odeme,masraf,gelir,gider',
            'miktar' => 'sometimes|numeric|min:0',
            'para_birimi' => 'sometimes|string|max:3',
            'aciklama' => 'nullable|string|max:1000',
            'tarih' => 'sometimes|date',
            'islem_statusu' => 'sometimes|string|in:bekliyor,onaylandi,reddedildi,tamamlandi',
            'referans_no' => 'nullable|string|max:100',
            'fatura_no' => 'nullable|string|max:100',
            'notlar' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $islem = FinansalIslem::findOrFail($id);
            $this->authorize('update', $islem);

            $cmd = UpdateFinansalIslemCommand::fromRequest($request->all());
            $islem = $this->islemManager->updateIslem($islem, $cmd);

            return ResponseService::success($islem, 'Finansal işlem başarıyla güncellendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Finansal işlem güncellenemedi', $e);
        }
    }

    /**
     * Delete financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $islem = FinansalIslem::findOrFail($id);
            $this->authorize('delete', $islem);

            $this->islemManager->deleteIslem($islem);

            return ResponseService::success(null, 'Finansal işlem başarıyla silindi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Finansal işlem silinemedi', $e);
        }
    }

    /**
     * Approve financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, int $id)
    {
        try {
            $islem = FinansalIslem::findOrFail($id);
            $this->authorize('approve', $islem);
            
            $onaylayanId = $request->user()->id;
            $islem = $this->islemManager->approveIslem($islem, $onaylayanId);

            return ResponseService::success($islem, 'Finansal işlem başarıyla onaylandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Finansal işlem onaylanamadı', $e);
        }
    }

    /**
     * Reject financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'not' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $islem = FinansalIslem::findOrFail($id);
            $this->authorize('approve', $islem);

            $onaylayanId = $request->user()->id;
            $islem = $this->islemManager->rejectIslem($islem, $onaylayanId, $request->input('not'));

            return ResponseService::success($islem, 'Finansal işlem başarıyla reddedildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Finansal işlem reddedilemedi', $e);
        }
    }

    /**
     * Complete financial transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(int $id)
    {
        try {
            $islem = FinansalIslem::findOrFail($id);
            $this->authorize('update', $islem);
            
            $islem = $this->islemManager->completeIslem($islem);

            return ResponseService::success($islem, 'Finansal işlem başarıyla tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Finansal işlem tamamlanamadı', $e);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🤖 AI-POWERED ENDPOINTS
    // ═══════════════════════════════════════════════════════════

    public function aiAnalyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisi_id' => 'nullable|exists:kisiler,id',
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $this->authorize('viewAny', FinansalIslem::class);

            $query = FinansalIslem::query();

            if ($request->has('kisi_id')) {
                $query->where('kisi_id', $request->input('kisi_id'));
            }

            if ($request->has('ilan_id')) {
                $query->where('ilan_id', $request->input('ilan_id'));
            }

            if ($request->has('start_date')) {
                $query->where('tarih', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('tarih', '<=', $request->input('end_date'));
            }

            $data = $query->get()->map(function ($islem) {
                return [
                    'tarih' => $islem->tarih->format('Y-m-d'),
                    'islem_tipi' => $islem->islem_tipi,
                    'miktar' => $islem->miktar,
                    'para_birimi' => $islem->para_birimi,
                    'islem_statusu' => $islem->islem_statusu,
                ];
            })->toArray();

            $result = $this->finansService->analyzeFinancials($data, [
                'kisi_id' => $request->input('kisi_id'),
                'ilan_id' => $request->input('ilan_id'),
            ]);

            return ResponseService::success($result, 'AI finansal analiz tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI finansal analiz başarısız', $e);
        }
    }

    public function aiPredict(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisi_id' => 'nullable|exists:kisiler,id',
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'period' => 'required|string|in:month,quarter,year',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $this->authorize('viewAny', FinansalIslem::class);

            $result = $this->finansService->predictFinancials(
                $request->input('kisi_id'),
                $request->input('ilan_id'),
                $request->input('period')
            );

            return ResponseService::success($result, 'AI finansal tahmin tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI finansal tahmin başarısız', $e);
        }
    }

    public function aiSuggestInvoice(int $id)
    {
        $this->authorize('viewAny', FinansalIslem::class);

        try {
            $islem = FinansalIslem::findOrFail($id);
            $result = $this->finansService->suggestInvoice($islem);

            return ResponseService::success($result, 'AI fatura önerisi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI fatura önerisi başarısız', $e);
        }
    }

    public function aiAnalyzeRisk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisi_id' => 'nullable|exists:kisiler,id',
            'ilan_id' => 'nullable|exists:ilanlar,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $this->authorize('viewAny', FinansalIslem::class);

            $result = $this->finansService->analyzeRisk(
                $request->input('kisi_id'),
                $request->input('ilan_id')
            );

            return ResponseService::success($result, 'AI risk analizi tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI risk analizi başarısız', $e);
        }
    }

    public function aiGenerateSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'islem_tipi' => 'nullable|string|in:komisyon,odeme,masraf,gelir,gider',
            'islem_statusu' => 'nullable|string|in:bekliyor,onaylandi,reddedildi,tamamlandi',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        try {
            $this->authorize('viewAny', FinansalIslem::class);

            $filters = $request->only(['start_date', 'end_date', 'islem_tipi', 'islem_statusu']);
            $result = $this->finansService->generateSummaryReport($filters);

            return ResponseService::success($result, 'AI özet rapor oluşturuldu');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI özet rapor oluşturulamadı', $e);
        }
    }
}
