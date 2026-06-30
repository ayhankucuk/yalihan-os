<?php

namespace App\Services\Marketing;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Services\Ilan\IlanService;
use App\Services\Logging\LogService;
use App\Services\Marketing\DynamicSloganService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Rectangle;

/**
 * Asset Engine (Image Factory)
 *
 * Phase 8.0: Pazarlama ve Sosyal Medya Motoru
 * Context7 Standardı: C7-ASSET-ENGINE-2025-12-23
 *
 * İlan verilerini alıp otomatik Instagram Post/Story üreten tasarım motoru.
 * - Template-based design generation
 * - Social media asset creation
 * - Brand consistency
 * - Multi-format support (Post, Story, Reel)
 */
class AssetEngine
{
    /**
     * Template directory
     */
    private const TEMPLATE_DIR = 'marketing/templates';

    /**
     * Output directory
     */
    private const OUTPUT_DIR = 'marketing/assets';

    /**
     * Supported formats
     */
    private const FORMATS = [
        'instagram_post' => ['width' => 1080, 'height' => 1080],
        'instagram_story' => ['width' => 1080, 'height' => 1920],
        'instagram_reel' => ['width' => 1080, 'height' => 1920],
        'facebook_post' => ['width' => 1200, 'height' => 630],
    ];

    /**
     * IlanService instance
     */
    private IlanService $ilanService;

    /**
     * DynamicSloganService instance
     */
    private DynamicSloganService $sloganService;

    /**
     * ImageManager instance
     */
    private ImageManager $imageManager;

