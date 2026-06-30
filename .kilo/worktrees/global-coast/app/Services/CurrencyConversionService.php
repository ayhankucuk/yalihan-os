<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CurrencyConversionService
{
    /**
     * Convert an amount from one currency to another.
     */
    public function convert(?float $amount, ?string $from, ?string $to): ?array
    {
        if ($amount === null || $from === null || $to === null) {
            return null;
        }

        $fromCode = Str::upper(trim($from));
        $toCode = Str::upper(trim($to));

        if ($fromCode === $toCode) {
            return null;
        }

        $supported = $this->getSupported();

        if (! isset($supported[$fromCode], $supported[$toCode])) {
            return null;
        }

        $fromRate = (float) Arr::get($supported, "$fromCode.rate", 1.0);
        $toRate = (float) Arr::get($supported, "$toCode.rate", 1.0);

        if ($fromRate <= 0 || $toRate <= 0) {
            return null;
        }

        $amountInBase = $amount * $fromRate;
        $converted = $amountInBase / $toRate;

        $decimals = (int) Arr::get($supported, "$toCode.decimals", 0);
        $symbol = Arr::get($supported, "$toCode.symbol", '');

        return [
            'currency' => $toCode,
            'amount' => $converted,
            'formatted' => $symbol.' '.number_format($converted, $decimals, ',', '.'),
            'symbol' => $symbol,
        ];
    }

    public function getSupported(): array
    {
        return config('currency.supported', []);
    }

    public function getDefault(): string
    {
        return Str::upper(config('currency.default', 'TRY'));
    }
}
