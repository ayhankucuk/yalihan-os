<?php

namespace App\DTO;

class CountryInvestmentReportDTO
{
    public function __construct(
        public string $countryCode,
        public string $countryName,
        public float $averageNetYield,
        public float $averageGrowthRate,
        public float $taxBurden,        // Vergi yükü %
        public float $commissionRate,   // Komisyon oranı %
        public string $currency,
        public array $topProperties     // En karlı mülkler
    ) {}

    public function toArray(): array
    {
        return [
            'country_code'       => $this->countryCode,
            'country_name'       => $this->countryName,
            'average_net_yield' => $this->averageNetYield,
            'average_growth'    => $this->averageGrowthRate,
            'tax_burden'         => $this->taxBurden,
            'commission_rate'    => $this->commissionRate,
            'currency'           => $this->currency,
            'top_properties'     => $this->topProperties,
        ];
    }
}
