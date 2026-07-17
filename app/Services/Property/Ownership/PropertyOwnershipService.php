<?php

namespace App\Services\Property\Ownership;

use App\Models\Property;
use App\Domain\PropertyOwnership\Models\PropertyOwnership;
use App\Enums\SahiplikTipi;
use App\Models\Kisi;
use App\Models\User;
use App\Policies\KisiPolicy;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;

/**
 * PropertyOwnershipService
 *
 * Sprint 12D — Canonical ownership management.
 *
 * Design rules:
 * - All write operations are atomic (single transaction)
 * - Share validation happens WITHIN the same transaction
 * - Idempotency key prevents duplicate processing
 * - Tenant isolation enforced on every operation
 * - No UPDATE/DELETE — only close + open
 *
 * @throws \DomainException for invariant violations
 * @throws \RuntimeException for tenant isolation violations
 */
class PropertyOwnershipService
{
    private TenantContextService $tenantContext;
    private KisiPolicy $kisiPolicy;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
        $this->kisiPolicy = app(KisiPolicy::class);
    }

    /**
     * Assign initial owner(s) to a Property.
     *
     * Idempotent: if idempotency key already exists, returns existing record.
     *
     * @throws \RuntimeException if cross-tenant detected
     * @throws \DomainException if share sum would exceed 1.0
     */
    public function assignOwnership(
        Property $property,
        Kisi $kisi,
        float $share,
        SahiplikTipi $ownershipType,
        string $effectiveDate,
        string $source = 'MANUAL',
        ?string $note = null,
        ?int $actorId = null,
        ?string $idempotencyKey = null,
    ): PropertyOwnership {
        return DB::transaction(function () use (
            $property, $kisi, $share, $ownershipType,
            $effectiveDate, $source, $note, $actorId, $idempotencyKey
        ) {
            $this->enforceTenantIsolation($property, $kisi);

            $key = $idempotencyKey ?? PropertyOwnership::generateIdempotencyKey(
                $property->id, $kisi->id, $effectiveDate, 'assign'
            );

            // Idempotency check
            $existing = PropertyOwnership::where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            // Share validation — within same transaction
            $this->validateShareSum($property->id, $share, null);

            $ownership = PropertyOwnership::create([
                'tenant_id' => $this->tenantContext->getTenant()->id,
                'property_id' => $property->id,
                'kisi_id' => $kisi->id,
                'pay_orani' => $share,
                'sahiplik_tipi' => $ownershipType->value,
                'baslangic_tarihi' => $effectiveDate,
                'atama_kaynagi' => $source,
                'atama_notu' => $note,
                'olusturan_id' => $actorId ?? auth()->id(),
                'idempotency_key' => $key,
            ]);

            return $ownership;
        });
    }

    /**
     * Transfer ownership: close current owner's record + open new owner's record.
     *
     * All within a single atomic transaction.
     *
     * @throws \DomainException if current owner not found
     * @throws \DomainException if share sum would exceed 1.0
     */
    public function transferOwnership(
        Property $property,
        Kisi $fromKisi,
        Kisi $toKisi,
        float $share,
        string $effectiveDate,
        string $source = 'MANUAL',
        ?string $note = null,
        ?int $actorId = null,
        ?string $idempotencyKey = null,
    ): array {
        return DB::transaction(function () use (
            $property, $fromKisi, $toKisi, $share,
            $effectiveDate, $source, $note, $actorId, $idempotencyKey
        ) {
            $this->enforceTenantIsolation($property, $fromKisi);
            $this->enforceTenantIsolation($property, $toKisi);

            $key = $idempotencyKey ?? PropertyOwnership::generateIdempotencyKey(
                $property->id, $toKisi->id, $effectiveDate, 'transfer'
            );

            // Idempotency check
            $existing = PropertyOwnership::where('idempotency_key', $key)->first();
            if ($existing) {
                $closed = PropertyOwnership::where('property_id', $property->id)
                    ->where('kisi_id', $fromKisi->id)
                    ->active()
                    ->first();
                return [$closed, $existing];
            }

            // Find and close current ownership
            $currentOwnership = PropertyOwnership::where('property_id', $property->id)
                ->where('kisi_id', $fromKisi->id)
                ->active()
                ->first();

            if (!$currentOwnership) {
                throw new \DomainException(
                    "No active ownership found for Kisi #{$fromKisi->id} on Property #{$property->id}"
                );
            }

            $currentOwnership->close($effectiveDate, $actorId ?? auth()->id());

            // Validate new share sum
            $this->validateShareSum($property->id, $share, null);

            // Open new ownership
            $newOwnership = PropertyOwnership::create([
                'tenant_id' => $this->tenantContext->getTenant()->id,
                'property_id' => $property->id,
                'kisi_id' => $toKisi->id,
                'pay_orani' => $share,
                'sahiplik_tipi' => SahiplikTipi::OWNER->value,
                'baslangic_tarihi' => $effectiveDate,
                'atama_kaynagi' => $source,
                'atama_notu' => $note,
                'olusturan_id' => $actorId ?? auth()->id(),
                'idempotency_key' => $key,
            ]);

            return [$currentOwnership, $newOwnership];
        });
    }

    /**
     * Change ownership shares for multiple owners atomically.
     *
     * All current active records are closed, new records opened.
     */
    public function changeShares(
        Property $property,
        array $changes,
        string $effectiveDate,
        ?int $actorId = null,
        ?string $idempotencyKey = null,
    ): array {
        return DB::transaction(function () use ($property, $changes, $effectiveDate, $actorId, $idempotencyKey) {
            $key = $idempotencyKey ?? hash('sha256', json_encode([
                'property' => $property->id,
                'changes' => $changes,
                'date' => $effectiveDate,
            ]));

            $existing = PropertyOwnership::where('idempotency_key', $key)->first();
            if ($existing) {
                return PropertyOwnership::where('property_id', $property->id)
                    ->active()
                    ->get()
                    ->toArray();
            }

            // Close all current active ownerships
            $currentOwners = PropertyOwnership::where('property_id', $property->id)
                ->active()
                ->get();

            $closed = [];
            foreach ($currentOwners as $co) {
                $co->close($effectiveDate, $actorId ?? auth()->id());
                $closed[] = $co;
            }

            // Validate total new shares
            $totalShare = array_sum(array_column($changes, 'share'));
            $this->validateShareSum($property->id, $totalShare, null);

            // Open new ownerships
            $opened = [];
            foreach ($changes as $change) {
                $opened[] = PropertyOwnership::create([
                    'tenant_id' => $this->tenantContext->getTenant()->id,
                    'property_id' => $property->id,
                    'kisi_id' => $change['kisi_id'],
                    'pay_orani' => $change['share'],
                    'sahiplik_tipi' => $change['type'] ?? SahiplikTipi::OWNER->value,
                    'baslangic_tarihi' => $effectiveDate,
                    'atama_kaynagi' => 'MANUAL',
                    'olusturan_id' => $actorId ?? auth()->id(),
                    'idempotency_key' => $key . '|' . $change['kisi_id'],
                ]);
            }

            return [$closed, $opened];
        });
    }

    /**
     * Get current active owners for a Property.
     */
    public function getCurrentOwnership(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyOwnership::where('property_id', $property->id)
            ->active()
            ->with('kisi')
            ->get();
    }

    /**
     * Get full ownership history for a Property.
     */
    public function getOwnershipHistory(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyOwnership::where('property_id', $property->id)
            ->with('kisi')
            ->orderBy('baslangic_tarihi', 'asc')
            ->get();
    }

    // ─── Private Helpers ──────────────────────────────────────────────────

    /**
     * Enforce tenant isolation: property and kisi must belong to current tenant.
     */
    private function enforceTenantIsolation(Property $property, Kisi $kisi): void
    {
        if (!$this->tenantContext->hasTenant()) {
            throw new \RuntimeException(
                'Tenant context not established. Cross-tenant access prevented.'
            );
        }

        $currentTenantId = $this->tenantContext->getTenant()->id;

        if ($property->tenant_id !== $currentTenantId) {
            throw new \RuntimeException(
                "Property #{$property->id} does not belong to current tenant."
            );
        }

        if ($kisi->tenant_id !== $currentTenantId) {
            throw new \RuntimeException(
                "Kisi #{$kisi->id} does not belong to current tenant."
            );
        }
    }

    /**
     * Validate that current shares + new share <= 1.0
     *
     * @throws \DomainException if sum exceeds 1.0
     */
    private function validateShareSum(int $propertyId, float $newShare, ?int $excludeOwnershipId): void
    {
        $currentSum = PropertyOwnership::where('property_id', $propertyId)
            ->active()
            ->when($excludeOwnershipId, fn ($q) => $q->where('id', '!=', $excludeOwnershipId))
            ->sum('pay_orani');

        if (bccomp((string) ($currentSum + $newShare), '1.0000', 4) > 0) {
            throw new \DomainException(sprintf(
                'Ownership share sum would exceed 1.0. Current: %s, New: %s, Max: 1.0000',
                $currentSum,
                $newShare
            ));
        }
    }
}
