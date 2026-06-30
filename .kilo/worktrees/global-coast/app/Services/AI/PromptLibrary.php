<?php

namespace App\Services\AI;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st*tus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class PromptLibrary
{
    protected array $presets = [
        'seo_kurumsal' => [
            'title' => 'Kurumsal SEO',
            'version' => '1.0.0',
            'content' => 'Kurumsal tonda, lokasyon ve fiyat odaklı SEO uyumlu metin üret. ',
        ],
        'hizli_satis' => [
            'title' => 'Hızlı Satış',
            'version' => '1.0.0',
            'content' => 'Hızlı satış odaklı, avantajları vurgulayan kısa ve etkili metin üret. ',
        ],
        'luks' => [
            'title' => 'Lüks',
            'version' => '1.0.0',
            'content' => 'Lüks segment için özellikleri ve yaşam tarzını öne çıkaran metin üret. ',
        ],
    ];

    public function list(): array
    {
        return $this->presets;
    }

    public function get(string $key): ?array
    {
        return $this->presets[$key] ?? null;
    }
}
