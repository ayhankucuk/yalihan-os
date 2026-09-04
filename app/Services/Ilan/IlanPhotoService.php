<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Traits\GuardsAgentWrites;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IlanPhotoService
{
    use GuardsAgentWrites;

    public function uploadPhotos(Ilan $ilan, array $photos): array
    {
        $this->blockAgentWrite('uploadPhotos');
        $validator = Validator::make(['photos' => $photos], [
            'photos' => 'required|array|max:10',
            'photos.*' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:5120',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        $uploadedPhotos = [];

        // BACKLOG-8: Atomic display_order with lockForUpdate + retry
        // lockForUpdate prevents concurrent transactions from reading stale max(display_order).
        // Unique constraint on (ilan_id, display_order) catches any remaining race.
        // Retry loop handles unique constraint violations safely.
        $maxAttempts = 5;
        $attempt = 0;

        do {
            if ($attempt > 0) {
                usleep(50_000); // 50ms backoff
            }
            $attempt++;

            try {
                DB::beginTransaction();

                // Lock the parent ilan row to prevent concurrent reads of max(display_order)
                Ilan::where('id', $ilan->id)->lockForUpdate()->exists();

                $maxOrder = (int) IlanFotografi::where('ilan_id', $ilan->id)->max('display_order') ?? 0;

                $currentIndex = 0;
                foreach ($photos as $photo) {
                    /** @var UploadedFile $photo */
                    $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $path = $photo->storeAs('ilan-fotograflari/' . $ilan->id, $fileName, 'public');

                    $fotografModel = new IlanFotografi();
                    $fotografModel->ilan_id = $ilan->id;
                    $fotografModel->dosya_yolu = $path;
                    $fotografModel->dosya_adi = $photo->getClientOriginalName();
                    $fotografModel->dosya_boyutu = $photo->getSize();
                    $fotografModel->mime_type = $photo->getMimeType();
                    $fotografModel->display_order = $maxOrder + $currentIndex + 1;
                    $fotografModel->save();
                    $currentIndex++;

                    $uploadedPhotos[] = [
                        'id' => $fotografModel->id,
                        'url' => Storage::disk('public')->url($path),
                        'name' => $fotografModel->dosya_adi,
                        'size' => $fotografModel->dosya_boyutu,
                    ];
                }

                DB::commit();
                $uploaded = true;
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                $uploaded = false;

                // MySQL duplicate key error code
                if ($e->getCode() === '23000' && $attempt < $maxAttempts) {
                    continue; // Retry with fresh lock
                }
                throw $e;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } while (!$uploaded && $attempt < $maxAttempts);

        if (!$uploaded) {
            return [
                'success' => false,
                'errors' => 'Fotoğraf yüklemesi eşzamanlılık nedeniyle başarısız oldu. Lütfen tekrar deneyin.',
            ];
        }

        return [
            'success' => true,
            'message' => count($uploadedPhotos) . ' fotoğraf başarıyla yüklendi.',
            'photos' => $uploadedPhotos,
        ];
    }

    public function deletePhoto(Ilan $ilan, IlanFotografi $photo): array
    {
        $this->blockAgentWrite('deletePhoto');

        if ($photo->ilan_id !== $ilan->id) {
            return [
                'success' => false,
                'message' => 'Fotoğraf ilgili ilana ait değil.',
            ];
        }

        if (Storage::disk('public')->exists($photo->dosya_yolu)) {
            Storage::disk('public')->delete($photo->dosya_yolu);
        }

        $photo->delete();

        return [
            'success' => true,
            'message' => 'Fotoğraf silindi.',
        ];
    }

    // Context7: sequence → display_order (forbidden kelime kullanimi engellendi)
    public function updatePhotoSequence(Ilan $ilan, array $photoSequences): array
    {
        $this->blockAgentWrite('updatePhotoSequence');
        $validator = Validator::make(['photo_sequences' => $photoSequences], [
            'photo_sequences' => 'required|array',
            'photo_sequences.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        DB::beginTransaction();
        try {
            foreach ($photoSequences as $photoId => $sequence) {
                IlanFotografi::where('id', $photoId)
                    ->where('ilan_id', $ilan->id)
                    ->update(['display_order' => (int) $sequence]);
            }
            DB::commit();

            return [
                'success' => true,
                'message' => 'Fotoğraf sıralaması güncellendi.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \DomainException(
                "Sıralama güncelleme sırasında hata: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
