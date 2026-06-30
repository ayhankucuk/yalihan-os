<?php

namespace App\Events\Hermes;

/**
 * HermesEventTraits
 *
 * Provides default implementations for HermesEventContract.
 * Use this traits in your domain events to satisfy the contract.
 */
trait HermesEventTraits
{
    /**
     * @inheritDoc
     */
    public function toPayload(): array
    {
        $payload = [];

        if (isset($this->ilan)) {
            $payload['ilan_id'] = $this->ilan->getKey();
            $payload['ilan_baslik'] = $this->ilan->baslik ?? null;
            $payload['ilan_fiyat'] = $this->ilan->fiyat ?? null;
        }

        if (isset($this->metadata)) {
            $payload['metadata'] = $this->metadata;
        }

        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
