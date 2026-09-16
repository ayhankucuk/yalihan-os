<?php

namespace App\Http\Controllers\Admin;

use App\Domain\CRM\DTOs\TalepListCriteria;
use App\Domain\CRM\Services\CreateTalepUseCase;
use App\Domain\CRM\Services\ListTaleplerUseCase;
use App\Domain\CRM\Services\DeleteTalepUseCase;
use App\Domain\CRM\Services\SearchTaleplerUseCase;
use App\Domain\CRM\Services\UpdateTalepUseCase;
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
 * Strangler Fig: Delegates to Domain UseCases when config('crm.use_domain_talep') is true.
 * Falls back to legacy orchestrator/authority service when false.
 *
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
        private readonly \App\Repositories\TalepRepository $repository,
        private readonly ?ListTaleplerUseCase $listTaleplerUseCase = null,
        private readonly ?CreateTalepUseCase $createTalepUseCase = null,
        private readonly ?UpdateTalepUseCase $updateTalepUseCase = null,
        private readonly ?DeleteTalepUseCase $deleteTalepUseCase = null,
        private readonly ?SearchTaleplerUseCase $searchTaleplerUseCase = null
    ) {}

    /**
     * Display a listing of Talepler.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Talep::class);

        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            $criteria = TalepListCriteria::fromArray($request->all());
            return view('admin.talepler.index', [
                'talepler'      => $this->listTaleplerUseCase->execute($criteria),
                'istatistikler' => $this->listTaleplerUseCase->getSummaryStats(),
                'statuslar'     => $this->listTaleplerUseCase->getAvailableStatuses(),
                ...$this->listTaleplerUseCase->getFormData()
            ]);
        }

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

        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            return view('admin.talepler.create', $this->listTaleplerUseCase->getFormData());
        }

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
            if (config('crm.use_domain_talep', false) && $this->createTalepUseCase) {
                $talep = $this->createTalepUseCase->executeFromSpillover($validated, Auth::user());
            } else {
                $talep = $this->storeTalepAction->handle($validated);
            }

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
        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            $talep = $this->listTaleplerUseCase->findOrFail((int) $id);
        } else {
            $talep = $this->repository->findOrFail($id);
        }
        $this->authorize('view', $talep);              // Layer 1: Capability check

        $talep->load(['kisi', 'danisman', 'kategori', 'altKategori', 'il', 'ilce', 'mahalle']);

        return view('admin.talepler.show', compact('talep'));
    }

    /**
     * Show the form for editing the specified Talep.
     */
    public function edit($id): View
    {
        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            $talep = $this->listTaleplerUseCase->findOrFail((int) $id);
            $formData = $this->listTaleplerUseCase->getFormData();
        } else {
            $talep = $this->repository->findOrFail($id);
            $formData = $this->orchestrator->getFormData();
        }
        $this->authorize('update', $talep);            // Layer 1: Capability check

        $talep->load(['kisi', 'danisman', 'kategori', 'altKategori', 'il', 'ilce', 'mahalle']);

        return view('admin.talepler.edit', [
            'talep' => $talep,
            ...$formData
        ]);
    }

    /**
     * Update the specified Talep.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            $talep = $this->listTaleplerUseCase->findOrFail((int) $id);
        } else {
            $talep = $this->repository->findOrFail($id);
        }
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
            if (config('crm.use_domain_talep', false) && $this->updateTalepUseCase) {
                $this->updateTalepUseCase->execute($talep, $validated, Auth::user());
            } else {
                $this->authorityService->updateTalep($talep, $validated, Auth::user());
            }

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
        if (config('crm.use_domain_talep', false) && $this->listTaleplerUseCase) {
            $talep = $this->listTaleplerUseCase->findOrFail((int) $id);
        } else {
            $talep = $this->repository->findOrFail($id);
        }
        $this->authorize('delete', $talep);            // Layer 1: Capability check

        try {
            $talepBilgi = $talep->kisi ? ($talep->kisi->ad.' '.$talep->kisi->soyad) : 'Talep #'.$talep->id;

            if (config('crm.use_domain_talep', false) && $this->deleteTalepUseCase) {
                $this->deleteTalepUseCase->execute($talep, Auth::user());
            } else {
                $this->deleteTalepAction->handle($talep);
            }

            return redirect()
                ->route('admin.talepler.index')
                ->with('success', $talepBilgi.' başarıyla silindi.');
        } catch (\Exception $e) {
            return redirect()->route('admin.talepler.index')->with('error', 'Talep silinirken hata oluştu.');
        }
    }

    /**
     * Sprint 4.2: Restore soft-deleted Talep
     */
    public function restore(Request $request, $id): RedirectResponse
    {
        $talep = $this->repository->findOrFail((int) $id);
        $this->authorize('restore', $talep);

        $baslik = $talep->baslik;
        $restored = $this->repository->restore((int) $id);

        if (!$restored) {
            return redirect()
                ->route('admin.talepler.index')
                ->with('error', '"' . $baslik . '" geri yüklenemedi.');
        }

        return redirect()
            ->route('admin.talepler.index')
            ->with('success', '"' . $baslik . '" başarıyla geri yüklendi.');
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

        if (config('crm.use_domain_talep', false) && $this->searchTaleplerUseCase) {
            $talepler = $this->searchTaleplerUseCase->execute($query, 20);
        } else {
            $talepler = $this->repository->search($query, 20);
        }

        $mapped = $talepler->map(function ($talep) {
            $kisiAd = $talep->kisi ? ($talep->kisi->tam_ad ?? ($talep->kisi->ad . ' ' . $talep->kisi->soyad)) : 'N/A';
            return [
                'id'    => $talep->id,
                'text'  => $talep->baslik . ' - ' . $kisiAd,
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
