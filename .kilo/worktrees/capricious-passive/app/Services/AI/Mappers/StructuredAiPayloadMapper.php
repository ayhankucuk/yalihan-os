<?php

namespace App\Services\AI\Mappers;

/**
 * 🛰️ StructuredAiPayloadMapper
 * 
 * Part of D4 Phase. Authority for mapping Domain-led Canonical Schemas 
 * to YalihanCortex capability payloads.
 * 
 * SSOT: Application Domain (Konut/Yazlik Services)
 */
class StructuredAiPayloadMapper
{
    /**
     * Map raw structured data to unified Cortex payload.
     * 
     * Handles normalization, null-checks, and field mapping to ensure 
     * the AI receives a consistent perspective of the listing.
     */
    public function map(array $data, string $useCase = 'title'): array
    {
        // 1. Determine base facts (Common across Konut/Yazlik)
        $facts = [
            'lokasyon' => $this->formatLocation($data['lokasyon'] ?? []),
            'emlak_tipi' => $data['konut_tipi'] ?? $data['yazlik_tipi'] ?? 'Gayrimenkul',
            'fiyat' => $this->formatPrice($data),
        ];

        // 2. Map use-case specific facts
        return match ($useCase) {
            'title'       => array_merge($facts, $this->getFeatureFacts($data, 5)),
            'description' => array_merge($facts, $this->getFeatureFacts($data, 20)),
            'summary'     => array_merge($facts, $this->getFeatureFacts($data, 10)),
            'seo_meta'    => array_merge($facts, $this->getFeatureFacts($data, 5)),
            default       => $facts
        };
    }

    protected function formatLocation(array $lokasyon): string
    {
        $parts = array_filter([
            $lokasyon['il_adi'] ?? $lokasyon['il'] ?? null,
            $lokasyon['ilce_adi'] ?? $lokasyon['ilce'] ?? null,
            $lokasyon['mahalle_adi'] ?? $lokasyon['mahalle'] ?? null,
        ]);

        return empty($parts) ? 'Belirtilmemiş' : implode(' / ', $parts);
    }

    protected function formatPrice(array $data): string
    {
        // Konut Satılık Price
        if (isset($data['fiyat']['satilik_fiyat'])) {
            return $data['fiyat']['satilik_fiyat'] . ' ' . ($data['fiyat']['para_birimi'] ?? 'TRY');
        }

        // Yazlik Kiralama Prices
        $prices = $data['fiyatlandirma'] ?? [];
        if (!empty($prices)) {
            $currency = $prices['para_birimi'] ?? 'TRY';
            if (isset($prices['gunluk_fiyat'])) return "Günlük " . $prices['gunluk_fiyat'] . " " . $currency;
            if (isset($prices['haftalik_fiyat'])) return "Haftalık " . $prices['haftalik_fiyat'] . " " . $currency;
            if (isset($prices['aylik_fiyat'])) return "Aylık " . $prices['aylik_fiyat'] . " " . $currency;
        }

        return 'Belirtilmemiş';
    }

    /**
     * Extract features into a flat fact list for AI digestion.
     */
    protected function getFeatureFacts(array $data, int $limit = 10): array
    {
        $facts = [];

        // Capacity facts (Yazlik)
        if (isset($data['kapasite'])) {
            if ($v = $data['kapasite']['max_misafir'] ?? null) $facts['kapasite'] = $v . " Misafir";
            if ($v = $data['kapasite']['oda_sayisi'] ?? null) $facts['oda_sayisi'] = $v;
        }

        // Room facts (Konut)
        if (isset($data['oda_sayisi'])) $facts['oda_sayisi'] = $data['oda_sayisi'];
        if (isset($data['salon_sayisi'])) $facts['salon_sayisi'] = $data['salon_sayisi'];
        if (isset($data['brut_m2'])) $facts['metrekare'] = $data['brut_m2'] . " m2";

        // Boolean features (Common)
        $featureGroups = ['ic_ozellikler', 'dis_ozellikler', 'havuz_deniz', 'ozellikler'];
        foreach ($featureGroups as $group) {
            if (isset($data[$group])) {
                foreach ($data[$group] as $key => $value) {
                    if ($value === true || $value === 1) {
                        $facts[] = str_replace('_', ' ', $key);
                    }
                }
            }
        }

        return ['features' => array_slice($facts, 0, $limit)];
    }
}
