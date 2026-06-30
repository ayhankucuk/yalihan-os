<?php

namespace App\Services\AI\Quality;

/**
 * Ilan Quality Auditor
 * 
 * Context7 Standard: C7-QUALITY-AUDITOR-2026-01-06
 * 
 * Sorumluluk: 
 * İlan verilerini deterministik kurallara göre denetler ve puanlar.
 * AI kullanmadan hızlı ve kesin geri bildirim sağlar.
 * Wizard sürecinde anlık geri bildirim için kullanılır.
 */
class IlanQualityAuditor
{
    /**
     * İlan verilerini denetle
     *
     * @param array $data İlan verileri (baslik, aciklama, fiyat, fotograflar, vs.)
     * @param array $context UPS bağlamı (opsiyonel)
     * @return array ['score', 'issues', 'suggested_fixes']
     */
    public function audit(array $data, array $context = []): array
    {
        $issues = [];
        $suggestedFixes = [];
        $score = 100;

        $baslik = $data['baslik'] ?? '';
        $aciklama = $data['aciklama'] ?? '';
        $fiyat = $data['fiyat'] ?? 0;
        
        // 1. Başlık Analizi
        if (empty($baslik)) {
            $issues[] = ['code' => 'TITLE_EMPTY', 'message' => 'Başlık boş.', 'severity' => 'high'];
            $suggestedFixes[] = ['code' => 'ADD_TITLE', 'message' => 'Başlık ekleyin.'];
            $score -= 30;
        } elseif (mb_strlen($baslik) < 10) {
            $issues[] = ['code' => 'TITLE_TOO_SHORT', 'message' => 'Başlık çok kısa (min 10 karakter önerilir).', 'severity' => 'medium'];
            $score -= 10;
        } elseif (mb_strlen($baslik) > 60) {
            // Çok uzun başlık da iyi değil ama puan kırmayalım, sadece uyarı
            // $issues[] = ['code' => 'TITLE_TOO_LONG', 'message' => 'Başlık biraz uzun, 60 karakter altı daha etkili olabilir.', 'severity' => 'low'];
        }

        // 2. Açıklama Analizi
        if (empty($aciklama)) {
            $issues[] = ['code' => 'DESC_EMPTY', 'message' => 'Açıklama boş.', 'severity' => 'high'];
            $suggestedFixes[] = ['code' => 'ADD_DESCRIPTION', 'message' => 'Açıklama ekleyin.'];
            $score -= 30;
        } elseif (mb_strlen($aciklama) < 150) {
            $issues[] = ['code' => 'DESC_TOO_SHORT', 'message' => 'Açıklama çok kısa (min 150 karakter, 300+ önerilir).', 'severity' => 'medium'];
            $score -= 20;
        } elseif (mb_strlen($aciklama) < 300) {
            $suggestedFixes[] = ['code' => 'DESC_LENGTH_WARNING', 'message' => 'Açıklama 300+ karakter olması önerilir.'];
            $score -= 5;
        }

        // 3. Fiyat Analizi
        if (empty($fiyat) || $fiyat <= 0) {
            $issues[] = ['code' => 'PRICE_INVALID', 'message' => 'Geçerli bir fiyat girilmedi.', 'severity' => 'critical'];
            $score -= 50;
        }

        // 4. Fotoğraf Kontrolü (Draft aşamasında 'temp_photos' veya 'fotograflar' gelebilir)
        $photoCount = 0;
        if (isset($data['fotograflar']) && is_array($data['fotograflar'])) {
            $photoCount = count($data['fotograflar']);
        } elseif (isset($data['draft_features']['photo_count'])) {
             $photoCount = (int) $data['draft_features']['photo_count'];
        }
        
        if ($photoCount == 0) {
             $issues[] = ['code' => 'NO_PHOTOS', 'message' => 'Hiç fotoğraf yüklenmemiş.', 'severity' => 'high'];
             $score -= 30;
        } elseif ($photoCount < 5) {
            $issues[] = ['code' => 'FEW_PHOTOS', 'message' => "Daha fazla fotoğraf yükleyin (Mevcut: $photoCount, Önerilen: 5+).", 'severity' => 'medium'];
            $score -= 10;
        }

        // 5. Konum Kontrolü
        if (empty($data['il_id']) || empty($data['ilce_id'])) {
             $issues[] = ['code' => 'LOCATION_MISSING', 'message' => 'Konum bilgisi (İl/İlçe) eksik.', 'severity' => 'high'];
             $score -= 15;
        }

        // 6. Spam Pattern Kontrolü
        if ($this->hasSpamPatterns($baslik . ' ' . $aciklama)) {
            $issues[] = ['code' => 'SPAM_DETECTED', 'message' => 'Spam pattern tespit edildi (çoklu emoji, ALL CAPS, abartılı ifadeler).', 'severity' => 'medium'];
            $score -= 25;
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggested_fixes' => $suggestedFixes,
        ];
    }

    /**
     * Spam/Kalitesiz içerik desenlerini kontrol et
     */
    private function hasSpamPatterns(string $text): bool
    {
        $patterns = [
            '/([!?.]{3,})/', // !!! veya ???
            '/(\b(ACİL|ŞOK|FIRSAT|KELEPİR)\b.*){3,}/i', // Tekrarlayan clickbait kelimeler
            '/([A-ZİĞÜŞÖÇ]{10,})/', // Sürekli BÜYÜK HARF kullanımı (10+ karakter)
            '/([^\w\s.,!?]){3,}/u', // Arka arkaya 3+ emoji veya özel karakter
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
