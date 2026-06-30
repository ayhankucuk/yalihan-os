<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Kisi;
// ❌ REMOVED: use App\Models\Deprecated\KisiTask; (deprecated)
use App\Services\CRM\KisiScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KisiCRMController extends Controller
{
    protected $scoringService;

    public function __construct(KisiScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Kişinin etkileşim geçmişini getir
     */
    public function getEtkilesimler(int $id): JsonResponse
    {
        $kisi = Kisi::findOrFail($id);

        $etkilesimler = $kisi->etkilesimler()
            ->with('kullanici:id,name,email')
            ->aktif()
            ->sonEtkilesimler(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $etkilesimler,
        ]);
    }

    /**
     * Yeni etkileşim ekle
     */
    public function addEtkilesim(Request $request, int $id): JsonResponse
    {
        $kisi = Kisi::findOrFail($id);

        $validated = $request->validate([
            'tip' => 'required|in:telefon,email,sms,toplanti,whatsapp,not',
            'notlar' => 'nullable|string',
            'etkilesim_tarihi' => 'required|date',
        ]);

        $etkilesim = $kisi->etkilesimler()->create([
            'kullanici_id' => auth()->id(),
            'tip' => $validated['tip'],
            'notlar' => $validated['notlar'],
            'etkilesim_tarihi' => $validated['etkilesim_tarihi'],
            'aktiflik_durumu' => 1, // ✅ Reconciled
        ]);

        // Son etkileşim tarihini güncelle
        $kisi->update(['son_etkilesim' => $validated['etkilesim_tarihi']]);

        // Skorunu yeniden hesapla
        $kisi->update(['skor' => $this->scoringService->calculateScore($kisi)]);

        return response()->json([
            'success' => true,
            'message' => 'Etkileşim kaydedildi',
            'data' => $etkilesim->load('kullanici'),
        ]);
    }

    /**
     * Kişinin task'larını getir
     */
    public function getTasks(int $id): JsonResponse
    {
        $kisi = Kisi::findOrFail($id);

        $tasks = $kisi->tasks()
            ->with(['atananUser:id,name,email', 'olusturanUser:id,name,email'])
            ->orderBy('created_at', 'asc') // context7-ignore
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'bekleyen' => $tasks->where('islem_durumu', '!=', 'tamamlandi')->values(),
                'tamamlanan' => $tasks->where('islem_durumu', 'tamamlandi')->values(),
            ],
        ]);
    }

    /**
     * Yeni task ekle
     * Context7: KisiTask deprecated - method disabled
     */
    public function addTask(Request $request, int $id): JsonResponse
    {
        // ❌ DISABLED: KisiTask table deprecated
        return response()->json([
            'success' => false,
            'message' => 'Task sistemi kullanım dışı (deprecated)',
        ], 410); // 410 Gone
    }

    /**
     * Task güncelle (tamamla/güncelle)
     * Context7: KisiTask deprecated - method disabled
     */
    public function updateTask(Request $request, int $taskId): JsonResponse
    {
        // ❌ DISABLED: KisiTask table deprecated
        return response()->json([
            'success' => false,
            'message' => 'Task sistemi kullanım dışı (deprecated)',
        ], 410); // 410 Gone
    }

    /**
     * Kişinin skorunu hesapla
     */
    public function calculateScore(int $id): JsonResponse
    {
        $kisi = Kisi::findOrFail($id);

        $skor = $this->scoringService->calculateScore($kisi);
        $kisi->update(['skor' => $skor]);

        return response()->json([
            'success' => true,
            'data' => [
                'skor' => $skor,
                'kisi' => $kisi->fresh(),
            ],
        ]);
    }
}
