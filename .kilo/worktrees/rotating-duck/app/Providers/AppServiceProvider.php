<?php

namespace App\Providers;

use App\Models\Ilan;
use App\Models\Setting;
// use App\Models\IlanTemplate; // DEPRECATED: Model removed (2026-01-11)
use App\Modules\ModuleServiceProvider;
use App\Observers\IlanObserver;
// use App\Observers\IlanTemplateObserver; // DEPRECATED: Observer removed (2026-01-11)
// use App\Observers\IlanTemplateVersionObserver; // DEPRECATED: Observer removed (2026-01-11)
use App\Services\AIService;
use App\Services\CurrencyConversionService;
use App\Services\PlanNotlariAIService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \\Illuminate\\Contracts\\Foundation\\Application $app
 *
 * @extends ServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 🏗️ SAAS: Register Tenant Context as Singleton
        $this->app->singleton(\App\Services\SaaS\TenantContextService::class);
        $this->app->singleton(\App\Services\SaaS\EntitlementService::class);
        $this->app->singleton(\App\Services\SaaS\BillingLedgerService::class);
        $this->app->singleton(\App\Services\SaaS\UsageMeteringService::class);
        $this->app->singleton(\App\Services\SaaS\AiMonetizationService::class);

        // Modül servisini kaydediyoruz
        $this->app->register(ModuleServiceProvider::class);

        // Template Resolver Service
        $this->app->singleton(
            \App\Contracts\TemplateResolverInterface::class,
            \App\Services\TemplateResolver::class
        );

        // AI Service'i singleton olarak kaydet
        $this->app->singleton(AIService::class, function ($app) {
            return new AIService(
                $app->make(\App\Services\AI\PromptGovernanceService::class),
                $app->make(\App\Services\SettingService::class)
            );
        });

        // Location Service'i singleton olarak kaydet (eğer class varsa)
        // Context7: Conditional service registration - only if class exists
        if (class_exists('App\\Services\\LocationService')) {
            $this->app->singleton('App\\Services\\LocationService', function ($app) {
                // @phpstan-ignore-next-line
                return new \App\Services\LocationService;
            });
        }

        // Plan Notları AI Service'i singleton olarak kaydet
        if (class_exists(PlanNotlariAIService::class) && class_exists(AIService::class)) {
            $this->app->singleton(PlanNotlariAIService::class, function ($app) {
                // @phpstan-ignore-next-line
                return new PlanNotlariAIService($app->make(AIService::class));
            });
        }

        // 🛡️ SAB v4.1 - Resilience Service
        $this->app->singleton(
            \App\Contracts\Resilience\CircuitBreakerInterface::class,
            \App\Services\Resilience\CircuitBreaker::class
        );

        // SAB8 - Action Feedback Service (singleton: shared stats cache)
        $this->app->singleton(\App\Services\Intelligence\ActionFeedbackService::class);

        // 🛡️ GOVERNANCE SYSTEM (SAB v12+)
        $this->app->bind(
            \App\Contracts\Governance\AuditLoggerInterface::class,
            \App\Services\Governance\EloquentGovernanceAuditLogger::class
        );
        $this->app->bind(
            \App\Contracts\Governance\GovernedEntityRepositoryInterface::class,
            \App\Repositories\Governance\EloquentGovernedEntityRepository::class
        );
        $this->app->bind(
            \App\Contracts\Governance\GovernanceServiceInterface::class,
            \App\Services\Governance\GovernanceService::class
        );
        $this->app->bind(
            \App\Contracts\Governance\GovernanceReadServiceInterface::class,
            \App\Services\Governance\Diff\GovernanceReadService::class
        );
        $this->app->bind(
            \App\Contracts\Governance\TelemetryPublisherInterface::class,
            \App\Services\Governance\Telemetry\LogTelemetryPublisher::class
        );

        // 🛡️ SAB S1 - Settings Authority
        $this->app->singleton(
            \App\Contracts\Settings\ConfigurationRegistryInterface::class,
            \App\Services\Settings\ConfigurationRegistry::class
        );
        $this->app->singleton(
            \App\Contracts\Settings\SettingsAuthorityInterface::class,
            \App\Services\Settings\SettingsAuthorityService::class
        );

        // 🛡️ N1-C - Notification Authority
        $this->app->singleton(
            \App\Contracts\Notification\NotificationAuthorityInterface::class,
            \App\Services\Notification\NotificationAuthorityService::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 🛡️ GOVERNANCE: Validate application configuration on boot
        // This ensures SSOT enforcement and fail-fast for incorrect env settings.
        // We skip in console unless it's the config:validate command to avoid blocking migrations.
        if (!app()->runningInConsole() || app()->bound('command.config.validate')) {
            \App\Support\Guards\ConfigGuard::validate();
        }

        // Blade bileşenlerini kaydet
        Blade::componentNamespace('App\View\Components', 'app');

        // Address module component'ini kaydet (eğer class mevcutsa)
        try {
            // @phpstan-ignore-next-line
            if (class_exists('App\\Modules\\Address\\Components\\AddressSelector')) {
                // @phpstan-ignore-next-line
                Blade::component('address-selector', 'App\\Modules\\Address\\Components\\AddressSelector');
            }
            // Cortex Akıllı Öneriler Bileşeni
            Blade::component('cortex-recommendations', \App\View\Components\Cortex\Recommendations::class);
        } catch (\Throwable $e) {
            // Components not available, skip silently
        }

        // Str sınıfını global olarak kullanılabilir hale getir
        if (! class_exists('Str')) {
            class_alias(\Illuminate\Support\Str::class, 'Str');
        }

        Ilan::observe(IlanObserver::class);
        \App\Models\IlanFotografi::observe(\App\Observers\IlanFotografiObserver::class);
        try {
            if (class_exists(\App\Models\IlanKategori::class)) {
                \App\Models\IlanKategori::observe(\App\Observers\IlanKategoriObserver::class);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("IlanKategori observer registration failed: " . $e->getMessage());
        }

        // CRM Observer (Added: 2025-11-25)
        \App\Models\Kisi::observe(\App\Observers\KisiObserver::class);
        \App\Models\Lead::observe(\App\Observers\LeadObserver::class);
        \App\Models\AILeadScore::observe(\App\Observers\AILeadScoreObserver::class);

        // Talep Observer (Added: 2025-11-29)
        // Context7: Otonom Fırsat Sentezi ve Bildirim Sistemi
        \App\Models\Talep::observe(\App\Observers\TalepObserver::class);

        // Template Audit Log Observer (DEPRECATED: 2026-01-11 - Model removed)
        // UPS Phase P: Automatic change tracking for templates
        // IlanTemplate::observe(IlanTemplateObserver::class);

        // Template Version Observer (DEPRECATED: 2026-01-11 - Model removed)
        // UPS Phase Q: Automatic version snapshots for templates
        // IlanTemplate::observe(IlanTemplateVersionObserver::class);

        // Feature Pack Observer (Added: 2026-01-05)
        // UPS Phase R: Autonomous sync for feature packs
        \App\Models\FeaturePack::observe(\App\Observers\FeaturePackObserver::class);

        // Görev Observer (Added: 2025-11-27)
        // Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
        \App\Modules\TakimYonetimi\Models\Gorev::observe(\App\Observers\GorevObserver::class);

        // CRM Satış Observer (Added: 2025-12-19)
        // Context7: C7-CRM-N8N-INTEGRATION-2025-12-19
        // \App\Modules\CRMSatis\Models\Satis::observe(\App\Observers\SatisObserver::class);
        // \App\Modules\CRMSatis\Models\Sozlesme::observe(\App\Observers\SozlesmeObserver::class);
        // \App\Modules\CRMSatis\Models\SatisRaporu::observe(\App\Observers\SatisRaporuObserver::class);

        // Google Drive Storage (for Laravel Backup)
        try {
            Storage::extend('google', function ($app, $config) {
                $client = new \Google\Client;
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/');

                return new \League\Flysystem\Filesystem($adapter, ['case_sensitive' => false]);
            });
        } catch (\Exception $e) {
            // Google Drive not configured yet, skip silently
        }

        // V2 Migration: temporarily disable settings check (table doesn't exist in SQLite)
        // if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
        //     $appLocale = Setting::where('key', 'app_locale')->value('value');
        //     if ($appLocale) {
        //         app()->setLocale($appLocale);
        //     }
        //     $defaultCurrency = Setting::where('key', 'currency_default')->value('value');
        //     if ($defaultCurrency) {
        //         session(['currency' => $defaultCurrency]);
        //     }
        // }

        View::composer('*', function ($view) {
            $currentLocale = app()->getLocale();
            $seoService = app(\App\Services\SeoMetaService::class);
            $view->with('seo', $seoService->getMeta($currentLocale));
        });

        View::composer('components.frontend.global.topbar', function ($view) {
            $locales = config('localization.supported_locales', []);
            $currentLocale = app()->getLocale();

            /** @var CurrencyConversionService $currencyService */
            $currencyService = app(CurrencyConversionService::class);
            $currencies = $currencyService->getSupported();
            $currentCurrency = session('currency', $currencyService->getDefault());

            $view->with([
                'locales' => $locales,
                'currentLocale' => $currentLocale,
                'currencies' => $currencies,
                'currentCurrency' => $currentCurrency,
            ]);
        });

        // UPS Cache Busting: FeatureAssignment observer
        try {
            if (class_exists(\App\Models\FeatureAssignment::class)) {
                \App\Models\FeatureAssignment::observe(\App\Observers\FeatureAssignmentObserver::class);
            }
        } catch (\Throwable $e) {
            // observer registration failed, skip
        }
    }
}
