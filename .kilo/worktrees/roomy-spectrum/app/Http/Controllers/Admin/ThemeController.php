<?php

namespace App\Http\Controllers\Admin;

use App\Services\ThemeService;
use Illuminate\Http\Request;

/**
 * ThemeController — Frontend Tema Yönetimi
 *
 * SAB: Thin Controller — iş mantığı ThemeService'dedir.
 * Yazma: ThemeService::setTheme() → SettingsAuthorityInterface (SAB uyumlu)
 */
class ThemeController extends AdminController
{
    public function __construct(private readonly ThemeService $themeService) {}

    /**
     * Tema seçici sayfasını göster.
     */
    public function index()
    {
        $themes      = $this->themeService->all();
        $aktif_tema  = $this->themeService->activeTheme();

        return view('admin.tema.index', compact('themes', 'aktif_tema'));
    }

    /**
     * Seçilen temayı kaydet.
     * SAB: SettingsAuthorityInterface üzerinden yazar.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:' . implode(',', array_keys(config('themes', [])))],
        ]);

        try {
            $this->themeService->setTheme($validated['theme']);

            return redirect()
                ->route('admin.tema.index')
                ->with('success', '✓ Tema başarıyla güncellendi: ' . config("themes.{$validated['theme']}.label"));

        } catch (\InvalidArgumentException $e) {
            report($e);
            return redirect()
                ->back()
                ->with('error', 'Geçersiz tema seçimi.');
        }
    }
}
