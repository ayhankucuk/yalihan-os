<?php

namespace App\Services\Utility;

/**
 * NumberToTextConverter
 *
 * Converts numeric price values to Turkish textual representation.
 * Example: 10_000_000 → "On Milyon Türk Lirası"
 */
class NumberToTextConverter
{
    /**
     * Supported currency names.
     */
    protected array $currencyMap = [
        'TRY' => 'Türk Lirası',
        'USD' => 'Amerikan Doları',
        'EUR' => 'Euro',
        'GBP' => 'İngiliz Sterlini',
    ];

    /**
     * Convert numeric amount to Turkish text.
     */
    public function convertToText(float $number, string $currency = 'TRY'): string
    {
        $integer = (int) round($number);

        if ($integer === 0) {
            return 'Sıfır '.$this->getCurrencyName($currency);
        }

        $text = $this->convertInteger($integer);

        return trim($text.' '.$this->getCurrencyName($currency));
    }

    protected function getCurrencyName(string $currency): string
    {
        $upper = strtoupper($currency);

        return $this->currencyMap[$upper] ?? 'Türk Lirası';
    }

    protected function convertInteger(int $number): string
    {
        $ones = ['', 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz'];
        $tens = ['', 'On', 'Yirmi', 'Otuz', 'Kırk', 'Elli', 'Altmış', 'Yetmiş', 'Seksen', 'Doksan'];
        $thousands = ['', 'Bin', 'Milyon', 'Milyar', 'Trilyon', 'Katrilyon'];

        $parts = [];
        $groupIndex = 0;

        while ($number > 0) {
            $group = $number % 1000;

            if ($group > 0) {
                $groupText = $this->convertThreeDigits($group, $ones, $tens);

                if ($groupIndex === 1 && $group === 1) {
                    $parts[] = 'Bin';
                } else {
                    $suffix = $thousands[$groupIndex] ?? '';
                    $parts[] = trim($groupText.' '.$suffix);
                }
            }

            $number = intdiv($number, 1000);
            $groupIndex++;
        }

        return implode(' ', array_reverse($parts));
    }

    protected function convertThreeDigits(int $number, array $ones, array $tens): string
    {
        $parts = [];
        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;

        if ($hundreds > 0) {
            $parts[] = $hundreds === 1 ? 'Yüz' : $ones[$hundreds].' Yüz';
        }

        if ($remainder >= 10) {
            $parts[] = $tens[intdiv($remainder, 10)];
            $remainder = $remainder % 10;
        } elseif ($remainder > 0 && $remainder < 10 && empty($parts)) {
            // no hundreds, proceed
        }

        if ($remainder > 0) {
            $parts[] = $ones[$remainder];
        }

        return trim(implode(' ', $parts));
    }
}


