<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Services\TCMBCurrencyService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exchange Rate API Controller
 *
 * Context7: Real-time currency rates for international listings
 */
class ExchangeRateController extends Controller
{
    use ValidatesApiRequests;

    protected TCMBCurrencyService $currencyService;

    public function __construct(TCMBCurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get today's exchange rates
     *
     * GET /api/exchange-rates
     */
    public function index(): JsonResponse
    {
        try {
            $rates = $this->currencyService->getTodayRates();

            return ResponseService::success([
                'data' => $rates,
                'count' => count($rates),
                'source' => 'TCMB',
                'updated_at' => now()->toDateTimeString(),
            ], 'Döviz kurları başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Döviz kurları yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get specific currency rate
     *
     * GET /api/exchange-rates/{code}
     */
    public function show(string $code): JsonResponse
    {
        try {
            $rate = $this->currencyService->getRate($code);

            if (! $rate) {
                return ResponseService::notFound("Para birimi {$code} bulunamadı");
            }

            $rates = $this->currencyService->getTodayRates();

            return ResponseService::success([
                'data' => $rates[$code] ?? null,
                'rate' => $rate,
                'symbol' => $this->currencyService->getCurrencySymbol($code),
            ], "Para birimi {$code} başarıyla getirildi");
        } catch (\Exception $e) {
            return ResponseService::serverError('Para birimi kuru yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Convert amount between currencies
     *
     * POST /api/exchange-rates/convert
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $amount = $validated['amount'];
            $from = strtoupper($validated['from']);
            $to = strtoupper($validated['to']);

            // Convert to TRY first
            $tryAmount = $from === 'TRY'
                ? $amount
                : $this->currencyService->convertToTRY($amount, $from);

            // Then convert to target currency
            $result = $to === 'TRY'
                ? $tryAmount
                : $this->currencyService->convertFromTRY($tryAmount, $to);

            return ResponseService::success([
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'result' => round($result, 2),
                'rate' => $from === 'TRY' ? null : $this->currencyService->getRate($from),
                'formatted' => $this->currencyService->getCurrencySymbol($to).' '.number_format($result, 2),
            ], 'Para birimi dönüşümü başarıyla tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Para birimi dönüşümü sırasında hata oluştu.', $e);
        }
    }

    /**
     * Get currency history
     *
     * GET /api/exchange-rates/{code}/history
     */
    public function history(string $code, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        try {
            $history = $this->currencyService->getRateHistory($code, $days);

            return ResponseService::success([
                'data' => $history->map(function ($rate) {
                    return [
                        'date' => $rate->effective_at?->format('Y-m-d'),
                        'rate' => (float) $rate->rate,
                        'from_currency' => $rate->from_currency,
                        'to_currency' => $rate->to_currency,
                    ];
                }),
                'currency' => $code,
                'days' => $days,
            ], 'Döviz kuru geçmişi başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Döviz kuru geçmişi yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get supported currencies
     *
     * GET /api/exchange-rates/supported
     */
    public function supported(): JsonResponse
    {
        $currencies = $this->currencyService->getSupportedCurrencies();

        return ResponseService::success([
            'data' => collect($currencies)->map(function ($code) {
                return [
                    'code' => $code,
                    'symbol' => $this->currencyService->getCurrencySymbol($code),
                    'name' => $this->getCurrencyName($code),
                ];
            }),
        ], 'Desteklenen para birimleri başarıyla getirildi');
    }

    /**
     * Force update rates (admin only)
     *
     * POST /api/exchange-rates/update
     */
    public function update(): JsonResponse
    {
        try {
            $updated = $this->currencyService->updateRates();

            return ResponseService::success([
                'updated_count' => $updated,
            ], "{$updated} döviz kuru başarıyla güncellendi");
        } catch (\Exception $e) {
            return ResponseService::serverError('Döviz kurları güncellenirken hata oluştu.', $e);
        }
    }

    /**
     * Get currency name
     *
     * @param  string  $code
     * @return string
     */
    private function getCurrencyName($code)
    {
        return match ($code) {
            'TRY' => 'Türk Lirası',
            'USD' => 'Amerikan Doları',
            'EUR' => 'Euro',
            'GBP' => 'İngiliz Sterlini',
            'CHF' => 'İsviçre Frangı',
            'CAD' => 'Kanada Doları',
            'AUD' => 'Avustralya Doları',
            'JPY' => 'Japon Yeni',
            default => $code
        };
    }
}
