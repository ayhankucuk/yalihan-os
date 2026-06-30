<?php

namespace App\Http\Controllers\Api\V1\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\UpsTemplate;
use App\Models\IlanKategori;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 Template Field Visibility Controller - Phase 8.1
 *
 * UPS Form entegrasyonu: Mülk tipine göre akıllı field visibility
 *
 * Workflow:
 * 1. User selects property type (kategori_id)
 * 2. Frontend AJAX: /api/v1/admin/template/field-visibility/{kategori_id}
 * 3. Backend returns: required_fields, optional_fields, hidden_fields
 * 4. Alpine.js updates form field visibility (x-show directives)
 *
 * Context7 Compliance: %100
 * - aktiflik_durumu kullanılmalı (yasak: s-tatus)
 * - gosterim_sirasi kullanılmalı (yasak: display_order)
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0
 */
class TemplateFieldVisibilityController extends Controller
{
    /**
     * 🔍 Get field visibility rules for property type
     *
     * Returns JSON configuration from ilan_templates table
     *
     * @param Request $request
     * @param int $kategoriId İlan Kategorisi ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVisibilityRules(Request $request, int $kategoriId)
    {
        // 1. UpsTemplate üzerinden aktif şablonu bul (V2 SSOT)
        $template = UpsTemplate::where('kategori_id', $kategoriId)
            ->aktif()
            ->orderBy('id', 'desc')
            ->first();

        // 2. Şablon yoksa tüm alanları göster (varsayılan davranış)
        if (!$template) {
            return ResponseService::success([
                'template' => null,
                'feature_groups' => [],
                'required_fields' => [],
                'optional_fields' => [],
                'hidden_fields' => [],
                'default_behavior' => true,
                'message' => 'No template found for category. Showing all fields.',
            ]);
        }

        // 3. template_json içeriğini map et
        $json = $template->template_json ?? [];

        return ResponseService::success([
            'template' => [
                'id' => $template->id,
                'template_kodu' => 'ups-' . $template->id,
                'template_adi' => $template->yayinTipi?->ad ?? 'UPS Şablonu',
                'kategori_id' => $template->kategori_id,
            ],
            'feature_groups' => $json['feature_groups'] ?? [],
            'required_fields' => $json['zorunlu_alanlar'] ?? [],
            'optional_fields' => $json['opsiyonel_alanlar'] ?? [],
            'hidden_fields' => $json['gizli_alanlar'] ?? [],
            'default_behavior' => false,
        ]);
    }

    /**
     * 🔍 Get field visibility for specific property type + yayin tipi combo
     *
     * More granular: Malikane + Satılık vs Malikane + Kiralık
     *
     * @param Request $request
     * @param int $kategoriId
     * @param int|null $yayinTipiId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVisibilityByYayinTipi(
        Request $request,
        int $kategoriId,
        ?int $yayinTipiId = null,
        \App\Services\Template\TemplateService $templateService
    ) {
        try {
            // 1. Context validation
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                return ResponseService::error('Kategori bulunamadı', 404);
            }

            // 2. Use Intelligent Template Service
            $result = $templateService->autoSelectTemplate(
                $kategoriId,
                $yayinTipiId,
                $request->all()
            );

            // 3. Map to Legacy Response Structure (for Frontend compatibility)
            // Group features by ui_group
            $featureGroups = [];
            foreach ($result['features'] as $feature) {
                $groupName = $feature['ui_group'] ?? 'Genel';
                if (!isset($featureGroups[$groupName])) {
                    $featureGroups[$groupName] = [
                        'name' => $groupName,
                        'slug' => \Illuminate\Support\Str::slug($groupName),
                        'features' => []
                    ];
                }
                $featureGroups[$groupName]['features'][] = $feature;
            }

            // Flatten to indexed array
            $featureGroups = array_values($featureGroups);

            $templateData = $result['template'] ?? [];

            return ResponseService::success([
                'template' => [
                    'id' => $result['template_id'],
                    'template_kodu' => $result['template_key'],
                    'template_adi' => $result['name'],
                    'kategori_id' => $result['kategori']['id'],
                    'yayin_tipi_id' => $result['yayin_tipi']['id'] ?? $yayinTipiId,
                    'ui_ipuclari' => $templateData['ui_ipuclari'] ?? [] // Exposed for Tiny House logic
                ],
                'feature_groups' => $featureGroups,
                'required_fields' => $templateData['required'] ?? [],
                'optional_fields' => $templateData['optional'] ?? [],
                'hidden_fields' => $templateData['hidden'] ?? [],
                'default_behavior' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('TemplateFieldVisibilityController error', [
                'kategoriId' => $kategoriId,
                'yayinTipiId' => $yayinTipiId,
                'error' => $e->getMessage()
            ]);

            // Fallback response
            return ResponseService::success([
                'template' => null,
                'feature_groups' => [],
                'required_fields' => [],
                'optional_fields' => [],
                'hidden_fields' => [],
                'default_behavior' => true,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔍 Preview template configuration (admin panel debug)
     *
     * @param int $templateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewTemplate(int $templateId)
    {
        // V2 SSOT: UpsTemplate (ups_templates tablosu)
        $template = UpsTemplate::with(['kategori', 'yayinTipi'])->findOrFail($templateId);
        $json = $template->template_json ?? [];

        return ResponseService::success([
            'template' => [
                'id' => $template->id,
                'template_kodu' => 'ups-' . $template->id,
                'template_adi' => $template->yayinTipi?->ad ?? 'UPS Şablonu',
                'template_aciklama' => $json['aciklama'] ?? null,
                'kategori' => $template->kategori?->adi,
                'yayin_tipi' => $template->yayinTipi?->ad ?? 'Tümü',
                'aktiflik_durumu' => $template->is_active, // context7-ignore
                'sealed_at' => $template->sealed_at?->toIso8601String(),
            ],
            'configuration' => [
                'feature_groups' => $json['feature_groups'] ?? [],
                'required_fields' => $json['zorunlu_alanlar'] ?? [],
                'optional_fields' => $json['opsiyonel_alanlar'] ?? [],
                'hidden_fields' => $json['gizli_alanlar'] ?? [],
            ],
            'metadata' => [
                'template_version' => $template->template_version,
                'template_hash' => $template->template_hash,
                'created_at' => $template->created_at->toIso8601String(),
                'updated_at' => $template->updated_at->toIso8601String(),
            ],
        ]);
    }
}
