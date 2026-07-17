<?php

namespace App\Domain\PropertyTimeline;

use App\Models\Property;
use App\Domain\PropertyAccess\Models\PropertyAccessAsset;
use App\Domain\PropertyDocument\Models\PropertyDocument;
use App\Domain\PropertyOwnership\Models\PropertyOwnership;
use App\Domain\PropertyRepresentative\Models\PropertyRepresentative;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Collection;

/**
 * PropertyTimelineService
 *
 * Sprint 12D — Immutable operational timeline.
 *
 * Queries all ownership, representative, key and document events
 * for a Property and returns a unified chronological timeline.
 *
 * This is a READ model — no writes happen here.
 */
class PropertyTimelineService
{
    private TenantContextService $tenantContext;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
    }

    /**
     * Get full timeline for a Property.
     *
     * Returns unified, sorted collection of timeline events.
     */
    public function getTimeline(Property $property): Timeline
    {
        $this->enforceTenantIsolation($property);

        $events = collect();

        // Ownership events
        $ownerships = PropertyOwnership::where('property_id', $property->id)
            ->orderBy('baslangic_tarihi', 'asc')
            ->get();

        foreach ($ownerships as $o) {
            $events->push(new TimelineEvent(
                occurredAt: $o->baslangic_tarihi,
                eventType: 'ownership_started',
                aggregateType: 'PropertyOwnership',
                aggregateId: $o->id,
                description: sprintf(
                    '%s olarak atandı: %s (pay: %s)',
                    $o->sahiplik_tipi,
                    $o->kisi?->tam_ad ?? "Kisi #{$o->kisi_id}",
                    $o->pay_orani
                ),
                actorId: $o->olusturan_id ?? null,
                metadata: [
                    'kisi_id' => $o->kisi_id,
                    'pay_orani' => $o->pay_orani,
                    'sahiplik_tipi' => $o->sahiplik_tipi,
                    'atama_kaynagi' => $o->atama_kaynagi,
                ],
            ));

            if ($o->bitis_tarihi) {
                $events->push(new TimelineEvent(
                    occurredAt: $o->bitis_tarihi,
                    eventType: 'ownership_ended',
                    aggregateType: 'PropertyOwnership',
                    aggregateId: $o->id,
                    description: sprintf(
                        'Sahiplik sona erdi: %s',
                        $o->kisi?->tam_ad ?? "Kisi #{$o->kisi_id}"
                    ),
                    actorId: null,
                    metadata: ['reason' => 'transfer_or_close'],
                ));
            }
        }

        // Representative events
        $reps = PropertyRepresentative::where('property_id', $property->id)
            ->orderBy('baslangic_tarihi', 'asc')
            ->get();

        foreach ($reps as $rep) {
            $events->push(new TimelineEvent(
                occurredAt: $rep->baslangic_tarihi,
                eventType: 'representative_assigned',
                aggregateType: 'PropertyRepresentative',
                aggregateId: $rep->id,
                description: sprintf(
                    'Yetkili temsilci atandı: %s (%s)',
                    $rep->kisi?->tam_ad ?? "Kisi #{$rep->kisi_id}",
                    $rep->temsil_yetu_tipi
                ),
                actorId: $rep->olusturan_id ?? null,
                metadata: [
                    'kisi_id' => $rep->kisi_id,
                    'temsil_yetu_tipi' => $rep->temsil_yetu_tipi,
                ],
            ));

            if ($rep->bitis_tarihi) {
                $events->push(new TimelineEvent(
                    occurredAt: $rep->bitis_tarihi,
                    eventType: 'representative_revoked',
                    aggregateType: 'PropertyRepresentative',
                    aggregateId: $rep->id,
                    description: sprintf(
                        'Temsil yetkisi sona erdi: %s',
                        $rep->kisi?->tam_ad ?? "Kisi #{$rep->kisi_id}"
                    ),
                    actorId: null,
                    metadata: [],
                ));
            }
        }

        // Access asset events
        $assets = PropertyAccessAsset::where('property_id', $property->id)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($assets as $asset) {
            $events->push(new TimelineEvent(
                occurredAt: $asset->created_at,
                eventType: 'access_asset_registered',
                aggregateType: 'PropertyAccessAsset',
                aggregateId: $asset->id,
                description: sprintf('Erişim varlığı kaydedildi: %s', $asset->varlik_tipi),
                actorId: $asset->olusturan_id,
                metadata: [
                    'varlik_tipi' => $asset->varlik_tipi,
                    'durum' => $asset->durum,
                ],
            ));
        }

        // Document events
        $docs = PropertyDocument::where('property_id', $property->id)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($docs as $doc) {
            $events->push(new TimelineEvent(
                occurredAt: $doc->created_at,
                eventType: 'document_registered',
                aggregateType: 'PropertyDocument',
                aggregateId: $doc->id,
                description: sprintf('Belge eklendi: %s', $doc->dokuman_tipi),
                actorId: $doc->olusturan_id,
                metadata: [
                    'dokuman_tipi' => $doc->dokuman_tipi,
                    'durum' => $doc->durum,
                    'son_gecerlilik_tarihi' => $doc->son_gecerlilik_tarihi?->toDateString(),
                ],
            ));
        }

        // Sort by occurredAt
        $sorted = $events->sortBy(fn ($e) => $e->occurredAt)->values();

        return new Timeline(
            propertyId: $property->id,
            tenantId: $property->tenant_id,
            events: $sorted->toArray(),
        );
    }

    private function enforceTenantIsolation(Property $property): void
    {
        if (!$this->tenantContext->hasTenant()) {
            throw new \RuntimeException('Tenant context not established.');
        }
        if ($property->tenant_id !== $this->tenantContext->getTenant()->id) {
            throw new \RuntimeException('Property does not belong to current tenant.');
        }
    }
}

/**
 * Immutable timeline event record.
 */
final readonly class TimelineEvent
{
    public function __construct(
        public \DateTimeInterface $occurredAt,
        public string $eventType,
        public string $aggregateType,
        public int $aggregateId,
        public string $description,
        public ?int $actorId,
        public array $metadata,
    ) {}
}

/**
 * Timeline collection for a Property.
 */
final readonly class Timeline
{
    public function __construct(
        public int $propertyId,
        public int $tenantId,
        public array $events,
    ) {}

    public function toArray(): array
    {
        return [
            'property_id' => $this->propertyId,
            'tenant_id' => $this->tenantId,
            'events' => array_map(fn (TimelineEvent $e) => [
                'occurred_at' => $e->occurredAt instanceof \Carbon\Carbon
                    ? $e->occurredAt->toDateString()
                    : $e->occurredAt->format('Y-m-d'),
                'event_type' => $e->eventType,
                'aggregate_type' => $e->aggregateType,
                'aggregate_id' => $e->aggregateId,
                'description' => $e->description,
                'actor_id' => $e->actorId,
                'metadata' => $e->metadata,
            ], $this->events),
        ];
    }
}
