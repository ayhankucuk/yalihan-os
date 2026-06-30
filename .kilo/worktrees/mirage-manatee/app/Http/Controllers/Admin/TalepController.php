<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talep;
use App\Services\CRM\TalepAuthorityService;
use App\Services\CRM\TalepOrchestrator;
use App\Actions\Admin\Talep\StoreTalepAction;
use App\Actions\Admin\Talep\DeleteTalepAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 🛰️ TalepController
 *
 * Thin proxy for Demand (Talep) management.
 * All complex filtering, stats, and coordination are delegated to TalepOrchestrator.
 * All mutations are handled via TalepAuthorityService or dedicated Actions.
 * @sab-ignore-thin
 * @sab-ignore-catch
 */
class TalepController extends Controller
{
    public function __construct(
        private readonly TalepOrchestrator $orchestrator,
        private readonly TalepAuthorityService $authorityService,
        private readonly StoreTalepAction $storeTalepAction,
        private readonly DeleteTalepAction $deleteTalepAction,
        private readonly \App\Repositories\TalepRepository $repository
    ) {}

    /**
     * Display a listing of Talepler.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Talep::class);

        return view('admin.talepler.index', [
            'talepler'      => $this->orchestrator->getTalepler($request->all()),
            'istatistikler' => $this->orchestrator->getSummaryStats(),
            'statuslar'     => $this->orchestrator->getAvailableStatuses(),
            ...$this->orchestrator->getFormData()
        ]);
    }

    /**
     * Show the form for creating a new Talep.
     */
    public function create(): View
    {
        $this->authorize('create', Talep::class);

        return view('admin.talepler.create', $this->orchestrator->getFormData());
    }

    /**
     * Store a newly created Talep.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'baslik'          => 'required|string|max:255',
            'aciklama'        => 'nullable|string',
            'tip'             => 'required|string|in:Satılık,Kiralık,Günlük Kiralık,Devren',
            'alt_kategori_id' => 'nullable|exists:ilan_kategoriler,id',
            'talep_durumu'    => 'required|string',
            'one_cikan'       => 'nullable|boolean',
            'il_id'           => 'required|exists:iller,id',
            'ilce_id'         => 'nullable|exists:ilceler,id',
            'mahalle_id'      => 'nullable|exists:mahalleler,id',
            'kisi_id'         => 'nullable|exists:kisiler,id',
            'danisman_id'     => 'nullable|exists:users,id',
            'kisi_ad'         => 'nullable|string|max:100',
            'kisi_soyad'      => 'nullable|string|max:100',
            'kisi_telefon'    => 'nullable|string|max:20',
            'kisi_email'      => 'nullable|email|max:100',
        ]);

        try {
            $talep = $this->storeTalepAction->handle($validated);

            return redirect()
                ->route('admin.talepler.show', $talep->id)
                ->with('success', 'Talep başarıyla oluşturuldu! 🎉');
        } catch (\Exception $e) {
            Log::error('Talep store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Talep oluşturulurken hata oluştu.');
        }
    }

    /**
     * Display the specified Talep.
     */
    public function show($id): View
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('view', $talep);              // Layer 1: Capability check

        $talep->load(['kisi', 'danisman', 'kategori', 'altKategori', 'il', 'ilce', 'mahalle']);

