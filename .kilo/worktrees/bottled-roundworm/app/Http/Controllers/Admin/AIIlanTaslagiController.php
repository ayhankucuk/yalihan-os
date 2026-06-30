<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AIIlanTaslagi;
use App\Services\AI\AIIlanTaslagiService;
use App\Actions\Admin\AI\RejectAITaslakAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI İlan Taslağı Controller
 *
 * Context7 Standardı: C7-AI-ILAN-TASLAGI-CONTROLLER-2025-11-20
 *
 * Admin panel'de AI ile oluşturulan ilan taslaklarını yönetir.
 * Onay, reddetme ve yayınlama işlemlerini gerçekleştirir.
 */
class AIIlanTaslagiController extends Controller
{
    protected AIIlanTaslagiService $ilanTaslagiService;

    public function __construct(AIIlanTaslagiService $ilanTaslagiService)
    {
        $this->ilanTaslagiService = $ilanTaslagiService;
    }

    /**
     * İlan taslaklarını listele
     */
    public function index(Request $request)
    {
        $query = AIIlanTaslagi::with(['danisman', 'ilan', 'approver']);

        // Ownership scope: non-admin kullanıcılar sadece kendi taslaklarını görür.
        $currentUser = auth()->user();
        $isAdmin = $currentUser && (
            (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) ||
            (method_exists($currentUser, 'hasRole') && $currentUser->hasRole(['admin', 'super-admin']))
        );
        if (!$isAdmin) {
            $query->where('danisman_id', $currentUser->id);
        }

        // Filtreleme
        if ($request->has('yayin_durumu')) {
            $query->where('yayin_durumu', $request->yayin_durumu);
        }

        // danisman_id filtresi YALNIZCA admin kullanıcılara açıktır.
        if ($isAdmin && $request->has('danisman_id')) {
            $query->where('danisman_id', $request->danisman_id);
        }

        // Arama
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('danisman', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $taslaklar = $query->orderBy('created_at', 'desc')->paginate(20); // context7-ignore

        return view('admin.ai.ilan-taslaklari.index', compact('taslaklar'));
    }

    /**
     * İlan taslağı detayını göster
     */
    public function show($id)
    {
        $taslak = AIIlanTaslagi::with(['danisman', 'ilan', 'approver'])->findOrFail($id);

        return view('admin.ai.ilan-taslaklari.show', compact('taslak'));
    }

    /**
     * İlan taslağını onayla
     */
    public function approve(Request $request, $id)
    {
        try {
            $taslak = AIIlanTaslagi::findOrFail($id);
            $approverId = auth()->id();

            $taslak = $this->ilanTaslagiService->approveDraft($taslak->id, $approverId);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'İlan taslağı onaylandı',
                    'data' => $taslak,
                ]);
            }

            return redirect()->back()->with('success', 'İlan taslağı onaylandı');

        } catch (\Exception $e) {
            Log::error('İlan taslağı onaylama hatası', [
                'error' => $e->getMessage(),
                'taslak_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Onaylama sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Onaylama sırasında hata oluştu');
        }
    }

    /**
     * İlan taslağını reddet
     */
    public function reject(Request $request, $id)
    {
        try {
            $taslak = AIIlanTaslagi::findOrFail($id);

            $request->validate([
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            app(RejectAITaslakAction::class)->handle($taslak);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'İlan taslağı reddedildi',
                    'data' => $taslak,
                ]);
            }

            return redirect()->back()->with('success', 'İlan taslağı reddedildi');

        } catch (\Exception $e) {
            Log::error('İlan taslağı reddetme hatası', [
                'error' => $e->getMessage(),
                'taslak_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reddetme sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Reddetme sırasında hata oluştu');
        }
    }

    /**
     * İlan taslağını yayınla
     */
    public function publish(Request $request, $id)
    {
        try {
            $taslak = AIIlanTaslagi::findOrFail($id);

            if ($taslak->yayin_durumu !== 'approved') {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sadece onaylanmış taslaklar yayınlanabilir',
                    ], 400);
                }

                return redirect()->back()->with('error', 'Sadece onaylanmış taslaklar yayınlanabilir');
            }

            $ilan = $this->ilanTaslagiService->publishDraft($taslak->id);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'İlan başarıyla yayınlandı',
                    'data' => [
                        'taslak' => $taslak->fresh(),
                        'ilan' => $ilan,
                    ],
                ]);
            }

            return redirect()->route('admin.ilanlar.edit', $ilan->id)
                ->with('success', 'İlan başarıyla yayınlandı');

        } catch (\Exception $e) {
            Log::error('İlan taslağı yayınlama hatası', [
                'error' => $e->getMessage(),
                'taslak_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yayınlama sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Yayınlama sırasında hata oluştu');
        }
    }

    /**
     * İlan taslağını sil
     */
    public function destroy($id)
    {
        try {
            $taslak = AIIlanTaslagi::findOrFail($id);
            $taslak->delete();

            return redirect()->route('admin.ai.ilan-taslaklari.index')
                ->with('success', 'İlan taslağı silindi');

        } catch (\Exception $e) {
            Log::error('İlan taslağı silme hatası', [
                'error' => $e->getMessage(),
                'taslak_id' => $id,
            ]);

            return redirect()->back()->with('error', 'Silme sırasında hata oluştu');
        }
    }
}
