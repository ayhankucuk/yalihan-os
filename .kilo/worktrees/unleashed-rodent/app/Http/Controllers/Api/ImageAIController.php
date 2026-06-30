<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\ImageBasedAIDescriptionService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageAIController extends Controller
{
    use ValidatesApiRequests;

    protected $imageAIService;

    public function __construct(ImageBasedAIDescriptionService $imageAIService)
    {
        $this->imageAIService = $imageAIService;
    }

    /**
     * Resim analizi ve açıklama üretimi
     */
    public function analyzeImage(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'image' => 'required|file|image|max:10240', // 10MB max
            'options' => 'sometimes|array',
            'options.detail' => 'sometimes|string|in:low,high,auto',
            'options.include_objects' => 'sometimes|boolean',
            'options.include_colors' => 'sometimes|boolean',
            'options.include_architecture' => 'sometimes|boolean',
            'options.include_style' => 'sometimes|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $image = $request->file('image');
            $options = $request->input('options', []);

            // Resmi geçici olarak kaydet
            $imagePath = $image->store('temp/ai-analysis', 'public');

            // AI analizi yap
            $analysis = $this->imageAIService->analyzeImage($imagePath, $options);

            // Geçici dosyayı sil
            Storage::disk('public')->delete($imagePath);

            if (! $analysis['success']) {
                // ✅ REFACTORED: Using ResponseService
                return ResponseService::error('AI analizi başarısız', 500, [], null, $analysis['error'] ?? 'Unknown error');
            }

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $analysis['analysis'],
                'raw_analysis' => $analysis['raw_analysis'] ?? null,
            ], 'Resim analizi başarıyla tamamlandı');
        } catch (\Exception $e) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Resim analizi sırasında hata oluştu', $e);
        }
    }

    /**
     * Otomatik etiketleme
     */
    public function generateTags(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'image' => 'required|file|image|max:10240',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $image = $request->file('image');
            $imagePath = $image->store('temp/ai-tags', 'public');

            $tags = $this->imageAIService->generateTags($imagePath);

            Storage::disk('public')->delete($imagePath);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'tags' => $tags,
                'tag_count' => count($tags),
            ], 'Etiketleme başarıyla tamamlandı');
        } catch (\Exception $e) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Etiketleme sırasında hata oluştu', $e);
        }
    }

    /**
     * Resim kalite analizi
     */
    public function analyzeQuality(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'image' => 'required|file|image|max:10240',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $image = $request->file('image');
            $imagePath = $image->store('temp/ai-quality', 'public');

            $quality = $this->imageAIService->analyzeImageQuality($imagePath);

            Storage::disk('public')->delete($imagePath);

            if (! $quality['success']) {
                // ✅ REFACTORED: Using ResponseService
                return ResponseService::error('Kalite analizi başarısız', 500, [], null, $quality['error'] ?? 'Unknown error');
            }

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($quality, 'Kalite analizi başarıyla tamamlandı');
        } catch (\Exception $e) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Kalite analizi sırasında hata oluştu', $e);
        }
    }

    /**
     * Toplu resim analizi
     */
    public function analyzeBatch(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|file|image|max:10240',
            'options' => 'sometimes|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $images = $request->file('images');
            $options = $request->input('options', []);
            $results = [];

            foreach ($images as $index => $image) {
                $imagePath = $image->store("temp/ai-batch-{$index}", 'public');

                $analysis = $this->imageAIService->analyzeImage($imagePath, $options);
                $results[] = [
                    'index' => $index,
                    'filename' => $image->getClientOriginalName(),
                    'analysis' => $analysis,
                ];

                Storage::disk('public')->delete($imagePath);
            }

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'results' => $results,
                'total_images' => count($images),
            ], 'Toplu resim analizi başarıyla tamamlandı');
        } catch (\Exception $e) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Toplu analiz sırasında hata oluştu', $e);
        }
    }
}