    public function __construct(IlanService $ilanService, DynamicSloganService $sloganService)
    {
        $this->ilanService = $ilanService;
        $this->sloganService = $sloganService;
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Generate Instagram Post asset
     *
     * @param Ilan $ilan
     * @param string $templateName
     * @param array $options
     * @return array
     */
    public function generateInstagramPost(Ilan $ilan, string $templateName = 'default', array $options = []): array
    {
        $startTime = LogService::startTimer('asset_engine_instagram_post');

        try {
            // Get social media metadata
            $metadata = $this->ilanService->getSocialMediaMetadata($ilan);

            // Generate dynamic slogan
            $sloganData = $this->sloganService->generateSlogan($ilan, 'medium');
            $metadata['slogan'] = $sloganData['slogan'];
            $metadata['hashtags'] = $sloganData['hashtags'];
            $metadata['cta'] = $sloganData['cta'];

            // Load template (or use override from options)
            $template = $options['template_override'] ?? $this->loadTemplate('instagram_post', $templateName);

            // Generate asset
            $asset = $this->renderAsset($template, $metadata, self::FORMATS['instagram_post'], $options);

            // Save asset
            $savedPath = $this->saveAsset($asset, $ilan->id, 'instagram_post', $templateName);

            $durationMs = LogService::stopTimer($startTime);

            LogService::action('asset_generated', 'marketing', $ilan->id, [
                'ilan_id' => $ilan->id,
                'format' => 'instagram_post',
                'template' => $templateName,
                'path' => $savedPath,
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => true,
                'asset_url' => Storage::url($savedPath),
                'asset_path' => $savedPath,
                'format' => 'instagram_post',
                'template' => $templateName,
                'metadata' => $metadata,
                'duration_ms' => $durationMs,
            ];
        } catch (\Exception $e) {
            LogService::error('Asset generation failed', [
                'ilan_id' => $ilan->id,
                'format' => 'instagram_post',
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate Instagram Story asset
     *
     * @param Ilan $ilan
     * @param string $templateName
     * @param array $options
     * @return array
     */
    public function generateInstagramStory(Ilan $ilan, string $templateName = 'default', array $options = []): array
    {
        $startTime = LogService::startTimer('asset_engine_instagram_story');

        try {
            $metadata = $this->ilanService->getSocialMediaMetadata($ilan);

            // Generate dynamic slogan
            $sloganData = $this->sloganService->generateSlogan($ilan, 'short');
            $metadata['slogan'] = $sloganData['slogan'];
            $metadata['hashtags'] = $sloganData['hashtags'];
            $metadata['cta'] = $sloganData['cta'];

            // Load template (or use override from options)
            $template = $options['template_override'] ?? $this->loadTemplate('instagram_story', $templateName);
            $asset = $this->renderAsset($template, $metadata, self::FORMATS['instagram_story'], $options);
            $savedPath = $this->saveAsset($asset, $ilan->id, 'instagram_story', $templateName);

            $durationMs = LogService::stopTimer($startTime);

            LogService::action('asset_generated', 'marketing', $ilan->id, [
                'ilan_id' => $ilan->id,
                'format' => 'instagram_story',
                'template' => $templateName,
            ]);

            return [
                'success' => true,
                'asset_url' => Storage::url($savedPath),
                'asset_path' => $savedPath,
                'format' => 'instagram_story',
                'template' => $templateName,
                'metadata' => $metadata,
                'duration_ms' => $durationMs,
            ];
        } catch (\Exception $e) {
            LogService::error('Asset generation failed', [
                'ilan_id' => $ilan->id,
                'format' => 'instagram_story',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Load template
     *
     * @param string $format
     * @param string $templateName
     * @return array
     */
    private function loadTemplate(string $format, string $templateName): array
    {
        $templatePath = self::TEMPLATE_DIR . '/' . $format . '/' . $templateName . '.json';

        if (!Storage::exists($templatePath)) {
            // Return default template structure
            return $this->getDefaultTemplate($format);
        }

        $templateContent = Storage::get($templatePath);
        return json_decode($templateContent, true);
    }

    /**
     * Get default template structure
     *
     * @param string $format
     * @return array
     */
    private function getDefaultTemplate(string $format): array
    {
        // Phase 8.1: Property-type-specific coordinates
        return match($format) {
            'instagram_post' => $this->getInstagramPostTemplate(),
            'instagram_story' => $this->getInstagramStoryTemplate(),
            default => $this->getFallbackTemplate(),
        };
    }
    
    /**
     * 🎨 Phase 8.1: Instagram Post Template (1:1 - 1080x1080)
     * 
     * Koordinat Sistemi:
     * - Malikane: Geniş açılı görsel + Alt bant mühürlü m²
     * - Taş Ev: Otantik doku + Ortada tarihi detay
     * - Residence: Modern skyline + Üst bantakıt + sosyal tesisler
     */
    private function getInstagramPostTemplate(): array
    {
        return [
            'layout' => 'centered',
            'background' => [
                'type' => 'gradient', // context7-ignore
                'colors' => ['#1e40af', '#3b82f6'],
            ],
            'elements' => [
                // Ana görsel (Fotoğraf)
                [
                    'type' => 'image', // context7-ignore
                    'position' => 'center',
                    'size' => 'large',
                    'coordinates' => [
                        'x' => 0,
                        'y' => 0,
                        'width' => 1080,
                        'height' => 880, // Üstten 880px
                    ],
                ],
                // Alt bant overlay (Mühürlü alan)
                [
                    'type' => 'rectangle', // context7-ignore
                    'position' => 'bottom',
                    'coordinates' => [
                        'x' => 0,
                        'y' => 880,
                        'width' => 1080,
                        'height' => 200,
                    ],
                    'style' => [
                        'background' => 'rgba(0, 0, 0, 0.85)',
                    ],
                ],
                // Mülk tipi + m² (Sol alt)
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{kategori}} • {{alan_m2}} m²',
                    'position' => 'bottom_left',
                    'coordinates' => [
                        'x' => 40,
                        'y' => 920,
                    ],
                    'style' => [
                        'font_size' => 32,
                        'font_weight' => 'bold',
                        'color' => '#ffffff',
                        'text_transform' => 'uppercase',
                    ],
                ],
                // Fiyat (Sağ alt - büyük)
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{fiyat}} {{para_birimi}}',
                    'position' => 'bottom_right',
                    'coordinates' => [
                        'x' => 1040,
                        'y' => 920,
                    ],
                    'style' => [
                        'font_size' => 48,
                        'font_weight' => 'bold',
                        'color' => '#fbbf24', // Amber-400
                        'text_align' => 'right',
                    ],
                ],
                // Konum (Alt - orta)
                [
                    'type' => 'text', // context7-ignore
                    'content' => '📍 {{il}} / {{ilce}}',
                    'position' => 'bottom',
                    'coordinates' => [
                        'x' => 40,
                        'y' => 1000,
                    ],
                    'style' => [
                        'font_size' => 24,
                        'color' => '#d1d5db', // Gray-300
                    ],
                ],
            ],
        ];
    }
    
    /**
     * 🎨 Phase 8.1: Instagram Story Template (9:16 - 1080x1920)
     * 
     * Dikey hiyerarşik mühürleme:
     * - Üst 1/3: Ana görsel (720px)
     * - Orta 1/3: Fiyat + ROI (720px)
     * - Alt 1/3: Özellikler + CTA (480px)
     */
    private function getInstagramStoryTemplate(): array
    {
        return [
            'layout' => 'vertical',
            'background' => [
                'type' => 'solid', // context7-ignore
                'color' => '#0f172a', // Slate-900
            ],
            'elements' => [
                // Ana görsel (Üst 1/3)
                [
                    'type' => 'image', // context7-ignore
                    'position' => 'top',
                    'coordinates' => [
                        'x' => 0,
                        'y' => 100,
                        'width' => 1080,
                        'height' => 720,
                    ],
                    'style' => [
                        'border_radius' => 20,
                        'shadow' => true,
                    ],
                ],
                // Fiyat (Orta - büyük)
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{fiyat}}',
                    'position' => 'center',
                    'coordinates' => [
                        'x' => 540, // Center
                        'y' => 950,
                    ],
                    'style' => [
                        'font_size' => 72,
                        'font_weight' => 'bold',
                        'color' => '#fbbf24', // Amber-400
                        'text_align' => 'center',
                    ],
                ],
                // Para birimi (Fiyat altı)
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{para_birimi}}',
                    'position' => 'center',
                    'coordinates' => [
                        'x' => 540,
                        'y' => 1020,
                    ],
                    'style' => [
                        'font_size' => 28,
                        'color' => '#94a3b8', // Slate-400
                        'text_align' => 'center',
                    ],
                ],
                // ROI Badge (Cortex Yatırım Özeti)
                [
                    'type' => 'badge', // context7-ignore
                    'content' => '🧠 ROI: {{roi}}% • Getiri: {{yillik_getiri}}%',
                    'position' => 'center',
                    'coordinates' => [
                        'x' => 540,
                        'y' => 1100,
                    ],
                    'style' => [
                        'font_size' => 24,
                        'color' => '#10b981', // Green-500
                        'background' => 'rgba(16, 185, 129, 0.15)',
                        'padding' => '12px 24px',
                        'border_radius' => 20,
                        'text_align' => 'center',
                    ],
                ],
                // Özellikler (3 kolon)
                [
                    'type' => 'grid', // context7-ignore
                    'content' => [
                        '{{oda_sayisi}} ODA',
                        '{{alan_m2}} m²',
                        '{{banyo_sayisi}} BANYO',
                    ],
                    'position' => 'center',
                    'coordinates' => [
                        'x' => 40,
                        'y' => 1200,
                        'width' => 1000,
                    ],
                    'style' => [
                        'font_size' => 20,
                        'color' => '#ffffff',
                        'text_align' => 'center',
                        'gap' => 20,
                    ],
                ],
                // Konum + Kategori
                [
                    'type' => 'text', // context7-ignore
                    'content' => '📍 {{il}} / {{ilce}} • {{kategori}}',
                    'position' => 'bottom',
                    'coordinates' => [
                        'x' => 540,
                        'y' => 1400,
                    ],
                    'style' => [
                        'font_size' => 22,
                        'color' => '#cbd5e1', // Slate-300
                        'text_align' => 'center',
                    ],
                ],
                // CTA Button
                [
                    'type' => 'button', // context7-ignore
                    'content' => 'DETAYLI İNCELE ➡️',
                    'position' => 'bottom',
                    'coordinates' => [
                        'x' => 40,
                        'y' => 1600,
                        'width' => 1000,
                        'height' => 80,
                    ],
                    'style' => [
                        'font_size' => 28,
                        'font_weight' => 'bold',
                        'color' => '#ffffff',
                        'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                        'border_radius' => 40,
                        'text_align' => 'center',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Fallback template
     */
    private function getFallbackTemplate(): array
    {
        return [
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
                ],
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{baslik}}',
                    'position' => 'bottom',
                    'style' => [
                        'font_size' => 48,
                        'font_weight' => 'bold',
                        'color' => '#ffffff',
                    ],
                ],
                [
                    'type' => 'text', // context7-ignore
                    'content' => '{{fiyat}} {{para_birimi}}',
                    'position' => 'bottom',
                    'offset' => 80,
                    'style' => [
                        'font_size' => 36,
                        'font_weight' => 'semibold',
                        'color' => '#ffffff',
                    ],
                ],
            ],
        ];
    }

    /**
     * Render asset from template
     *
     * Phase 8.0: Real image rendering with Intervention Image
     * Layering system: Background → Overlay → Badge → Typography
     *
     * @param array $template
     * @param array $metadata
     * @param array $dimensions
     * @param array $options
     * @return string Base64 encoded image
     */
    private function renderAsset(array $template, array $metadata, array $dimensions, array $options): string
    {
        $width = $dimensions['width'];
        $height = $dimensions['height'];

        // LAYER 1: Create canvas with gradient background
        $canvas = $this->createCanvasWithBackground($template['background'] ?? [], $metadata, $width, $height);

        // LAYER 2: Apply overlay (for text readability) - only if needed
        if (!isset($options['skip_overlay']) || !$options['skip_overlay']) {
            $this->applyOverlay($canvas, $width, $height);
        }

        // LAYER 3 & 4: Render elements (Badge, Typography, Images)
        if (isset($template['elements']) && is_array($template['elements'])) {
            foreach ($template['elements'] as $element) {
                $this->renderElement($canvas, $element, $metadata, $width, $height);
            }
        }

        // Convert to base64
        return base64_encode($canvas->toPng());
    }


    /**
     * Apply overlay layer (semi-transparent black for text readability)
     *
     * @param \Intervention\Image\Image $canvas
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function applyOverlay($canvas, int $width, int $height)
    {
        // Convert canvas to GD for overlay
        $gdCanvas = $this->imageToGdResource($canvas);

        // Create semi-transparent black overlay
        $overlayGd = imagecreatetruecolor($width, $height);
        imagesavealpha($overlayGd, true);
        $overlayColor = imagecolorallocatealpha($overlayGd, 0, 0, 0, 102); // 40% opacity (255 * 0.4 = 102)
        imagefill($overlayGd, 0, 0, $overlayColor);

        // Composite overlay onto canvas
        imagecopymerge($gdCanvas, $overlayGd, 0, 0, 0, 0, $width, $height, 40);
        imagedestroy($overlayGd);

        // Convert back to Intervention Image
        ob_start();
        imagepng($gdCanvas);
        $imageData = ob_get_clean();
        imagedestroy($gdCanvas);

        return $this->imageManager->read($imageData);
    }

    /**
     * Render element (text, image, badge)
     *
     * @param \Intervention\Image\Image $canvas
     * @param array $element
     * @param array $metadata
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function renderElement($canvas, array $element, array $metadata, int $width, int $height)
    {
        $type = $element['type'] ?? 'text'; // context7-ignore

        switch ($type) {
            case 'text':
                return $this->renderText($canvas, $element, $metadata, $width, $height);

            case 'image':
                return $this->renderImage($canvas, $element, $metadata, $width, $height);

            case 'badge':
                return $this->renderBadge($canvas, $element, $metadata, $width, $height);

            case 'shape':
                return $this->renderShape($canvas, $element, $width, $height);

            default:
                return $canvas;
        }
    }

    /**
     * Render text element
     *
     * @param \Intervention\Image\Image $canvas
     * @param array $element
     * @param array $metadata
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function renderText($canvas, array $element, array $metadata, int $width, int $height)
    {
        $content = $this->replacePlaceholders($element['content'] ?? '', $metadata);
        if (empty($content)) {
            return;
        }

        $style = $element['style'] ?? [];

        // Support both position-based and coordinate-based positioning
        $x = $element['x'] ?? null;
        $y = $element['y'] ?? null;

        if ($x === null || $y === null) {
            // Use position-based system
            $position = $element['position'] ?? 'center';
            $offset = $element['offset'] ?? 0;
            $positionCoords = $this->calculatePosition($position, $offset, $width, $height);
            $x = $positionCoords['x'];
            $y = $positionCoords['y'];
        }

        // Font settings
        $fontSize = $style['font_size'] ?? $element['font_size'] ?? 48;
        $fontWeight = $style['font_weight'] ?? 'normal';
        $fontFamily = $element['font_family'] ?? $style['font_family'] ?? null;

        // Map font family to font weight (e.g., PlayfairDisplay-Bold -> playfair-bold)
        if ($fontFamily) {
            $fontWeight = $this->mapFontFamilyToWeight($fontFamily);
        }

        $color = $style['color'] ?? $element['color'] ?? '#ffffff';
        $textAlign = $style['text_align'] ?? $element['align'] ?? 'center';
        $maxWidth = $element['max_width'] ?? null;
        $lineHeight = $element['line_height'] ?? 1.2;

        // Use GD for text rendering with custom fonts
        $gdResource = $this->imageToGdResource($canvas);
        $fontPath = $this->getFontPath($fontWeight);

        // Calculate text dimensions for centering
        if (!empty($fontPath) && file_exists($fontPath)) {
            // Use custom TTF font
            $textBox = @imagettfbbox($fontSize, 0, $fontPath, $content);
            if ($textBox !== false) {
                $textWidth = abs($textBox[4] - $textBox[0]);
                $textHeight = abs($textBox[5] - $textBox[1]);

                // Adjust X position based on alignment
                $finalX = $x;
                if ($textAlign === 'center') {
                    $finalX -= $textWidth / 2;
                } elseif ($textAlign === 'right') {
                    $finalX -= $textWidth;
                }

                // Adjust Y position (GD uses bottom-left as origin)
                $finalY = $y + $textHeight;

                $textColor = $this->hexColorToGd($gdResource, $color);
                @imagettftext($gdResource, $fontSize, 0, (int) $finalX, (int) $finalY, $textColor, $fontPath, $content);
            } else {
                // Fallback to built-in font if TTF rendering fails
                $this->renderTextWithBuiltInFont($gdResource, $content, ['x' => $x, 'y' => $y], $textAlign, $color);
            }
        } else {
            // Fallback to built-in font if custom font not available
            $this->renderTextWithBuiltInFont($gdResource, $content, ['x' => $x, 'y' => $y], $textAlign, $color);
        }

        // Convert back to Intervention Image
        ob_start();
        imagepng($gdResource);
        $imageData = ob_get_clean();
        imagedestroy($gdResource);

        return $this->imageManager->read($imageData);
    }

    /**
     * Render image element
     *
     * @param \Intervention\Image\Image $canvas
     * @param array $element
     * @param array $metadata
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function renderImage($canvas, array $element, array $metadata, int $width, int $height)
    {
        $imageUrl = $element['source'] ?? $metadata['image_url'] ?? null;

        // Support cover_image source (for luxury template)
        if ($element['source'] === 'cover_image') {
            $imageUrl = $metadata['image_url'] ?? null;
        }

        if (!$imageUrl) {
            return;
        }

        try {
            $imagePath = $this->resolveImagePath($imageUrl);
            if (!$imagePath || !file_exists($imagePath)) {
                return;
            }

            $image = $this->imageManager->read($imagePath);

            // Size calculation - support both size keyword and direct width/height
            $imageWidth = $element['width'] ?? null;
            $imageHeight = $element['height'] ?? null;

            if ($imageWidth && $imageHeight) {
                $imageSize = ['width' => $imageWidth, 'height' => $imageHeight];
            } else {
                $size = $element['size'] ?? 'medium';
                $imageSize = $this->calculateImageSize($size, $width, $height);
            }

            $image->resize($imageSize['width'], $imageSize['height']);

            // Position calculation - support both position-based and coordinate-based
            $x = $element['x'] ?? null;
            $y = $element['y'] ?? null;

            if ($x === null || $y === null) {
                $position = $element['position'] ?? 'center';
                $offset = $element['offset'] ?? 0;
                $positionCoords = $this->calculatePosition($position, $offset, $width, $height, $imageSize['width'], $imageSize['height']);
                $x = $positionCoords['x'];
                $y = $positionCoords['y'];
            }

            // Apply opacity if specified
            $opacity = isset($element['opacity']) ? (int) $element['opacity'] : 100;

            // Convert both to GD for compositing
            $gdCanvas = $this->imageToGdResource($canvas);
            $gdImage = $this->imageToGdResource($image);

            // Composite image onto canvas with opacity support
            if ($opacity < 100) {
                // Create a temporary image with alpha channel
                $tempGd = imagecreatetruecolor($imageSize['width'], $imageSize['height']);
                imagesavealpha($tempGd, true);
                $transparent = imagecolorallocatealpha($tempGd, 0, 0, 0, 127);
                imagefill($tempGd, 0, 0, $transparent);

                // Copy image with opacity
                imagecopy($tempGd, $gdImage, 0, 0, 0, 0, $imageSize['width'], $imageSize['height']);
                imagecopymerge($gdCanvas, $tempGd, (int) $x, (int) $y, 0, 0, $imageSize['width'], $imageSize['height'], $opacity);
                imagedestroy($tempGd);
            } else {
                imagecopy($gdCanvas, $gdImage, (int) $x, (int) $y, 0, 0, $imageSize['width'], $imageSize['height']);
            }

            imagedestroy($gdImage);

            // Convert back to Intervention Image
            ob_start();
            imagepng($gdCanvas);
            $imageData = ob_get_clean();
            imagedestroy($gdCanvas);

            return $this->imageManager->read($imageData);
        } catch (\Exception $e) {
            LogService::warning('Image element rendering failed', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return $canvas;
        }
    }

    /**
     * Render badge element
     *
     * @param \Intervention\Image\Image $canvas
     * @param array $element
     * @param array $metadata
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function renderBadge($canvas, array $element, array $metadata, int $width, int $height)
    {
        // Support both badge from metadata and direct badge element
        $badge = $element['source'] ?? $metadata['badge']['primary_badge'] ?? null;

        // Direct badge text (for luxury template)
        $badgeText = $element['text'] ?? null;

        if (!$badge && !$badgeText) {
            return;
        }

        if ($badge) {
            $label = $badge['label'] ?? '';
            $color = $this->getBadgeColor($badge['color'] ?? 'blue');
            $icon = $badge['icon'] ?? '';
            $badgeText = $icon ? "{$icon} {$label}" : $label;
        } else {
            $color = $element['background_color'] ?? '#3b82f6';
        }

        // Badge dimensions
        $badgeWidth = $element['width'] ?? 200;
        $badgeHeight = $element['height'] ?? 60;
        $padding = $element['padding'] ?? 15;

        // Support both position-based and coordinate-based positioning
        $x = $element['x'] ?? null;
        $y = $element['y'] ?? null;

        if ($x === null || $y === null) {
            // Use position-based system
            $position = $element['position'] ?? 'top-right';
            $offset = $element['offset'] ?? 20;
            $positionCoords = $this->calculatePosition($position, $offset, $width, $height, $badgeWidth, $badgeHeight);
            $x = $positionCoords['x'];
            $y = $positionCoords['y'];
        }

        // Font settings
        $fontSize = $element['font_size'] ?? 18;
        $fontFamily = $element['font_family'] ?? null;
        $fontWeight = $fontFamily ? $this->mapFontFamilyToWeight($fontFamily) : 'bold';
        $textColor = $element['text_color'] ?? '#ffffff';

        // Create badge background using GD for better control
        $gdCanvas = $this->imageToGdResource($canvas);
        $badgeGd = imagecreatetruecolor($badgeWidth, $badgeHeight);
        imagesavealpha($badgeGd, true);
        $transparent = imagecolorallocatealpha($badgeGd, 0, 0, 0, 127);
        imagefill($badgeGd, 0, 0, $transparent);

        // Fill background
        $bgColor = $this->hexColorToGd($badgeGd, $color);
        imagefilledrectangle($badgeGd, 0, 0, $badgeWidth - 1, $badgeHeight - 1, $bgColor);

        // Add text
        $fontPath = $this->getFontPath($fontWeight);
        if ($fontPath && file_exists($fontPath)) {
            $textBox = @imagettfbbox($fontSize, 0, $fontPath, $badgeText);
            if ($textBox !== false) {
                $textWidth = abs($textBox[4] - $textBox[0]);
                $textHeight = abs($textBox[5] - $textBox[1]);
                $textX = ($badgeWidth - $textWidth) / 2;
                $textY = ($badgeHeight + $textHeight) / 2;

                $txtColor = $this->hexColorToGd($badgeGd, $textColor);
                @imagettftext($badgeGd, $fontSize, 0, (int) $textX, (int) $textY, $txtColor, $fontPath, $badgeText);
            }
        }

        // Composite badge onto canvas
        imagecopy($gdCanvas, $badgeGd, (int) $x, (int) $y, 0, 0, $badgeWidth, $badgeHeight);
        imagedestroy($badgeGd);

        // Convert back to Intervention Image
        ob_start();
        imagepng($gdCanvas);
        $imageData = ob_get_clean();
        imagedestroy($gdCanvas);

        return $this->imageManager->read($imageData);
    }

    /**
     * Render shape element
     *
     * Phase 8.0: Luxury Template Support
     * Supports rectangles, circles, borders, opacity
     *
     * @param \Intervention\Image\Image $canvas
     * @param array $element
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function renderShape($canvas, array $element, int $width, int $height)
    {
        $shapeType = $element['shape_type'] ?? 'rectangle';
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $shapeWidth = $element['width'] ?? 100;
        $shapeHeight = $element['height'] ?? 100;
        $backgroundColor = $element['background_color'] ?? null;
        $borderColor = $element['border_color'] ?? null;
        $borderWidth = $element['border_width'] ?? 0;
        $opacity = isset($element['opacity']) ? (int) $element['opacity'] : 100;

        // Convert to GD for shape rendering
        $gdCanvas = $this->imageToGdResource($canvas);

        switch ($shapeType) {
            case 'rectangle':
                $this->renderRectangle($gdCanvas, $x, $y, $shapeWidth, $shapeHeight, $backgroundColor, $borderColor, $borderWidth, $opacity);
                break;

            case 'circle':
                $radius = min($shapeWidth, $shapeHeight) / 2;
                $centerX = $x + $shapeWidth / 2;
                $centerY = $y + $shapeHeight / 2;
                $this->renderCircle($gdCanvas, $centerX, $centerY, $radius, $backgroundColor, $borderColor, $borderWidth, $opacity);
                break;

            default:
                // Unknown shape type, skip
                return $canvas;
        }

        // Convert back to Intervention Image
        ob_start();
        imagepng($gdCanvas);
        $imageData = ob_get_clean();
        imagedestroy($gdCanvas);

        return $this->imageManager->read($imageData);
    }

    /**
     * Render rectangle shape
     *
     * @param \GdImage $gdCanvas
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @param string|null $backgroundColor
     * @param string|null $borderColor
     * @param int $borderWidth
     * @param int $opacity
     * @return void
     */
    private function renderRectangle(\GdImage $gdCanvas, int $x, int $y, int $width, int $height, ?string $backgroundColor, ?string $borderColor, int $borderWidth, int $opacity): void
    {
        // Draw border first (if specified)
        if ($borderColor && $borderWidth > 0) {
            $borderGdColor = $this->hexColorToGd($gdCanvas, $borderColor);
            for ($i = 0; $i < $borderWidth; $i++) {
                imagerectangle($gdCanvas, $x + $i, $y + $i, $x + $width - $i - 1, $y + $height - $i - 1, $borderGdColor);
            }
        }

        // Draw fill (if specified and not transparent)
        if ($backgroundColor && $backgroundColor !== 'transparent') {
            $fillGdColor = $this->hexColorToGd($gdCanvas, $backgroundColor);

            // Apply opacity if needed
            if ($opacity < 100) {
                $alpha = (int) (127 * (100 - $opacity) / 100);
                $fillGdColor = imagecolorallocatealpha(
                    $gdCanvas,
                    ($fillGdColor >> 16) & 0xFF,
                    ($fillGdColor >> 8) & 0xFF,
                    $fillGdColor & 0xFF,
                    $alpha
                );
            }

            imagefilledrectangle($gdCanvas, $x + $borderWidth, $y + $borderWidth, $x + $width - $borderWidth - 1, $y + $height - $borderWidth - 1, $fillGdColor);
        }
    }

    /**
     * Render circle shape
     *
     * @param \GdImage $gdCanvas
     * @param int $centerX
     * @param int $centerY
     * @param int $radius
     * @param string|null $backgroundColor
     * @param string|null $borderColor
     * @param int $borderWidth
     * @param int $opacity
     * @return void
     */
    private function renderCircle(\GdImage $gdCanvas, int $centerX, int $centerY, int $radius, ?string $backgroundColor, ?string $borderColor, int $borderWidth, int $opacity): void
    {
        // Draw border first (if specified)
        if ($borderColor && $borderWidth > 0) {
            $borderGdColor = $this->hexColorToGd($gdCanvas, $borderColor);
            for ($i = 0; $i < $borderWidth; $i++) {
                imageellipse($gdCanvas, $centerX, $centerY, ($radius - $i) * 2, ($radius - $i) * 2, $borderGdColor);
            }
        }

        // Draw fill (if specified and not transparent)
        if ($backgroundColor && $backgroundColor !== 'transparent') {
            $fillGdColor = $this->hexColorToGd($gdCanvas, $backgroundColor);

            // Apply opacity if needed
            if ($opacity < 100) {
                $alpha = (int) (127 * (100 - $opacity) / 100);
                $fillGdColor = imagecolorallocatealpha(
                    $gdCanvas,
                    ($fillGdColor >> 16) & 0xFF,
                    ($fillGdColor >> 8) & 0xFF,
                    $fillGdColor & 0xFF,
                    $alpha
                );
            }

            imagefilledellipse($gdCanvas, $centerX, $centerY, ($radius - $borderWidth) * 2, ($radius - $borderWidth) * 2, $fillGdColor);
        }
    }

    /**
     * Calculate position coordinates
     *
     * @param string $position
     * @param int $offset
     * @param int $width
     * @param int $height
     * @param int|null $elementWidth
     * @param int|null $elementHeight
     * @return array
     */
    private function calculatePosition(string $position, int $offset, int $width, int $height, ?int $elementWidth = null, ?int $elementHeight = null): array
    {
        $elementWidth = $elementWidth ?? 0;
        $elementHeight = $elementHeight ?? 0;

        return match ($position) {
            'top-left' => ['x' => $offset, 'y' => $offset],
            'top-right' => ['x' => $width - $elementWidth - $offset, 'y' => $offset],
            'top-center', 'top' => ['x' => ($width - $elementWidth) / 2, 'y' => $offset],
            'bottom-left' => ['x' => $offset, 'y' => $height - $elementHeight - $offset],
            'bottom-right' => ['x' => $width - $elementWidth - $offset, 'y' => $height - $elementHeight - $offset],
            'bottom-center', 'bottom' => ['x' => ($width - $elementWidth) / 2, 'y' => $height - $elementHeight - $offset],
            'center-left', 'left' => ['x' => $offset, 'y' => ($height - $elementHeight) / 2],
            'center-right', 'right' => ['x' => $width - $elementWidth - $offset, 'y' => ($height - $elementHeight) / 2],
            'center' => ['x' => ($width - $elementWidth) / 2, 'y' => ($height - $elementHeight) / 2],
            default => ['x' => ($width - $elementWidth) / 2, 'y' => ($height - $elementHeight) / 2],
        };
    }

    /**
     * Replace placeholders in content
     *
     * @param string $content
     * @param array $metadata
     * @return string
     */
    private function replacePlaceholders(string $content, array $metadata): string
    {
        $replacements = [
            '{{baslik}}' => $metadata['baslik'] ?? '',
            '{{fiyat}}' => $this->formatPrice($metadata['fiyat'] ?? 0, $metadata['para_birimi'] ?? 'TRY'),
            '{{para_birimi}}' => $metadata['para_birimi'] ?? 'TRY',
            '{{location.il}}' => $metadata['location']['il'] ?? '',
            '{{location.ilce}}' => $metadata['location']['ilce'] ?? '',
            '{{location.mahalle}}' => $metadata['location']['mahalle'] ?? '',
            '{{roi.roi_percentage}}' => $metadata['roi']['roi_percentage'] ?? '',
            '{{slogan}}' => $metadata['slogan'] ?? '',
            '{{cta}}' => $metadata['cta'] ?? '',
        ];

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Format price
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    private function formatPrice(float $price, string $currency): string
    {
        $formatted = number_format($price, 0, ',', '.');

        $currencySymbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $currencySymbols[$currency] ?? $currency;
        return "{$symbol} {$formatted}";
    }

    /**
     * Calculate image size based on size keyword
     *
     * @param string $size
     * @param int $canvasWidth
     * @param int $canvasHeight
     * @return array
     */
    private function calculateImageSize(string $size, int $canvasWidth, int $canvasHeight): array
    {
        return match ($size) {
            'small' => ['width' => (int) ($canvasWidth * 0.3), 'height' => (int) ($canvasHeight * 0.3)],
            'medium' => ['width' => (int) ($canvasWidth * 0.5), 'height' => (int) ($canvasHeight * 0.5)],
            'large' => ['width' => (int) ($canvasWidth * 0.8), 'height' => (int) ($canvasHeight * 0.8)],
            'full' => ['width' => $canvasWidth, 'height' => $canvasHeight],
            default => ['width' => (int) ($canvasWidth * 0.5), 'height' => (int) ($canvasHeight * 0.5)],
        };
    }

    /**
     * Resolve image path from URL or storage path
     *
     * @param string $imageUrl
     * @return string|null
     */
    private function resolveImagePath(string $imageUrl): ?string
    {
        // If it's a full URL, download temporarily
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            // For now, return null (can be extended to download)
            return null;
        }

        // If it's a storage path
        if (str_starts_with($imageUrl, 'storage/') || str_starts_with($imageUrl, '/storage/')) {
            return storage_path('app/public/' . ltrim($imageUrl, '/storage/'));
        }

        // If it's already an absolute path
        if (file_exists($imageUrl)) {
            return $imageUrl;
        }

        return null;
    }

    /**
     * Convert hex color to RGB
     *
     * @param string $hex
     * @return array
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Get badge color
     *
     * @param string $colorName
     * @return string
     */
    private function getBadgeColor(string $colorName): string
    {
        $colors = [
            'green' => '#10b981',
            'blue' => '#3b82f6',
            'gold' => '#f59e0b',
            'red' => '#ef4444',
            'purple' => '#8b5cf6',
        ];

        return $colors[$colorName] ?? $colors['blue'];
    }

    /**
     * Get font path
     *
     * Phase 8.0: Custom Font Support
     * Google Fonts: Montserrat, Roboto, Playfair Display
     *
     * @param string $fontWeight
     * @return string
     */
    private function getFontPath(string $fontWeight): string
    {
        $fonts = [
            // Montserrat (Modern, Clean - Primary font)
            'normal' => storage_path('fonts/Montserrat-Regular.ttf'),
            'semibold' => storage_path('fonts/Montserrat-SemiBold.ttf'),
            'bold' => storage_path('fonts/Montserrat-Bold.ttf'),

            // Roboto (Readable, Professional - Secondary font)
            'roboto-normal' => storage_path('fonts/Roboto-Regular.ttf'),
            'roboto-bold' => storage_path('fonts/Roboto-Bold.ttf'),

            // Playfair Display (Elegant, Luxury - Accent font)
            'playfair-normal' => storage_path('fonts/PlayfairDisplay-Regular.ttf'),
            'playfair-bold' => storage_path('fonts/PlayfairDisplay-Bold.ttf'),
        ];

        // Fallback: If specific font weight not found, use closest match
        if (!isset($fonts[$fontWeight])) {
            // Map common variations
            $fontWeightMap = [
                'playfair-bold' => 'playfair-bold',
                'playfair-normal' => 'playfair-normal',
                'roboto-bold' => 'roboto-bold',
                'roboto-normal' => 'roboto-normal',
            ];

            $fontWeight = $fontWeightMap[$fontWeight] ?? $fontWeight;
        }

        // Check if font file exists
        $fontPath = $fonts[$fontWeight] ?? $fonts['normal'];

        if (!file_exists($fontPath)) {
            // Fallback to system font if custom font not found
            LogService::warning('Custom font not found, using system font', [
                'font_weight' => $fontWeight,
                'font_path' => $fontPath,
            ]);
            return '';
        }

        return $fontPath;
    }

    /**
     * Map font family name to font weight
     *
     * Phase 8.0: Luxury Template Support
     * Maps font family names like "PlayfairDisplay-Bold" to internal weight keys
     *
     * @param string $fontFamily
     * @return string
     */
    private function mapFontFamilyToWeight(string $fontFamily): string
    {
        $mapping = [
            'PlayfairDisplay-Bold' => 'playfair-bold',
            'PlayfairDisplay-Regular' => 'playfair-normal',
            'Playfair Display Bold' => 'playfair-bold',
            'Playfair Display Regular' => 'playfair-normal',
            'Montserrat-Bold' => 'bold',
            'Montserrat-SemiBold' => 'semibold',
            'Montserrat-Regular' => 'normal',
            'Roboto-Bold' => 'roboto-bold',
            'Roboto-Regular' => 'roboto-normal',
        ];

        return $mapping[$fontFamily] ?? 'normal';
    }

    /**
     * Get available fonts
     *
     * @return array
     */
    public function getAvailableFonts(): array
    {
        $fontsDir = storage_path('fonts');
        $fonts = [];

        if (is_dir($fontsDir)) {
            $files = glob($fontsDir . '/*.ttf');
            foreach ($files as $file) {
                $fonts[] = basename($file);
            }
        }

        return $fonts;
    }

    /**
     * Check if font system is ready
     *
     * @return bool
     */
    public function isFontSystemReady(): bool
    {
        $primaryFont = storage_path('fonts/Montserrat-Regular.ttf');
        return file_exists($primaryFont);
    }

    /**
     * Create canvas with background
     *
     * Phase 8.0: Luxury Template Support
     * Supports background_color for solid backgrounds
     *
     * @param array $backgroundConfig
     * @param array $metadata
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function createCanvasWithBackground(array $backgroundConfig, array $metadata, int $width, int $height)
    {
        // Support legacy background_color format (for luxury template)
        if (isset($backgroundConfig['background_color']) && !isset($backgroundConfig['type'])) { // context7-ignore
            $backgroundConfig['type'] = 'solid'; // context7-ignore
            $backgroundConfig['color'] = $backgroundConfig['background_color'];
        }

        $type = $backgroundConfig['type'] ?? 'gradient'; // context7-ignore

        switch ($type) {
            case 'gradient':
                return $this->createGradientCanvas($backgroundConfig['colors'] ?? ['#1e40af', '#3b82f6'], $width, $height);

            case 'image':
                $imageUrl = $backgroundConfig['source'] ?? $metadata['image_url'] ?? null;
                if ($imageUrl) {
                    return $this->createImageBackgroundCanvas($imageUrl, $width, $height);
                }
                return $this->createGradientCanvas(['#1e40af', '#3b82f6'], $width, $height);

            case 'solid':
                $color = $backgroundConfig['color'] ?? '#1e40af';
                $canvas = $this->imageManager->create($width, $height);
                $canvas->fill($color);
                return $canvas;

            default:
                return $this->createGradientCanvas(['#1e40af', '#3b82f6'], $width, $height);
        }
    }

    /**
     * Create gradient canvas using GD
     *
     * @param array $colors
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function createGradientCanvas(array $colors, int $width, int $height)
    {
        // Use GD directly for gradient (more efficient)
        $gdResource = imagecreatetruecolor($width, $height);

        $color1 = $colors[0] ?? '#1e40af';
        $color2 = $colors[1] ?? $colors[0] ?? '#3b82f6';

        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        // Create gradient (vertical by default)
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;

            $r = (int) ($rgb1['r'] + ($rgb2['r'] - $rgb1['r']) * $ratio);
            $g = (int) ($rgb1['g'] + ($rgb2['g'] - $rgb1['g']) * $ratio);
            $b = (int) ($rgb1['b'] + ($rgb2['b'] - $rgb1['b']) * $ratio);

            $color = imagecolorallocate($gdResource, $r, $g, $b);
            imageline($gdResource, 0, $y, $width, $y, $color);
        }

        // Convert GD resource to Intervention Image
        ob_start();
        imagepng($gdResource);
        $imageData = ob_get_clean();
        imagedestroy($gdResource);

        return $this->imageManager->read($imageData);
    }

    /**
     * Create image background canvas
     *
     * @param string $imageUrl
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function createImageBackgroundCanvas(string $imageUrl, int $width, int $height)
    {
        try {
            $imagePath = $this->resolveImagePath($imageUrl);
            if (!$imagePath || !file_exists($imagePath)) {
                return $this->createGradientCanvas(['#1e40af', '#3b82f6'], $width, $height);
            }

            $backgroundImage = $this->imageManager->read($imagePath);
            $backgroundImage->cover($width, $height);

            return $backgroundImage;
        } catch (\Exception $e) {
            LogService::warning('Image background loading failed', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return $this->createGradientCanvas(['#1e40af', '#3b82f6'], $width, $height);
        }
    }

    /**
     * Convert Intervention Image to GD resource
     *
     * @param \Intervention\Image\Image $image
     * @return \GdImage|false
     */
    private function imageToGdResource($image)
    {
        // Get PNG data from Intervention Image
        $pngData = $image->toPng();

        // Convert PNG data to GD resource
        $gdResource = @imagecreatefromstring($pngData);

        if ($gdResource === false) {
            // Fallback: create empty image
            $gdResource = imagecreatetruecolor($image->width(), $image->height());
        }

        return $gdResource;
    }

    /**
     * Convert hex color to GD color resource
     *
     * @param \GdImage $gdResource
     * @param string $hexColor
     * @return int
     */
    private function hexColorToGd(\GdImage $gdResource, string $hexColor): int
    {
        $rgb = $this->hexToRgb($hexColor);
        return imagecolorallocate($gdResource, $rgb['r'], $rgb['g'], $rgb['b']);
    }

    /**
     * Render text with built-in font (fallback)
     *
     * @param \GdImage $gdResource
     * @param string $content
     * @param array $positionCoords
     * @param string $textAlign
     * @param string $color
     * @return void
     */
    private function renderTextWithBuiltInFont(\GdImage $gdResource, string $content, array $positionCoords, string $textAlign, string $color): void
    {
        $textWidth = imagefontwidth(5) * strlen($content);
        $textHeight = imagefontheight(5);

        $x = $positionCoords['x'];
        if ($textAlign === 'center') {
            $x -= $textWidth / 2;
        } elseif ($textAlign === 'right') {
            $x -= $textWidth;
        }
        $y = $positionCoords['y'];

        $textColor = $this->hexColorToGd($gdResource, $color);
        imagestring($gdResource, 5, (int) $x, (int) $y, $content, $textColor);
    }

    /**
     * Save asset to storage
     *
     * @param string $assetData Base64 encoded image
     * @param int $ilanId
     * @param string $format
     * @param string $templateName
     * @return string
     */
    private function saveAsset(string $assetData, int $ilanId, string $format, string $templateName): string
    {
        // For preview templates, save to preview directory
        if ($templateName === 'preview') {
            $filename = "preview_{$format}_" . time() . '.png';
            $path = self::OUTPUT_DIR . '/preview/' . $filename;
        } else {
            $filename = "ilan_{$ilanId}_{$format}_{$templateName}_" . time() . '.png';
            $path = self::OUTPUT_DIR . '/' . $ilanId . '/' . $filename;
        }

        Storage::put($path, base64_decode($assetData));

        return $path;
    }

    /**
     * Get available templates for format
     *
     * @param string $format
     * @return array
     */
    public function getAvailableTemplates(string $format): array
    {
        $templateDir = self::TEMPLATE_DIR . '/' . $format;

        if (!Storage::exists($templateDir)) {
            return ['default'];
        }

        $templates = [];
        $files = Storage::files($templateDir);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $templates[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return empty($templates) ? ['default'] : $templates;
    }
}
