<?php

namespace App\Http\Controllers\Frontend;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Preference Controller
 * 
 * Handles user preferences for language (locale) and currency
 * Frontend topbar toggle integration
 */
class PreferenceController
{
    /**
     * Update user locale preference
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setLocale(Request $request): JsonResponse
    {
        $locale = $request->input('locale');
        
        // Validate locale
        $supportedLocales = array_keys(config('localization.supported_locales', []));
        
        if (!in_array($locale, $supportedLocales)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz dil seçimi',
            ], 400);
        }

        // Set application locale
        App::setLocale($locale);
        
        // Store in session
        Session::put('locale', $locale);
        
        return response()->json([
            'success' => true,
            'message' => 'Dil tercihi güncellendi',
            'locale' => $locale,
        ]);
    }

    /**
     * Update user currency preference
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setCurrency(Request $request): JsonResponse
    {
        $currency = strtoupper($request->input('currency'));
        
        // Validate currency
        $supportedCurrencies = array_keys(config('currency.supported', []));
        
        if (!in_array($currency, $supportedCurrencies)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz para birimi seçimi',
            ], 400);
        }

        // Store in session
        Session::put('currency', $currency);
        
        return response()->json([
            'success' => true,
            'message' => 'Para birimi tercihi güncellendi',
            'currency' => $currency,
        ]);
    }
}
