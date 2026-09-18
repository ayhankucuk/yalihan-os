<?php

namespace App\Services\N8n;

use App\Exceptions\CrossTenantIdentifierInjectionException;
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
        $danismanTenantId = $this->resolveViaDanisman($danismanId);

        // ilanId is optional fallback — when provided alongside danismanId, both MUST agree.
        if ($ilanId !== null) {
            $ilanTenantId = $this->resolveViaIlan($ilanId);

            // If both resolve, they MUST be consistent — otherwise it is a cross-tenant injection.
            if ($danismanTenantId !== null && $ilanTenantId !== null) {
                if ($danismanTenantId !== $ilanTenantId) {
                    $this->logCrossTenantInjection([
                        'flow' => 'ilanTaslagi',
                        'danisman_id' => $danismanId,
                        'ilan_id' => $ilanId,
                        'danisman_tenant_id' => $danismanTenantId,
                        'ilan_tenant_id' => $ilanTenantId,
                    ]);

                    throw new CrossTenantIdentifierInjectionException;
                }

                return $danismanTenantId;
            }

            // Only one resolved — use whichever resolved.
            // If neither resolved, fall through to exception below.
            if ($danismanTenantId !== null) {
                return $danismanTenantId;
            }
            if ($ilanTenantId !== null) {
                return $ilanTenantId;
            }
        } else {
            // No ilanId — must resolve via danisman.
            if ($danismanTenantId !== null) {
                return $danismanTenantId;
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
     * @param int|null $propertyId  nullable — Ilan (property)
     * @param int|null $kisiId     nullable — Kisi (person/lead)
     * @return int tenant_id
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveForSozlesmeTaslagi(?int $propertyId, ?int $kisiId): int
    {
        if ($propertyId === null && $kisiId === null) {
            LogService::warning('n8n tenant ownership: unresolvable for sozlesmeTaslagi', [
                'property_id' => $propertyId,
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                'Tenant ownership could not be resolved for sozlesmeTaslagi. ' .
                'Both propertyId and kisiId are null.'
            );
        }

        $propertyTenantId = $propertyId !== null ? $this->resolveViaIlan($propertyId) : null;
        $kisiTenantId = $kisiId !== null ? $this->resolveViaKisi($kisiId) : null;

        // If both resolve, they MUST be consistent — cross-tenant injection.
        if ($propertyTenantId !== null && $kisiTenantId !== null) {
            if ($propertyTenantId !== $kisiTenantId) {
                $this->logCrossTenantInjection([
                    'flow' => 'sozlesmeTaslagi',
                    'property_id' => $propertyId,
                    'kisi_id' => $kisiId,
                    'property_tenant_id' => $propertyTenantId,
                    'kisi_tenant_id' => $kisiTenantId,
                ]);

                throw new CrossTenantIdentifierInjectionException;
            }

            return $propertyTenantId;
        }

        // One resolved — use it.
        if ($propertyTenantId !== null) {
            return $propertyTenantId;
        }
        if ($kisiTenantId !== null) {
            return $kisiTenantId;
        }

        LogService::warning('n8n tenant ownership: unresolvable for sozlesmeTaslagi', [
            'property_id' => $propertyId,
            'kisi_id' => $kisiId,
        ], LogService::CHANNEL_API);

        throw new TenantOwnershipUnresolvableException(
            'Tenant ownership could not be resolved for sozlesmeTaslagi. ' .
            "property_id={$propertyId}, kisi_id={$kisiId}. " .
            'Both Ilan.tenant_id and Kisi.tenant_id returned null.'
        );
    }

    /**
     * Resolve tenant for mesajTaslagi flow via polymorphic communicable chain.
     *
     * Communication has NO direct tenant_id.
     * It morphs to Ilan, Kisi, or User — all of which have tenant_id.
     *
     * Resolution chain:
     *   Communication.communicable_id + communicable_type
     *     → Ilan[id].tenant_id  OR  Kisi[id].tenant_id  OR  User[id].tenant_id
     *
     * @param int $communicationId
     * @return int tenant_id
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveForMesajTaslagi(int $communicationId): int
    {
        // withoutGlobalScopes: N8n webhook flows have no authenticated user;
        // CountryScope would filter to WHERE ulke_id = NULL = nothing found.
        $communication = Communication::withoutGlobalScopes()->find($communicationId);

        if ($communication === null) {
            LogService::warning('n8n tenant ownership: communication not found', [
                'communication_id' => $communicationId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Communication #{$communicationId} not found. Cannot resolve tenant ownership."
            );
        }

        if ($communication->communicable_type === null || $communication->communicable_id === null) {
            LogService::warning('n8n tenant ownership: communication has no communicable', [
                'communication_id' => $communicationId,
                'communicable_type' => $communication->communicable_type,
                'communicable_id' => $communication->communicable_id,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Communication #{$communicationId} has no communicable. Cannot resolve tenant ownership."
            );
        }

        // Polymorphic lookup: bypass morphTo() which applies scopes to the target model.
        $tenantId = $this->resolveCommunicableTenantId(
            $communication->communicable_type,
            $communication->communicable_id
        );

        if ($tenantId === null) {
            LogService::warning('n8n tenant ownership: communicable has null tenant_id', [
                'communication_id' => $communicationId,
                'communicable_type' => $communication->communicable_type,
                'communicable_id' => $communication->communicable_id,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Communication #{$communicationId} points to a {$communication->communicable_type} " .
                "with tenant_id=null. Cannot resolve tenant ownership."
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
        $user = User::withoutGlobalScopes()->find($danismanId);

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

    private function logCrossTenantInjection(array $context): void
    {
        LogService::auth(
            'n8n: cross-tenant identifier injection blocked',
            null,
            $context
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LEGACY SERVICE PUBLIC API (Phase 3 — LEGACY_AI_SERVICES_TENANT_PARITY_01)
    // These public helpers let legacy services derive tenant ownership from
    // canonical domain entities without duplicating resolution logic.
    // All throw TenantOwnershipUnresolvableException on failure (fail-closed).
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Resolve tenant_id via danisman (User) — public for legacy services.
     *
     * @param int $danismanId
     * @return int
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveTenantViaDanisman(int $danismanId): int
    {
        $tenantId = $this->resolveViaDanisman($danismanId);

        if ($tenantId === null) {
            LogService::warning('legacy service: tenant unresolvable via danisman', [
                'danisman_id' => $danismanId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Legacy AI service: tenant_id unresolvable via danisman_id={$danismanId}"
            );
        }

        return $tenantId;
    }

    /**
     * Resolve tenant_id via ilan (property) — public for legacy services.
     *
     * @param int $ilanId
     * @return int
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveTenantViaIlan(int $ilanId): int
    {
        $tenantId = $this->resolveViaIlan($ilanId);

        if ($tenantId === null) {
            LogService::warning('legacy service: tenant unresolvable via ilan', [
                'ilan_id' => $ilanId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Legacy AI service: tenant_id unresolvable via ilan_id={$ilanId}"
            );
        }

        return $tenantId;
    }

    /**
     * Resolve tenant_id via kisi (person) — public for legacy services.
     *
     * @param int $kisiId
     * @return int
     * @throws TenantOwnershipUnresolvableException
     */
    public function resolveTenantViaKisi(int $kisiId): int
    {
        $tenantId = $this->resolveViaKisi($kisiId);

        if ($tenantId === null) {
            LogService::warning('legacy service: tenant unresolvable via kisi', [
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);

            throw new TenantOwnershipUnresolvableException(
                "Legacy AI service: tenant_id unresolvable via kisi_id={$kisiId}"
            );
        }

        return $tenantId;
    }
}
