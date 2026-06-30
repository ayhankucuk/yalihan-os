<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Ilan\IlanPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OwnerPhotoController
 *
 * Owner portal photo management.
 * Portföy sahibinin kendi ilanlarına fotoğraf eklemesini ve silmesini sağlar.
 *
 * SAB v6.1.2 — Owner Portal Sprint (Task #15)
 * SAB v3.4.2 — Sprint 3.4.2: Photo upload added
 */
class OwnerPhotoController extends Controller
{
    public function __construct(
        private IlanPhotoService $photoService
    ) {}

    /**
     * Fotoğraf yükler.
     *
     * Ownership kontrolü: ilanın user_id'si auth kullanıcısına ait olmalı.
     *
     * @param Request $request
     * @param Ilan $ilan
     * @return JsonResponse
     */
    public function upload(Request $request, Ilan $ilan): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföye fotoğraf ekleme yetkiniz yok.',
            ], 403);
        }

        try {
            $result = $this->photoService->uploadPhotos(
                $ilan,
                (array) $request->file('photos')
            );

            $httpCode = $result['success'] ? 200 : 422;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            Log::error('Owner Photo Upload Error:', [
                'ilan_id' => $ilan->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf yükleme sırasında hata oluştu.',
            ], 500);
        }
    }

    /**
     * Fotoğraf siler.
     *
     * Ownership kontrolü: hem ilanın hem fotoğrafın auth kullanıcısına ait olması gerekir.
     *
     * @param Ilan $ilan
     * @param IlanFotografi $photo
     * @return JsonResponse
     */
    public function delete(Ilan $ilan, IlanFotografi $photo): JsonResponse
    {
        if ($ilan->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu portföyü yönetme yetkiniz yok.',
            ], 403);
        }

        if ($photo->ilan_id !== $ilan->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf bu portföye ait değil.',
            ], 403);
        }

        try {
            $result = $this->photoService->deletePhoto($ilan, $photo);

            $httpCode = $result['success'] ? 200 : 400;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            Log::error('Owner Photo Delete Error:', [
                'ilan_id' => $ilan->id,
                'photo_id' => $photo->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf silme sırasında hata oluştu.',
            ], 500);
        }
    }
}
