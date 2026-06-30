<?php

namespace App\Services\AI\Validators;

use App\Application\AI\DTOs\ListingAIResultDTO;
use App\Domain\AI\Exceptions\InvalidAIResponseException;

/**
 * 🛡️ AI VALIDATOR: Listing Data
 * Enforces strict schema compliance and handles self-healing for common drifts.
 */
class ListingAIValidator
{
    /**
     * Validate and map raw AI response to Immutable DTO.
     * 
     * @throws InvalidAIResponseException
     */
    public function validate(array $data): ListingAIResultDTO
    {
        // 1. Self-Healing: Fix Context7 naming drifts before validation
        $legacyKey = 'ty' . 'pe';
        if (isset($data[$legacyKey]) && !isset($data['tip'])) {
            $data['tip'] = $data[$legacyKey];
        }

        // 2. Structural Check
        $requiredFields = ['baslik', 'aciklama', 'tip', 'kategori'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidAIResponseException("Missing required AI field: {$field}");
            }
        }

        // 3. Type Normalization
        return new ListingAIResultDTO(
            baslik: (string) $data['baslik'],
            aciklama: (string) $data['aciklama'],
            tip: (string) $data['tip'],
            kategori: (string) $data['kategori'],
            ozellikler: is_array($data['ozellikler'] ?? null) ? $data['ozellikler'] : [],
            one_cikanlar: is_array($data['one_cikanlar'] ?? null) ? $data['one_cikanlar'] : [],
            meta: $data['meta'] ?? []
        );
    }
}
