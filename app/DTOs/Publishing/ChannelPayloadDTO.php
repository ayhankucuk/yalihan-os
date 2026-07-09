<?php

namespace App\DTOs\Publishing;

/**
 * Channel Payload DTO — Sprint 6.5
 *
 * Tüm kanal payload'larının ortak container'ı.
 * Adapter'lar bu DTO'yu üretir; iş mantığı buraya yazılmaz.
 */
final class ChannelPayloadDTO
{
    /**
     * @param  array<string, mixed>  $mappedFields  Kanal-özgü alan eşleşmeleri
     * @param  array<string, mixed>  $seo             Başlık, meta
     * @param  array<string, mixed>  $photos          Fotoğraf sıralaması + URL
     * @param  array<string, mixed>  $pricing         Fiyatlandırma
     * @param  array<string, mixed>  $raw             Ham payload (debug)
     * @param  string[]            $errors          Doğrulama hataları
     */
    public function __construct(
        public readonly string $channel,          // airbnb | sahibinden | hepsiemlak
        public readonly int $ilanId,
        public readonly array $mappedFields,
        public readonly array $photos = [],
        public readonly array $seo = [],
        public readonly array $pricing = [],
        public readonly array $raw = [],
        public readonly array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'channel'      => $this->channel,
            'ilan_id'     => $this->ilanId,
            'mapped_fields' => $this->mappedFields,
            'photos'       => $this->photos,
            'seo'          => $this->seo,
            'pricing'      => $this->pricing,
            'raw'           => $this->raw,
            'errors'        => $this->errors,
            'is_valid'     => empty($this->errors),
            'generated_at'  => now()->toIso8601String(),
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function errorSummary(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        return implode('; ', $this->errors);
    }
}
