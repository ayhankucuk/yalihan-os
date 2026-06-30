<?php

namespace App\Enums\AI;

enum DeepSeekModel: string
{
    /**
     * DeepSeek V3 — genel kullanım (chat, ilan üretimi, CRM)
     * API adı: deepseek-chat
     * Eski sahte adlar: deepseek-v4-flash, deepseek-v4-pro (API'de YOK)
     */
    case CHAT = 'deepseek-chat';

    /**
     * DeepSeek R1 — derin analitik (ROI, scoring, matching)
     * API adı: deepseek-reasoner
     */
    case REASONER = 'deepseek-reasoner';

    /**
     * @deprecated Sahte model adı — API'de mevcut değil.
     * Geriye dönük uyumluluk için tutuldu, hiçbir zaman API'ye gönderilmez.
     * Kullanım: mevcut config/DB değerlerini CHAT'e yönlendirmek için.
     */
    case V4_FLASH = 'deepseek-v4-flash';

    /**
     * @deprecated Sahte model adı — API'de mevcut değil.
     */
    case V4_PRO = 'deepseek-v4-pro';

    /**
     * Verilen değerin gerçek (API'ye gönderilebilir) model adını döner.
     * Eski/sahte değerleri otomatik olarak doğru değere çevirir.
     */
    public function resolve(): self
    {
        return match ($this) {
            self::V4_FLASH, self::CHAT => self::CHAT,
            self::V4_PRO, self::REASONER => self::REASONER,
        };
    }

    /**
     * Verilen değerin API'ye gönderilebilir model string'ini döner.
     */
    public function apiValue(): string
    {
        return $this->resolve()->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::CHAT    => 'DeepSeek V3 (Chat — Genel)',
            self::REASONER => 'DeepSeek R1 (Reasoner — Analitik)',
            self::V4_FLASH => 'DeepSeek V4 Flash [DEPRECATED]',
            self::V4_PRO   => 'DeepSeek V4 Pro [DEPRECATED]',
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $model) => $model->value,
            self::cases()
        );
    }

    /**
     * API'ye gönderilebilir geçerli model adları (sahte olanlar hariç)
     */
    public static function validApiValues(): array
    {
        return [self::CHAT->value, self::REASONER->value];
    }

    public static function options(): array
    {
        return array_map(
            fn (self $model) => [
                'value' => $model->value,
                'label' => $model->label(),
            ],
            self::cases()
        );
    }
}
