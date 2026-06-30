<?php

declare(strict_types=1);

namespace App\Adapters\Fake\PropertyPipeline;

use App\Contracts\PropertyPipeline\DriveAdapterInterface;

/**
 * FakeDriveAdapter — P01 Sprint 4.1
 *
 * No real HTTP calls. Returns deterministic fake folder data.
 */
class FakeDriveAdapter implements DriveAdapterInterface
{
    public function createPropertyFolder(int $ilanId, string $baslik): array
    {
        $folderId = 'fake-drive-' . $ilanId . '-' . time();

        return [
            'folder_id' => $folderId,
            'folder_url' => "https://drive.fake/ilan/{$folderId}",
        ];
    }
}
