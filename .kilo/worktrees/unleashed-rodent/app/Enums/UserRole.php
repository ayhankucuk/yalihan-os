<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case DANISMAN = 'danisman';
    case EDITOR = 'editor';

    /**
     * Tüm rol değerlerini döndürür
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * İnsan tarafından okunabilir rol adları
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Süper Admin',
            self::DANISMAN => 'Danışman',
            self::EDITOR => 'Editör',
        };
    }
}
