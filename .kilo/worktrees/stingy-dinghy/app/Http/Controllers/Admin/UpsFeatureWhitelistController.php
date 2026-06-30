<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\CategoryFeatureWhitelist;
use App\Models\IlanKategori;
use App\Services\Admin\AdminSettingsCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Actions\Admin\Ups\StoreFeatureWhitelistAction;
use App\Actions\Admin\Ups\UpdateFeatureWhitelistAction;
use App\Actions\Admin\Ups\DeleteFeatureWhitelistAction;

class UpsFeatureWhitelistController extends Controller
{
    /**
     * Display a listing of the whitelist entries.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $entries = CategoryFeatureWhitelist::with('kategori')
            ->orderBy('kategori_id') // context7-ignore
            ->orderBy('feature_category_slug') // context7-ignore
            ->paginate($perPage)
            ->withQueryString();

        $kategoriler = IlanKategori::orderBy('name')->get(['id', 'name']);

        return view('admin.ups-feature-whitelist.index', [
            'entries' => $entries,
            'kategoriler' => $kategoriler,
        ]);
    }

    /**
     * Show the form for creating a new whitelist entry.
     */
    public function create(): View
    {
        $kategoriler = IlanKategori::orderBy('name')->get(['id', 'name']);

        return view('admin.ups-feature-whitelist.form', [
            'entry' => new CategoryFeatureWhitelist(),
            'kategoriler' => $kategoriler,
            'mode' => 'create',
        ]);
    }

    /**
     * Store a newly created whitelist entry.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        app(StoreFeatureWhitelistAction::class)->handle($data);

        return redirect()
            ->route('admin.ups-feature-whitelist.index')
            ->with('success', 'Whitelist girişi eklendi');
    }

    /**
     * Show the form for editing the specified whitelist entry.
     */
    public function edit(CategoryFeatureWhitelist $whitelist): View
    {
        $kategoriler = IlanKategori::orderBy('name')->get(['id', 'name']);

        return view('admin.ups-feature-whitelist.form', [
            'entry' => $whitelist,
            'kategoriler' => $kategoriler,
            'mode' => 'edit',
        ]);
    }

    /**
     * Update the specified whitelist entry.
     */
    public function update(Request $request, CategoryFeatureWhitelist $whitelist): RedirectResponse
    {
        $data = $this->validateData($request, $whitelist->id);

        app(UpdateFeatureWhitelistAction::class)->handle($whitelist, $data);

        return redirect()
            ->route('admin.ups-feature-whitelist.index')
            ->with('success', 'Whitelist girişi güncellendi');
    }

    /**
     * Remove the specified whitelist entry.
     */
    public function destroy(CategoryFeatureWhitelist $whitelist): RedirectResponse
    {
        app(DeleteFeatureWhitelistAction::class)->handle($whitelist);

        // Invalidate UPS feature cache after whitelist change
        app(AdminSettingsCacheService::class)->invalidateUpsFeatures();

        return redirect()
            ->route('admin.ups-feature-whitelist.index')
            ->with('success', 'Whitelist girişi silindi');
    }

    /**
     * Validate request data.
     */
    private function validateData(Request $request, ?int $id = null): array
    {
        $kategoriId = $request->input('kategori_id');

        return $request->validate([
            'kategori_id' => ['required', 'integer', 'exists:ilan_kategorileri,id'],
            'feature_category_slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_-]+$/',
                'unique:category_feature_whitelist,feature_category_slug,' . ($id ?? 'NULL') . ',id,kategori_id,' . ($kategoriId ?? 'NULL'),
            ],
            'aktiflik_durumu' => ['sometimes', 'boolean'],
        ], [
            'feature_category_slug.regex' => 'Slug sadece küçük harf, rakam, tire veya alt tire içerebilir.',
        ]);
    }
}
