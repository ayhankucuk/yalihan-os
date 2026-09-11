<?php

namespace App\Services\ActionCenter;

use App\Events\IlanCreated;
use App\Events\IlanPriceChanged;
use App\Events\IlanYayinlandiEvent;
use App\Events\LeadAgentAtandi;
use App\Events\LeadOlusturuldu;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Events\Reservation\ReservationPayoutReadyEvent;
use App\Events\TalepReceived;
use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Events\Workforce\PublishingDecisionReady;
use App\Models\ActionEvidence;
use App\Models\Ilan;
use App\Models\Lead;
use App\Models\PropertyReservation;
use App\Models\Talep;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\SaaS\TenantContextService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ActionCenterService — Central orchestrator for Sprint 15 Action Center.
 *
 * Transforms domain events into persisted Gorev records with priority scoring,
 * assignment tracking, evidence collection, and escalation support.
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.2
 *
 * Key contracts:
 * - Idempotency: same source_event + entity_id combination → no duplicate Gorev
 * - Tenant isolation: all actions are tenant-scoped via BelongsToTenant trait
 * - Lifecycle: GENERATED → ASSIGNED → IN_PROGRESS → DONE/CANCEL
 * - Evidence: trackActionEvidence records proof of completion
 */
class ActionCenterService
{
    /** @var TenantContextService */
    protected TenantContextService $tenantService;

    /**
     * Priority scoring map — architecture §3.3.
     * Maps action types to [priority, score].
     */
    public const PRIORITY_MAP = [
        'contact_lead_sla'           => ['acil', 100],
        'cancel_readiness'           => ['acil', 95],
        'process_payout'             => ['acil', 90],
        'contact_lead'               => ['acil', 85],
        'ilan_foto_yukle'            => ['yuksek', 75],
        'lead_matching_check'        => ['yuksek', 72],
        'review_publication_decision'=> ['yuksek', 70],
        'contact_assigned_lead'      => ['yuksek', 68],
        'match_demand_to_listings'   => ['yuksek', 65],
        're_evaluate_matching'       => ['normal', 50],
        'post_stay_inspection'       => ['normal', 40],
        'review_ai_description'      => ['normal', 30],
        'ilan_aciklama_yaz'          => ['yuksek', 75],
        'ilan_fiyatlandir'           => ['normal', 45],
    ];

