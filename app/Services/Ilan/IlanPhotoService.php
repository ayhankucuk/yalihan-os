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
            $fotografModel->display_order = IlanFotografi::where('ilan_id', $ilan->id)->count() + 1;
            $fotografModel->save();

            $uploadedPhotos[] = [
                'id' => $fotografModel->id,
                'url' => Storage::disk('public')->url($path),
                'name' => $fotografModel->dosya_adi,
                'size' => $fotografModel->dosya_boyutu,
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
                    ->update(['sira' => (int) $sequence]);
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
