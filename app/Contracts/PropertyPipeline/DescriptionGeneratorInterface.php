<?php

declare(strict_types=1);

namespace App\Contracts\PropertyPipeline;

/**
 * DescriptionGenerator Interface — P01 Property Pipeline (Sprint 4.1)
 *
 * Port: AI / Fake implementation.
 * Description generation port.
 */
interface DescriptionGeneratorInterface
{
    /**
     * Generate property description.
     *
     * @param string $baslik
     * @param array $ozellikler
     * @return string Generated description
     */
    public function generate(string $baslik, array $ozellikler = []): string;
}
