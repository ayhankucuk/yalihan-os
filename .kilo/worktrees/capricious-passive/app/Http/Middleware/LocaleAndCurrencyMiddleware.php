<?php

namespace App\Http\Middleware;

use App\Services\LocaleControlService;
use App\Services\CurrencyControlService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;

class LocaleAndCurrencyMiddleware
{
    protected $localeService;
    protected $currencyService;

    public function __construct(LocaleControlService $localeService, CurrencyControlService $currencyService)
    {
        $this->localeService = $localeService;
        $this->currencyService = $currencyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Resolve & Set Locale
        // "ai" gibi sistem route prefix'leri locale olarak değerlendirilmemeli.
        $nonLocaleSegments = ['ai'];
        $urlLocale = $request->segment(1);
        if ($urlLocale && strlen($urlLocale) === 2 && !in_array($urlLocale, $nonLocaleSegments, true)) {
            if (!$this->localeService->isLocaleActive($urlLocale)) {
                abort(404);
            }
        }

        $locale = $this->localeService->resolveLocale();
        App::setLocale($locale);

        // 2. Resolve Currency
        $currency = $this->currencyService->resolveCurrency();

        // 3. Share with all views (Blade)
        View::share('current_locale', $locale);
        View::share('current_currency', $currency);

        // Share active languages and currencies for switchers
        View::share('active_languages', $this->localeService->getActiveLanguages());
        View::share('active_currencies', $this->currencyService->getActiveCurrencies());

        return $next($request);
    }
}