    public function __construct(TenantContextService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Generate Gorev records from a domain event.
     *
     * Maps 11 event types to action(s) per architecture §3.1.
     * ReservationCreatedEvent is intentionally NOT handled here —
     * it's already processed by CreateOperationalTasksJob.
     *
     * Idempotency: checks source_event + entity_id before creating.
     *
     * @param object $event Domain event instance
     * @return Collection<Gorev> Created Gorev records (may be empty if idempotent skip)
     */
    public function generateActionsFromEvent(object $event): Collection
    {
        $eventClass = get_class($event);
        $actions = collect();

        try {
            match (true) {
                $event instanceof IlanCreated => $actions = $this->generateIlanCreatedActions($event),
                $event instanceof IlanYayinlandiEvent => $actions = $this->generateIlanPublishedActions($event),
                $event instanceof IlanPriceChanged => $actions = $this->generateIlanPriceChangedActions($event),
                $event instanceof LeadOlusturuldu => $actions = $this->generateLeadCreatedActions($event),
                $event instanceof LeadAgentAtandi => $actions = $this->generateLeadAssignedActions($event),
                $event instanceof TalepReceived => $actions = $this->generateTalepReceivedActions($event),
                $event instanceof PublishingDecisionReady => $actions = $this->generatePublishingDecisionActions($event),
                $event instanceof PhotoAnalysisCompleted => $actions = $this->generatePhotoAnalysisActions($event),
                $event instanceof ReservationCancelledEvent => $actions = $this->generateReservationCancelledActions($event),
                $event instanceof ReservationCompletedEvent => $actions = $this->generateReservationCompletedActions($event),
                $event instanceof ReservationPayoutReadyEvent => $actions = $this->generatePayoutReadyActions($event),
                default => $actions = collect(),
            };
        } catch (\Throwable $e) {
            Log::error('ActionCenterService: generateActionsFromEvent failed', [
                'event_class' => $eventClass,
                'error' => $e->getMessage(),
            ]);
        }

        return $actions;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Event-to-Action generators (private)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * IlanCreated → 3 Gorev: foto yükle, açıklama yaz, fiyatlandırma.
     */
    private function generateIlanCreatedActions(IlanCreated $event): Collection
    {
        $ilan = $event->ilan;
        $tenantId = $ilan->tenant_id ?? $this->resolveTenantId();
        $actions = collect();

        $definitions = [
            [
                'action_type' => 'ilan_foto_yukle',
                'baslik' => "Fotoğraf Yükle: {$ilan->baslik}",
                'aciklama' => "İlan #{$ilan->id} için fotoğraflar yüklenmeli.",
                'gorev_tipi' => 'ilan_hazirlama',
                'deadline_hours' => 24,
            ],
            [
                'action_type' => 'ilan_aciklama_yaz',
                'baslik' => "Açıklama Yaz: {$ilan->baslik}",
                'aciklama' => "İlan #{$ilan->id} için açıklama metni hazırlanmalı.",
                'gorev_tipi' => 'ilan_hazirlama',
                'deadline_hours' => 48,
            ],
            [
                'action_type' => 'ilan_fiyatlandir',
                'baslik' => "Fiyatlandırma: {$ilan->baslik}",
                'aciklama' => "İlan #{$ilan->id} için fiyatlandırma stratejisi belirlenmeli.",
                'gorev_tipi' => 'ilan_hazirlama',
                'deadline_hours' => 72,
            ],
        ];

        foreach ($definitions as $def) {
            $gorev = $this->createActionIfNotExists([
                'baslik' => $def['baslik'],
                'aciklama' => $def['aciklama'],
                'gorev_tipi' => $def['gorev_tipi'],
                'action_type' => $def['action_type'],
                'source_event' => IlanCreated::class,
                'tenant_id' => $tenantId,
                'ilan_id' => $ilan->id,
                'deadline' => now()->addHours($def['deadline_hours']),
            ]);
            if ($gorev) {
                $actions->push($gorev);
            }
        }

        return $actions;
    }

    /**
     * IlanYayinlandiEvent → 1 Gorev: lead matching check (+1h).
     */
    private function generateIlanPublishedActions(IlanYayinlandiEvent $event): Collection
    {
        $ilan = $event->ilan;
        $tenantId = $ilan->tenant_id ?? $this->resolveTenantId();

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Lead Eşleştirme Kontrolü: {$ilan->baslik}",
            'aciklama' => "Yayınlanan ilan #{$ilan->id} için mevcut taleplerle eşleştirme kontrolü.",
            'gorev_tipi' => 'musteri_takibi',
            'action_type' => 'lead_matching_check',
            'source_event' => IlanYayinlandiEvent::class,
            'tenant_id' => $tenantId,
            'ilan_id' => $ilan->id,
            'deadline' => now()->addHour(),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * IlanPriceChanged → 1 Gorev: re-evaluate matching (+4h).
     */
    private function generateIlanPriceChangedActions(IlanPriceChanged $event): Collection
    {
        $ilan = $event->ilan;
        $tenantId = $ilan->tenant_id ?? $this->resolveTenantId();

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Fiyat Değişimi Eşleştirme: {$ilan->baslik}",
            'aciklama' => "Fiyat değişti ({$event->oldPrice} → {$event->newPrice}). Eşleştirme yeniden değerlendirilmeli.",
            'gorev_tipi' => 'diger',
            'action_type' => 're_evaluate_matching',
            'source_event' => IlanPriceChanged::class,
            'tenant_id' => $tenantId,
            'ilan_id' => $ilan->id,
            'deadline' => now()->addHours(4),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * LeadOlusturuldu → 1 Gorev: contact lead SLA (+2h, acil).
     */
    private function generateLeadCreatedActions(LeadOlusturuldu $event): Collection
    {
        $lead = $event->lead;
        $tenantId = $lead->tenant_id ?? $this->resolveTenantId();

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Lead İletişim (SLA): {$lead->name}",
            'aciklama' => "Yeni lead #{$lead->id} oluşturuldu. 2 saat içinde iletişime geçilmeli.",
            'gorev_tipi' => 'musteri_takibi',
            'action_type' => 'contact_lead_sla',
            'source_event' => LeadOlusturuldu::class,
            'tenant_id' => $tenantId,
            'lead_id' => $lead->id,
            'deadline' => now()->addHours(2),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * LeadAgentAtandi → 1 Gorev: contact assigned lead (+4h).
     */
    private function generateLeadAssignedActions(LeadAgentAtandi $event): Collection
    {
        $lead = $event->lead;
        $tenantId = $lead->tenant_id ?? $this->resolveTenantId();

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Atanan Lead İletişim: {$lead->name}",
            'aciklama' => "Lead #{$lead->id} size atandı. 4 saat içinde iletişime geçin.",
            'gorev_tipi' => 'musteri_takibi',
            'action_type' => 'contact_assigned_lead',
            'source_event' => LeadAgentAtandi::class,
            'tenant_id' => $tenantId,
            'lead_id' => $lead->id,
            'atanan_user_id' => $event->yeniAgentId,
            'deadline' => now()->addHours(4),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * TalepReceived → 1 Gorev: match demand to listings (+4h).
     */
    private function generateTalepReceivedActions(TalepReceived $event): Collection
    {
        $talep = $event->talep;
        $tenantId = $talep->tenant_id ?? $this->resolveTenantId();

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Talep Eşleştirme: Talep #{$talep->id}",
            'aciklama' => "Yeni talep alındı. Mevcut ilanlarla eşleştirme yapılmalı.",
            'gorev_tipi' => 'musteri_takibi',
            'action_type' => 'match_demand_to_listings',
            'source_event' => TalepReceived::class,
            'tenant_id' => $tenantId,
            'deadline' => now()->addHours(4),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * PublishingDecisionReady → 1 Gorev: review publication decision (+24h).
     */
    private function generatePublishingDecisionActions(PublishingDecisionReady $event): Collection
    {
        $tenantId = $event->tenantId() ?? $this->resolveTenantId();
        $ilanId = $event->workspace->ilan_id ?? null;

        $gorev = $this->createActionIfNotExists([
            'baslik' => "Yayın Kararı İnceleme: İlan #{$ilanId}",
            'aciklama' => "Yayın kararı hazır. Karar incelenmeli ve onaylanmalı.",
            'gorev_tipi' => 'ilan_hazirlama',
            'action_type' => 'review_publication_decision',
            'source_event' => PublishingDecisionReady::class,
            'tenant_id' => $tenantId,
            'ilan_id' => $ilanId,
            'deadline' => now()->addHours(24),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * PhotoAnalysisCompleted → 1 Gorev: review AI description (+48h).
     */
    private function generatePhotoAnalysisActions(PhotoAnalysisCompleted $event): Collection
    {
        $tenantId = $event->tenantId() ?? $this->resolveTenantId();
        $ilanId = $event->workspace->ilan_id ?? null;

        $gorev = $this->createActionIfNotExists([
            'baslik' => "AI Açıklama İnceleme: İlan #{$ilanId}",
            'aciklama' => "Fotoğraf analizi tamamlandı. AI ürettiği açıklama incelenmeli.",
            'gorev_tipi' => 'ilan_hazirlama',
            'action_type' => 'review_ai_description',
            'source_event' => PhotoAnalysisCompleted::class,
            'tenant_id' => $tenantId,
            'ilan_id' => $ilanId,
            'deadline' => now()->addHours(48),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * ReservationCancelledEvent → 1 Gorev: cancel readiness (immediate, acil).
     */
    private function generateReservationCancelledActions(ReservationCancelledEvent $event): Collection
    {
        $gorev = $this->createActionIfNotExists([
            'baslik' => "İptal Hazırlığı: Rezervasyon #{$event->reservationId}",
            'aciklama' => "Rezervasyon iptal edildi. Hazırlık görevleri iptal edilmeli.",
            'gorev_tipi' => 'kontrol',
            'action_type' => 'cancel_readiness',
            'source_event' => ReservationCancelledEvent::class,
            'tenant_id' => $event->tenantId,
            'ilan_id' => $event->ilanId,
            'reservation_id' => $event->reservationId,
            'deadline' => now(),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * ReservationCompletedEvent → 1 Gorev: post-stay inspection (+1d).
     */
    private function generateReservationCompletedActions(ReservationCompletedEvent $event): Collection
    {
        $gorev = $this->createActionIfNotExists([
            'baslik' => "Çıkış Sonrası Kontrol: Rezervasyon #{$event->reservationId}",
            'aciklama' => "Misafir çıkışı tamamlandı. Mülk kontrolü yapılmalı.",
            'gorev_tipi' => 'kontrol',
            'action_type' => 'post_stay_inspection',
            'source_event' => ReservationCompletedEvent::class,
            'tenant_id' => $event->tenantId,
            'ilan_id' => $event->ilanId,
            'reservation_id' => $event->reservationId,
            'deadline' => now()->addDay(),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    /**
     * ReservationPayoutReadyEvent → 1 Gorev: process payout (+24h, acil).
     */
    private function generatePayoutReadyActions(ReservationPayoutReadyEvent $event): Collection
    {
        $gorev = $this->createActionIfNotExists([
            'baslik' => "Ödeme İşle: Rezervasyon #{$event->reservationId}",
            'aciklama' => "Ödeme hazır. Sahibe ödeme yapılmalı. Tutar: {$event->ownerEntitlement} {$event->currency}",
            'gorev_tipi' => 'diger',
            'action_type' => 'process_payout',
            'source_event' => ReservationPayoutReadyEvent::class,
            'tenant_id' => $event->tenantId,
            'ilan_id' => $event->ilanId,
            'reservation_id' => $event->reservationId,
            'deadline' => now()->addHours(24),
        ]);

        return $gorev ? collect([$gorev]) : collect();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public API methods (architecture §3.2)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Prioritize actions using business rules (architecture §3.3).
     *
     * Sorts by priority score descending. Also normalizes the oncelik field
     * on each Gorev based on the PRIORITY_MAP.
     *
     * @param Collection<Gorev> $actions
     * @return Collection<Gorev> Sorted by priority score (highest first)
     */
    public function prioritizeActions(Collection $actions): Collection
    {
        return $actions->sortByDesc(function (Gorev $gorev) {
            $actionType = $this->extractActionType($gorev);
            $score = self::PRIORITY_MAP[$actionType][1] ?? 0;

            // Overdue actions get +20 boost
            if ($gorev->geciktiMi()) {
                $score += 20;
            }

            return $score;
        })->values();
    }

    /**
     * Assign a Gorev to a user. Sets assigned_at timestamp.
     *
     * @param Gorev $gorev
     * @param int $userId
     * @return Gorev
     */
    public function assignAction(Gorev $gorev, int $userId): Gorev
    {
        $gorev->update([
            'atanan_user_id' => $userId,
            'assigned_at' => now(),
            'gorev_durumu' => $gorev->gorev_durumu === 'bekliyor' ? 'devam_ediyor' : $gorev->gorev_durumu,
        ]);

        Log::info('ActionCenterService: action assigned', [
            'gorev_id' => $gorev->id,
            'user_id' => $userId,
        ]);

        return $gorev->fresh();
    }

    /**
     * Store evidence for a Gorev in the action_evidence table.
     *
     * Phase 3: replaces JSON notlar field storage with a dedicated table.
     * Backward compatible: creates ActionEvidence record and appends
     * a reference to gorev.notlar JSON for any legacy consumers.
     *
     * @param Gorev $gorev
     * @param array $evidence ['type' => 'note|photo|system_log|screenshot', 'data' => ...]
     * @param int|null $recordedBy User ID who recorded this evidence
     * @return ActionEvidence
     */
    public function trackActionEvidence(Gorev $gorev, array $evidence, ?int $recordedBy = null): ActionEvidence
    {
        $type = $evidence['type'] ?? 'system_log';
        $data = $evidence['data'] ?? $evidence;

        $record = ActionEvidence::create([
            'gorev_id' => $gorev->id,
            'evidence_type' => $type,
            'evidence_data' => $data,
            'recorded_by' => $recordedBy,
        ]);

        // Backward-compat: also append ref to gorev.notlar JSON
        $legacy = $gorev->notlar ? json_decode($gorev->notlar, true) : [];
        if (!is_array($legacy)) {
            $legacy = [];
        }
        $legacy[] = [
            'type' => $type,
            'data' => $data,
            'recorded_at' => now()->toIso8601String(),
            'evidence_id' => $record->id,
        ];
        $gorev->updateQuietly(['notlar' => json_encode($legacy, JSON_UNESCAPED_UNICODE)]);

        Log::info('ActionCenterService: evidence tracked', [
            'gorev_id' => $gorev->id,
            'evidence_id' => $record->id,
            'evidence_type' => $type,
        ]);

        return $record;
    }

    /**
     * Get the action queue for a tenant, filtered and paginated.
     *
     * @param int $tenantId
     * @param array $filters ['durum' => ?, 'oncelik' => ?, 'assigned_to' => ?]
     * @return LengthAwarePaginator
     */
    public function getActionQueue(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Gorev::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('source_event');

        if (isset($filters['durum'])) {
            $query->where('gorev_durumu', $filters['durum']);
        }

        if (isset($filters['oncelik'])) {
            $query->where('oncelik', $filters['oncelik']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('atanan_user_id', $filters['assigned_to']);
        }

        if (!empty($filters['overdue'])) {
            $query->where('bitis_tarihi', '<', now())
                ->whereNotIn('gorev_durumu', ['tamamlandi', 'iptal']);
        }

        // Sort by priority score then deadline
        $priorityOrder = ['acil' => 1, 'yuksek' => 2, 'normal' => 3, 'dusuk' => 4];

        return $query
            ->orderByRaw("CASE oncelik WHEN 'acil' THEN 1 WHEN 'yuksek' THEN 2 WHEN 'normal' THEN 3 WHEN 'dusuk' THEN 4 ELSE 5 END")
            ->orderBy('bitis_tarihi', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get overdue actions for a tenant.
     *
     * @param int $tenantId
     * @return Collection<Gorev>
     */
    public function getOverdueActions(int $tenantId): Collection
    {
        return Gorev::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('source_event')
            ->where('bitis_tarihi', '<', now())
            ->where('gorev_durumu', '!=', 'tamamlandi')
            ->where('gorev_durumu', '!=', 'iptal')
            ->orderBy('bitis_tarihi', 'asc')
            ->get();
    }

    /**
     * Escalate a Gorev — increase priority and log reason.
     *
     * @param Gorev $gorev
     * @param string $reason
     * @return Gorev
     */
    public function escalateAction(Gorev $gorev, string $reason): Gorev
    {
        $escalatedPriority = match ($gorev->oncelik) {
            'dusuk' => 'normal',
            'normal' => 'yuksek',
            'yuksek' => 'acil',
            'acil' => 'acil',
            default => 'yuksek',
        };

        $gorev->update([
            'oncelik' => $escalatedPriority,
            'ai_reasoning' => $gorev->ai_reasoning
                ? $gorev->ai_reasoning . "\n[ESCALATION] " . $reason
                : "[ESCALATION] " . $reason,
        ]);

        Log::warning('ActionCenterService: action escalated', [
            'gorev_id' => $gorev->id,
            'reason' => $reason,
            'new_priority' => $escalatedPriority,
        ]);

        return $gorev->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a Gorev if an idempotent match doesn't already exist.
     *
     * Idempotency: source_event + (ilan_id | reservation_id | lead_id) combination.
     *
     * @param array $params Action definition
     * @return Gorev|null null if idempotent skip
     */
    private function createActionIfNotExists(array $params): ?Gorev
    {
        $sourceEvent = $params['source_event'];
        $tenantId = $params['tenant_id'] ?? null;

        // Idempotency check: same source_event + same entity
        $query = Gorev::withoutTenant()
            ->where('source_event', $sourceEvent);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (!empty($params['ilan_id'])) {
            $query->where('ilan_id', $params['ilan_id']);
        }
        if (!empty($params['reservation_id'])) {
            $query->where('reservation_id', $params['reservation_id']);
        }
        if (!empty($params['lead_id'])) {
            $query->where('lead_id', $params['lead_id']);
        }

        // For IlanCreated, also check action_type to allow 3 distinct actions
        if (!empty($params['action_type'])) {
            $query->where('baslik', 'like', '%' . explode(':', $params['baslik'])[0] . '%');
        }

        if ($query->exists()) {
            Log::info('ActionCenterService: idempotent skip', [
                'source_event' => $sourceEvent,
                'ilan_id' => $params['ilan_id'] ?? null,
                'reservation_id' => $params['reservation_id'] ?? null,
                'lead_id' => $params['lead_id'] ?? null,
            ]);
            return null;
        }

        // Resolve priority from PRIORITY_MAP
        $actionType = $params['action_type'];
        $priorityInfo = self::PRIORITY_MAP[$actionType] ?? ['normal', 50];
        $priority = $priorityInfo[0];

        $gorev = Gorev::withoutTenant()->create([
            'baslik' => $params['baslik'],
            'aciklama' => $params['aciklama'],
            'gorev_tipi' => $params['gorev_tipi'],
            'gorev_durumu' => 'bekliyor',
            'oncelik' => $priority,
            'olusturan_user_id' => null, // system-generated (null = no FK constraint)
            'atanan_user_id' => $params['atanan_user_id'] ?? null,
            'ilan_id' => $params['ilan_id'] ?? null,
            'reservation_id' => $params['reservation_id'] ?? null,
            'lead_id' => $params['lead_id'] ?? null,
            'tenant_id' => $tenantId,
            'source_event' => $sourceEvent,
            'source_module' => 'ActionCenter',
            'ai_confidence_score' => null,
            'ai_reasoning' => $params['aciklama'],
            'ai_model_version' => null,
            'baslangic_tarihi' => now(),
            'bitis_tarihi' => $params['deadline'] ?? null,
            'tamamlanma_yuzdesi' => 0,
            'notlar' => null,
        ]);

        Log::info('ActionCenterService: action created', [
            'gorev_id' => $gorev->id,
            'source_event' => $sourceEvent,
            'action_type' => $actionType,
            'oncelik_seviyesi' => $priority,
            'tenant_id' => $tenantId,
        ]);

        return $gorev;
    }

    /**
     * Extract action type from a Gorev by matching its baslik prefix.
     */
    private function extractActionType(Gorev $gorev): string
    {
        $baslik = $gorev->baslik;
        foreach (self::PRIORITY_MAP as $actionType => $_) {
            $prefix = match ($actionType) {
                'ilan_foto_yukle' => 'Fotoğraf Yükle',
                'ilan_aciklama_yaz' => 'Açıklama Yaz',
                'ilan_fiyatlandir' => 'Fiyatlandırma',
                'lead_matching_check' => 'Lead Eşleştirme Kontrolü',
                're_evaluate_matching' => 'Fiyat Değişimi Eşleştirme',
                'contact_lead_sla' => 'Lead İletişim (SLA)',
                'contact_assigned_lead' => 'Atanan Lead İletişim',
                'match_demand_to_listings' => 'Talep Eşleştirme',
                'review_publication_decision' => 'Yayın Kararı İnceleme',
                'review_ai_description' => 'AI Açıklama İnceleme',
                'cancel_readiness' => 'İptal Hazırlığı',
                'post_stay_inspection' => 'Çıkış Sonrası Kontrol',
                'process_payout' => 'Ödeme İşle',
                default => null,
            };
            if ($prefix && str_starts_with($baslik, $prefix)) {
                return $actionType;
            }
        }
        return 'normal';
    }

    /**
     * Resolve tenant ID from context service.
     */
    private function resolveTenantId(): ?int
    {
        if ($this->tenantService->hasTenant()) {
            return $this->tenantService->getTenant()->id;
        }
        return null;
    }
}
