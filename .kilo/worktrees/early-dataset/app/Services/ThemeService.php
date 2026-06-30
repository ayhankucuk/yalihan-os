<?php

namespace App\Services;

use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Contracts\Settings\SettingsAuthorityInterface;
use Illuminate\Support\Facades\Cache;

/**
 * ThemeService — Frontend Tema Yönetimi
 *
 * Aktif temayı settings tablosundaki 'frontend_theme' anahtarından okur.
 * CSS custom property bloğunu string olarak üretir; layouts/frontend.blade.php
 * bu bloğu <style>:root{ ... }</style> içine enjekte eder.
 *
 * SAB:
 *   - Okuma: ConfigurationRegistryInterface (Settings SSOT)
 *   - Yazma: SettingsAuthorityInterface (Settings Authority)
 *   - Cache: 'theme.css_vars' → 60 dakika (ayar değişince invalidate edilmeli)
 */
class ThemeService
{
    public const SETTINGS_KEY   = 'frontend_theme';
    public const SETTINGS_GROUP = 'appearance';
    public const DEFAULT_THEME  = 'propertius';
    public const CACHE_KEY      = 'theme.css_vars';
    public const CACHE_TTL      = 3600; // 60 dakika

    public function __construct(
        private readonly ConfigurationRegistryInterface $registry,
        private readonly SettingsAuthorityInterface $authority,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Aktif tema slug'ını döner.
     */
    public function activeTheme(): string
    {
        $slug = $this->registry->get(self::SETTINGS_KEY, self::DEFAULT_THEME);

        return $this->isValid($slug) ? $slug : self::DEFAULT_THEME;
    }

    /**
     * Aktif tema konfigürasyonunu döner.
     *
     * @return array{label: string, description: string, preview: array, vars: array}
     */
    public function activeConfig(): array
    {
        return config('themes.' . $this->activeTheme(), config('themes.' . self::DEFAULT_THEME));
    }

    /**
     * CSS :root değişken bloğunu döner — doğrudan <style> içine enjekte edilir.
     * Sonuç önbelleğe alınır; ayar değişince flushCache() çağrılmalıdır.
     */
    public function getCssVars(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->buildCssVars($this->activeConfig()['vars'] ?? []);
        });
    }

    /**
     * Tüm mevcut temaları listeler.
     *
     * @return array<string, array{label: string, description: string, preview: array, vars: array}>
     */
    public function all(): array
    {
        return config('themes', []);
    }

    /**
     * Aktif temayı değiştirir ve cache'i invalidate eder.
     * SAB: Yazma işlemi yalnızca SettingsAuthorityInterface üzerinden.
     */
    public function setTheme(string $slug): void
    {
        if (! $this->isValid($slug)) {
            throw new \InvalidArgumentException("Geçersiz tema: [{$slug}]");
        }

        $this->authority->set(
            key:         self::SETTINGS_KEY,
            value:       $slug,
            group:       self::SETTINGS_GROUP,
            type:        'string', // context7-ignore
            description: 'Frontend aktif tema',
        );

        $this->flushCache();
    }

    /**
     * Tema cache'ini temizler (ayar değiştiğinde çağrılır).
     */
    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────

    private function isValid(string $slug): bool
    {
        return array_key_exists($slug, config('themes', []));
    }

    private function buildCssVars(array $vars): string
    {
        if (empty($vars)) {
            return '';
        }

        $lines = [];
        foreach ($vars as $property => $value) {
            $lines[] = "            {$property}: {$value};";
        }

        return implode("\n", $lines);
    }
}
