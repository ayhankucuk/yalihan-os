<?php

declare(strict_types=1);

namespace App\Services\CommandCenter\Handlers;

use App\DTOs\Command\NormalizedCommandInput;
use App\Services\Ilan\IlanSearchService;
use Illuminate\Support\Facades\Log;

/**
 * PropertySearchIntentHandler
 *
 * Context7 Standard: C7-PROPERTY-SEARCH-INTENT-HANDLER-2026-09-19
 *
 * Portföy / ilan arama komutlarını deterministik IlanSearchService ile işler.
 */
class PropertySearchIntentHandler
{
    public function __construct(
        private IlanSearchService $ilanSearchService
    ) {}

    public function handle(NormalizedCommandInput $input, array $params): string
    {
        $maxFiyat = $params['maxFiyat'] ?? null;
        $currency = $params['paraBirimi'] ?? 'EUR';

        Log::info('PropertySearchIntentHandler: Portföy sorgulanıyor', [
            'max_fiyat' => $maxFiyat,
            'currency' => $currency,
        ]);

        $searchParams = [
            'yayin_durumu' => 'yayinda',
            'maxFiyat' => $maxFiyat,
            'paraBirimi' => $currency, // Phase 1j: wired from IntentRouter extraction
            'perPage' => 10,
            'sort' => 'fiyat:asc',
        ];

        $results = $this->ilanSearchService->search($searchParams);
        $items = $results['data'] ?? [];
        $total = $results['meta']['total'] ?? count($items);

        if (empty($items)) {
            $formattedMax = $maxFiyat ? number_format((float) $maxFiyat, 0, ',', '.').' '.$currency : '';

            return "🔍 *Portföy Arama Sonucu*\n\n".
                   "Belirtilen kriterlerde ({$formattedMax}) yayında ilan bulunamadı.";
        }

        $formattedMax = $maxFiyat ? number_format((float) $maxFiyat, 0, ',', '.').' '.$currency : 'Tüm Fiyatlar';
        $response = "🏡 *Yalıhan Portföy Arama Sonuçları*\n";
        $response .= "📊 Kriter: Max {$formattedMax} | Bulunan: {$total} İlan\n\n";

        foreach ($items as $index => $item) {
            $num = $index + 1;
            $title = e($item->baslik ?? 'İsimsiz İlan');
            $price = isset($item->fiyat) ? number_format((float) $item->fiyat, 0, ',', '.') : '0';
            $curr = $item->para_birimi ?? $currency;
            $id = $item->id ?? 0;

            $response .= "{$num}. *{$title}*\n";
            $response .= "   💰 Fiyat: {$price} {$curr}\n";
            $response .= "   🔗 [İlan Detayı](https://panel.yalihanemlak.com.tr/admin/ilanlar/{$id})\n\n";
        }

        return trim($response);
    }
}
