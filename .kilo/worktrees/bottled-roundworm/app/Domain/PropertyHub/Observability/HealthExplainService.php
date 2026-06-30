<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Observability;

/**
 * Health Explain Service
 *
 * Responsibility: Map internal health states to deterministic, human-readable explanations.
 * ✅ SAB: SSOT derived strings.
 */
class HealthExplainService
{
    /**
     * Get explanation for a specific health state.
     */
    public function explain(string $state, array $context = []): string
    {
        return match ($state) {
            'healthy'          => 'Bu düğüm yönetilen (governed) durumla %100 uyumludur. Sapma tespit edilmedi.',
            'missing_template' => "KRİTİK: Bu kategori için hem canlı veritabanında hem de yönetilen snapshot'ta şablon (template) bulunmuyor.",
            'shadow'           => "GÖLGE: Bu düğüm yönetilen snapshot'ta tanımlı ancak canlı veritabanında henüz oluşturulmamış (Missing in Live).",
            'drift'            => $this->explainDrift($context),
            'empty'            => 'Boş: Bu kombinasyon için herhangi bir konfigürasyon tanımlanmamış.',
            default            => 'Bilinmeyen Durum: Detaylı analiz yapılamıyor.',
        };
    }

    /**
     * Detailed drift explanation based on context.
     */
    private function explainDrift(array $context): string
    {
        $reason = $context['reason'] ?? 'bilinmiyor';

        if ($reason === 'content_mismatch') {
            return 'İÇERİK SAPMASI: Canlı veritabanındaki JSON içeriği, imzalı snapshot içeriğiyle uyuşmuyor.';
        }

        if ($reason === 'name_mismatch') {
            return 'İSİM SAPMASI: Şablon adı yönetilen versiyondan farklı.';
        }

        return 'SAPMA: Canlı veri yönetilen durumdan ayrışmış durumda.';
    }
}
