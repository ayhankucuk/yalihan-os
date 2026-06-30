<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Services\Marketing\AssetEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Marketing Asset Template Controller
 *
 * Phase 8.0: Pazarlama ve Sosyal Medya Motoru
 * Context7 Standardı: C7-ASSET-ENGINE-2025-12-23
 *
 * Template editor ve preview yönetimi için controller.
 */
class MarketingAssetController extends AdminController
{
    /**
     * AssetEngine instance
     */
    private AssetEngine $assetEngine;
    private \App\Services\Marketing\MarketingTemplateService $templateService;

    public function __construct(AssetEngine $assetEngine, \App\Services\Marketing\MarketingTemplateService $templateService)
    {
        parent::__construct();
        $this->assetEngine = $assetEngine;
        $this->templateService = $templateService;
    }

    /**
     * Template listesi
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $formats = [
            'instagram_post' => ['name' => 'Instagram Post', 'icon' => 'instagram', 'dimensions' => '1080x1080'],
            'instagram_story' => ['name' => 'Instagram Story', 'icon' => 'instagram', 'dimensions' => '1080x1920'],
            'instagram_reel' => ['name' => 'Instagram Reel', 'icon' => 'instagram', 'dimensions' => '1080x1920'],
            'facebook_post' => ['name' => 'Facebook Post', 'icon' => 'facebook', 'dimensions' => '1200x630'],
        ];

        $templates = [];
        foreach ($formats as $format => $info) {
            $templates[$format] = [
                'format' => $format,
                'name' => $info['name'],
                'icon' => $info['icon'],
                'dimensions' => $info['dimensions'],
                'templates' => $this->assetEngine->getAvailableTemplates($format),
            ];
        }

        return view('admin.marketing.templates.index', compact('formats', 'templates'));
    }

    /**
     * Template editor
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $format = $request->input('format', 'instagram_post');
        $templateName = $request->input('template', 'default');

        // Load template
        $template = null;

        if ($templateName === 'new') {
            // Use default template structure for new templates
            $template = $this->getDefaultTemplateStructure($format);
        } else {
            $templatePath = "marketing/templates/{$format}/{$templateName}.json";
            if (Storage::exists($templatePath)) {
                $templateContent = Storage::get($templatePath);
                $template = json_decode($templateContent, true);
            } else {
                // Use default template structure if file doesn't exist
                $template = $this->getDefaultTemplateStructure($format);
            }
        }

        // Get sample ilan for preview
        $sampleIlan = Ilan::with(['kategori', 'il', 'ilce', 'mahalle'])
            ->where('yayin_durumu', 'published')
            ->first();

        return view('admin.marketing.templates.edit', compact('format', 'templateName', 'template', 'sampleIlan'));
    }

    /**
     * Template kaydet
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:instagram_post,instagram_story,instagram_reel,facebook_post',
            'template_name' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
            'template_data' => 'required|string',
        ]);

        // Validate JSON structure
        $templateData = json_decode($validated['template_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['template_data' => 'Geçersiz JSON formatı'])->withInput();
        }

        // Validate template structure
        $validationResult = $this->validateTemplateStructure($templateData, $validated['format']);
        if (!$validationResult['valid']) {
            return back()->withErrors(['template_data' => $validationResult['error']])->withInput();
        }

        // Save template via Service
        $this->templateService->saveTemplate($validated['format'], $validated['template_name'], $templateData);

        return redirect()
            ->route('admin.marketing.templates.index')
            ->with('success', "Template '{$validated['template_name']}' başarıyla kaydedildi.");
    }

    /**
     * Template güncelle
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:instagram_post,instagram_story,instagram_reel,facebook_post',
            'template_name' => 'required|string|max:50',
            'template_data' => 'required|string',
        ]);

        // Validate JSON structure
        $templateData = json_decode($validated['template_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['template_data' => 'Geçersiz JSON formatı'])->withInput();
        }

        // Validate template structure
        $validationResult = $this->validateTemplateStructure($templateData, $validated['format']);
        if (!$validationResult['valid']) {
            return back()->withErrors(['template_data' => $validationResult['error']])->withInput();
        }

        // Update template via Service
        $this->templateService->saveTemplate($validated['format'], $validated['template_name'], $templateData);

        return redirect()
            ->route('admin.marketing.templates.index')
            ->with('success', "Template '{$validated['template_name']}' başarıyla güncellendi.");
    }

    /**
     * Template sil
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:instagram_post,instagram_story,instagram_reel,facebook_post',
            'template_name' => 'required|string',
        ]);

        // Prevent deletion of default template
        if ($validated['template_name'] === 'default') {
            return back()->withErrors(['template_name' => 'Default template silinemez.']);
        }

        if ($this->templateService->deleteTemplate($validated['format'], $validated['template_name'])) {
            return redirect()
                ->route('admin.marketing.templates.index')
                ->with('success', "Template '{$validated['template_name']}' başarıyla silindi.");
        }

        return back()->withErrors(['template_name' => 'Template bulunamadı.']);
    }

    /**
     * Template preview (API endpoint)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|string|in:instagram_post,instagram_story,instagram_reel,facebook_post',
            'template_data' => 'required|string',
            'ilan_id' => 'nullable|integer|exists:ilanlar,id',
        ]);

        // Parse template data
        $templateData = json_decode($validated['template_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz JSON formatı',
            ], 400);
        }

        // Get ilan
        $ilan = null;
        if ($validated['ilan_id']) {
            $ilan = Ilan::with(['kategori', 'il', 'ilce', 'mahalle'])->find($validated['ilan_id']);
        }

        if (!$ilan) {
            // Use first published ilan as sample
            $ilan = Ilan::with(['kategori', 'il', 'ilce', 'mahalle'])
                ->where('yayin_durumu', 'published')
                ->first();
        }

        if (!$ilan) {
            return response()->json([
                'success' => false,
                'error' => 'Preview için ilan bulunamadı. Lütfen en az bir yayınlanmış ilan oluşturun.',
            ], 404);
        }

        try {
            // Generate preview asset
            $result = match ($validated['format']) {
                'instagram_post' => $this->assetEngine->generateInstagramPost($ilan, 'preview', ['template_override' => $templateData]),
                'instagram_story' => $this->assetEngine->generateInstagramStory($ilan, 'preview', ['template_override' => $templateData]),
                default => throw new \InvalidArgumentException("Unsupported format: {$validated['format']}"),
            };

            return response()->json([
                'success' => true,
                'preview_url' => $result['asset_url'],
                'asset_path' => $result['asset_path'],
                'metadata' => $result['metadata'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Template yapısını doğrula
     *
     * @param array $templateData
     * @param string $format
     * @return array
     */
    private function validateTemplateStructure(array $templateData, string $format): array
    {
        // Required fields
        if (!isset($templateData['layout'])) {
            return ['valid' => false, 'error' => 'Template layout tanımlı değil.'];
        }

        if (!isset($templateData['background'])) {
            return ['valid' => false, 'error' => 'Template background tanımlı değil.'];
        }

        if (!isset($templateData['elements']) || !is_array($templateData['elements'])) {
            return ['valid' => false, 'error' => 'Template elements tanımlı değil veya geçersiz.'];
        }

        // Validate background
        $backgroundType = $templateData['background']['type'] ?? null; // context7-ignore
        if (!in_array($backgroundType, ['gradient', 'solid', 'image'])) {
            return ['valid' => false, 'error' => 'Geçersiz background type. (gradient, solid, image)'];
        }

        // Validate elements
        foreach ($templateData['elements'] as $index => $element) {
            if (!isset($element['type'])) { // context7-ignore
                return ['valid' => false, 'error' => "Element #{$index}: type tanımlı değil."];
            }

            $validTypes = ['text', 'image', 'badge', 'shape'];
            if (!in_array($element['type'], $validTypes)) { // context7-ignore
                return ['valid' => false, 'error' => "Element #{$index}: Geçersiz type. ({$element['type']})"]; // context7-ignore
            }

            // Validate text element
            if ($element['type'] === 'text' && !isset($element['content'])) { // context7-ignore
                return ['valid' => false, 'error' => "Element #{$index}: Text element için content tanımlı değil."];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Default template structure
     *
     * @param string $format
     * @return array
     */
    private function getDefaultTemplateStructure(string $format): array
    {
        return match ($format) {
            'instagram_post' => [
                'layout' => 'centered',
                'background' => [
                    'type' => 'gradient', // context7-ignore
                    'colors' => ['#1e40af', '#3b82f6'],
                ],
                'elements' => [
                    [
                        'type' => 'image', // context7-ignore
                        'position' => 'center',
                        'size' => 'large',
                        'source' => '{{image_url}}',
                    ],
                    [
                        'type' => 'badge', // context7-ignore
                        'position' => 'top-right',
                        'source' => '{{badge.primary_badge}}',
                    ],
                    [
                        'type' => 'text', // context7-ignore
                        'content' => '{{baslik}}',
                        'position' => 'bottom',
                        'offset' => 120,
                        'style' => [
                            'font_size' => 48,
                            'font_weight' => 'bold',
                            'color' => '#ffffff',
                            'text_align' => 'center',
                        ],
                    ],
                    [
                        'type' => 'text', // context7-ignore
                        'content' => '{{fiyat}} {{para_birimi}}',
                        'position' => 'bottom',
                        'offset' => 60,
                        'style' => [
                            'font_size' => 36,
                            'font_weight' => 'semibold',
                            'color' => '#ffffff',
                            'text_align' => 'center',
                        ],
                    ],
                ],
            ],
            'instagram_story' => [
                'layout' => 'vertical',
                'background' => [
                    'type' => 'gradient', // context7-ignore
                    'colors' => ['#1e40af', '#3b82f6'],
                    'direction' => 'vertical',
                ],
                'elements' => [
                    [
                        'type' => 'image', // context7-ignore
                        'position' => 'top',
                        'size' => 'medium',
                        'source' => '{{image_url}}',
                        'offset' => 100,
                    ],
                    [
                        'type' => 'badge', // context7-ignore
                        'position' => 'top-left',
                        'offset' => 40,
                        'source' => '{{badge.primary_badge}}',
                    ],
                    [
                        'type' => 'text', // context7-ignore
                        'content' => '{{baslik}}',
                        'position' => 'center',
                        'offset' => -100,
                        'style' => [
                            'font_size' => 42,
                            'font_weight' => 'bold',
                            'color' => '#ffffff',
                            'text_align' => 'center',
                        ],
                    ],
                    [
                        'type' => 'text', // context7-ignore
                        'content' => '{{fiyat}} {{para_birimi}}',
                        'position' => 'center',
                        'offset' => -40,
                        'style' => [
                            'font_size' => 32,
                            'font_weight' => 'semibold',
                            'color' => '#ffffff',
                            'text_align' => 'center',
                        ],
                    ],
                ],
            ],
            default => [
                'layout' => 'centered',
                'background' => [
                    'type' => 'gradient', // context7-ignore
                    'colors' => ['#1e40af', '#3b82f6'],
                ],
                'elements' => [],
            ],
        };
    }
    
    /**
     * Generate marketing assets for a specific listing
     * 
     * POST /admin/marketing/assets/ilanlar/{ilan}/generate
     */
    public function generateForListing(Ilan $ilan)
    {
        try {
            // Validate listing has required data
            if (!$ilan->fiyat || !$ilan->baslik) {
                return response()->json([
                    'success' => false,
                    'message' => 'İlan eksik: Fiyat ve başlık zorunludur'
                ], 422);
            }
            
            // Check if listing has photo
            if ($ilan->photos()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'İlan için en az bir fotoğraf gereklidir'
                ], 422);
            }
            
            // Generate Instagram Post
            $postResult = $this->assetEngine->generateInstagramPost($ilan);
            
            // Generate Instagram Story
            $storyResult = $this->assetEngine->generateInstagramStory($ilan);
            
            return response()->json([
                'success' => true,
                'message' => 'Görseller başarıyla oluşturuldu',
                'data' => [
                    'post' => $postResult,
                    'story' => $storyResult,
                    'generation_status' => 'success',
                    'is_ready' => true,
                    'generated_at' => now()->toISOString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Marketing asset generation error', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Görsel üretimi başarısız: ' . $e->getMessage(),
                'generation_status' => 'failed',
                'is_ready' => false
            ], 500);
        }
    }
    
    /**
     * Get existing marketing assets for a listing
     * 
     * GET /admin/marketing/assets/ilanlar/{ilan}
     */
    public function getListingAssets(Ilan $ilan)
    {
        try {
            $assetsPath = "marketing/assets/{$ilan->id}";
            
            if (!Storage::disk('public')->exists($assetsPath)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_ready' => false,
                        'assets' => []
                    ]
                ]);
            }
            
            $files = Storage::disk('public')->files($assetsPath);
            
            $assets = collect($files)->map(function($file) {
                $filename = basename($file);
                
                // Determine type from filename
                $type = 'unknown';
                if (str_contains($filename, 'post')) {
                    $type = 'instagram_post';
                } elseif (str_contains($filename, 'story')) {
                    $type = 'instagram_story';
                }
                
                return [
                    'type' => $type, // context7-ignore
                    'url' => Storage::url($file),
                    'path' => $file,
                    'filename' => $filename,
                    'size' => Storage::disk('public')->size($file),
                    'created_at' => Storage::disk('public')->lastModified($file),
                ];
            })->values()->all();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_ready' => count($assets) > 0,
                    'assets' => $assets
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Görseller alınamadı: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete marketing assets for a listing
     * 
     * DELETE /admin/marketing/assets/ilanlar/{ilan}
     */
    public function deleteListingAssets(Ilan $ilan)
    {
        try {
            $assetsPath = "marketing/assets/{$ilan->id}";
            
            if (Storage::disk('public')->exists($assetsPath)) {
                Storage::disk('public')->deleteDirectory($assetsPath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Görseller silindi'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Silme başarısız: ' . $e->getMessage()
            ], 500);
        }
    }
}