        return view('admin.talepler.show', compact('talep'));
    }

    /**
     * Show the form for editing the specified Talep.
     */
    public function edit($id): View
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('update', $talep);            // Layer 1: Capability check

        $talep->load(['kisi', 'danisman', 'kategori', 'altKategori', 'il', 'ilce', 'mahalle']);

        return view('admin.talepler.edit', [
            'talep' => $talep,
            ...$this->orchestrator->getFormData()
        ]);
    }

    /**
     * Update the specified Talep.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('update', $talep);            // Layer 1: Capability check

        $validated = $request->validate([
            'baslik'          => 'required|string|max:255',
            'aciklama'        => 'nullable|string',
            'tip'             => 'required|string|in:Satılık,Kiralık,Günlük Kiralık,Devren',
            'alt_kategori_id' => 'nullable|exists:ilan_kategoriler,id',
            'talep_durumu'    => 'required|string',
            'one_cikan'       => 'nullable|boolean',
            'il_id'           => 'required|exists:iller,id',
            'ilce_id'         => 'nullable|exists:ilceler,id',
            'mahalle_id'      => 'nullable|exists:mahalleler,id',
            'kisi_id'         => 'nullable|exists:kisiler,id',
            'danisman_id'     => 'nullable|exists:users,id',
            'min_fiyat'       => 'nullable|numeric',
            'max_fiyat'       => 'nullable|numeric',
            'notlar'          => 'nullable|string',
        ]);

        try {
            $this->authorityService->updateTalep($talep, $validated, Auth::user());

            return redirect()
                ->route('admin.talepler.show', $talep->id)
                ->with('success', 'Talep başarıyla güncellendi! 🚀');
        } catch (\Exception $e) {
            Log::error('Talep update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Talep güncellenirken hata oluştu.');
        }
    }

    /**
     * Remove the specified Talep.
     */
    public function destroy($id): RedirectResponse
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('delete', $talep);            // Layer 1: Capability check

        try {
            $talepBilgi = $talep->kisi ? ($talep->kisi->ad.' '.$talep->kisi->soyad) : 'Talep #'.$talep->id;
            $this->deleteTalepAction->handle($talep);

            return redirect()
                ->route('admin.talepler.index')
                ->with('success', $talepBilgi.' başarıyla silindi.');
        } catch (\Exception $e) {
            return redirect()->route('admin.talepler.index')->with('error', 'Talep silinirken hata oluştu.');
        }
    }

    /**
     * 🎯 Eşleşme Radarı - Matching Cockpit
     */
    public function showMatches($id): View
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('view', $talep);              // Layer 1: Capability check

        $matches = $this->orchestrator->getMatches($talep);

        return view('admin.talepler.matches', [
            'talep'           => $talep,
            'eslesenIlanlar'  => $matches['eslesenIlanlar'],
            'semanticMatches' => $matches['semanticMatches']
        ]);
    }

    /**
     * 🔍 Eşleşen İlanlar - Legacy route for eslesen view
     */
    public function eslesen($id): View
    {
        $talep = $this->repository->findOrFail($id);  // Layer 2: 404 concealment
        $this->authorize('view', $talep);              // Layer 1: Capability check

        $matches = $this->orchestrator->getMatches($talep);

        return view('admin.talepler.eslesen', [
            'talep'          => $talep,
            'eslesenIlanlar' => $matches['eslesenIlanlar']
        ]);
    }

    /**
     * 🔎 AJAX Search endpoint for talepler
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('q', '');
        $talepler = $this->repository->search($query, 20);

        $mapped = $talepler->map(function ($talep) {
            return [
                'id'    => $talep->id,
                'text'  => $talep->baslik . ' - ' . ($talep->kisi ? $talep->kisi->ad_soyad : 'N/A'),
                'value' => $talep->id
            ];
        });

        return response()->json($mapped);
    }

    /**
     * 📦 Bulk action handler for talepler
     */
    public function bulkAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|integer|exists:talepler,id'
        ]);

        try {
            $count = 0;
            $action = $validated['action'];
            $ids = $validated['ids'];

            foreach ($ids as $id) {
                // Find via repository to enforce tenant boundaries (Fail-Safe Kernel)
                $talep = $this->repository->findById($id);
                if (!$talep) continue;

                match ($action) {
                    'activate'   => $this->authorityService->setOneCikan($talep, true, Auth::user()),
                    'deactivate' => $this->authorityService->setOneCikan($talep, false, Auth::user()),
                    'delete'     => $this->deleteTalepAction->handle($talep),
                    default      => null
                };

                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} talep başarıyla işlendi.",
                'count'   => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Talep bulk action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Toplu işlem sırasında hata oluştu.'
            ], 500);
        }
    }
}
