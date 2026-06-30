<?php

namespace App\Services\AI;

class AIPromptBuilder
{
    /**
     * Build analysis prompt
     */
    public function buildAnalysisPrompt(mixed $data, array $context = []): string
    {
        $basePrompt = 'Analiz et ve öneriler sun:';

        if (isset($context['type'])) { // context7-ignore
            switch ($context['type']) { // context7-ignore
                case 'category':
                    $basePrompt = 'Kategori analizi yap ve optimizasyon önerileri sun:';
                    break;
                case 'feature':
                    $basePrompt = 'Özellik analizi yap ve öneriler sun:';
                    break;
                case 'content':
                    $basePrompt = 'İçerik analizi yap ve iyileştirme önerileri sun:';
                    break;
            }
        }

        return $basePrompt . "\n\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build suggestion prompt
     */
    public function buildSuggestionPrompt(mixed $context, string $type = 'general'): string
    {
        $prompts = [
            'category' => 'Bu kategoriler için öneriler sun:',
            'feature' => 'Bu özellikler için öneriler sun:',
            'content' => 'Bu içerik için öneriler sun:',
            'qr_code' => 'QR kod kullanımı için öneriler sun. İlan bilgilerine göre QR kodun nerede ve nasıl kullanılacağına dair pratik öneriler ver:',
            'navigation' => 'İlan navigasyonu için öneriler sun. Kullanıcı deneyimini iyileştirmek için önceki/sonraki ilan navigasyonu ve benzer ilanlar önerileri ver:',
            'general' => 'Genel öneriler sun:',
        ];

        $basePrompt = $prompts[$type] ?? $prompts['general'];

        // QR Code için özel prompt
        if ($type === 'qr_code' && isset($context['ilan'])) {
            $basePrompt .= "\n\nİlan Bilgileri:\n";
            $basePrompt .= '- Başlık: ' . ($context['ilan']['baslik'] ?? 'N/A') . "\n";
            $basePrompt .= '- Kategori: ' . ($context['ilan']['kategori'] ?? 'N/A') . "\n";
            $basePrompt .= '- Lokasyon: ' . ($context['ilan']['lokasyon'] ?? 'N/A') . "\n";
            $basePrompt .= '- Fiyat: ' . ($context['ilan']['fiyat'] ?? 'N/A') . "\n";
            $basePrompt .= "\nQR kod kullanım önerileri:\n";
            $basePrompt .= "- Fiziksel görüntülemelerde nerede kullanılmalı?\n";
            $basePrompt .= "- Print materyallerde nasıl yerleştirilmeli?\n";
            $basePrompt .= "- Sosyal medya paylaşımlarında nasıl kullanılmalı?\n";
            $basePrompt .= "- Mobil kullanıcı deneyimi için öneriler\n";
        }

        // Navigation için özel prompt
        if ($type === 'navigation' && isset($context['ilan'])) {
            $basePrompt .= "\n\nİlan Bilgileri:\n";
            $basePrompt .= '- Başlık: ' . ($context['ilan']['baslik'] ?? 'N/A') . "\n";
            $basePrompt .= '- Kategori: ' . ($context['ilan']['kategori'] ?? 'N/A') . "\n";
            $basePrompt .= '- Lokasyon: ' . ($context['ilan']['lokasyon'] ?? 'N/A') . "\n";
            $basePrompt .= '- Fiyat: ' . ($context['ilan']['fiyat'] ?? 'N/A') . "\n";
            $basePrompt .= "\nNavigasyon önerileri:\n";
            $basePrompt .= "- Hangi ilanlar önceki/sonraki olarak gösterilmeli?\n";
            $basePrompt .= "- Benzer ilanlar nasıl belirlenmeli?\n";
            $basePrompt .= "- Kullanıcı deneyimini iyileştirmek için ne yapılmalı?\n";
        }

        return $basePrompt . "\n\n" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build prompt for listing copy generation (title + description)
     */
    public function buildListingCopyPrompt(array $context): string
    {
        $ilan = $context['ilan'] ?? [];
        $featuresPayload = $context['features'] ?? $context['feature_categories'] ?? [];

        $lines = [];
        $lines[] = 'Aşağıdaki emlak ilanı için etkileyici, SEO uyumlu ama abartısız bir başlık ve detaylı bir açıklama üret.';
        $lines[] = 'Türkçe yaz. Kısa, net ve güven verici ol.';
        $lines[] = 'Çıktıyı SADECE JSON formatında döndür:';
        $lines[] = '{"title": string, "description": string, "quality_score": integer (0-100), "improvement_hints": string[] }';
        $lines[] = '';
        $lines[] = 'İlan Özeti:';
        $lines[] = '- Kategori: ' . ($ilan['kategori'] ?? '');
        $lines[] = '- Yayın Tipi: ' . ($ilan['yayin_tipi'] ?? '');
        $lines[] = '- Lokasyon: ' . ($ilan['lokasyon'] ?? '');
        $lines[] = '- Fiyat: ' . ($ilan['fiyat'] ?? '');
        $lines[] = '- Metrekare: ' . ($ilan['metrekare'] ?? '');
        $lines[] = '- Oda Sayısı: ' . ($ilan['oda_sayisi'] ?? '');

        $lines[] = '\nUPS Özellik Şablonu (FeatureTemplateResolver):';
        foreach ($featuresPayload as $category) {
            $featureNames = [];
            foreach ($category['features'] ?? [] as $feature) {
                $featureNames[] = $feature['name'] ?? $feature['slug'] ?? '';
            }
            if (!empty($featureNames)) {
                $lines[] = '- ' . ($category['name'] ?? 'Genel') . ': ' . implode(', ', $featureNames);
            }
        }

        if (!empty($context['options']) && is_array($context['options'])) {
            $lines[] = '\nYazım Tercihleri:';
            foreach ($context['options'] as $key => $value) {
                $lines[] = '- ' . $key . ': ' . (is_scalar($value) ? $value : json_encode($value));
            }
        }

        // Restb.ai Parity: Add Vision Analysis Results
        if (!empty($context['vision_analysis']) && is_array($context['vision_analysis'])) {
            $visionData = $context['vision_analysis'];

            // Summarize room types
            if (!empty($visionData['room_types'])) {
                $lines[] = '- Tespit Edilen Odalar: ' . implode(', ', array_unique($visionData['room_types']));
            }

            // Summarize conditions
            if (!empty($visionData['conditions'])) {
                $lines[] = '- Genel Durum: ' . implode(', ', array_unique($visionData['conditions']));
            }

            // Detected features
            if (!empty($visionData['detected_features'])) {
                $lines[] = '- Görsel Özellikler: ' . implode(', ', array_unique($visionData['detected_features']));
            }
        }

        $lines[] = '\nKurallar:';
        $lines[] = '- Title 70 karakteri geçmemeli.';
        $lines[] = '- Description en az 2 paragraf olmalı.';
        $lines[] = '- Quality_score 0-100 arası bir TAM SAYI olmalı (subjektif kalite skoru).';
        $lines[] = '- improvement_hints, ilanın daha iyi hale gelmesi için kısa maddelerden oluşmalı.';

        return implode("\n", $lines);
    }

    /**
     * Build field suggestion prompt
     */
    public function buildFieldSuggestionPrompt(string $kategoriSlug, string $yayinTipi, string $fieldSlug, array $context): string
    {
        // Kategori özel prompt'lar
        $categoryContext = [
            'arsa' => [
                'ada_no' => 'Lokasyon bilgisinden ve TKGM verilerinden ada numarasını öner.',
                'parsel_no' => 'Lokasyon bilgisinden ve TKGM verilerinden parsel numarasını öner.',
                'imar_durumu' => 'Arsa konumu ve çevresindeki yapılaşmaya göre imar durumunu öner.',
                'kaks' => 'İmar durumuna ve lokasyona göre KAKS değeri öner (örn: 1.25, 1.50).',
                'taks' => 'İmar durumuna göre TAKS değeri öner (örn: 0.30, 0.40).',
                'gabari' => 'Bölgenin yapılaşma karakterine göre gabari öner (örn: 9.50m).',
            ],
            'yazlik' => [
                'gunluk_fiyat' => 'Lokasyon, metrekare ve özelliklere göre günlük fiyat öner.',
                'haftalik_fiyat' => 'Günlük fiyattan haftalık fiyat hesapla (7 gün × %85 indirim).',
                'aylik_fiyat' => 'Günlük fiyattan aylık fiyat hesapla (30 gün × %70 indirim).',
                'yaz_sezonu_fiyat' => 'Piyasa verilerine göre yaz sezonu fiyatı öner.',
                'ara_sezon_fiyat' => 'Yaz sezonu fiyatından %70 olarak hesapla.',
                'kis_sezonu_fiyat' => 'Yaz sezonu fiyatından %50 olarak hesapla.',
                'minimum_konaklama' => 'Sezona ve bölgeye göre minimum konaklama öner (3-7 gün).',
                'maksimum_misafir' => 'Metrekareye göre maksimum misafir sayısı öner (m²/15).',
                'denize_uzaklik' => 'Google Maps API ile denize uzaklığı hesapla.',
            ],
            'konut' => [
                'esyali' => 'İlan fotoğraflarından ve açıklamadan eşyalı durumunu belirle.',
                'm2_fiyati' => 'Satış fiyatı / Metrekare ile hesapla.',
            ],
        ];

        $fieldContext = $categoryContext[$kategoriSlug][$fieldSlug] ?? 'Bu field için uygun değer öner.';

        $prompt = "
🎯 Emlak İlan Field Suggestion

Kategori: {$kategoriSlug}
Yayın Tipi: {$yayinTipi}
Field: {$fieldSlug}

Context:
" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

Görev: {$fieldContext}

Sadece önerilen değeri döndür (açıklama veya birim olmadan).
Örnek: Ada no için → 1234
Örnek: Günlük fiyat için → 500
Örnek: İmar durumu için → İmarlı
";

        return $prompt;
    }

    /**
     * Property Analysis Prompt Builder
     */
    public function buildPropertyAnalysisPrompt(array $propertyData, array $context): string
    {
        return "Mevcut emlak özellikleri analizi:\n\n" .
            'Özellikler: ' . json_encode($propertyData, JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Bu özellikler için:\n" .
            "1. Eksik olan önemli özellikler neler?\n" .
            "2. Hangi özellikler daha detaylandırılabilir?\n" .
            "3. Bu emlak için hangi özellikler değer katabilir?\n" .
            "4. AI ile otomatik doldurulabilecek özellikler hangileri?\n\n" .
            'Her öneri için önem derecesi ve gerekçe belirt.';
    }

    /**
     * Smart Form Prompt Builder
     */
    public function buildSmartFormPrompt(string $kategoriSlug, string $yayinTipi, array $context): string
    {
        $kategoriNames = [
            'konut' => 'Konut',
            'arsa' => 'Arsa',
            'yazlik' => 'Yazlık',
            'isyeri' => 'İşyeri',
        ];

        $kategoriName = $kategoriNames[$kategoriSlug] ?? $kategoriSlug;

        return "{$kategoriName} kategorisi için akıllı form oluştur:\n\n" .
            "Form field'ları şu kategorilerde organize et:\n" .
            "1. Altyapı\n" .
            "2. Genel Özellikler\n" .
            "3. Manzara\n" .
            "4. Konum\n\n" .
            "Her field için:\n" .
            "- Field tipi (text, number, boolean, select, textarea)\n" .
            "- Zorunlu mu? (true/false)\n" .
            "- AI önerisi var mı? (true/false)\n" .
            "- AI otomatik doldurma var mı? (true/false)\n" .
            "- Select seçenekleri (eğer select ise)\n" .
            "- Birim (m², km, vs.)\n\n" .
            'JSON formatında döndür.';
    }
}
