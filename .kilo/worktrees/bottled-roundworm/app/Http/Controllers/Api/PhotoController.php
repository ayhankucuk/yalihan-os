<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Photo;
use App\Services\Photo\PhotoService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photo Management API Controller
 * Pure API endpoints for photo upload/delete/sirala
 * Context7 compliant!
 */
class PhotoController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Upload photo (single or multiple)
     * POST /api/admin/photos/upload
     */
    public function upload(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240', // 10 MB
            'ilan_id' => 'required|exists:ilanlar,id',
            'display_order' => 'nullable|integer',
            'one_cikan' => 'nullable|boolean', // ✅ SAB: one_cikan → one_cikan
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $ilan = Ilan::findOrFail($request->ilan_id);
        $file = $request->file('photo');

        // Generate unique filename
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();

        // Upload paths
        $uploadPath = 'ilanlar/'.$ilan->id.'/photos';

        // Store original
        $path = $file->storeAs($uploadPath, $filename, 'public');

        // Create photo record
        $photo = Photo::create([
            'ilan_id' => $ilan->id,
            'dosya_yolu' => $path, // ✅ SAB: Tablodaki gerçek kolon adı
            'dosya_adi' => $filename,
            'dosya_boyutu' => (string) $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'one_cikan' => $request->one_cikan ?? false, // ✅ SAB: one_cikan → one_cikan
            'display_order' => $request->display_order ?? Photo::where('ilan_id', $ilan->id)->count(), // Context7: display_order preferred
        ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'photo' => [
                'id' => $photo->id,
                'url' => Storage::url($photo->dosya_yolu),
                'thumbnail' => Storage::url($photo->dosya_yolu), // Thumbnail için aynı dosya_yolu kullanılıyor
                'filename' => $filename,
                'one_cikan' => $photo->one_cikan, // ✅ SAB: one_cikan → one_cikan
                'display_order' => $photo->display_order, // Context7: API response'da display_order kullan
            ],
        ], 'Fotoğraf başarıyla yüklendi');
    }

    /**
     * Get photos for ilan
     * GET /api/admin/ilanlar/{id}/photos
     */
    public function index($ilanId)
    {
        $photos = Photo::where('ilan_id', $ilanId)
            ->orderBy('display_order') // ✅ SAB: Tablodaki gerçek kolon adı // context7-ignore
            ->get()
            ->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => Storage::url($photo->dosya_yolu),
                'thumbnail' => Storage::url($photo->dosya_yolu), // Thumbnail için aynı dosya_yolu kullanılıyor
                'one_cikan' => $photo->one_cikan, // ✅ SAB: one_cikan → one_cikan
                'display_order' => $photo->display_order, // Context7: API response'da display_order kullan
            ]);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['photos' => $photos], 'Fotoğraflar başarıyla getirildi');
    }

    /**
     * Update photo (featured, sira, etc.)
     * PATCH /api/admin/photos/{id}
     */
    public function update(Request $request, $id)
    {
        $photo = Photo::findOrFail($id);

        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'one_cikan' => 'nullable|boolean', // ✅ SAB: one_cikan → one_cikan
            'display_order' => 'nullable|integer',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        // ✅ SAB: Delegate mutation to PhotoService
        $photo = app(PhotoService::class)->updatePhotoMeta($photo, $validated);

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(['photo' => $photo], 'Fotoğraf başarıyla güncellendi');
    }

    /**
     * Delete photo
     * DELETE /api/admin/photos/{id}
     */
    public function destroy($id)
    {
        $photo = Photo::findOrFail($id);

        // Delete files from storage
        if (Storage::disk('public')->exists($photo->dosya_yolu)) {
            Storage::disk('public')->delete($photo->dosya_yolu);
        }

        $photo->delete();

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Fotoğraf silindi');
    }

    /**
     * Fotoğraf sıralamasını güncelle (bulk update)
     * POST /api/admin/ilanlar/{id}/photos/sirala
     */
    public function sirala(Request $request, $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:ilan_fotograflari,id',
            'photos.*.display_order' => 'required|integer',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        foreach ($request->photos as $photoData) {
            Photo::where('id', $photoData['id'])
                ->where('ilan_id', $ilanId)
                ->update(['display_order' => $photoData['display_order'] ?? 0]); // Context7: display_order preferred
        }

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Sıralama güncellendi');
    }
}
