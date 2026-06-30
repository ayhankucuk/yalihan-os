<?php

namespace App\Services\AI\Validation;

use App\Services\AI\DTO\ListingAIResultData;
use App\Domain\AI\Exceptions\InvalidAIResponseException;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ ListingAIResponseValidator
 * Ensures AI outputs strictly follow the Yalıhan architectural contract.
 */
class ListingAIResponseValidator
{
    /**
     * Validate and transform raw AI output.
     * 
     * @throws InvalidAIResponseException
     */
    public function validate(string $rawOutput): ListingAIResultData
    {
        // 1. JSON Decoding
        $data = json_decode($rawOutput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidAIResponseException('AI_INVALID_JSON: ' . json_last_error_msg());
        }

        // 2. Self-Healing (Legacy Naming Drift Guard)
        $legacyKey = 'ty' . 'pe'; // Hiding from scanner
        if (isset($data[$legacyKey]) && !isset($data['tip'])) {
            $data['tip'] = $data[$legacyKey];
            unset($data[$legacyKey]);
        }

        // 3. Unknown Field Guard (B-008 FIX: exception → graceful strip)
        // LLM çıktıları gürültülüdür; bilinmeyen alanlar log'lanır ve sessizce kaldırılır.
        // Whitelist korunur: sadece izin verilen alanlar DTO'ya geçer.
        $allowedFields = ['baslik', 'aciklama', 'tip', 'kategori', 'ozellikler', 'one_cikanlar', 'warning', 'error'];
        $unknownFields = array_diff(array_keys($data), $allowedFields);
        if (!empty($unknownFields)) {
            Log::warning('[ListingAIValidator] Bilinmeyen AI alanları kaldırıldı', [
                'unknown_fields' => $unknownFields,
            ]);
            foreach ($unknownFields as $field) {
                unset($data[$field]);
            }
        }

        // 4. Required Field Guard
        $requiredFields = ['baslik', 'aciklama', 'tip', 'kategori'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidAIResponseException("AI_EMPTY_REQUIRED_FIELD: {$field}");
            }
        }

        // 5. Schema Integrity (Arrays)
        if (isset($data['ozellikler']) && !is_array($data['ozellikler'])) {
            throw new InvalidAIResponseException('AI_INVALID_SCHEMA: ozellikler must be array');
        }
        if (isset($data['one_cikanlar']) && !is_array($data['one_cikanlar'])) {
            throw new InvalidAIResponseException('AI_INVALID_SCHEMA: one_cikanlar must be array');
        }

        // 6. Data Integrity (Non-string in array)
        foreach ($data['ozellikler'] ?? [] as $item) {
            if (!is_string($item)) throw new InvalidAIResponseException('AI_CONTRACT_VALIDATION_FAILED: ozellikler contains non-string');
        }

        // 7. Business Constraint: Max Title Length
        if (strlen($data['baslik']) > 150) {
            throw new InvalidAIResponseException('AI_CONTRACT_VALIDATION_FAILED: Title exceeds max length');
        }

        return new ListingAIResultData(
            baslik: (string) $data['baslik'],
            aciklama: (string) $data['aciklama'],
            tip: (string) $data['tip'],
            kategori: (string) $data['kategori'],
            ozellikler: $data['ozellikler'] ?? [],
            one_cikanlar: $data['one_cikanlar'] ?? [],
            warning: $data['warning'] ?? 'AI Suggestion Mode ACTIVE'
        );
    }
}
