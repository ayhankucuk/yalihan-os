<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Price\CurrencyRateService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Currency Rate API Controller
 *
 * Context7 Standardı: C7-CURRENCY-API-2025-10-11
 */
class CurrencyRateController extends Controller
{
    use ValidatesApiRequests;

    protected $currencyService;

    public function __construct(CurrencyRateService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get current exchange rates
     */
    public function getRates(): JsonResponse
    {
        try {
            $rateData = $this->currencyService->getRates();

            return ResponseService::success([
                'rates' => $rateData['rates'],
                'last_updated' => $rateData['last_updated'],
                'source' => $rateData['source'],
                'base_currency' => $rateData['base_currency'],
            ], 'Döviz kurları başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Döviz kurları yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Convert between currencies
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|in:TRY,USD,EUR,GBP',
            'to' => 'required|string|in:TRY,USD,EUR,GBP',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $converted = $this->currencyService->convert(
                $request->amount,
                $request->from,
                $request->to
            );

            return ResponseService::success([
                'original' => [
                    'amount' => $request->amount,
                    'currency' => $request->from,
                    'formatted' => $this->currencyService->format($request->amount, $request->from),
                ],
                'converted' => [
                    'amount' => $converted,
                    'currency' => $request->to,
                    'formatted' => $this->currencyService->format($converted, $request->to),
                ],
                'rate' => $this->currencyService->getRate($request->from, $request->to),
            ], 'Para birimi dönüşümü başarıyla tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Döviz çevrimi başarısız.', $e);
        }
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): JsonResponse
    {
        return ResponseService::success([
            'currencies' => $this->currencyService->getSupportedCurrencies(),
        ], 'Desteklenen para birimleri başarıyla getirildi');
    }

    /**
     * Refresh rates cache
     */
    public function refresh(): JsonResponse
    {
        try {
            $rates = $this->currencyService->refresh();

            return ResponseService::success([
                'rates' => $rates,
            ], 'Döviz kurları güncellendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Kurlar yenilenirken hata oluştu.', $e);
        }
    }
}
