<?php

namespace App\Services\Photo;

use App\Models\Photo;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * PhotoService — SAB Thin Controller Compliance
 *
 * Tüm Photo model mutation işlemleri bu Service katmanından yapılır.
 * Controller'da doğrudan ->update(), ->save(), ->delete(), ham SQL kullanımı yasaktır.
 */
class PhotoService
{
    use GuardsAgentWrites;
    /**
     * Fotoğraf bilgilerini güncelle.
     *
     * @return array{photo: Photo, data: array}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updatePhoto(int $photoId, array $validated): array
    {
        $this->blockAgentWrite('updatePhoto');

        $photo = Photo::findOrFail($photoId);

        $photo->update([
            'category' => $validated['category'],
            'kapak_fotografi' => $validated['one_cikan'] ?? false,
            'display_order' => $validated['display_order'] ?? $photo->display_order,
        ]);

        return [
            'photo' => $photo,
            'data' => [
                'id' => $photo->id,
                'category' => $photo->category,
                'kapak_fotografi' => (bool) $photo->kapak_fotografi,
                'display_order' => $photo->display_order,
                'url' => $photo->url,
                'thumbnail_url' => $photo->thumbnail_url,
                'size' => $photo->dosya_boyutu,
                'formatted_size' => $photo->formatted_size,
                'views' => $photo->views,
                'updated_at' => $photo->updated_at,
            ],
        ];
    }

    /**
     * Fotoğraf meta bilgilerini güncelle (one_cikan, display_order).
     * one_cikan true olursa aynı ilanın diğer fotoğrafları false'a çekilir.
     */
    public function updatePhotoMeta(Photo $photo, array $validated): Photo
    {
        $this->blockAgentWrite('updatePhotoMeta');

        if (! empty($validated['one_cikan'])) {
            Photo::where('ilan_id', $photo->ilan_id)
                ->update(['one_cikan' => false]);
        }

        $photo->update([
            'one_cikan' => $validated['one_cikan'] ?? $photo->one_cikan,
            'display_order' => $validated['display_order'] ?? $photo->display_order,
        ]);

        return $photo->fresh();
    }

    /**
     * Fotoğrafı sil (dosya + model).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deletePhoto(int $photoId): void
    {
        $this->blockAgentWrite('deletePhoto');

        $photo = Photo::findOrFail($photoId);

        // Dosyaları sil
        if ($photo->path) {
            Storage::disk('public')->delete($photo->path);
        }
        if ($photo->thumbnail) {
            Storage::disk('public')->delete($photo->thumbnail);
        }

        $photo->delete();
    }

    /**
     * Toplu fotoğraf silme.
     */
    public function bulkDelete(array $photoIds): int
    {
        $this->blockAgentWrite('bulkDelete');

        $photos = Photo::whereIn('id', $photoIds)->get();

        $pathsToDelete = [];
        foreach ($photos as $photo) {
            if ($photo->path) {
                $pathsToDelete[] = $photo->path;
            }
            if ($photo->thumbnail) {
                $pathsToDelete[] = $photo->thumbnail;
            }
        }

        if (! empty($pathsToDelete)) {
            Storage::disk('public')->delete($pathsToDelete);
        }

        return Photo::whereIn('id', $photoIds)->delete();
    }

    /**
     * Toplu kategori taşıma.
     */
    public function bulkMove(array $photoIds, ?string $targetCategory): int
    {
        $this->blockAgentWrite('bulkMove');

        return Photo::whereIn('id', $photoIds)
            ->update(['category' => $targetCategory]);
    }

    /**
     * Toplu öne çıkarma.
     */
    public function bulkFeature(array $photoIds): int
    {
        $this->blockAgentWrite('bulkFeature');

        $photos = Photo::whereIn('id', $photoIds)->get();

        foreach ($photos as $photo) {
            $photo->setAsFeatured();
        }

        return $photos->count();
    }

    /**
     * Toplu öne çıkarmayı kaldırma.
     */
    public function bulkUnfeature(array $photoIds): int
    {
        $this->blockAgentWrite('bulkUnfeature');

        return Photo::whereIn('id', $photoIds)
            ->update(['kapak_fotografi' => false]);
    }

