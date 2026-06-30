<?php

namespace App\Enums;

/**
 * İlan Segment Sıralaması - Revy Mantığına Göre
 * Context7: Sequential workflow segments for property listing management
 */
enum IlanSegment: string
{
    // Segment 1: Temel Bilgi Formu
    case PORTFOLIO_INFO = 'portfolio_info';

    // Segment 2: Dökümanlar ve Notlar
    case DOCUMENTS_NOTES = 'documents_notes';

    // Segment 3: Portal İlan Bilgileri
    case PORTAL_LISTING = 'portal_listing';

    // Segment 4: Uygun Alıcılar
    case SUITABLE_BUYERS = 'suitable_buyers';

    // Segment 5: İşlem Kapama
    case TRANSACTION_CLOSURE = 'transaction_closure';

    /**
     * Segment sıralaması - İş akışı için kritik
     */
    public static function getOrder(): array
    {
        return [
            self::PORTFOLIO_INFO,
            self::DOCUMENTS_NOTES,
            self::PORTAL_LISTING,
            self::SUITABLE_BUYERS,
            self::TRANSACTION_CLOSURE,
        ];
    }

    /**
     * Segment başlıkları
     */
    public function getTitle(): string
    {
        return match ($this) {
            self::PORTFOLIO_INFO => 'Portföy Bilgi Formu',
            self::DOCUMENTS_NOTES => 'Dökümanlar ve Notlar',
            self::PORTAL_LISTING => 'Portal İlan Bilgileri',
            self::SUITABLE_BUYERS => 'Uygun Alıcılar',
            self::TRANSACTION_CLOSURE => 'İşlem Kapama',
        };
    }

    /**
     * Segment açıklamaları
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PORTFOLIO_INFO => 'Temel emlak bilgilerini girin',
            self::DOCUMENTS_NOTES => 'Yasal belgeler ve iç notlar',
            self::PORTAL_LISTING => 'Dış portallara yayın ayarları',
            self::SUITABLE_BUYERS => 'Potansiyel alıcı eşleştirme',
            self::TRANSACTION_CLOSURE => 'Satış/kiralama tamamlama',
        };
    }

    /**
     * Segment ikonları
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PORTFOLIO_INFO => 'fas fa-file-alt',
            self::DOCUMENTS_NOTES => 'fas fa-file-upload',
            self::PORTAL_LISTING => 'fas fa-globe',
            self::SUITABLE_BUYERS => 'fas fa-users',
            self::TRANSACTION_CLOSURE => 'fas fa-check-circle',
        };
    }

    /**
     * Sonraki segment
     */
    public function getNext(): ?self
    {
        $order = self::getOrder();
        $currentIndex = array_search($this, $order);

        if ($currentIndex !== false && $currentIndex < count($order) - 1) {
            return $order[$currentIndex + 1];
        }

        return null;
    }

    /**
     * Önceki segment
     */
    public function getPrevious(): ?self
    {
        $order = self::getOrder();
        $currentIndex = array_search($this, $order);

        if ($currentIndex !== false && $currentIndex > 0) {
            return $order[$currentIndex - 1];
        }

        return null;
    }

    /**
     * Segment tamamlanabilir mi?
     */
    public function canComplete(): bool
    {
        return match ($this) {
            self::PORTFOLIO_INFO => true,  // Temel bilgiler yeterli
            self::DOCUMENTS_NOTES => true, // Dökümanlar opsiyonel
            self::PORTAL_LISTING => true,  // Portal ayarları opsiyonel
            self::SUITABLE_BUYERS => true, // Alıcı eşleştirme opsiyonel
            self::TRANSACTION_CLOSURE => true, // İşlem kapatma opsiyonel
        };
    }

    /**
     * Segment zorunlu mu?
     */
    public function isRequired(): bool
    {
        return match ($this) {
            self::PORTFOLIO_INFO => true,  // Temel bilgiler zorunlu
            self::DOCUMENTS_NOTES => false, // Dökümanlar opsiyonel
            self::PORTAL_LISTING => false, // Portal ayarları opsiyonel
            self::SUITABLE_BUYERS => false, // Alıcı eşleştirme opsiyonel
            self::TRANSACTION_CLOSURE => false, // İşlem kapatma opsiyonel
        };
    }
}
