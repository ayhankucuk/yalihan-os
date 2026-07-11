<?php

namespace App\DTOs;

/**
 * Channel Config DTO — Tek bir yayın kanalını temsil eder.
 */
final class ChannelConfigDTO
{
    public function __construct(
        public readonly string $canonicalName,
        public readonly string $displayName,
        public readonly bool   $isDefault,
        public readonly bool   $aktiflikDurumu,
        public readonly array  $capabilities = [],
    ) {}

    public function toArray(): array
    {
        return [
            'canonical_name' => $this->canonicalName,
            'display_name' => $this->displayName,
            'is_default' => $this->isDefault,
            'aktiflik_durumu' => $this->aktiflikDurumu,
            'capabilities' => $this->capabilities,
        ];
    }
}
