<?php

namespace App\Services\Property\Access;

use App\Models\Property;
use App\Domain\PropertyAccess\Models\PropertyAccessAsset;
use App\Domain\PropertyAccess\Models\PropertyKeyCustody;
use App\Models\Kisi;
use App\Models\User;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PropertyAccessAssetService
 *
 * Sprint 12D — Physical key and access credential management.
 */
class PropertyAccessAssetService
{
    private TenantContextService $tenantContext;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
    }

    /**
     * Register a new access asset for a Property.
     */
    public function registerAsset(
        Property $property,
        string $assetType,
        ?string $identifier = null,
        ?string $description = null,
        ?int $actorId = null,
    ): PropertyAccessAsset {
        $this->enforcePropertyTenantIsolation($property);

        return PropertyAccessAsset::create([
            'tenant_id' => $this->tenantContext->getTenant()->id,
            'property_id' => $property->id,
            'varlik_tipi' => $assetType,
            'tanimlayici_no' => $identifier,
            'tanim' => $description,
            'durum' => PropertyAccessAsset::STATUS_AKTIF,
            'olusturan_id' => $actorId ?? auth()->id(),
        ]);
    }

    /**
     * Transfer custody of an asset to a new holder.
     *
     * Creates an immutable custody record.
     * The previous open custody (if any) is closed by opening a return record.
     */
    public function transferCustody(
        PropertyAccessAsset $asset,
        Kisi $newHolder,
        ?string $note = null,
        ?int $actorId = null,
        ?string $idempotencyKey = null,
    ): PropertyKeyCustody {
        $this->enforceAssetTenantIsolation($asset);

        $key = $idempotencyKey ?? Str::uuid()->toString();

        // Idempotency check
        $existing = PropertyKeyCustody::where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return PropertyKeyCustody::create([
            'tenant_id' => $this->tenantContext->getTenant()->id,
            'asset_id' => $asset->id,
            'kisi_id' => $newHolder->id,
            'islem_tipi' => PropertyKeyCustody::TYPE_TESLIM,
            'notu' => $note,
            'olusturan_id' => $actorId ?? auth()->id(),
            'idempotency_key' => $key,
        ]);
    }

    /**
     * Return custody — records the return of the current holder.
     *
     * Idempotent: if key is already returned (no current holder), returns null.
     */
    public function returnCustody(
        PropertyAccessAsset $asset,
        ?int $actorId = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
    ): ?PropertyKeyCustody {
        $this->enforceAssetTenantIsolation($asset);

        $key = $idempotencyKey ?? Str::uuid()->toString();

        $existing = PropertyKeyCustody::where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        // Get current holder — if no holder (already returned), skip
        $currentHolder = $asset->currentHolder();
        if (!$currentHolder) {
            return null;
        }

        $custody = PropertyKeyCustody::create([
            'tenant_id' => $this->tenantContext->getTenant()->id,
            'asset_id' => $asset->id,
            'kisi_id' => $currentHolder->id,
            'islem_tipi' => PropertyKeyCustody::TYPE_IADE,
            'notu' => $note,
            'olusturan_id' => $actorId ?? auth()->id(),
            'idempotency_key' => $key,
        ]);

        return $custody;
    }

    /**
     * Report an asset as lost.
     */
    public function reportLost(
        PropertyAccessAsset $asset,
        ?int $actorId = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
    ): PropertyAccessAsset {
        $this->enforceAssetTenantIsolation($asset);

        $key = $idempotencyKey ?? Str::uuid()->toString();

        PropertyKeyCustody::create([
            'tenant_id' => $this->tenantContext->getTenant()->id,
            'asset_id' => $asset->id,
            'kisi_id' => $asset->currentHolder()?->id ?? 0,
            'islem_tipi' => PropertyKeyCustody::TYPE_KAYIP_BILDIRIM,
            'notu' => $note,
            'olusturan_id' => $actorId ?? auth()->id(),
            'idempotency_key' => $key,
        ]);

        $asset->durum = PropertyAccessAsset::STATUS_KAYIP;
        $asset->save();

        return $asset;
    }

    /**
     * Replace a lost/deactivated asset with a new one.
     */
    public function replace(
        PropertyAccessAsset $asset,
        ?string $newIdentifier = null,
        ?int $actorId = null,
    ): PropertyAccessAsset {
        $this->enforceAssetTenantIsolation($asset);

        $asset->durum = PropertyAccessAsset::STATUS_IPTAL;
        $asset->save();

        return $this->registerAsset(
            property: $asset->property,
            assetType: $asset->varlik_tipi,
            identifier: $newIdentifier,
            description: $asset->tanim,
            actorId: $actorId,
        );
    }

    /**
     * Deactivate a credential (smart lock, alarm code, etc.)
     */
    public function deactivateCredential(
        PropertyAccessAsset $asset,
        ?int $actorId = null,
    ): PropertyAccessAsset {
        $this->enforceAssetTenantIsolation($asset);

        $asset->durum = PropertyAccessAsset::STATUS_DEAKTIVE;
        $asset->save();

        return $asset;
    }

    /**
     * Get all assets for a Property.
     */
    public function getAssetsForProperty(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyAccessAsset::where('property_id', $property->id)
            ->with('custodies.kisi')
            ->get();
    }

    /**
     * Get current holder of an asset.
     */
    public function getCurrentHolder(PropertyAccessAsset $asset): ?Kisi
    {
        return $asset->currentHolder();
    }

    private function enforceAssetTenantIsolation(PropertyAccessAsset $asset): void
    {
        if ($asset->tenant_id !== $this->tenantContext->getTenant()->id) {
            throw new \RuntimeException('Asset does not belong to current tenant.');
        }
    }

    private function enforcePropertyTenantIsolation(Property $property): void
    {
        if ($property->tenant_id !== $this->tenantContext->getTenant()->id) {
            throw new \RuntimeException('Property does not belong to current tenant.');
        }
    }
}
