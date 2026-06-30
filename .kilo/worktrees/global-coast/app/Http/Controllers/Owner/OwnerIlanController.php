<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-service-layer Read-only actions (index, show) use direct model access intentionally.
 * Write actions (create, store) delegate to IlanService → IlanCrudService — SAB zinciri korunur.
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnerIlanRequest;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Services\Ilan\IlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OwnerIlanController
 *
 * Mülk sahibi için portföy (ilan) yönetimi.
 * Read: doğrudan model erişimi (read-only için service gerekmez).
 * Write: IlanService üzerinden — SAB write zinciri korunur.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #15)
 * SAB v3.4.1 — Sprint 3.4.1: create + store eklendi (edit/update out of scope)
 */
class OwnerIlanController extends Controller
{
    /**
     * İlanları listeler.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Mülk sahibinin ilanları (SAB Task #15: user_id = auth user)
        $ilanlar = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(12);

        return view('owner.ilanlar.index', compact('ilanlar'));
    }

    /**
     * İlan detaylarını gösterir.
     */
    public function show($id): View
    {
        $user = auth()->user();

        // İlanın bu mülk sahibine ait olup olmadığını kontrol et
        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'anaKategori', 'altKategori', 'fotograflar', 'danisman'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('owner.ilanlar.show', compact('ilan'));
    }

    /**
     * Yeni portföy oluşturma formunu gösterir.
     *
     * SAB v6.1.2 — Sprint 3.4.1 Product Start: Portfolio Create
     * Owner create + store only. No edit/update.
     */
    public function create(): View
    {
        $anaKategoriler = IlanKategori::whereNull('parent_id')
            ->with('children')
            ->orderBy('display_order') // context7-ignore
            ->get();

        $iller = Il::orderBy('il_adi')->select(['id', 'il_adi'])->get();

        return view('owner.ilanlar.create', [
            'anaKategoriler' => $anaKategoriler,
            'iller' => $iller,
        ]);
    }

    /**
     * Yeni portföyü veritabanına kaydeder.
     *
     * SAB v6.1.2 — Sprint 3.4.1
     * Write authority: IlanService::storeListing() → IlanCrudService::store()
     */
    public function store(StoreOwnerIlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Owner, ilanı kendi user_id ile oluşturur
        $data['user_id'] = auth()->id();

        $ilanService = app(IlanService::class);
        $result = $ilanService->storeListing($data);

        return to_route('owner.ilanlar.show', $result['id'])
            ->with('success', $result['message'] ?? 'Portföy başarıyla oluşturuldu.');
    }
}
