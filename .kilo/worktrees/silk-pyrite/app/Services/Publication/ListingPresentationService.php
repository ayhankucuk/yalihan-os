<?php

namespace App\Services\Publication;

use App\Services\CurrencyConversionService;

/**
 * Service for handling presentation logic such as currency conversions.
 */
class ListingPresentationService
{
    public function __construct(
        protected CurrencyConversionService $currencyService
    ) {}

    /**
     * Apply currency conversion for a collection or paginated listings.
     */
    public function applyCurrencyConversions($listings, string $currency): mixed
    {
        foreach ($listings as $ilan) {
            $ilan->converted_price = $this->currencyService->convert(
                $ilan->fiyat,
                $ilan->para_birimi,
                $currency
            );
        }

        return $listings;
    }
}
