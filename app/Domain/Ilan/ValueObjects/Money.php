<?php

namespace App\Domain\Ilan\ValueObjects;

use InvalidArgumentException;

/**
 * 💰 Money Value Object
 *
 * Sorumluluk: Gayrimenkul fiyatlarını, para birimini ve kur dönüşümlerini
 * domain seviyesinde doğrular ve yönetir (Immutable).
 */
final class Money
{
    private const ALLOWED_CURRENCIES = ['TRY', 'USD', 'EUR', 'GBP'];

    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'TRY'
    ) {
        if ($this->amount < 0) {
            throw new InvalidArgumentException('Fiyat tutarı negatif olamaz.');
        }

        $upperCurrency = strtoupper($this->currency);
        if (!in_array($upperCurrency, self::ALLOWED_CURRENCIES, true)) {
            throw new InvalidArgumentException("Geçersiz para birimi: {$this->currency}");
        }
    }

    public static function fromArray(array $data): self
    {
        $amount = (float) ($data['fiyat'] ?? $data['amount'] ?? 0);
        $currency = (string) ($data['para_birimi'] ?? $data['currency'] ?? 'TRY');

        return new self($amount, $currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'fiyat' => $this->amount,
            'para_birimi' => $this->currency,
            'formatted' => $this->formatted(),
        ];
    }
}
