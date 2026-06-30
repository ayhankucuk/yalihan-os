<?php

declare(strict_types=1);

namespace App\Adapters\Fake\PropertyPipeline;

use App\Contracts\PropertyPipeline\DescriptionGeneratorInterface;

/**
 * FakeDescriptionGenerator — P01 Sprint 4.1
 *
 * No AI calls. Returns deterministic fake description.
 */
class FakeDescriptionGenerator implements DescriptionGeneratorInterface
{
    public function generate(string $baslik, array $ozellikler = []): string
    {
        $ özelliklerList = implode(', ', $ozellikler ?: ['bahçe', 'havuz', 'deniz manzarası']);

        return "🏠 {$baslik}\n\n" .
               "Bu mülk, {$özelliklerList} ile donatılmıştır.\n\n" .
               "Detaylı bilgi için ilan sahibiyle iletişime geçin.\n\n" .
               "[FAKE — AI açıklama üretimi simüle edildi]";
    }
}
