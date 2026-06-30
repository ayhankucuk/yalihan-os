<?php

declare(strict_types=1);

namespace App\Contracts\PropertyPipeline;

/**
 * DriveAdapter Interface — P01 Property Pipeline (Sprint 4.1)
 *
 * Port: Google Drive / Fake local implementation.
 * Drive port contract.
 */
interface DriveAdapterInterface
{
    /**
     * Create a folder for a property listing.
     *
     * @param int $ilanId
     * @param string $baslik
     * @return array{folder_id: string, folder_url: string}
     */
    public function createPropertyFolder(int $ilanId, string $baslik): array;
}
