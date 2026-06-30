<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Ilan\IlanPhotoService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ilan Photo Controller
 *
 * Context7 Standardı: C7-ILAN-PHOTO-CONTROLLER-2026-01-21
 * Specialized controller for managing listing photos.
 */
class IlanPhotoController extends AdminController
{
    protected IlanPhotoService $photoService;

    public function __construct(IlanPhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    /**
     * Upload photos to listing
     * Context7: İlan fotoğrafı yükle
     *
     * @param Request $request
     * @param Ilan $ilan
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPhotos(Request $request, Ilan $ilan)
    {
        try {
            $result = $this->photoService->uploadPhotos($ilan, (array) $request->file('photos'));
            $httpCode = $result['success'] ? 200 : 422;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            Log::error('Photo Upload Error:', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf yükleme sırasında hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a photo from listing
     * Context7: İlan fotoğrafı sil
     *
     * @param Ilan $ilan
     * @param IlanFotografi $photo
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePhoto(Ilan $ilan, IlanFotografi $photo)
    {
        try {
            $result = $this->photoService->deletePhoto($ilan, $photo);
            $httpCode = $result['success'] ? 200 : 400;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            Log::error('Photo Delete Error:', ['ilan_id' => $ilan->id, 'photo_id' => $photo->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf silme sırasında hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update photo sequence
     * Context7: Fotoğraf sıralamasını güncelle
     *
     * @param Request $request
     * @param Ilan $ilan
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhotoSequence(Request $request, Ilan $ilan)
    {
        try {
            $result = $this->photoService->updatePhotoSequence($ilan, (array) $request->photo_display_orders);
            $httpCode = $result['success'] ? 200 : 422;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            Log::error('Photo Sequence Update Error:', ['ilan_id' => $ilan->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncelleme sırasında hata: ' . $e->getMessage(),
            ], 500);
        }
    }
}
