<?php

namespace App\Services\Price;

/**
 * Fiyat Yazı Dönüştürme Servisi
 *
 * Context7 Standardı: C7-PRICE-TEXT-2025-10-11
 *
 * Kullanım:
 * $service = app(PriceTextService::class);
 * echo $service->convertToText(2500000); // İki Milyon Beş Yüz Bin Türk Lirası
 * echo $service->convertToText(2500000, 'USD'); // Two Million Five Hundred Thousand US Dollars
 */
class PriceTextService
{
    /**
     * Fiyatı yazıya çevir (Türkçe)
     */
    public function convertToText(float $price, string $currency = 'TRY'): string
    {
        $text = $this->numberToText($price, 'tr');
        $currencyText = $this->getCurrencyText($currency, 'tr');

        return $text.' '.$currencyText;
    }

    /**
     * Fiyatı yazıya çevir (İngilizce)
     */
    public function convertToTextEnglish(float $price, string $currency = 'USD'): string
    {
        $text = $this->numberToText($price, 'en');
        $currencyText = $this->getCurrencyText($currency, 'en');

        return $text.' '.$currencyText;
    }

    /**
     * Sayıyı yazıya çevir
     */
    protected function numberToText(float $number, string $language = 'tr'): string
    {
        if ($number == 0) {
            return $language === 'tr' ? 'Sıfır' : 'Zero';
        }

        // Negatif sayılar için
        if ($number < 0) {
            $prefix = $language === 'tr' ? 'Eksi ' : 'Minus ';

            return $prefix.$this->numberToText(abs($number), $language);
        }

        // Tam sayı kısmı
        $integerPart = floor($number);

        // Kuruş kısmı
        $decimalPart = round(($number - $integerPart) * 100);

        $text = $this->integerToText($integerPart, $language);

        // Kuruş varsa ekle
        if ($decimalPart > 0) {
            $decimalText = $this->integerToText($decimalPart, $language);
            $text .= $language === 'tr'
                ? ' Lira '.$decimalText.' Kuruş'
                : ' and '.$decimalText.' Cents';
        }

        return $text;
    }

    /**
     * Tam sayıyı yazıya çevir
     */
    protected function integerToText(int $number, string $language = 'tr'): string
    {
        if ($language === 'tr') {
            return $this->integerToTextTurkish($number);
        }

        return $this->integerToTextEnglish($number);
    }

    /**
     * Türkçe sayı yazımı
     */
    protected function integerToTextTurkish(int $number): string
    {
        if ($number == 0) {
            return 'Sıfır';
        }

        $ones = ['', 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz'];
        $tens = ['', 'On', 'Yirmi', 'Otuz', 'Kırk', 'Elli', 'Altmış', 'Yetmiş', 'Seksen', 'Doksan'];

        $result = '';

        // Milyar
        if ($number >= 1000000000) {
            $billions = floor($number / 1000000000);
            $result .= ($billions == 1 ? '' : $this->integerToTextTurkish($billions).' ').'Milyar ';
            $number %= 1000000000;
        }

        // Milyon
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $result .= ($millions == 1 ? '' : $this->integerToTextTurkish($millions).' ').'Milyon ';
            $number %= 1000000;
        }

        // Bin
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $result .= ($thousands == 1 ? '' : $this->integerToTextTurkish($thousands).' ').'Bin ';
            $number %= 1000;
        }

        // Yüzler
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= ($hundreds == 1 ? '' : $ones[$hundreds].' ').'Yüz ';
            $number %= 100;
        }

        // Onlar
        if ($number >= 10) {
            $result .= $tens[floor($number / 10)].' ';
            $number %= 10;
        }

        // Birler
        if ($number > 0) {
            $result .= $ones[$number].' ';
        }

        return trim($result);
    }

    /**
     * İngilizce sayı yazımı
     */
    protected function integerToTextEnglish(int $number): string
    {
        if ($number == 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $result = '';

        // Billions
        if ($number >= 1000000000) {
            $billions = floor($number / 1000000000);
            $result .= $this->integerToTextEnglish($billions).' Billion ';
            $number %= 1000000000;
        }

        // Millions
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $result .= $this->integerToTextEnglish($millions).' Million ';
            $number %= 1000000;
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $result .= $this->integerToTextEnglish($thousands).' Thousand ';
            $number %= 1000;
        }

        // Hundreds
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= $ones[$hundreds].' Hundred ';
            $number %= 100;
        }

        // Teens
        if ($number >= 10 && $number < 20) {
            $result .= $teens[$number - 10].' ';

            return trim($result);
        }

        // Tens
        if ($number >= 10) {
            $result .= $tens[floor($number / 10)].' ';
            $number %= 10;
        }

        // Ones
        if ($number > 0) {
            $result .= $ones[$number].' ';
        }

        return trim($result);
    }

    /**
     * Para birimi metni
     */
    protected function getCurrencyText(string $currency, string $language = 'tr'): string
    {
        $currencies = [
            'tr' => [
                'TRY' => 'Türk Lirası',
                'TL' => 'Türk Lirası',
                'USD' => 'Amerikan Doları',
                'EUR' => 'Euro',
                'GBP' => 'İngiliz Sterlini',
            ],
            'en' => [
                'TRY' => 'Turkish Lira',
                'TL' => 'Turkish Lira',
                'USD' => 'US Dollars',
                'EUR' => 'Euros',
                'GBP' => 'British Pounds',
            ],
        ];

        return $currencies[$language][$currency] ?? $currency;
    }

    /**
     * Fiyat aralığını yazıya çevir
     */
    public function convertRangeToText(float $minPrice, float $maxPrice, string $currency = 'TRY'): string
    {
        $minText = $this->convertToText($minPrice, $currency);
        $maxText = $this->convertToText($maxPrice, $currency);

        return $minText.' ile '.$maxText.' arası';
    }

    /**
     * Kısa format (sadece büyük birimler)
     */
    public function convertToShortText(float $price, string $currency = 'TRY'): string
    {
        $currencySymbol = $this->getCurrencySymbol($currency);

        if ($price >= 1000000000) {
            $value = $price / 1000000000;

            return number_format($value, 1).' Milyar '.$currencySymbol;
        }

        if ($price >= 1000000) {
            $value = $price / 1000000;

            return number_format($value, 1).' Milyon '.$currencySymbol;
        }

        if ($price >= 1000) {
            $value = $price / 1000;

            return number_format($value, 0).' Bin '.$currencySymbol;
        }

        return number_format($price, 0).' '.$currencySymbol;
    }

    /**
     * Para birimi sembolü
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'TRY' => '₺',
            'TL' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        return $symbols[$currency] ?? $currency;
    }
}
