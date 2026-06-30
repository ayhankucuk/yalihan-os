<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // Kullanıcı aktivite takibi için eventler
        'App\Events\UserLoggedIn' => [
            'App\Listeners\LogUserActivity',
        ],
        \App\Events\IlanCreated::class => [
            \App\Listeners\FindMatchingDemands::class, // Context7: Tersine Eşleştirme (Reverse Matching)
            \App\Listeners\InvalidateIlanCache::class, // Cache invalidation
            \App\Listeners\SendEmailOnIlanCreated::class, // Email notification
            \App\Listeners\UpdateAnalyticsProjections::class, // [Phase 16] Analytics CQRS-lite Sync
        ],
        \App\Events\IlanUpdated::class => [
            \App\Listeners\InvalidateIlanCache::class, // Cache invalidation
            \App\Listeners\UpdateAnalyticsProjections::class, // [Phase 16] Analytics CQRS-lite Sync
        ],
        \App\Events\IlanDeleted::class => [
            \App\Listeners\InvalidateIlanCache::class, // Cache invalidation
            \App\Listeners\UpdateAnalyticsProjections::class, // [Phase 16] Analytics CQRS-lite Sync
        ],
        // Phase 12: Event-Driven Foundation — İlan Lifecycle Events
        \App\Events\IlanKopyalandi::class => [
            \App\Listeners\InvalidateIlanCache::class,
        ],
        \App\Events\IlanPasifeAlindi::class => [
            \App\Listeners\InvalidateIlanCache::class,
        ],
        \App\Events\IlanYayinlandiEvent::class => [
            \App\Listeners\NotifyLeadsOnNewListing::class,
        ],
        \App\Events\LeadOlusturuldu::class => [
            \App\Listeners\AutoReplyToLeadCreation::class,
            \App\Listeners\ProcessNewLeadForCRM::class,
            \App\Listeners\NotifyAdminsOnNewLead::class,
            \App\Listeners\EvaluateLeadWithCortex::class,
        ],
        \App\Events\LeadDurumDegisti::class => [
            // Analytics, History log vb. dinleyiciler eklenecek
        ],
        \App\Events\LeadAgentAtandi::class => [
            \App\Listeners\NotifyAgentOnLeadAssignment::class,
        ],
        \App\Events\TalepReceived::class => [
            \App\Jobs\AnalyzeAndPrioritizeDemand::class, // Context7: Otonom Fırsat Sentezi ve Bildirim Sistemi
        ],
        \App\Events\IlanPriceChanged::class => [
            // Fiyat değişim takibi ve n8n entegrasyonu
            \App\Listeners\NotifyN8nOnIlanPriceChanged::class,
        ],
        // Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
        \App\Events\GorevCreated::class => [
            \App\Listeners\NotifyN8nOnGorevCreated::class,
        ],
        \App\Events\GorevDurumChanged::class => [
            \App\Listeners\NotifyN8nOnGorevDurumChanged::class,
        ],
        \App\Events\GorevDeadlineYaklasiyor::class => [
            \App\Listeners\NotifyN8nOnGorevDeadlineYaklasiyor::class,
        ],
        \App\Events\GorevGecikti::class => [
            \App\Listeners\NotifyN8nOnGorevGecikti::class,
        ],

        \App\Events\RaporOlusturuldu::class => [
            \App\Listeners\RaporuPaylas::class,
        ],

        // [SAB]: PropertySchema Domain Events — Event-Driven Cache Invalidation
        \App\Domain\PropertyHub\Events\TemplateSealedEvent::class => [
            \App\Domain\PropertyHub\Listeners\TemplateSealedListener::class,
        ],
        \App\Domain\PropertyHub\Events\FeatureAssignedEvent::class => [
            \App\Domain\PropertyHub\Listeners\FeatureAssignedListener::class,
        ],

        // [SAB]: Financial Ledger Events (v6.3)
        \App\Events\LedgerDoubleEntryRecorded::class => [
            \App\Listeners\UpdateLedgerBalanceProjection::class,
        ],

        // [SAB]: CQRS Read Model Projections (Phase 16.1)
        \App\Events\ListingCreated::class => [
            \App\Listeners\ListingProjector::class . '@handleListingCreated',
        ],
        \App\Events\ListingUpdated::class => [
            \App\Listeners\ListingProjector::class . '@handleListingUpdated',
        ],
        \App\Events\LeadRegistered::class => [
            \App\Listeners\LeadProjector::class,
        ],

        // Copilot Pipeline Events
        \App\Events\Copilot\PipelineStepFailed::class => [
            \App\Listeners\Copilot\RetryFailedStepListener::class,
            \App\Listeners\Copilot\AlertOnPipelineFailureListener::class,
        ],
        \App\Events\Copilot\PipelineStepCompleted::class => [
            // Metrics handled via subscriber
        ],
        \App\Events\Copilot\PipelineGoverned::class => [
            // Metrics handled via subscriber
        ],

        // SAB4: Multi-Agent Orchestration Events
        // Events dispatched by agents for observability and future extensions.
        \App\Events\Governance\FindingDetected::class => [],
        \App\Events\Governance\DecisionMade::class => [],
        \App\Events\Governance\ActionApplied::class => [],
        \App\Events\Governance\ActionFailed::class => [],
        \App\Events\Governance\RollbackExecuted::class => [],
        \App\Events\Governance\FindingSuppressed::class => [],
        \App\Events\Governance\OverrideApplied::class => [],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        \App\Models\Ilan::class => [
            \App\Observers\IlanEmbeddingObserver::class,
            \App\Observers\IlanObserver::class,
        ],
        \App\Models\LedgerEntry::class => [\App\Observers\LedgerEntryObserver::class],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\CRM\KisiSyncListener::class,
        \App\Listeners\Copilot\PipelineMetricsListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Performans modülü için kullanıcı aktivitelerini dinle
        Event::listen('user.activity', function ($user, $action) {
            // Kullanıcı aktivite logu oluştur
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
