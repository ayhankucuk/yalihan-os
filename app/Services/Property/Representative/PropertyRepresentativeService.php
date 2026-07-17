<?php

namespace App\Services\Property\Representative;

use App\Models\Property;
use App\Domain\PropertyRepresentative\Models\PropertyRepresentative;
use App\Enums\TemsilYetkiTipi;
use App\Models\Kisi;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;

/**
 * PropertyRepresentativeService
 *
 * Sprint 12D — Authorized representative management.
 *
 * Invariant: One active representative per authority type per property.
 */
class PropertyRepresentativeService
{
    private TenantContextService $tenantContext;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
    }

    /**
     * Assign a representative to a Property.
     *
     * Idempotent via idempotency key.
     *
     * @throws \DomainException if type already has active representative
     */
    public function assignRepresentative(
        Property $property,
        Kisi $kisi,
        TemsilYetkiTipi $authorityType,
        string $effectiveDate,
        ?string $note = null,
        ?int $actorId = null,
        ?string $idempotencyKey = null,
    ): PropertyRepresentative {
        $tenantId = $this->tenantContext->getTenant()->id;

        return DB::transaction(function () use (
            $property, $kisi, $authorityType, $effectiveDate,
            $note, $actorId, $idempotencyKey, $tenantId
        ) {
            $this->enforceTenantIsolation($property, $kisi, $tenantId);

            $key = $idempotencyKey ?? $this->generateKey(
                $property->id, $kisi->id, $authorityType, $effectiveDate
            );

            $existing = PropertyRepresentative::where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            // Invariant: one active rep per type per property
            $activeRep = PropertyRepresentative::where('property_id', $property->id)
                ->where('temsil_yetu_tipi', $authorityType->value)
                ->active()
                ->first();

            if ($activeRep) {
                throw new \DomainException(sprintf(
                    'Property #%d already has an active %s representative (Kisi #%d). Close it first.',
                    $property->id, $authorityType->label(), $activeRep->kisi_id
                ));
            }

            return PropertyRepresentative::create([
                'tenant_id' => $tenantId,
                'property_id' => $property->id,
                'kisi_id' => $kisi->id,
                'temsil_yetu_tipi' => $authorityType->value,
                'baslangic_tarihi' => $effectiveDate,
                'notu' => $note,
                'olusturan_id' => $actorId ?? auth()->id(),
                'idempotency_key' => $key,
            ]);
        });
    }

    /**
     * Revoke a representative assignment.
     */
    public function revokeRepresentative(
        PropertyRepresentative $representative,
        string $effectiveDate,
        ?int $actorId = null,
    ): void {
        if (!$representative->isActive()) {
            throw new \DomainException('Representative assignment is already revoked.');
        }
        $representative->close($effectiveDate);
    }

    /**
     * Get current active representatives for a Property.
     */
    public function getCurrentRepresentatives(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyRepresentative::where('property_id', $property->id)
            ->active()
            ->with('kisi')
            ->get();
    }

    /**
     * Get representative by authority type.
     */
    public function getRepresentativeByType(
        Property $property,
        TemsilYetkiTipi $type
    ): ?PropertyRepresentative {
        return PropertyRepresentative::where('property_id', $property->id)
            ->where('temsil_yetu_tipi', $type->value)
            ->active()
            ->with('kisi')
            ->first();
    }

    private function enforceTenantIsolation(Property $property, Kisi $kisi, int $tenantId): void
    {
        if ($property->tenant_id !== $tenantId) {
            throw new \RuntimeException('Property does not belong to current tenant.');
        }
        if ($kisi->tenant_id !== $tenantId) {
            throw new \RuntimeException('Kisi does not belong to current tenant.');
        }
    }

    private function generateKey(int $propertyId, int $kisiId, TemsilYetkiTipi $type, string $date): string
    {
        return hash('sha256', implode('|', [
            'assign_representative',
            (string) $propertyId,
            (string) $kisiId,
            $type->value,
            $date,
        ]));
    }
}
