<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Setting;
use App\Models\Language;
use App\Models\Currency;
use App\Services\LocaleControlService;
use App\Services\CurrencyControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ayarlar Controller - System Settings Management
 * Context7: Enhanced with validation, templates, and helper methods
 */
class AyarlarController extends AdminController
{
    /**
     * Available setting groups
     */
    const GROUPS = [
        'general' => 'Genel Ayarlar',
        'contact' => 'İletişim Bilgileri',
        'email' => 'Email Ayarları',
        'social' => 'Sosyal Medya',
        'seo' => 'SEO Ayarları',
        'currency' => 'Para Birimi',
        'ai' => 'AI Ayarları',
        'system' => 'Sistem Ayarları',
        'security' => 'Güvenlik',
        'performance' => 'Performans',
        'qrcode' => 'QR Kod Ayarları',
        'navigation' => 'Navigasyon Ayarları',
    ];

    protected $localeService;
    protected $currencyService;

    public function __construct(LocaleControlService $localeService, CurrencyControlService $currencyService)
    {
        $this->localeService = $localeService;
        $this->currencyService = $currencyService;
    }

    public function index(Request $request)
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group'); // context7-ignore
        $groups = self::GROUPS;

        // Get all settings as key-value array for easy access in view
        $settingsArray = Setting::all()->pluck('value', 'key')->toArray();

        // Enterprise Locale & Currency
        $languages = Language::orderBy('display_order')->get();
        $currencies = Currency::orderBy('display_order')->get();

        // Try to use settings/index.blade.php first, fallback to ayarlar/index.blade.php
        if (view()->exists('admin.settings.index')) {
            return view('admin.settings.index', compact('settings', 'groups', 'settingsArray', 'languages', 'currencies'))
                ->with('settings', $settingsArray);
        }

