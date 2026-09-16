<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ilan\Form;

use App\Domain\Ilan\ValueObjects\FieldKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FieldKeyTest extends TestCase
{
    public function test_creates_valid_snake_case_key(): void
    {
        $key = FieldKey::fromString('oda_sayisi');
        $this->assertSame('oda_sayisi', $key->value());
        $this->assertSame('oda_sayisi', (string)$key);
    }

    public function test_normalizes_legacy_kebab_case_aliases(): void
    {
        $this->assertSame('oda_sayisi', FieldKey::fromString('oda-sayisi')->value());
        $this->assertSame('brut_m2', FieldKey::fromString('brut-metrekare')->value());
        $this->assertSame('net_m2', FieldKey::fromString('net-metrekare')->value());
        $this->assertSame('bina_yasi', FieldKey::fromString('bina-yasi')->value());
        $this->assertSame('krediye_uygunluk', FieldKey::fromString('kredi-uygunlugu')->value());
        $this->assertSame('tapu_durumu', FieldKey::fromString('tapu-durumu')->value());
        $this->assertSame('denize_mesafe_m', FieldKey::fromString('denize-mesafe')->value());
        $this->assertSame('aidat_tutari', FieldKey::fromString('aidat')->value());
    }

    public function test_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FieldKey::fromString('Invalid Key! @#$');
    }

    public function test_equality_comparison(): void
    {
        $k1 = FieldKey::fromString('oda_sayisi');
        $k2 = FieldKey::fromString('oda-sayisi');
        $k3 = FieldKey::fromString('banyo_sayisi');

        $this->assertTrue($k1->equals($k2));
        $this->assertFalse($k1->equals($k3));
    }
}
