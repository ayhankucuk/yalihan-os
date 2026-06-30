<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Contracts\TemplateResolverInterface;
use App\Exceptions\TemplateAmbiguousException;
use App\Models\YayinTipiSablonu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Template API Controller
 *
 * Exposes template resolution and feature assignment endpoints
 *
 * @see docs/technical/TEMPLATE_SYSTEM_ARCHITECTURE.md
 * @see docs/technical/policies/TEMPLATE_RESOLVER_ERROR_CONTRACT.md
 */
class TemplateController extends Controller
{
    protected TemplateResolverInterface $resolver;

    public function __construct(TemplateResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Resolve template by kategori_id + yayin_tipi
     *
     * GET /api/v1/templates/resolve?kategori_id=1&yayin_tipi=Satılık
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resolve(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|integer|min:1',
            'yayin_tipi' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'INVALID_INPUT',
                'Validation failed',
                $validator->errors()->toArray(),
                400
            );
        }

        $kategoriId = (int) $request->input('kategori_id');
        $yayinTipi = $request->input('yayin_tipi');

        try {
            $template = $this->resolver->resolve($kategoriId, $yayinTipi);

            if (!$template) {
                return $this->errorResponse(
                    'TEMPLATE_NOT_FOUND',
                    'No active template found for this category and publication type',
                    [
                        'kategori_id' => $kategoriId,
                        'yayin_tipi' => $yayinTipi,
                    ],
                    404
                );
            }

            return $this->successResponse([
                'template' => [
                    'id' => $template->id,
                    'kategori_id' => $template->kategori_id,
                    'yayin_tipi' => $template->yayin_tipi,
                    'aktiflik_durumu' => (bool) $template->aktiflik_durumu,
                    'display_order' => $template->display_order,
                ],
            ]);

        } catch (TemplateAmbiguousException $e) {
            return $this->errorResponse(
                'TEMPLATE_AMBIGUOUS',
                'Data integrity error: multiple templates found',
                $e->getContext(),
                500
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse(
                'INVALID_INPUT',
                $e->getMessage(),
                [
                    'kategori_id' => $kategoriId,
                    'yayin_tipi' => $yayinTipi,
                ],
                400
            );
        }
    }

    /**
     * Get all templates for a category
     *
     * GET /api/v1/templates/category/{kategoriId}
     *
     * @param int $kategoriId
     * @return JsonResponse
     */
    public function getByCategory(int $kategoriId): JsonResponse
    {
        if ($kategoriId <= 0) {
            return $this->errorResponse(
                'INVALID_INPUT',
                'kategori_id must be positive',
                ['kategori_id' => $kategoriId],
                400
            );
        }

        $templates = $this->resolver->getTemplatesForCategory($kategoriId);

        return $this->successResponse([
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'kategori_id' => $template->kategori_id,
                    'yayin_tipi' => $template->yayin_tipi,
                    'aktiflik_durumu' => (bool) $template->aktiflik_durumu,
                    'display_order' => $template->display_order,
                ];
            }),
            'count' => $templates->count(),
        ]);
    }

    /**
     * Get features for a template
     *
     * GET /api/v1/templates/{templateId}/features
     *
     * @param int $templateId
     * @return JsonResponse
     */
    public function getFeatures(int $templateId): JsonResponse
    {
        $template = YayinTipiSablonu::find($templateId);

        if (!$template) {
            return $this->errorResponse(
                'TEMPLATE_NOT_FOUND',
                'Template not found',
                ['template_id' => $templateId],
                404
            );
        }

        if (!$template->aktiflik_durumu) {
            return $this->errorResponse(
                'TEMPLATE_INACTIVE',
                'Template is inactive',
                ['template_id' => $templateId],
                410
            );
        }

        // Load feature assignments with features
        $assignments = $template->featureAssignments()
            ->with('feature')
            ->where('is_visible', true)
            ->orderBy('display_order') // context7-ignore
            ->get();

        $features = $assignments->map(function ($assignment) {
            return [
                'id' => $assignment->feature->id,
                'slug' => $assignment->feature->slug,
                'name' => $assignment->feature->name,
                'type' => $assignment->feature->type, // context7-ignore
                'unit' => $assignment->feature->unit,
                'options' => $assignment->feature->options,
                'is_required' => (bool) $assignment->is_required,
                'is_visible' => (bool) $assignment->is_visible,
                'display_order' => $assignment->display_order,
            ];
        });

        return $this->successResponse([
            'template' => [
                'id' => $template->id,
                'kategori_id' => $template->kategori_id,
                'yayin_tipi' => $template->yayin_tipi,
            ],
            'features' => $features,
            'count' => $features->count(),
        ]);
    }

    /**
     * Success response helper
     *
     * @param array $data
     * @return JsonResponse
     */
    protected function successResponse(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Error response helper (per Error Contract)
     *
     * @param string $code
     * @param string $message
     * @param array $context
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $code,
        string $message,
        array $context = [],
        int $httpCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'user_message' => $this->getUserMessage($code),
                'context' => $context,
            ],
        ], $httpCode);
    }

    /**
     * Get user-friendly message (Turkish)
     *
     * @param string $code
     * @return string
     */
    protected function getUserMessage(string $code): string
    {
        $messages = [
            'TEMPLATE_NOT_FOUND' => 'Bu kategori ve yayın tipi için şablon bulunamadı.',
            'TEMPLATE_INACTIVE' => 'Bu şablon şu anda kullanılamıyor.',
            'TEMPLATE_AMBIGUOUS' => 'Sistem hatası. Lütfen destek ile iletişime geçin.',
            'INVALID_INPUT' => 'Geçersiz istek.',
        ];

        return $messages[$code] ?? 'Bir hata oluştu.';
    }
}