    /**
     * Fotoğraf istatistikleri.
     * Context7: Ham SQL kullanımı Service katmanında — Controller'da yasak.
     */
    public function getPhotoStats(): array
    {
        $totalSize = Photo::sum('dosya_boyutu');
        $formattedTotalSize = number_format($totalSize / (1024 * 1024), 2) . ' MB';

        return [
            'total' => Photo::count(),
            'this_month' => Photo::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_size' => $formattedTotalSize,
            'categories' => Photo::query()
                ->select('category')
                ->get()
                ->groupBy('category')
                ->map->count()
                ->toArray(),
        ];
    }

    /**
     * Fotoğraf yükleme ve kaydet.
     *
     * @param  array  $files  UploadedFile dizisi
     * @return array Yüklenen fotoğraf bilgileri
     */
    public function storePhotos(array $files, array $meta): array
    {
        $uploadedPhotos = [];

        foreach ($files as $index => $photo) {
            $filename = time() . '_' . $index . '_' . Str::slug($photo->getClientOriginalName(), '_');

            $path = $photo->storeAs('photos/ilan', $filename, 'public');

            $imageDimensions = @getimagesize($photo->getPathname());
            $width = $imageDimensions ? $imageDimensions[0] : null;
            $height = $imageDimensions ? $imageDimensions[1] : null;

            $thumbnailPath = $this->generateThumbnail($path);
            $optimizedSize = $this->optimizeImage($path);

            $photoModel = Photo::create([
                'ilan_id' => $meta['ilan_id'] ?? null,
                'dosya_yolu' => $path,
                'dosya_adi' => $filename,
                'dosya_boyutu' => $optimizedSize ?? $photo->getSize(),
                'mime_type' => $photo->getMimeType(),
                'kapak_fotografi' => false,
                'display_order' => $index,
            ]);

            $uploadedPhotos[] = [
                'id' => $photoModel->id,
                'filename' => $filename,
                'original_name' => $photo->getClientOriginalName(),
                'path' => $photoModel->dosya_yolu,
                'thumbnail_path' => $photoModel->thumbnail,
                'url' => $photoModel->url,
                'thumbnail_url' => $photoModel->thumbnail_url,
                'size' => $photoModel->dosya_boyutu,
                'formatted_size' => $photoModel->formatted_size,
                'category' => $photoModel->category,
                'title' => $meta['title'] ?? $photo->getClientOriginalName(),
                'description' => $meta['description'] ?? null,
                'alt_text' => $meta['alt_text'] ?? $meta['title'] ?? null,
                'tags' => $meta['tags'] ?? null,
                'uploaded_at' => $photoModel->created_at,
            ];
        }

        return $uploadedPhotos;
    }

    /**
     * Fotoğraf görüntüleme sayısını artır.
     */
    public function incrementViews(int $photoId): int
    {
        try {
            $photo = Photo::findOrFail($photoId);
            $photo->incrementViews();

            return $photo->views;
        } catch (\Exception $e) {
            Log::error('Increment views error: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Thumbnail oluştur.
     */
    public function generateThumbnail(string $originalPath): ?string
    {
        try {
            $thumbnailPath = 'thumbnails/' . basename($originalPath);
            $manager = new ImageManager(new Driver());
            $image = $manager->read(storage_path('app/public/' . $originalPath));

            $image->cover(300, 300);
            $encoded = $image->toJpeg(80);
            Storage::disk('public')->put($thumbnailPath, (string) $encoded);

            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Resim optimizasyonu.
     */
    public function optimizeImage(string $path): ?int
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read(storage_path('app/public/' . $path));

            if ($image->width() > 1920) {
                $image->scale(width: 1920);
            }

            $encoded = $image->toJpeg(85);
            Storage::disk('public')->put($path, (string) $encoded);

            return Storage::disk('public')->size($path);
        } catch (\Exception $e) {
            Log::error('Image optimization error: ' . $e->getMessage());

            return null;
        }
    }
}
