<?php

namespace App\Services\N8n;

use App\Exceptions\TenantOwnershipUnresolvableException;
use App\Models\Communication;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\User;
use App\Services\Logging\LogService;

/**
 * TenantOwnershipResolver
 *
 * Canonical tenant ownership resolver for N8n webhook flows.
 *
 * Architecture: tenant_id and ulke_id are independent ownership dimensions.
 * tenant_id is resolved server-side from canonical domain entities ONLY.
 * External N8n/webhook payload MUST NOT be trusted for tenant_id.
 *
 * Resolution semantics (mirrors CountryOwnershipResolver pattern):
 *
 * | Flow              | Source FK           | Resolution Chain                              | Status |
 * |-------------------|---------------------|-----------------------------------------------|--------|
 * | ilanTaslagi       | danisman_id         | User(danisman_id).tenant_id                   | PASS   |
 * | ilanTaslagi       | ilan_id             | Ilan(ilan_id).tenant_id (fallback)           | PASS   |
 * | sozlesmeTaslagi   | property_id         | Ilan(property_id).tenant_id                  | PASS   |
 * | sozlesmeTaslagi   | kisi_id             | Kisi(kisi_id).tenant_id                      | PASS   |
 * | mesajTaslagi      | communication_id    | Communication.communicable.tenant_id (poly)  | PASS   |
 *
 * NO silent NULL. When ownership cannot be resolved, an exception is thrown.
 * The exception is the intentional fail-closed behaviour.
 */
