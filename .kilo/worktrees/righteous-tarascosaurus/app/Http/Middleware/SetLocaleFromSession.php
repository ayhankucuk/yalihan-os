<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Locale From Session
 * 
 * Middleware to automatically set application locale from session
 */
class SetLocaleFromSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check session for stored locale
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            
            // Validate against supported locales
            $supportedLocales = array_keys(config('localization.supported_locales', []));
            
            if (in_array($locale, $supportedLocales)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
