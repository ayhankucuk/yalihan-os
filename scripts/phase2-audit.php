<?php
/**
 * Phase 2 Route Audit — Orphan Controller Classification
 * Sprint 4.9 PRR
 */

$classifications = [

  // === REGISTER: Has public HTTP API, needs route registration ===

  // Admin — Dashboard / Management
  'App\Http\Controllers\Admin\AIArsaAnalizController'           => ['cat' => 'Register', 'reason' => 'CRUD dashboard, 18 public methods — needs route'],
  'App\Http\Controllers\Admin\AIIlanTaslagiController'           => ['cat' => 'Register', 'reason' => 'Approve/reject/publish workflow — needs route'],
  'App\Http\Controllers\Admin\AIMessageController'               => ['cat' => 'Register', 'reason' => 'AI mesaj onay workflow — needs route'],
  'App\Http\Controllers\Admin\GovernanceObservabilityController'  => ['cat' => 'Register', 'reason' => 'Governance dashboard — needs route'],
  'App\Http\Controllers\Admin\IlanAnalizController'              => ['cat' => 'Register', 'reason' => 'Ilan analiz dashboard — needs route'],
  'App\Http\Controllers\Admin\IlanQualityDashboardController'     => ['cat' => 'Register', 'reason' => 'Quality dashboard — needs route'],
  'App\Http\Controllers\Admin\IlanValidationController'          => ['cat' => 'Register', 'reason' => 'Validation rules API — needs route'],
  'App\Http\Controllers\Admin\ImpactMetricsController'            => ['cat' => 'Register', 'reason' => 'Impact metrics — needs route'],
  'App\Http\Controllers\Admin\MatchingTestController'             => ['cat' => 'Register', 'reason' => 'Matching test interface — needs route'],
  'App\Http\Controllers\Admin\MyListingsExportController'         => ['cat' => 'Register', 'reason' => 'Export endpoint — needs route'],
  'App\Http\Controllers\Admin\PropertyHubVersionController'      => ['cat' => 'Register', 'reason' => 'Version management — needs route'],
  'App\Http\Controllers\Admin\PropertyTypeManagerController'      => ['cat' => 'Register', 'reason' => 'Property type CRUD — needs route'],
  'App\Http\Controllers\Admin\SemanticSearchController'          => ['cat' => 'Register', 'reason' => 'Search management — needs route'],
  'App\Http\Controllers\Admin\ShadowDashboardController'          => ['cat' => 'Register', 'reason' => 'Shadow mode dashboard — needs route'],
  'App\Http\Controllers\Admin\SimpleImpactController'            => ['cat' => 'Register', 'reason' => 'Impact API — needs route'],
  'App\Http\Controllers\Admin\ThemeController'                   => ['cat' => 'Register', 'reason' => 'Theme settings — needs route'],
  'App\Http\Controllers\Admin\UpsAnalyticsController'             => ['cat' => 'Register', 'reason' => 'UPS analytics — needs route'],
  'App\Http\Controllers\Admin\UpsFeatureManagerController'        => ['cat' => 'Register', 'reason' => 'UPS feature CRUD — needs route'],
  'App\Http\Controllers\Admin\UpsFeaturePackController'          => ['cat' => 'Register', 'reason' => 'UPS feature pack — needs route'],
  'App\Http\Controllers\Admin\UpsFeatureWhitelistController'      => ['cat' => 'Register', 'reason' => 'UPS whitelist CRUD — needs route'],
  'App\Http\Controllers\Admin\UpsGovernanceController'            => ['cat' => 'Register', 'reason' => 'UPS governance — needs route'],
  'App\Http\Controllers\Admin\UpsHealthController'                => ['cat' => 'Register', 'reason' => 'UPS health check — needs route'],
  'App\Http\Controllers\Admin\UpsPackController'                 => ['cat' => 'Register', 'reason' => 'UPS pack management — needs route'],
  'App\Http\Controllers\Admin\UpsPolicyController'               => ['cat' => 'Register', 'reason' => 'UPS policy view — needs route'],
  'App\Http\Controllers\Admin\UpsTemplateController'              => ['cat' => 'Register', 'reason' => 'UPS template reorder — needs route'],
  'App\Http\Controllers\Admin\UpsTemplateManagerController'       => ['cat' => 'Register', 'reason' => 'UPS template CRUD — needs route'],
  'App\Http\Controllers\Admin\UpsVersionController'              => ['cat' => 'Register', 'reason' => 'UPS version management — needs route'],
  'App\Http\Controllers\Admin\ValidationController'               => ['cat' => 'Register', 'reason' => 'Validation view — needs route'],
  'App\Http\Controllers\Admin\VisibilityController'               => ['cat' => 'Register', 'reason' => 'Visibility config — needs route'],
  'App\Http\Controllers\Admin\WalletController'                  => ['cat' => 'Register', 'reason' => 'Wallet/commission view — needs route'],
  'App\Http\Controllers\Admin\WorkspaceDashboardController'       => ['cat' => 'Register', 'reason' => 'Workspace dashboard — needs route'],
  'App\Http\Controllers\Admin\WorkspaceExecutionController'       => ['cat' => 'Register', 'reason' => 'Execution replay/retry API — needs route'],
  'App\Http\Controllers\Admin\YalihanBekciController'            => ['cat' => 'Register', 'reason' => 'Bekci health dashboard — needs route'],
  'App\Http\Controllers\Admin\YazlikKiralamaController'          => ['cat' => 'Register', 'reason' => 'Yazlik booking management — needs route'],
  'App\Http\Controllers\Admin\YazlikStructuredDataController'    => ['cat' => 'Register', 'reason' => 'Structured data generation — needs route'],

  // Admin — Internal (service-layer, called by other controllers/services)
  'App\Http\Controllers\Admin\GoogleCalendarController'         => ['cat' => 'Internal', 'reason' => 'OAuth callback — called by Drive service, not directly by HTTP'],
  'App\Http\Controllers\Admin\MyListingsController'              => ['cat' => 'Internal', 'reason' => 'Used by other controllers, not directly via HTTP'],
  'App\Http\Controllers\Admin\NotificationController'            => ['cat' => 'Internal', 'reason' => 'Notification service — called by other services'],
  'App\Http\Controllers\Admin\OutboundNotificationController'     => ['cat' => 'Internal', 'reason' => 'Outbound notifier — service layer, not HTTP'],
  'App\Http\Controllers\Admin\OzellikController'                => ['cat' => 'Internal', 'reason' => 'Ozellik CRUD — service-backed, review needed'],
  'App\Http\Controllers\Admin\OzellikKategoriController'         => ['cat' => 'Internal', 'reason' => 'Kategori management — service-backed'],
  'App\Http\Controllers\Admin\PageAnalyzerController'            => ['cat' => 'Internal', 'reason' => 'Page analysis service — called by queue jobs'],
  'App\Http\Controllers\Admin\PhotoController'                   => ['cat' => 'Internal', 'reason' => 'Photo service — called by IlanCrudService, not HTTP'],
  'App\Http\Controllers\Admin\ProfileController'                  => ['cat' => 'Internal', 'reason' => 'Profile view — likely called by other controllers'],
  'App\Http\Controllers\Admin\PropertyEventApiController'        => ['cat' => 'Internal', 'reason' => 'Property event API — internal event handler'],
  'App\Http\Controllers\Admin\PropertyHubController'              => ['cat' => 'Internal', 'reason' => 'PropertyHub core — used by other controllers'],
  'App\Http\Controllers\Admin\PropertyTypeController'             => ['cat' => 'Internal', 'reason' => 'Property type CRUD — service layer, review needed'],
  'App\Http\Controllers\Admin\ReportController'                   => ['cat' => 'Internal', 'reason' => 'Report view — called internally'],
  'App\Http\Controllers\Admin\ReportingController'                => ['cat' => 'Internal', 'reason' => 'Reporting service — internal use'],
  'App\Http\Controllers\Admin\SiteApartmanController'            => ['cat' => 'Internal', 'reason' => 'Site management — internal service'],
  'App\Http\Controllers\Admin\SiteController'                    => ['cat' => 'Internal', 'reason' => 'Site management — internal service'],
  'App\Http\Controllers\Admin\SmartFormController'                => ['cat' => 'Internal', 'reason' => 'Smart form builder — internal service'],
  'App\Http\Controllers\Admin\SystemMonitorController'            => ['cat' => 'Internal', 'reason' => 'System monitor — internal health check service'],
  'App\Http\Controllers\Admin\TKGMParselController'              => ['cat' => 'Internal', 'reason' => 'TKGM parcel query — internal service'],
  'App\Http\Controllers\Admin\TakvimController'                   => ['cat' => 'Internal', 'reason' => 'Calendar management — internal service'],
  'App\Http\Controllers\Admin\TalepController'                   => ['cat' => 'Internal', 'reason' => 'Talep CRUD — internal service/controller'],
  'App\Http\Controllers\Admin\TalepPortfolyoController'           => ['cat' => 'Internal', 'reason' => 'Talep portfolio — internal view'],
  'App\Http\Controllers\Admin\TemplateController'                 => ['cat' => 'Internal', 'reason' => 'Template management — internal service'],
  'App\Http\Controllers\Admin\TemplateSyncController'             => ['cat' => 'Internal', 'reason' => 'Template sync — internal job dispatcher'],
  'App\Http\Controllers\Admin\UserController'                     => ['cat' => 'Internal', 'reason' => 'User CRUD — internal service'],
  'App\Http\Controllers\Admin\WikimapiaSearchController'          => ['cat' => 'Internal', 'reason' => 'Wikimapia search — internal service'],
  'App\Http\Controllers\Admin\MarketIntelligenceController'       => ['cat' => 'Internal', 'reason' => 'Market intelligence — internal service'],
  'App\Http\Controllers\Admin\MarketingAssetController'           => ['cat' => 'Internal', 'reason' => 'Marketing asset service — internal'],
  'App\Http\Controllers\Admin\MatchingFeedbackController'         => ['cat' => 'Internal', 'reason' => 'Matching feedback — internal service'],

  // Admin — Event Only (fires events, no HTTP route needed)
  'App\Http\Controllers\Admin\AiRuntimeController'                => ['cat' => 'Event Only', 'reason' => 'Runtime config change → fires event, no direct HTTP'],
  'App\Http\Controllers\Admin\DescriptionDraftController'         => ['cat' => 'Event Only', 'reason' => 'Draft approve/reject → Hermes event chain, no direct HTTP'],
  'App\Http\Controllers\Admin\FormValidationController'           => ['cat' => 'Event Only', 'reason' => 'Form validation → event driven, no standalone HTTP'],

  // Admin — Queue Only (dispatches jobs, no HTTP route needed)
  'App\Http\Controllers\Admin\BelediyeVeriDemoController'         => ['cat' => 'Queue Only', 'reason' => 'Demo data fetcher — dispatches background jobs, no HTTP'],
  'App\Http\Controllers\Admin\Context7DashboardController'        => ['cat' => 'Queue Only', 'reason' => 'Context7 rule dashboard — dispatches scan jobs'],
  'App\Http\Controllers\Admin\IlanPhotoController'               => ['cat' => 'Queue Only', 'reason' => 'Photo upload → dispatches AI job, no direct HTTP'],
  'App\Http\Controllers\Admin\LinkHealthController'               => ['cat' => 'Queue Only', 'reason' => 'Link health check — dispatches background jobs'],
  'App\Http\Controllers\Admin\TemplateAiDesignController'        => ['cat' => 'Queue Only', 'reason' => 'AI design pipeline — dispatches job chain'],
  'App\Http\Controllers\Admin\TemplateAiPipelineController'      => ['cat' => 'Queue Only', 'reason' => 'AI pipeline trigger — dispatches jobs'],

  // Admin — Deprecated (duplicate, archive)
  'App\Http\Controllers\Admin\YayinTipiYoneticisiController'     => ['cat' => 'Deprecated', 'reason' => 'Duplicate of PropertyTypeManagerController — archive'],

  // AI
  'App\Http\Controllers\AI\TenantAiDashboardController'           => ['cat' => 'Event Only', 'reason' => 'AI dashboard — fires events, no direct HTTP'],

  // API — Register
  'App\Http\Controllers\Api\AIController'                        => ['cat' => 'Register', 'reason' => 'Core AI API (24 methods) — needs route'],
  'App\Http\Controllers\Api\AIContentController'                 => ['cat' => 'Register', 'reason' => 'AI content generation API — needs route'],
  'App\Http\Controllers\Api\AdminAIController'                    => ['cat' => 'Register', 'reason' => 'Admin AI chat/analytics — needs route'],
  'App\Http\Controllers\Api\AdvancedAIController'                => ['cat' => 'Register', 'reason' => 'Advanced AI endpoints — needs route'],
  'App\Http\Controllers\Api\AIFeatureSuggestionController'        => ['cat' => 'Register', 'reason' => 'Feature suggestion API — needs route'],
  'App\Http\Controllers\Api\AIOpportunityController'               => ['cat' => 'Register', 'reason' => 'Opportunity API — needs route'],
  'App\Http\Controllers\Api\AdaptiveUIUXController'              => ['cat' => 'Register', 'reason' => 'UI/UX optimization API — needs route'],
  'App\Http\Controllers\Api\AutoLearningController'               => ['cat' => 'Register', 'reason' => 'Auto learning API — needs route'],
  'App\Http\Controllers\Api\BookingRequestController'             => ['cat' => 'Register', 'reason' => 'Booking API — needs route'],
  'App\Http\Controllers\Api\BulkListingController'               => ['cat' => 'Register', 'reason' => 'Bulk operations API — needs route'],
  'App\Http\Controllers\Api\BulkOperationsController'             => ['cat' => 'Register', 'reason' => 'Bulk ilan operations — needs route'],
  'App\Http\Controllers\Api\CalendarToolsController'             => ['cat' => 'Register', 'reason' => 'Calendar availability API — needs route'],
  'App\Http\Controllers\Api\CategoriesController'                 => ['cat' => 'Register', 'reason' => 'Category lookup API — needs route'],
  'App\Http\Controllers\Api\CategoryController'                   => ['cat' => 'Register', 'reason' => 'Category management API — needs route'],
  'App\Http\Controllers\Api\ConfigOptionController'               => ['cat' => 'Register', 'reason' => 'Config option API — needs route'],
  'App\Http\Controllers\Api\Context7Controller'                   => ['cat' => 'Register', 'reason' => 'Context7 system API — needs route'],
  'App\Http\Controllers\Api\CrossModuleIntelligenceController'   => ['cat' => 'Register', 'reason' => 'Cross-module AI API — needs route'],
  'App\Http\Controllers\Api\CurrencyRateController'               => ['cat' => 'Register', 'reason' => 'Currency rate API — needs route'],
  'App\Http\Controllers\Api\DanismanController'                   => ['cat' => 'Register', 'reason' => 'Danisman lookup API — needs route'],
  'App\Http\Controllers\Api\DashboardCqrsController'             => ['cat' => 'Register', 'reason' => 'Dashboard CQRS API — needs route'],
  'App\Http\Controllers\Api\DemirbasController'                  => ['cat' => 'Register', 'reason' => 'Demirbas category API — needs route'],
  'App\Http\Controllers\Api\EnvironmentAnalysisController'        => ['cat' => 'Register', 'reason' => 'Environment analysis API — needs route'],
  'App\Http\Controllers\Api\EventController'                     => ['cat' => 'Register', 'reason' => 'Event CRUD API — needs route'],
  'App\Http\Controllers\Api\ExchangeRateController'              => ['cat' => 'Register', 'reason' => 'Exchange rate API — needs route'],
  'App\Http\Controllers\Api\FavoriController'                    => ['cat' => 'Register', 'reason' => 'Favori API — needs route'],
  'App\Http\Controllers\Api\FeatureController'                    => ['cat' => 'Register', 'reason' => 'Feature lookup API — needs route'],
  'App\Http\Controllers\Api\FieldDependencyController'           => ['cat' => 'Register', 'reason' => 'Field dependency API — needs route'],
  'App\Http\Controllers\Api\FieldMcpController'                  => ['cat' => 'Register', 'reason' => 'Field MCP API — needs route'],
  'App\Http\Controllers\Api\GeoProxyController'                  => ['cat' => 'Register', 'reason' => 'Geocoding proxy API — needs route'],
  'App\Http\Controllers\Api\GeocodingController'                 => ['cat' => 'Register', 'reason' => 'Geocoding API — needs route'],
  'App\Http\Controllers\Api\IlanAIController'                    => ['cat' => 'Register', 'reason' => 'Ilan AI analysis API — needs route'],
  'App\Http\Controllers\Api\IlanWizardController'                 => ['cat' => 'Register', 'reason' => 'Ilan wizard API (13 methods) — needs route'],
  'App\Http\Controllers\Api\IntelligenceHubController'           => ['cat' => 'Register', 'reason' => 'Intelligence hub API — needs route'],
  'App\Http\Controllers\Api\KisiCRMController'                   => ['cat' => 'Register', 'reason' => 'Kisi CRM API — needs route'],
  'App\Http\Controllers\Api\KisiController'                      => ['cat' => 'Register', 'reason' => 'Kisi API — needs route'],
  'App\Http\Controllers\Api\ListingNavigationController'          => ['cat' => 'Register', 'reason' => 'Listing navigation API — needs route'],
  'App\Http\Controllers\Api\ListingSearchController'             => ['cat' => 'Register', 'reason' => 'Listing search API — needs route'],
  'App\Http\Controllers\Api\LocationController'                  => ['cat' => 'Register', 'reason' => 'Location lookup API — needs route'],
  'App\Http\Controllers\Api\MarketAnalysisController'             => ['cat' => 'Register', 'reason' => 'Market analysis API — needs route'],
  'App\Http\Controllers\Api\MatchController'                     => ['cat' => 'Register', 'reason' => 'Match API — needs route'],
  'App\Http\Controllers\Api\N8nWebhookController'                 => ['cat' => 'Register', 'reason' => 'n8n webhook API (7 methods) — needs route'],
  'App\Http\Controllers\Api\NLPController'                       => ['cat' => 'Register', 'reason' => 'NLP API — needs route'],
  'App\Http\Controllers\Api\PhotoController'                     => ['cat' => 'Register', 'reason' => 'Photo API — needs route'],
  'App\Http\Controllers\Api\PitchController'                      => ['cat' => 'Register', 'reason' => 'Share/pitch API — needs route'],
  'App\Http\Controllers\Api\PlanNotesController'                 => ['cat' => 'Register', 'reason' => 'Plan notes query API — needs route'],
  'App\Http\Controllers\Api\PropertyController'                  => ['cat' => 'Register', 'reason' => 'Property TKGM API — needs route'],
  'App\Http\Controllers\Api\QRCodeController'                    => ['cat' => 'Register', 'reason' => 'QR code generation API — needs route'],
  'App\Http\Controllers\Api\ReferenceController'                 => ['cat' => 'Register', 'reason' => 'Reference generation API — needs route'],
  'App\Http\Controllers\Api\SearchController'                    => ['cat' => 'Register', 'reason' => 'Search API — needs route'],
  'App\Http\Controllers\Api\SeasonController'                     => ['cat' => 'Register', 'reason' => 'Season pricing API — needs route'],
  'App\Http\Controllers\Api\SiteApartmanController'             => ['cat' => 'Register', 'reason' => 'Site/apartment API — needs route'],
  'App\Http\Controllers\Api\SiteController'                      => ['cat' => 'Register', 'reason' => 'Site API — needs route'],
  'App\Http\Controllers\Api\SiteOzellikleriController'           => ['cat' => 'Register', 'reason' => 'Site ozellik API — needs route'],
  'App\Http\Controllers\Api\SmartFieldController'                 => ['cat' => 'Register', 'reason' => 'Smart field API — needs route'],
  'App\Http\Controllers\Api\StrategicDecisionController'           => ['cat' => 'Register', 'reason' => 'Strategic decision API — needs route'],
  'App\Http\Controllers\Api\TKGMController'                      => ['cat' => 'Register', 'reason' => 'TKGM API — needs route'],
  'App\Http\Controllers\Api\TalepController'                     => ['cat' => 'Register', 'reason' => 'Talep API — needs route'],
  'App\Http\Controllers\Api\TemplateController'                   => ['cat' => 'Register', 'reason' => 'Template API — needs route'],
  'App\Http\Controllers\Api\UnifiedSearchController'              => ['cat' => 'Register', 'reason' => 'Unified search API — needs route'],
  'App\Http\Controllers\Api\UserController'                       => ['cat' => 'Register', 'reason' => 'User/danisman API — needs route'],
  'App\Http\Controllers\Api\VoiceSearchController'                => ['cat' => 'Register', 'reason' => 'Voice search API — needs route'],
  'App\Http\Controllers\Api\WorkforceDashboardController'         => ['cat' => 'Register', 'reason' => 'Workforce dashboard API — needs route'],
  'App\Http\Controllers\Api\YazlikKiralamaController'            => ['cat' => 'Register', 'reason' => 'Yazlik booking API — needs route'],
  'App\Http\Controllers\Api\AkilliCevreAnaliziController'        => ['cat' => 'Register', 'reason' => 'Environment analysis API — needs route'],
  'App\Http\Controllers\Api\ImageAIController'                  => ['cat' => 'Register', 'reason' => 'Image AI analysis API — needs route'],
  'App\Http\Controllers\Api\PredictiveAnalyticsController'        => ['cat' => 'Register', 'reason' => 'Predictive analytics API — needs route'],
  'App\Http\Controllers\Api\PropertyFeatureSuggestionController'  => ['cat' => 'Register', 'reason' => 'Feature suggestion API — needs route'],

  // API — Internal
  'App\Http\Controllers\Api\AnalyticsController'                 => ['cat' => 'Internal', 'reason' => 'Analytics API — internal service, review needed'],
  'App\Http\Controllers\Api\CortexNeuralNetworkController'       => ['cat' => 'Internal', 'reason' => 'Cortex NN — internal ML service'],
  'App\Http\Controllers\Api\CortexTitleOptimizationController'   => ['cat' => 'Internal', 'reason' => 'Title optimization — internal ML'],
  'App\Http\Controllers\Api\GeminiTemplateController'            => ['cat' => 'Internal', 'reason' => 'Gemini template — internal service'],
  'App\Http\Controllers\Api\DriveWebhookController'              => ['cat' => 'Internal', 'reason' => 'Drive webhook — registered Sprint 4.8, verify route'],
  'App\Http\Controllers\Api\FacebookWebhookController'            => ['cat' => 'Internal', 'reason' => 'Facebook webhook — registered, verify route'],
  'App\Http\Controllers\Api\InstagramWebhookController'          => ['cat' => 'Internal', 'reason' => 'Instagram webhook — registered, verify route'],
  'App\Http\Controllers\Api\WhatsAppWebhookController'           => ['cat' => 'Internal', 'reason' => 'WhatsApp webhook — registered, verify route'],
  'App\Http\Controllers\Api\TelegramWebhookController'            => ['cat' => 'Internal', 'reason' => 'Telegram webhook — registered, verify route'],

  // API — Delete (placeholder / superseded)
  'App\Http\Controllers\Api\AIChatController'                    => ['cat' => 'Delete', 'reason' => 'Single index() method — placeholder, no real function'],
  'App\Http\Controllers\Api\AiHealthController'                  => ['cat' => 'Delete', 'reason' => 'Duplicate of existing /api/ai/health endpoint'],
  'App\Http\Controllers\Api\AIFrontendAssistantController'         => ['cat' => 'Delete', 'reason' => 'Frontend assistant — likely superseded by ChatController'],

  // Frontend — Register
  'App\Http\Controllers\Frontend\DanismanController'              => ['cat' => 'Register', 'reason' => 'Frontend danisman pages — needs route'],
  'App\Http\Controllers\Frontend\DynamicFormController'           => ['cat' => 'Register', 'reason' => 'Dynamic form renderer — needs route'],
  'App\Http\Controllers\Frontend\PreferenceController'            => ['cat' => 'Register', 'reason' => 'Preference settings — needs route'],

  // Owner — Register
  'App\Http\Controllers\Owner\OwnerAuthController'               => ['cat' => 'Register', 'reason' => 'Owner authentication — needs route'],
  'App\Http\Controllers\Owner\OwnerBelgeController'              => ['cat' => 'Register', 'reason' => 'Owner document download — needs route'],
  'App\Http\Controllers\Owner\OwnerContentController'            => ['cat' => 'Register', 'reason' => 'Owner content generation — needs route'],
  'App\Http\Controllers\Owner\OwnerDashboardController'          => ['cat' => 'Register', 'reason' => 'Owner dashboard — needs route'],
  'App\Http\Controllers\Owner\OwnerIlanController'                => ['cat' => 'Register', 'reason' => 'Owner ilan management (8 methods) — needs route'],
  'App\Http\Controllers\Owner\OwnerIntelligenceController'        => ['cat' => 'Register', 'reason' => 'Owner AI readiness — needs route'],
  'App\Http\Controllers\Owner\OwnerMesajController'              => ['cat' => 'Register', 'reason' => 'Owner messaging — needs route'],
  'App\Http\Controllers\Owner\OwnerPhotoController'             => ['cat' => 'Register', 'reason' => 'Owner photo upload — needs route'],
  'App\Http\Controllers\Owner\OwnerReportController'             => ['cat' => 'Register', 'reason' => 'Owner report export — needs route'],
  'App\Http\Controllers\Owner\OwnerTeklifController'             => ['cat' => 'Register', 'reason' => 'Owner offer view — needs route'],
];

