<?php

namespace App\Services\N8n;

use App\Exceptions\CountryOwnershipUnresolvableException;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\User;
use App\Services\Logging\LogService;

/**
 * CountryOwnershipResolver
 *
 * Canonical country ownership resolver for N8n webhook flows.
 *
 * Pre-Implementation Gate Authority (REMEDIATION_N8N_AI_COUNTRY_OWNERSHIP_01):
 *
 * | Flow              | Source FK      | Resolution Chain                          | Status   |
 * |-------------------|----------------|-------------------------------------------|----------|
 * | ilanTaslagi       | danisman_id    | User(danisman_id).ulke_id                 | PASS     |
 * | ilanTaslagi       | ilan_id        | Ilan(ilan_id).ulke_id (fallback)          | PASS     |
 * | sozlesmeTaslagi   | property_id    | Ilan(property_id).ulke_id                  | PASS     |
 * | sozlesmeTaslagi   | kisi_id        | Kisi(kisi_id).ulke_id                     | PASS     |
 * | mesajTaslagi      | communication_id | Communication.communicable.ulke_id      | PASS     |
 *
 * NO silent NULL. When ownership cannot be resolved, an exception is thrown.
 * The exception is the intentional fail-closed behaviour.
 */
class CountryOwnershipResolver
{
    /**
     * Resolve country for ilanTaslagi flow.
     *
     * @param int $danismanId
     * @param int|null $ilanId  nullable — used as fallback
     * @return int ulke_id
     * @throws CountryOwnershipUnresolvableException
     */
    public function resolveForIlanTaslagi(int $danismanId, ?int $ilanId = null): int
    {
        // Primary: resolve via danisman (advisor)
        $ulkeId = $this->resolveViaDanisman($danismanId);
        if ($ulkeId !== null) {
            return $ulkeId;
        }

        // Fallback: resolve via ilan
        if ($ilanId !== null) {
            $ulkeId = $this->resolveViaIlan($ilanId);
            if ($ulkeId !== null) {
                return $ulkeId;
            }
        }

        LogService::warning('n8n country ownership: unresolvable for ilanTaslagi', [
            'danisman_id' => $danismanId,
            'ilan_id' => $ilanId,
        ], LogService::CHANNEL_API);

        throw new CountryOwnershipUnresolvableException(
            "Country ownership could not be resolved for ilanTaslagi. " .
            "danisman_id={$danismanId}, ilan_id=" . ($ilanId ?? 'null') . ". " .
            "Both User.ulke_id and Ilan.ulke_id returned null."
        );
    }

    /**
     * Resolve country for sozlesmeTaslagi flow.
     *
     * @param int|null $propertyId  Ilan id
     * @param int|null $kisiId
     * @return int ulke_id
     * @throws CountryOwnershipUnresolvableException
     */
    public function resolveForSozlesmeTaslagi(?int $propertyId, ?int $kisiId): int
    {
        if ($propertyId !== null) {
            $ulkeId = $this->resolveViaIlan($propertyId);
            if ($ulkeId !== null) {
                return $ulkeId;
            }
        }

        if ($kisiId !== null) {
            $ulkeId = $this->resolveViaKisi($kisiId);
            if ($ulkeId !== null) {
                return $ulkeId;
            }
        }

        LogService::warning('n8n country ownership: unresolvable for sozlesmeTaslagi', [
            'property_id' => $propertyId,
            'kisi_id' => $kisiId,
        ], LogService::CHANNEL_API);

        throw new CountryOwnershipUnresolvableException(
            "Country ownership could not be resolved for sozlesmeTaslagi. " .
            "property_id=" . ($propertyId ?? 'null') . ", kisi_id=" . ($kisiId ?? 'null') . ". " .
            "Neither Ilan.ulke_id nor Kisi.ulke_id returned a value."
        );
    }

