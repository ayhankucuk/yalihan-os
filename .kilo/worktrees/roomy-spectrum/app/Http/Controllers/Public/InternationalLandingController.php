<?php

namespace App\Http\Controllers\Public;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\InvestorAnalyticsService;
use App\Services\CountryComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class InternationalLandingController extends Controller
{
    public function __construct(
        private readonly InvestorAnalyticsService $investorService,
        private readonly CountryComparisonService $countryComparisonService
    ) {}

    public function investInTurkey(string $locale)
    {
        $this->setLocaleOrFail($locale);

        $report = $this->countryComparisonService->generateCountryReport('TR');

        // Fetch categories for the search section if needed
        $propertyTypes = \App\Models\IlanKategori::whereNull('parent_id')->get()->map(fn($item) => [
            'value' => $item->id,
            'label' => $item->name
        ]);

        return view('public.landing.invest-in-turkey', [
            'report' => $report->toArray(),
            'propertyTypes' => $propertyTypes,
            'locale' => $locale
        ]);
    }

    public function goldenVisaGreece(string $locale)
    {
        $this->setLocaleOrFail($locale);
        $countryReport = $this->countryComparisonService->generateCountryReport('GR');

        return view('public.landing.golden-visa-greece', [
            'report' => $countryReport,
            'locale' => $locale
        ]);
    }

    public function ukPropertyInvestment(string $locale)
    {
        $this->setLocaleOrFail($locale);
        $countryReport = $this->countryComparisonService->generateCountryReport('UK');

        return view('public.landing.uk-investment', [
            'report' => $countryReport,
            'locale' => $locale
        ]);
    }

    public function rentalIncomeCalculator(string $locale)
    {
        $this->setLocaleOrFail($locale);

        return view('public.landing.calculator', [
            'locale' => $locale
        ]);
    }

    private function setLocaleOrFail(string $locale): void
    {
        $localeService = app(\App\Services\LocaleControlService::class);
        if (!$localeService->isLocaleActive($locale)) {
            abort(404);
        }

        App::setLocale($locale);
    }
}
