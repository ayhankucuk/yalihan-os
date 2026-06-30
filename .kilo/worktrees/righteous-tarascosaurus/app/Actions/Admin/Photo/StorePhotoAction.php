<?php

namespace App\Actions\Admin\Photo;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class StorePhotoAction
{
    public function handle(array $photos, array $metadata): array
    {
        $uploadedPhotos = [];

        foreach ($photos as $index => $photo) {
            $filename = time().'_'.$index.'_'.Str::slug($photo->getClientOriginalName(), '_');
            $path = $photo->storeAs('photos/ilan', $filename, 'public');

            $thumbnailPath = $this->generateThumbnail($path);
            $optimizedSize = $this->optimizeImage($path);

            $photoModel = Photo::create([
                'ilan_id' => $metadata['ilan_id'] ?? null,
                'category' => $metadata['category'] ?? 'other',
                'dosya_yolu' => $path,
                'dosya_adi' => $filename,
                'dosya_boyutu' => $optimizedSize ?? $photo->getSize(),
                'mime_type' => $photo->getMimeType(),
                'kapak_fotografi' => false,
                'display_order' => $index,
                'title' => $metadata['title'] ?? $photo->getClientOriginalName(),
                'description' => $metadata['description'] ?? null,
                'alt_text' => $metadata['alt_text'] ?? ($metadata['title'] ?? null),
                'tags' => $metadata['tags'] ?? null,
            ]);

            $uploadedPhotos[] = $photoModel;
        }

        return $uploadedPhotos;
    }

    private function generateThumbnail(string $originalPath): ?string
    {
        try {
            $thumbnailPath = 'thumbnails/'.basename($originalPath);
            $manager = new ImageManager(new Driver());
            $image = $manager->read(storage_path('app/public/'.$originalPath));
            $image->cover(300, 300);
            $encoded = $image->toJpeg(80);
            Storage::disk('public')->put($thumbnailPath, (string) $encoded);
            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation error: '.$e->getMessage());
            return null;
        }
    }

    private function optimizeImage(string $path): ?int
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read(storage_path('app/public/'.$path));
            if ($image->width() > 1920) {
                $image->scale(width: 1920);
            }
            $encoded = $image->toJpeg(85);
            Storage::disk('public')->put($path, (string) $encoded);
            return Storage::disk('public')->size($path);
        } catch (\Exception $e) {
            Log::error('Image optimization error: '.$e->getMessage());
            return null;
        }
    }
}