    /**
     * Resolve country for mesajTaslagi flow via polymorphic communicable chain.
     *
     * Communication table has NO direct ulke_id column.
     * But Communication has polymorphic: communicable_type + communicable_id
     * which morphs to Ilan, Kisi, or User — all of which have ulke_id.
     *
     * Resolution chain:
     *   Communication(communication_id)
     *   → communicable (Ilan|Kisi|User)
     *   → ulke_id
     *
     * Fail-closed: throws if Communication not found, polymorphic target
     * has no ulke_id, or target entity's ulke_id is null.
     * This is correct: a message draft whose owning entity has no country
     * cannot be silently written without breaking tenant isolation.
     *
     * @param int $communicationId
     * @return int ulke_id
     * @throws CountryOwnershipUnresolvableException
     */
    public function resolveForMesajTaslagi(int $communicationId): int
    {
        $communication = \App\Models\Communication::withoutCountryScope()->find($communicationId);

        if ($communication === null) {
            LogService::warning('n8n country ownership: communication not found', [
                'communication_id' => $communicationId,
            ], LogService::CHANNEL_API);

            throw new CountryOwnershipUnresolvableException(
                "Country ownership could not be resolved for mesajTaslagi. " .
                "communication_id={$communicationId}. Communication not found."
            );
        }

        if ($communication->communicable === null) {
            LogService::warning('n8n country ownership: communicable is null', [
                'communication_id' => $communicationId,
                'communicable_type' => $communication->communicable_type,
                'communicable_id' => $communication->communicable_id,
            ], LogService::CHANNEL_API);

            throw new CountryOwnershipUnresolvableException(
                "Country ownership could not be resolved for mesajTaslagi. " .
                "communication_id={$communicationId}. " .
                "Communication.communicable is null (orphaned record)."
            );
        }

        $ulkeId = $communication->communicable->ulke_id ?? null;

        if ($ulkeId === null) {
            LogService::warning('n8n country ownership: communicable.ulke_id is null', [
                'communication_id' => $communicationId,
                'communicable_type' => $communication->communicable_type,
                'communicable_id' => $communication->communicable_id,
            ], LogService::CHANNEL_API);

            throw new CountryOwnershipUnresolvableException(
                "Country ownership could not be resolved for mesajTaslagi. " .
                "communication_id={$communicationId}. " .
                "communicable_type={$communication->communicable_type} " .
                "communicable_id={$communication->communicable_id} " .
                "has null ulke_id."
            );
        }

        return $ulkeId;
    }

    /**
     * Resolve via danisman (advisor/user).
     * Returns null if user not found or ulke_id is null.
     */
    private function resolveViaDanisman(int $danismanId): ?int
    {
        $user = User::find($danismanId);

        if ($user === null) {
            LogService::warning('n8n country resolver: user not found', [
                'danisman_id' => $danismanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($user->ulke_id === null) {
            LogService::warning('n8n country resolver: user.ulke_id is null', [
                'danisman_id' => $danismanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $user->ulke_id;
    }

    /**
     * Resolve via ilan (property/listing).
     * Returns null if ilan not found or ulke_id is null.
     */
    private function resolveViaIlan(int $ilanId): ?int
    {
        $ilan = Ilan::find($ilanId);

        if ($ilan === null) {
            LogService::warning('n8n country resolver: ilan not found', [
                'ilan_id' => $ilanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($ilan->ulke_id === null) {
            LogService::warning('n8n country resolver: ilan.ulke_id is null', [
                'ilan_id' => $ilanId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $ilan->ulke_id;
    }

    /**
     * Resolve via kisi (person/lead).
     * Kisi has a direct ulke_id column.
     * Returns null if kisi not found or ulke_id is null.
     */
    private function resolveViaKisi(int $kisiId): ?int
    {
        $kisi = Kisi::find($kisiId);

        if ($kisi === null) {
            LogService::warning('n8n country resolver: kisi not found', [
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);
            return null;
        }

        if ($kisi->ulke_id === null) {
            LogService::warning('n8n country resolver: kisi.ulke_id is null', [
                'kisi_id' => $kisiId,
            ], LogService::CHANNEL_API);
            return null;
        }

        return $kisi->ulke_id;
    }
}
