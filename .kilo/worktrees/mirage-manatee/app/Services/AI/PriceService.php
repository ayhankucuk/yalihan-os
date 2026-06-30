<?php

namespace App\Services\AI;

use App\Services\AIService;

class PriceService
{
    public function __construct(
        private AIService $ai,
        private \App\Services\Template\TemplateService $templateService
    ) {}

    public function predict(array $payload): array
    {
        $context = $payload['context'] ?? $payload;
        $kategoriId = $payload['kategori_id'] ?? null;
        $yayinTipiId = $payload['yayin_tipi_id'] ?? null;

        // 1. Fetch Template for multipliers
        $multipliers = [];
        if ($kategoriId && $yayinTipiId) {
            $templateData = $this->templateService->autoSelectTemplate($kategoriId, $yayinTipiId);
            $multipliers = $templateData['template']['ai_fiyat_onerisi'] ?? [];
        }

        // 2. Inject multipliers into context for AI visibility
        if (!empty($multipliers)) {
            $context['business_rules'] = array_merge(
                $context['business_rules'] ?? [],
                ['price_multipliers' => $multipliers]
            );
        }

        $result = $this->ai->analyze($context, ['type' => 'price']); // context7-ignore
        $price = $result['price'] ?? ($result['data']['price'] ?? null);

        return [
            'success' => true,
            'data' => [
                'suggested_price' => $price,
                'meta' => array_merge(
                    $result['meta'] ?? [],
                    ['using_template_rules' => !empty($multipliers)]
                ),
            ],
            'message' => 'Fiyat tahmini tamamlandı',
        ];
    }
}
