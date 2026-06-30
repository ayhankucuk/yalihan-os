<?php

namespace App\Domain\PropertyHub\ValueObjects;

/**
 * SealedTemplateJson — Value Object
 *
 * [SAB ENFORCEMENT]: Aggregate Root hafifleme
 * Template JSON'unun validate, canonicalize ve hash islemlerini
 * kapsulleyen immutable Value Object.
 *
 * Bu sinif Aggregate Root icindeki private helper'larin
 * domain-level bir soyutlamaya donusturulmus halidir.
 *
 * Immutable: Olusturulduktan sonra degistirilemez.
 */
final class SealedTemplateJson
{
    private readonly string $canonicalJson;
    private readonly string $hash;
    private readonly array $normalizedData;

    /**
     * @throws \Exception validation hatasi
     */
    public function __construct(array $rawJson)
    {
        $this->validate($rawJson);
        $this->normalizedData = $this->normalizeRecursive($rawJson);
        $this->canonicalJson = json_encode($this->normalizedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->hash = hash('sha256', $this->canonicalJson);
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function toArray(): array
    {
        return $this->normalizedData;
    }

    public function toJson(): string
    {
        return $this->canonicalJson;
    }

    /**
     * Hash karsilastirmasi — duplicate detection icin
     */
    public function equalsHash(string $otherHash): bool
    {
        return $this->hash === $otherHash;
    }

    // ─── Private Logic ──────────────────────────────────

    /**
     * @throws \Exception
     */
    private function validate(array $json): void
    {
        $required = $json['zorunlu_alanlar'] ?? [];
        $optional = $json['opsiyonel_alanlar'] ?? [];
        $hidden = $json['gizli_alanlar'] ?? [];

        $reqOptIntersect = array_intersect($required, $optional);
        if (!empty($reqOptIntersect)) {
            throw new \Exception('Cakisma: Alanlar hem zorunlu hem opsiyonel olamaz: ' . implode(', ', $reqOptIntersect));
        }

        $reqHiddenIntersect = array_intersect($required, $hidden);
        if (!empty($reqHiddenIntersect)) {
            throw new \Exception('Cakisma: Alanlar hem zorunlu hem gizli olamaz: ' . implode(', ', $reqHiddenIntersect));
        }
    }

    private function normalizeRecursive(array $data): array
    {
        ksort($data, SORT_REGULAR);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = array_is_list($value)
                    ? tap($value, fn(&$v) => sort($v, SORT_REGULAR))
                    : $this->normalizeRecursive($value);
            }
        }
        return $data;
    }
}