// Count
$cats = ['Register'=>[], 'Internal'=>[], 'Event Only'=>[], 'Queue Only'=>[], 'Deprecated'=>[], 'Delete'=>[]];
foreach ($classifications as $ctrl => $info) {
    $cats[$info['cat']][] = ['ctrl' => $ctrl, 'reason' => $info['reason']];
}

$total = count($classifications);
$orphan = 52; // confirmed baseline

echo "=== PHASE 2 ROUTE AUDIT — CLASSIFICATION RESULTS ===\n\n";
echo "Orphan Baseline: 52\n";
echo "Classified: {$total}\n";
echo "Unclassified: " . ($orphan - $total) . "\n\n";

$catLabels = [
    'Register' => '📋 REGISTER — needs route',
    'Internal' => '🔧 INTERNAL — service-layer',
    'Event Only' => '⚡ EVENT ONLY — fires Hermes events',
    'Queue Only' => '📤 QUEUE ONLY — dispatches jobs',
    'Deprecated' => '🗄️ DEPRECATED — archive',
    'Delete' => '🗑️ DELETE — placeholder',
];

foreach (['Register', 'Internal', 'Event Only', 'Queue Only', 'Deprecated', 'Delete'] as $cat) {
    $items = $cats[$cat];
    if (empty($items)) continue;
    sort($items);
    echo "[{$catLabels[$cat]}] — " . count($items) . " controller(s)\n";
    foreach ($items as $item) {
        $short = str_replace('App\Http\Controllers\\', '', $item['ctrl']);
        echo "  • {$short}\n    → {$item['reason']}\n";
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
$register = count($cats['Register']);
$internal = count($cats['Internal']);
$event = count($cats['Event Only']);
$queue = count($cats['Queue Only']);
$depr = count($cats['Deprecated']);
$del = count($cats['Delete']);
echo "Register:    {$register}\n";
echo "Internal:    {$internal}\n";
echo "Event Only:  {$event}\n";
echo "Queue Only:  {$queue}\n";
echo "Deprecated:  {$depr}\n";
echo "Delete:      {$del}\n";
echo "TOTAL:       " . ($register+$internal+$event+$queue+$depr+$del) . "\n";
