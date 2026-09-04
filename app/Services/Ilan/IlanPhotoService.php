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

        // BACKLOG-8: Atomic display_order with lockForUpdate + retry + orphan cleanup
        // Each retry attempt starts fresh: reset state, clean orphaned files on failure.
        $maxAttempts = 5;
        $attempt = 0;

        do {
            if ($attempt > 0) {
                usleep(50_000); // 50ms backoff before retry
            }
            $attempt++;

            // Fresh state per attempt — prevents $uploadedPhotos accumulation across retries
            $attemptPhotos = [];
            $attemptSavedPaths = [];
            $currentIndex = 0;

            try {
                DB::beginTransaction();

                // Lock the parent ilan row so concurrent transactions block here
                Ilan::where('id', $ilan->id)->lockForUpdate()->exists();

                $maxOrder = (int) IlanFotografi::where('ilan_id', $ilan->id)->max('display_order') ?? 0;

                foreach ($photos as $photo) {
                    /** @var UploadedFile $photo */
                    $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $path = $photo->storeAs('ilan-fotograflari/' . $ilan->id, $fileName, 'public');
                    $attemptSavedPaths[] = $path; // Track so we can clean up on failure

                    $fotografModel = new IlanFotografi();
                    $fotografModel->ilan_id = $ilan->id;
                    $fotografModel->dosya_yolu = $path;
                    $fotografModel->dosya_adi = $photo->getClientOriginalName();
                    $fotografModel->dosya_boyutu = $photo->getSize();
                    $fotografModel->mime_type = $photo->getMimeType();
                    $fotografModel->display_order = $maxOrder + $currentIndex + 1;
                    $fotografModel->save();
                    $currentIndex++;

                    $attemptPhotos[] = [
                        'id' => $fotografModel->id,
                        'url' => Storage::disk('public')->url($path),
                        'name' => $fotografModel->dosya_adi,
                        'size' => $fotografModel->dosya_boyutu,
                    ];
                }

                DB::commit();
                // Success — return attempt photos (not accumulated from previous failed attempts)
                return [
                    'success' => true,
                    'message' => count($attemptPhotos) . ' fotoğraf başarıyla yüklendi.',
                    'photos' => $attemptPhotos,
                ];
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();

                // MySQL duplicate key: retry up to $maxAttempts
                if ($e->getCode() === '23000' && $attempt < $maxAttempts) {
                    // Clean up storage files from the failed attempt before retrying
                    foreach ($attemptSavedPaths as $savedPath) {
                        Storage::disk('public')->delete($savedPath);
                    }
                    continue;
                }

                // Non-retryable DB error — clean up files before re-throwing
                foreach ($attemptSavedPaths as $savedPath) {
                    Storage::disk('public')->delete($savedPath);
                }
                throw $e;
            } catch (\Exception $e) {
                DB::rollBack();

                // Clean up any files saved in this failed attempt
                foreach ($attemptSavedPaths as $savedPath) {
                    Storage::disk('public')->delete($savedPath);
                }
                throw $e;
            }
        } while ($attempt < $maxAttempts);

        // All retries exhausted
        return [
            'success' => false,
            'errors' => 'Fotoğraf yüklemesi eşzamanlılık nedeniyle başarısız oldu. Lütfen tekrar deneyin.',
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
