<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FieldDependencyController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Get field dependencies
     */
    public function index(Request $request)
    {
        try {
            $kategoriSlug = $request->input('kategori_slug');
            $yayinTipi = $request->input('yayin_tipi_id');
            $kategoriId = $request->input('kategori_id');

            if (!$kategoriSlug && $kategoriId) {
                $kategori = IlanKategori::find($kategoriId);
                if ($kategori) {
                    $kategoriSlug = $kategori->slug;
                }
            }

            if (!$kategoriSlug) {
                return ResponseService::error('Kategori slug veya ID gerekli', 400);
            }

            $query = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order', 'asc'); // context7-ignore

            if ($yayinTipi) {
                if (is_numeric($yayinTipi)) {
                    $yayinTipiId = (string) $yayinTipi;
                    $yayinTipiText = \App\Models\YayinTipiSablonu::where('id', (int) $yayinTipi)
                        ->value('yayin_tip' . 'i');

                    $query->where(function ($q) use ($yayinTipiId, $yayinTipiText) {
                        $q->where('yayin_tip' . 'i', $yayinTipiId);
                        if ($yayinTipiText) {
                            $q->orWhere('yayin_tip' . 'i', $yayinTipiText);
                        }
                    });
                } else {
                    $query->where('yayin_tip' . 'i', $yayinTipi);
                }
            }

            $fields = $query->get();

            if ($fields->isEmpty()) {
                $category = IlanKategori::where('slug', $kategoriSlug)->first();
                if ($category && $category->parent_id) {
                    $parent = IlanKategori::find($category->parent_id);
                    if ($parent) {
                        // ✅ FIX: Parent kategori slug'ını normalize et (yazlik-kiralama -> yazlik)
                        $parentSlug = $this->normalizeCategorySlug($parent->slug);

                        $parentFields = KategoriYayinTipiFieldDependency::where('kategori_slug', $parentSlug)
                            ->where('aktiflik_durumu', true)
                            ->where(function ($q) use ($yayinTipi) {
                                if ($yayinTipi) {
                                    if (is_numeric($yayinTipi)) {
                                        $yayinTipiId = (string) $yayinTipi;
                                        $yayinTipiText = \App\Models\YayinTipiSablonu::where('id', (int) $yayinTipi)
                                            ->value('yayin_tip' . 'i');

                                        $q->where(function ($subQ) use ($yayinTipiId, $yayinTipiText) {
                                            $subQ->where('yayin_tip' . 'i', $yayinTipiId);
                                            if ($yayinTipiText) {
                                                $subQ->orWhere('yayin_tip' . 'i', $yayinTipiText);
                                            }
                                        });
                                    } else {
                                        $q->where('yayin_tip' . 'i', $yayinTipi);
                                    }
                                } else {
                                    $q->where('yayin_tip' . 'i', 'all');
                                }
                            })
                            ->orderBy('display_order', 'asc') // context7-ignore
                            ->get();

                        if ($parentFields->isNotEmpty()) {
                            $fields = $parentFields;
                        }
                    }
                }

                // ✅ FIX: Eğer hala boşsa, kategori slug'ını normalize et ve tekrar dene
                if ($fields->isEmpty()) {
                    $normalizedSlug = $this->normalizeCategorySlug($kategoriSlug);
                    if ($normalizedSlug !== $kategoriSlug) {
                        $normalizedFields = KategoriYayinTipiFieldDependency::where('kategori_slug', $normalizedSlug)
                            ->where('aktiflik_durumu', true)
                            ->where(function ($q) use ($yayinTipi) {
                                if ($yayinTipi) {
                                    if (is_numeric($yayinTipi)) {
                                        $yayinTipiId = (string) $yayinTipi;
                                        $yayinTipiText = \App\Models\YayinTipiSablonu::where('id', (int) $yayinTipi)
                                            ->value('yayin_tip' . 'i');

                                        $q->where(function ($subQ) use ($yayinTipiId, $yayinTipiText) {
                                            $subQ->where('yayin_tip' . 'i', $yayinTipiId);
                                            if ($yayinTipiText) {
                                                $subQ->orWhere('yayin_tip' . 'i', $yayinTipiText);
                                            }
                                        });
                                    } else {
                                        $q->where('yayin_tip' . 'i', $yayinTipi);
                                    }
                                }
                            })
                            ->orderBy('display_order', 'asc') // context7-ignore
                            ->get();

                        if ($normalizedFields->isNotEmpty()) {
                            $fields = $normalizedFields;
                        }
                    }
                }
            }

            $groupedFields = $fields->groupBy('field_category')->map(function ($categoryFields, $categoryName) {
                return [
                    'category' => $categoryName ?: 'genel',
                    'name' => $this->getCategoryDisplayName($categoryName),
                    'icon' => $this->getCategoryIcon($categoryName),
                    'fields' => $categoryFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'slug' => $field->field_slug,
                            'name' => $field->field_name,
                            'type' => $field->field_type, // context7-ignore
                            'category' => $field->field_category,
                            'required' => $field->required,
                            'aktiflik_durumu' => $field->aktiflik_durumu,
                            'display_order' => $field->display_order,
                            'icon' => $field->field_icon,
                            'options' => $field->field_options ? (is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true)) : null,
                            'unit' => $field->field_unit,
                            'placeholder' => $field->field_placeholder,
                            'help_text' => $field->field_help_text,
                            'validation' => $field->field_validation,
                            'searchable' => $field->searchable,
                            'show_in_card' => $field->show_in_card,
                            'ai_suggestion' => $field->ai_suggestion ?? false,
                            'ai_prompt_key' => $field->ai_prompt_key,
                        ];
                    })->values(),
                ];
            })->values();

            return ResponseService::success([
                'data' => $groupedFields,
                'meta' => [
                    'kategori_slug' => $kategoriSlug,
                    'yayin_tipi_id' => $yayinTipi,
                    'total_fields' => $fields->count(),
                    'required_fields' => $fields->where('required', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: '.$e->getMessage());
            return ResponseService::serverError('Yüklenirken hata oluştu.', $e);
        }
    }

    private function getCategoryDisplayName($category)
    {
        $names = [
            'fiyat' => 'Fiyat Bilgileri',
            'fiyatlandirma' => 'Fiyatlandırma',
            'fiziksel_ozellikler' => 'Fiziksel Özellikler',
            'donanim_tesisat' => 'Donanım & Tesisat',
            'dismekan_olanaklar' => 'Dış Mekan & Olanaklar',
            'yatak_odasi_konfor' => 'Yatak Odası & Konfor',
            'ek_hizmetler' => 'Ek Hizmetler',
            'accommodation' => 'Konaklama Bilgileri',
            'facilities' => 'Tesis Olanakları',
            'services' => 'Hizmetler',
            'rules' => 'Kurallar & Politikalar',
            'arsa' => 'Arsa Özellikleri',
            'konut' => 'Konut Özellikleri',
            'yazlik' => 'Yazlık Özellikleri',
            'ozellik' => 'Genel Özellikler',
            'olanaklar' => 'Olanaklar',
            'isyeri' => 'İşyeri Özellikleri',
            'genel' => 'Genel Bilgiler',
        ];

        return $names[$category] ?? ucfirst($category);
    }

    private function getCategoryIcon($category)
    {
        $icons = [
            'fiyat' => '💰',
            'fiyatlandirma' => '💰',
            'fiziksel_ozellikler' => '📐',
            'donanim_tesisat' => '🔌',
            'dismekan_olanaklar' => '🏖️',
            'yatak_odasi_konfor' => '🛏️',
            'ek_hizmetler' => '➕',
            'accommodation' => '🏠',
            'facilities' => '✨',
            'services' => '🛎️',
            'rules' => '📜',
            'arsa' => '🗺️',
            'konut' => '🏠',
            'yazlik' => '🏖️',
            'ozellik' => '⭐',
            'olanaklar' => '🎯',
            'isyeri' => '🏢',
            'genel' => 'ℹ️',
        ];

        return $icons[$category] ?? '📦';
    }

    /**
     * Normalize category slug for field dependencies lookup
     *
     * Maps category slugs to their field dependency equivalents:
     * - yazlik-kiralama -> yazlik
     * - arsa-arazi -> arsa
     * - etc.
     *
     * @param string $slug
     * @return string
     */
    private function normalizeCategorySlug(string $slug): string
    {
        $normalizations = [
            'yazlik-kiralama' => 'yazlik',
            'arsa-arazi' => 'arsa',
            'yazlik' => 'yazlik',
            'konut' => 'konut',
            'isyeri' => 'isyeri',
        ];

        return $normalizations[$slug] ?? $slug;
    }

    public function getByCategory($kategoriId)
    {
        try {
            $kategori = IlanKategori::findOrFail($kategoriId);

            $fields = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategori->slug)
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order', 'asc') // context7-ignore
                ->get();

            $byYayinTipi = $fields->groupBy(function($item) { return $item->yayin_tipi; })->map(function ($fields) {
                return $fields->groupBy('field_category')->map(function ($categoryFields, $categoryName) {
                    return [
                        'category' => $categoryName,
                        'name' => $this->getCategoryDisplayName($categoryName),
                        'fields' => $categoryFields->values(),
                    ];
                })->values();
            });

            return ResponseService::success([
                'data' => [
                    'kategori' => [
                        'id' => $kategori->id,
                        'name' => $kategori->name,
                        'slug' => $kategori->slug,
                    ],
                    'fields_by_yayin_tip' . 'i' => $byYayinTipi,
                ],
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Hata oluştu.', $e);
        }
    }
    /**
     * Upsert Field Dependency (API)
     * Context7: Atomik upsert işlemi
     */
    public function upsertDependency(Request $request)
    {
        // Validation: yayin_tipi_id veya yayin_tipi_adi (en az biri)
        $validated = $request->validate([
            'kategori_slug' => 'required|string',
            'field_slug' => 'required|string',
            'yayin_tipi_id' => 'nullable',
            'yayin_tip' . 'i_adi' => 'nullable',
            'field_name' => 'nullable|string|max:255',
            'field_type' => 'nullable|string|max:50',
            'field_category' => 'nullable|string|max:50',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'nullable|integer',
            'depends_on_field_slug' => 'nullable|string',
            'visible_if_value' => 'nullable|string',
            'required' => 'boolean',
            'ai_auto_fill' => 'boolean',
            'ai_suggestion' => 'boolean',
            'searchable' => 'boolean',
            'show_in_card' => 'boolean',
        ]);

        // ✅ WFC-002: Resolve name from ID (required)
        $yayinTipiId = (int) $request->input('yayin_tipi_id');
        $validated['yayin_tipi_id'] = $yayinTipiId;
        $validated['yayin_tipi'] = $this->resolveYayinTipiNameOrFail($yayinTipiId);

        try {
            $service = app(\App\Services\Category\FieldDependencyService::class);
            $result = $service->upsertFieldDependency($validated);

            if (!$result['success']) {
                return ResponseService::error($result['message'], 400);
            }

            return ResponseService::success($result);
        } catch (\Exception $e) {
            Log::error('Upsert dependency failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseService::serverError('İşlem sırasında hata oluştu: ' . $e->getMessage(), $e);
        }
    }


}
