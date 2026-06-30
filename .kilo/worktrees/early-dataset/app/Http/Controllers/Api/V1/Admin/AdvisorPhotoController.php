<?php

namespace App\Http\Controllers\Api\V1\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AdvisorPhoto;
use App\Models\Kisi;
use App\Services\Photo\PhotoAnalysisService;
use App\Services\Photo\PhotoOrderingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Advisor Photo Controller - Phase 5.3
 * POST /api/v1/admin/advisors/{advisorId}/photos/upload
 * GET /api/v1/admin/advisors/{advisorId}/photos
 * DELETE /api/v1/admin/advisors/{advisorId}/photos/{photoId}
 */
class AdvisorPhotoController extends Controller
{
    public function __construct(
        private PhotoAnalysisService $analysisService,
        private PhotoOrderingService $orderingService,
    ) {}

    /**
     * POST /api/v1/admin/advisors/{advisorId}/photos/upload
     * Upload and analyze advisor photo
     */
    public function upload(Request $request, int $advisorId): JsonResponse
    {
        $advisor = Kisi::findOrFail($advisorId);

        $request->validate([
            'photo' => 'required|image|max:10240|mimes:jpeg,png,gif,webp',
        ]);

        $file = $request->file('photo');

        try {
            // 1. Store photo
            $path = 'advisor_photos/' . $advisor->id . '/' . uniqid() . '.' . $file->extension();
            $file->storeAs('/', $path, 'public');

            // 2. Get image info
            $storagePath = Storage::disk('public')->path($path);
            $imageInfo = getimagesize($storagePath);

            // 3. Analyze quality
            $analysis = $this->analysisService->analyzePhoto($storagePath);

            // 4. Create database record
            $photo = AdvisorPhoto::create([
                'kisi_id' => $advisor->id,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'width' => $imageInfo[0] ?? 0,
                'height' => $imageInfo[1] ?? 0,
                'file_size' => $file->getSize(),
                'quality_score' => $analysis['quality_score'],
                'quality_metrics' => $analysis['quality_metrics'],
                'analysis_details' => $analysis['analysis_details'],
                'improvement_suggestions' => $analysis['improvement_suggestions'],
                'visual_keywords' => $analysis['visual_keywords'],
                'analyzed_at' => now(),
            ]);

            // 5. Recalculate optimal siralamasi
            $this->orderingService->applyOptimalOrder($advisor); // context7-ignore

            // 6. Refresh photo to get updated display_order and featured durumu
            $photo->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'photo' => [
                        'id' => $photo->id,
                        'quality_score' => $photo->quality_score,
                        'display_order' => $photo->display_order,
                        'featured' => $photo->featured,
                    ],
                    'analysis' => [
                        'quality_score' => $analysis['quality_score'],
                        'suggestions' => $analysis['improvement_suggestions'],
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf yüklenirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/advisors/{advisorId}/photos
     * List advisor's photos (ordered by display_order)
     */
    public function index(int $advisorId): JsonResponse
    {
        $advisor = Kisi::findOrFail($advisorId);

        $photos = $advisor->photos()
            ->ordered() // context7-ignore
            ->get()
            ->map(fn($photo) => [
                'id' => $photo->id,
                'url' => Storage::disk('public')->url($photo->path),
                'quality_score' => $photo->quality_score,
                'display_order' => $photo->display_order,
                'featured' => $photo->featured,
                'quality_metrics' => $photo->quality_metrics,
                'created_at' => $photo->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * DELETE /api/v1/admin/advisors/{advisorId}/photos/{photoId}
     * Delete advisor photo
     */
    public function destroy(int $advisorId, int $photoId): JsonResponse
    {
        $advisor = Kisi::findOrFail($advisorId);
        $photo = AdvisorPhoto::where('id', $photoId)
            ->where('kisi_id', $advisor->id)
            ->firstOrFail();

        try {
            // Delete file from storage
            Storage::disk('public')->delete($photo->path);

            // Delete database record
            $photo->delete();

            // Recalculate siralamasi
            $this->orderingService->applyOptimalOrder($advisor); // context7-ignore

            return response()->json([
                'success' => true,
                'message' => 'Fotoğraf silindi ve sıralama güncellendi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fotoğraf silinirken hata oluştu',
            ], 500);
        }
    }
}