        return view('admin.ayarlar.index', compact('settings', 'groups', 'settingsArray', 'languages', 'currencies'));
    }

    public function create()
    {
        $groups = self::GROUPS;
        $templates = $this->getTemplates();

        return view('admin.ayarlar.create', compact('groups', 'templates'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage', Setting::class);

        // Validation
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/', // snake_case validation
                'unique:settings,key',
            ],
            'value' => 'nullable|string',
            'type' => ['required', Rule::in(['string', 'integer', 'boolean', 'json'])], // context7-ignore
            'group' => ['required', 'string', Rule::in(array_keys(self::GROUPS))],
            'description' => 'nullable|string|max:500',
        ], [
            'key.regex' => 'Ayar anahtarı sadece küçük harf, rakam ve alt çizgi içerebilir (snake_case)',
            'group.in' => 'Geçersiz grup seçildi',
        ]);

        // Additional validation for JSON type
        if ($validated['type'] === 'json' && ! empty($validated['value'])) { // context7-ignore
            json_decode($validated['value']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['value' => 'Geçersiz JSON formatı'])->withInput();
            }
        }

        // Create setting
        app(\App\Actions\Setting\CreateSettingAction::class)->handle($validated);

        return redirect()->route('admin.ayarlar.index')
            ->with('success', 'Ayar başarıyla oluşturuldu!');
    }

    /**
     * Get predefined templates
     * Context7: Quick templates for common settings
     */
    private function getTemplates()
    {
        return [
            // General
            'site_name' => [
                'key' => 'site_name',
                'value' => 'Yalıhan Emlak',
                'type' => 'string', // context7-ignore
                'group' => 'general',
                'description' => 'Sitenin ana başlığı',
                'icon' => '🏠',
                'category' => 'general',
            ],
            'site_description' => [
                'key' => 'site_description',
                'value' => 'Bodrum\'da güvenilir emlak danışmanlığı',
                'type' => 'string', // context7-ignore
                'group' => 'general',
                'description' => 'Site açıklaması (meta description)',
                'icon' => '📝',
                'category' => 'general',
            ],
            'default_language' => [
                'key' => 'default_language',
                'value' => 'tr',
                'type' => 'string', // context7-ignore
                'group' => 'general',
                'description' => 'Varsayılan dil (tr, en, de, ru)',
                'icon' => '🌍',
                'category' => 'general',
            ],

            // System
            'maintenance_mode' => [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean', // context7-ignore
                'group' => 'system',
                'description' => 'Site bakım modunda mı?',
                'icon' => '🔧',
                'category' => 'system',
            ],
            'max_upload_size' => [
                'key' => 'max_upload_size',
                'value' => '10',
                'type' => 'integer', // context7-ignore
                'group' => 'system',
                'description' => 'Maksimum dosya yükleme boyutu (MB)',
                'icon' => '📁',
                'category' => 'system',
            ],
            'session_lifetime' => [
                'key' => 'session_lifetime',
                'value' => '120',
                'type' => 'integer', // context7-ignore
                'group' => 'system',
                'description' => 'Oturum süresi (dakika)',
                'icon' => '⏰',
                'category' => 'system',
            ],

            // Social
            'social_media' => [
                'key' => 'social_media',
                'value' => json_encode([
                    'facebook' => 'https://facebook.com/yalihanemlak',
                    'instagram' => 'https://instagram.com/yalihanemlak',
                    'twitter' => 'https://twitter.com/yalihanemlak',
                    'linkedin' => 'https://linkedin.com/company/yalihanemlak',
                ], JSON_PRETTY_PRINT),
                'type' => 'json', // context7-ignore
                'group' => 'social',
                'description' => 'Sosyal medya hesap linkleri',
                'icon' => '📱',
                'category' => 'social',
            ],

            // Email
            'smtp_host' => [
                'key' => 'smtp_host',
                'value' => 'smtp.gmail.com',
                'type' => 'string', // context7-ignore
                'group' => 'email',
                'description' => 'SMTP sunucu adresi',
                'icon' => '📧',
                'category' => 'email',
            ],

            // AI
            'ai_provider' => [
                'key' => 'ai_provider',
                'value' => 'ollama',
                'type' => 'string', // context7-ignore
                'group' => 'ai',
                'description' => 'Varsayılan AI provider (ollama, openai, gemini, claude)',
                'icon' => '🤖',
                'category' => 'ai',
            ],

            // Currency
            'default_currency' => [
                'key' => 'default_currency',
                'value' => 'TRY',
                'type' => 'string', // context7-ignore
                'group' => 'currency',
                'description' => 'Varsayılan para birimi (TRY, USD, EUR)',
                'icon' => '💰',
                'category' => 'currency',
            ],

            // Security
            'force_https' => [
                'key' => 'force_https',
                'value' => 'true',
                'type' => 'boolean', // context7-ignore
                'group' => 'security',
                'description' => 'HTTPS zorunluluğu',
                'icon' => '🔒',
                'category' => 'security',
            ],

            // SEO
            'google_analytics_id' => [
                'key' => 'google_analytics_id',
                'value' => 'G-XXXXXXXXXX',
                'type' => 'string', // context7-ignore
                'group' => 'seo',
                'description' => 'Google Analytics ID',
                'icon' => '📊',
                'category' => 'seo',
            ],
        ];
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($setting);
        }

        return view('admin.ayarlar.show', compact('setting'));
    }

    public function edit($id)
    {
        $ayar = Setting::findOrFail($id);

        return view('admin.ayarlar.edit', compact('ayar'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage', Setting::class);

        $setting = Setting::findOrFail($id);

        $validated = $request->validate([
            'value' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        app(\App\Actions\Setting\UpdateSettingAction::class)->handle($setting, $validated);

        return redirect()->route('admin.ayarlar.index')
            ->with('success', 'Ayar güncellendi!');
    }

    public function destroy($id)
    {
        $this->authorize('manage', Setting::class);

        $setting = Setting::findOrFail($id);
        app(\App\Actions\Setting\DestroySettingAction::class)->handle($setting);

        return redirect()->route('admin.ayarlar.index')
            ->with('success', 'Ayar silindi!');
    }

    /**
     * Bulk update settings (for settings form)
     * Context7: Handle form submission from settings page
     */
    public function bulkUpdate(Request $request)
    {
        $this->authorize('manage', Setting::class);

        try {
            $settingsToUpdate = $request->except(['_token', '_method']);

            app(\App\Actions\Setting\BulkUpdateSettingAction::class)->handle($settingsToUpdate);

            return redirect()->route('admin.ayarlar.index')
                ->with('success', 'Ayarlar başarıyla güncellendi!');
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::error('Settings bulk update failed', [], $e);

            return redirect()->back()
                ->with('error', 'Ayarlar güncellenirken hata oluştu: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get template groups for bulk creation
     * Context7: Grouped templates for related settings
     */
    public function getTemplateGroups()
    {
        return [
            'email_smtp' => [
                'name' => 'Email SMTP Ayarları',
                'icon' => '📧',
                'description' => 'Email gönderimi için gerekli tüm SMTP ayarları',
                'settings' => [
                    [
                        'key' => 'smtp_host',
                        'value' => 'smtp.gmail.com',
                        'type' => 'string', // context7-ignore
                        'group' => 'email',
                        'description' => 'SMTP sunucu adresi',
                    ],
                    [
                        'key' => 'smtp_port',
                        'value' => '587',
                        'type' => 'integer', // context7-ignore
                        'group' => 'email',
                        'description' => 'SMTP port numarası (587 TLS, 465 SSL)',
                    ],
                    [
                        'key' => 'smtp_username',
                        'value' => '',
                        'type' => 'string', // context7-ignore
                        'group' => 'email',
                        'description' => 'SMTP kullanıcı adı (email)',
                    ],
                    [
                        'key' => 'smtp_password',
                        'value' => '',
                        'type' => 'string', // context7-ignore
                        'group' => 'email',
                        'description' => 'SMTP şifresi',
                    ],
                    [
                        'key' => 'smtp_encryption',
                        'value' => 'tls',
                        'type' => 'string', // context7-ignore
                        'group' => 'email',
                        'description' => 'Şifreleme tipi (tls veya ssl)',
                    ],
                ],
            ],

            'ai_complete' => [
                'name' => 'AI Provider Tam Kurulum',
                'icon' => '🤖',
                'description' => 'AI sistemini kullanmaya hazır hale getir',
                'settings' => [
                    [
                        'key' => 'ai_durumu',
                        'value' => 'true',
                        'type' => 'boolean', // context7-ignore
                        'group' => 'ai',
                        'description' => 'AI özellikleri aktif mi?',
                    ],
                    [
                        'key' => 'ai_provider',
                        'value' => 'ollama',
                        'type' => 'string', // context7-ignore
                        'group' => 'ai',
                        'description' => 'Varsayılan AI provider',
                    ],
                    [
                        'key' => 'ollama_url',
                        'value' => 'http://localhost:11434',
                        'type' => 'string', // context7-ignore
                        'group' => 'ai',
                        'description' => 'Ollama sunucu URL',
                    ],
                    [
                        'key' => 'ollama_model',
                        'value' => 'gemma2:2b',
                        'type' => 'string', // context7-ignore
                        'group' => 'ai',
                        'description' => 'Ollama model adı',
                    ],
                ],
            ],

            'security_basic' => [
                'name' => 'Temel Güvenlik Ayarları',
                'icon' => '🔒',
                'description' => 'Minimum güvenlik gereksinimleri',
                'settings' => [
                    [
                        'key' => 'force_https',
                        'value' => 'true',
                        'type' => 'boolean', // context7-ignore
                        'group' => 'security',
                        'description' => 'HTTPS zorunluluğu',
                    ],
                    [
                        'key' => 'csrf_protection',
                        'value' => 'true',
                        'type' => 'boolean', // context7-ignore
                        'group' => 'security',
                        'description' => 'CSRF koruması aktif',
                    ],
                    [
                        'key' => 'max_login_attempts',
                        'value' => '5',
                        'type' => 'integer', // context7-ignore
                        'group' => 'security',
                        'description' => 'Maksimum giriş denemesi',
                    ],
                    [
                        'key' => 'login_lockout_time',
                        'value' => '15',
                        'type' => 'integer', // context7-ignore
                        'group' => 'security',
                        'description' => 'Giriş engelleme süresi (dakika)',
                    ],
                ],
            ],
        ];
    }

    /**
     * Bulk create settings
     * Context7: Create multiple related settings at once
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|unique:settings,key|regex:/^[a-z][a-z0-9_]*$/',
            'settings.*.value' => 'nullable|string',
            'settings.*.type' => 'required|in:string,integer,boolean,json', // context7-ignore
            'settings.*.group' => 'required|string',
            'settings.*.description' => 'nullable|string',
        ]);

        $created = app(\App\Actions\Setting\BulkStoreSettingAction::class)->handle($validated['settings']);

        return response()->json([
            'success' => true,
            'message' => count($created).' ayar oluşturuldu!',
            'created' => $created,
        ]);
    }

    /**
     * Clear all caches
     */
    public function clearCaches()
    {
        $this->authorize('manage', Setting::class);

        app(\App\Actions\Setting\ClearSettingCacheAction::class)->handle();

        return redirect()->back()
            ->with('success', 'Tüm ayar cache\'leri temizlendi!');
    }

    /**
     * Language Toggle
     */
    public function toggleLanguage(Request $request)
    {
        $this->authorize('manage', Setting::class);

        $active = (bool) $request->aktiflik_durumu;

        if (app(\App\Actions\Setting\ToggleLanguageSettingAction::class)->handle($request->code, $active)) {
            return back()->with('success', 'Dil durumu güncellendi.');
        }

        return back()->with('error', 'Varsayılan dil pasif edilemez!');
    }

    /**
     * Language Set Default
     */
    public function setDefaultLanguage(Request $request)
    {
        $this->authorize('manage', Setting::class);

        app(\App\Actions\Setting\SetDefaultLanguageSettingAction::class)->handle($request->code);

        return back()->with('success', 'Varsayılan dil değiştirildi.');
    }

    /**
     * Currency Toggle
     */
    public function toggleCurrency(Request $request)
    {
        $this->authorize('manage', Setting::class);

        $active = (bool) $request->aktiflik_durumu;

        if (app(\App\Actions\Setting\ToggleCurrencySettingAction::class)->handle($request->code, $active)) {
            return back()->with('success', 'Para birimi durumu güncellendi.');
        }

        return back()->with('error', 'TRY veya varsayılan para birimi pasif edilemez!');
    }

    /**
     * Currency Set Default
     */
    public function setDefaultCurrency(Request $request)
    {
        $this->authorize('manage', Setting::class);

        app(\App\Actions\Setting\SetDefaultCurrencySettingAction::class)->handle($request->code);

        return back()->with('success', 'Varsayılan para birimi değiştirildi.');
    }
}
