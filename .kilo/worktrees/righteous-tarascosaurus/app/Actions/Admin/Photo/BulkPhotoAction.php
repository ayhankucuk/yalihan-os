<?php

namespace App\Actions\Admin\Photo;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;

class BulkPhotoAction
{
    public function handle(string $action, array $photoIds, array $extraData = []): int
    {
        $processedCount = 0;

        switch ($action) {
            case 'delete':
                $photos = Photo::whereIn('id', $photoIds)->get();
                $pathsToDelete = [];
                foreach ($photos as $photo) {
                    if ($photo->dosya_yolu) $pathsToDelete[] = $photo->dosya_yolu;
                    if ($photo->thumbnail) $pathsToDelete[] = $photo->thumbnail;
                }
                if (!empty($pathsToDelete)) {
                    Storage::disk('public')->delete($pathsToDelete);
                }
                $processedCount = Photo::whereIn('id', $photoIds)->delete();
                break;

            case 'move':
                $processedCount = Photo::whereIn('id', $photoIds)
                    ->update(['category' => $extraData['target_category'] ?? null]);
                break;

            case 'feature':
                $photos = Photo::whereIn('id', $photoIds)->get();
                foreach ($photos as $photo) {
                    $photo->setAsFeatured();
                }
                $processedCount = count($photos);
                break;

            case 'unfeature':
                $processedCount = Photo::whereIn('id', $photoIds)
                    ->update(['kapak_fotografi' => false]);
                break;
        }

        return $processedCount;
    }
}