class TenantOwnershipResolver
{
    /**
     * Resolve tenant for ilanTaslagi flow.
     *
     * @param int $danismanId
     * @param int|null $ilanId  nullable — used as fallback
     * @return int tenant_id
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveForIlanTaslagi(int $danismanId, ?int $ilanId = null): int
    {
        // Primary: resolve via danisman (advisor)
        $tenantId = $this->resolveViaDanisman($danismanId);
        if ($tenantId !== null) {
            return $tenantId;
        }

        // Fallback: resolve via ilan
        if ($ilanId !== null) {
            $tenantId = $this->resolveViaIlan($ilanId);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        LogService::warning('n8n tenant ownership: unresolvable for ilanTaslagi', [
            'danisman_id' => $danismanId,
            'ilan_id' => $ilanId,
        ], LogService::CHANNEL_API);

        throw new TenantOwnershipUnresolvableException(
            "Tenant ownership could not be resolved for ilanTaslagi. " .
            "danisman_id={$danismanId}, ilan_id=" . ($ilanId ?? 'null') . ". " .
            "Both User.tenant_id and Ilan.tenant_id returned null."
        );
    }

    /**
     * Resolve tenant for sozlesmeTaslagi flow.
     *
     * @param int|null $propertyId  Ilan id
     * @param int|null $kisiId
     * @return int tenant_id
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveForSozlesmeTaslagi(?int $propertyId, ?int $kisiId): int
    {
        if ($propertyId !== null) {
            $tenantId = $this->resolveViaIlan($propertyId);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        if ($kisiId !== null) {
            $tenantId = $this->resolveViaKisi($kisiId);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        LogService::warning('n8n tenant ownership: unresolvable for sozlesmeTaslagi', [
            'property_id' => $propertyId,
            'kisi_id' => $kisiId,
        ], LogService::CHANNEL_API);

        throw new TenantOwnershipUnresolvableException(
            "Tenant ownership could not be resolved for sozlesmeTaslagi. " .
            "property_id=" . ($propertyId ?? 'null') . ", kisi_id=" . ($kisiId ?? 'null') . ". " .
            "Both Ilan.tenant_id and Kisi.tenant_id returned null."
        );
    }

    /**
     * Resolve tenant for mesajTaslagi flow.
     *
     * Uses the polymorphic chain: Communication → communicable → tenant_id
     * This mirrors the CountryOwnershipResolver pattern (communicable.ulke_id).
     *
     * NOTE: Communication.tenant_id is NOT used as authoritative source here.
     * Reasoning: Communication uses HasCountryScope (not TenantScope), meaning
     * its own tenant_id is not auto-enforced by a global scope. The polymorphic
     * communicable entity (Ilan|Kisi|User) is the canonical tenant source.
     * Following the established pattern from CountryOwnershipResolver ensures
     * consistency between country and tenant resolution chains.
     *
     * @param int $communicationId
     * @return int tenant_id
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveForMesajTaslagi(int $communicationId): int
    {
        // N8n webhook flows have no tenant context (TenantScope would return nothing).
        $communication = Communication::withoutGlobalScopes()->find($communicationId);

        if ($communication === null) {
            LogService::warning('n8n tenant ownership: communication not found', [
                'communication_id' => $communicationId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Tenant ownership could not be resolved for mesajTaslagi. " .
                "communication_id={$communicationId}. Communication not found."
            );
        }

        // Resolve via direct query by type+ID.
        // Bypasses morphTo() null-check to avoid CountryScope re-application on Kisi.
        $tenantId = $this->resolveCommunicableTenantId(
            $communication->communicable_type,
            $communication->communicable_id
        );

        if ($tenantId === null) {
            LogService::warning('n8n tenant ownership: communicable.tenant_id is null', [
                'communication_id' => $communicationId,
                'communicable_type' => $communication->communicable_type,
                'communicable_id' => $communication->communicable_id,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Tenant ownership could not be resolved for mesajTaslagi. " .
                "communication_id={$communicationId}. " .
                "communicable_type={$communication->communicable_type} " .
                "communicable_id={$communication->communicable_id} " .
                "has null tenant_id."
            );
        }

        return $tenantId;
    }

    /**
     * Resolve via danisman (advisor/user).
     * User has a direct tenant_id column.
     * Returns null if user not found or tenant_id is null.
     */
    private function resolveViaDanisman(int $danismanId): ?int
    {
        $user = User::find($danismanId);

        if ($user === null) {
            LogService::warning('n8n tenant resolver: user not found', [
                'danisman_id' => $danismanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($user->tenant_id === null) {
            LogService::warning('n8n tenant resolver: user.tenant_id is null', [
                'danisman_id' => $danismanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $user->tenant_id;
    }

    /**
     * Resolve via ilan (property/listing).
     * Ilan has a direct tenant_id column.
     * Returns null if ilan not found or tenant_id is null.
     */
    private function resolveViaIlan(int $ilanId): ?int
    {
        $ilan = Ilan::withoutGlobalScopes()->find($ilanId);

        if ($ilan === null) {
            LogService::warning('n8n tenant resolver: ilan not found', [
                'ilan_id' => $ilanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($ilan->tenant_id === null) {
            LogService::warning('n8n tenant resolver: ilan.tenant_id is null', [
                'ilan_id' => $ilanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $ilan->tenant_id;
    }

    /**
     * Resolve via kisi (person/lead).
     * Kisi has a direct tenant_id column.
     * Returns null if kisi not found or tenant_id is null.
     */
    private function resolveViaKisi(int $kisiId): ?int
    {
        // N8n webhook flows have no authenticated user; CountryScope would block the lookup.
        $kisi = Kisi::withoutGlobalScopes()->find($kisiId);

        if ($kisi === null) {
            LogService::warning('n8n tenant resolver: kisi not found', [
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($kisi->tenant_id === null) {
            LogService::warning('n8n tenant resolver: kisi.tenant_id is null', [
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $kisi->tenant_id;
    }

    /**
     * Resolve tenant_id from a polymorphic target by type+id using direct queries.
     *
     * This bypasses morphTo() which applies scopes to the target model.
     *
     * @param string $type  FQCN of the polymorphic model
     * @param int $id       ID of the polymorphic record
     * @return int|null
     */
    private function resolveCommunicableTenantId(string $type, int $id): ?int
    {
        return match ($type) {
            Kisi::class => $this->resolveViaKisi($id),
            Ilan::class => $this->resolveViaIlan($id),
            User::class => $this->resolveViaDanisman($id),
            default => null,
        };
    }
}
