<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Listing;

use App\Services\Listing\ListingStateMachine;
use DomainException;
use Tests\TestCase;

/**
 * ListingStateMachineLocationTest
 *
 * Sprint 6.2: Tests coordinate validation during publishing quality gate check.
 */
class ListingStateMachineLocationTest extends TestCase
{
    private ListingStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new ListingStateMachine();
    }

    /**
     * Test publishing is blocked if coordinates are missing.
     */
    public function test_publishing_fails_with_missing_coordinates(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Yayınlama başarısız: Geçerli coğrafi koordinatlar (enlem/boylam) eksik veya sıfır.');

        $this->stateMachine->yayinIcinKontrolEt(50, 100, null, null);
    }

    /**
     * Test publishing is blocked if coordinates are zero.
     */
    public function test_publishing_fails_with_zero_coordinates(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Yayınlama başarısız: Geçerli coğrafi koordinatlar (enlem/boylam) eksik veya sıfır.');

        $this->stateMachine->yayinIcinKontrolEt(50, 100, 0.0, 0.0);
    }

    /**
     * Test publishing is blocked if coordinates are outside Muğla province bounds.
     */
    public function test_publishing_fails_with_out_of_bounds_coordinates(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Yayınlama başarısız: Koordinat değerleri Muğla il sınırları dışındadır');

        // Coordinates of Istanbul (41.0082, 28.9784) are outside Mugla bounds
        $this->stateMachine->yayinIcinKontrolEt(50, 100, 41.0082, 28.9784);
    }

    /**
     * Test publishing succeeds with valid Muğla coordinates.
     */
    public function test_publishing_succeeds_with_valid_coordinates(): void
    {
        // Yalıkavak Marina coordinates (37.1042, 27.2900) are inside Mugla bounds
        $this->stateMachine->yayinIcinKontrolEt(50, 100, 37.1042, 27.2900);
        $this->assertTrue(true); // Reached without throwing exception
    }
}
