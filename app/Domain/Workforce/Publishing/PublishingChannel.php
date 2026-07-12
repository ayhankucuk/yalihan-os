<?php

namespace App\Domain\Workforce\Publishing;

/**
 * PublishingChannel — Desteklenen yayin kanallari.
 *
 * Sprint 7.4
 */
enum PublishingChannel: string
{
    case YALIHAN    = 'yalihan';
    case SAHİBİNDEN = 'sahibinden';
    case HEPSİEMLAK = 'hepsiemlak';
    case EMLAKJET   = 'emlakjet';
    case ZİNGAT    = 'zingat';
    case AIRBNB     = 'airbnb';
    case BOOKİNG    = 'booking';
    case CHANEX     = 'channex';

    public function label(): string
    {
        return match($this) {
            self::YALIHAN     => 'Yalihan',
            self::SAHİBİNDEN => 'Sahibinden',
            self::HEPSİEMLAK => 'HepsiEmlak',
            self::EMLAKJET    => 'Emlakjet',
            self::ZİNGAT      => 'Zingat',
            self::AIRBNB      => 'Airbnb',
            self::BOOKİNG     => 'Booking.com',
            self::CHANEX      => 'Channex',
        };
    }

    public function type(): string
    {
        return match($this) {
            self::YALIHAN, self::SAHİBİNDEN, self::HEPSİEMLAK,
            self::EMLAKJET, self::ZİNGAT => 'turkish_portal',
            self::AIRBNB, self::BOOKİNG, self::CHANEX => 'vacation_rental',
        };
    }

    public function isTurkishPortal(): bool
    {
        return $this->type() === 'turkish_portal';
    }

    public function isVacationRental(): bool
    {
        return $this->type() === 'vacation_rental';
    }

    /** Kira icin mi (yoksa satilik)? */
    public function requiresRental(): bool
    {
        return $this->isVacationRental();
    }

    /** Minimum kalite skoru esigi */
    public function minQualityScore(): int
    {
        return match($this) {
            self::YALIHAN      => 0,
            self::SAHİBİNDEN  => 30,
            self::HEPSİEMLAK   => 50,
            self::EMLAKJET    => 60,
            self::ZİNGAT      => 60,
            self::AIRBNB      => 55,
            self::BOOKİNG     => 55,
            self::CHANEX       => 50,
        };
    }
}
