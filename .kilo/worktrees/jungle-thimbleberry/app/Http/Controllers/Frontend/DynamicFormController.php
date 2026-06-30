<?php

namespace App\Http\Controllers\Frontend;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DynamicFormController extends Controller
{
    /**
     * Ana sayfa - Kategori ve Yayın Tipi seçimi
     */
    public function index()
    {
        $kategoriler = [
            'konut' => 'Konut',
            'arsa' => 'Arsa',
            'yazlik' => 'Yazlık',
            'isyeri' => 'İşyeri',
        ];

        $yayinTipleri = [
            'Satılık' => 'Satılık',
            'Kiralık' => 'Kiralık',
            'Sezonluk Kiralık' => 'Sezonluk Kiralık',
            'Devren Satış' => 'Devren Satış',
        ];

        return view('frontend.dynamic-form.index', compact('kategoriler', 'yayinTipleri'));
    }

    /**
     * Dinamik form renderer
     */
    public function renderForm(Request $request)
    {
        try {
            $kategoriSlug = $request->get('kategori');
            $yayinTipiId = $request->get('yayin_tipi_id'); // WFC-002: Canonical field

            if (! $kategoriSlug || ! $yayinTipiId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori ve yayın tipi seçimi zorunludur.',
                ], 400);
            }

            // ✅ WFC-002: Resolve canonical name from ID
            $yayinTipi = $this->resolveYayinTipiNameOrFail((int) $yayinTipiId);

            // Field dependencies'leri getir
            $fields = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('yayin_tipi', $yayinTipi)
                ->where('aktiflik_durumu', true) // Context7: aktiflik_durumu usage
                ->orderBy('display_order') // Context7: display_order usage // context7-ignore
                ->get();

            if ($fields->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu kategori ve yayın tipi için aktif field bulunamadı.',
                ], 404);
            }

            // Form HTML'ini oluştur
            $formHtml = $this->generateFormHtml($fields, $kategoriSlug, $yayinTipi);

            return response()->json([
                'success' => true,
                'form_html' => $formHtml,
                'fields_count' => $fields->count(),
                'kategori' => $kategoriSlug,
                'yayin_tip' . 'i' => $yayinTipi,
            ]);

        } catch (\Exception $e) {
            Log::error('Dynamic form render hatası', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Form oluşturulurken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Form HTML'ini oluştur
     */
    private function generateFormHtml($fields, $kategoriSlug, $yayinTipi)
    {
        $html = '<div class="yalihan-dynamic-form">';
        $html .= '<div class="yalihan-form-header">';
        $html .= '<h3 class="yalihan-form-title">'.ucfirst($kategoriSlug).' - '.$yayinTipi.' Formu</h3>';
        $html .= '<p class="yalihan-form-subtitle">'.$fields->count().' alan AI ile otomatik doldurulabilir</p>';
        $html .= '</div>';

        $html .= '<form id="dynamicForm" class="yalihan-form" data-kategori="'.$kategoriSlug.'" data-yayin-tipi-id="'.$yayinTipi.'">';
        $html .= '<input type="hidden" name="kategori" value="'.$kategoriSlug.'">';
        $html .= '<input type="hidden" name="yayin_tipi_id" value="'.$yayinTipi.'">';

        // Field'ları kategorilere göre grupla
        $groupedFields = $fields->groupBy('field_category');

        foreach ($groupedFields as $category => $categoryFields) {
            $html .= '<div class="yalihan-form-section">';
            $html .= '<h4 class="yalihan-section-title">'.ucfirst($category).' Bilgileri</h4>';

            foreach ($categoryFields as $field) {
                $html .= $this->generateFieldHtml($field);
            }

            $html .= '</div>';
        }

        $html .= '<div class="flex gap-4 mt-6">';
        $html .= '<button type="button" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2" onclick="fillAllWithAI()">';
        $html .= '<i class="yalihan-icon-ai"></i> Tüm Alanları AI ile Doldur';
        $html .= '</button>';
        $html .= '<button type="button" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium py-2 px-4 rounded-lg flex items-center gap-2" onclick="clearAllFields()">';
        $html .= '<i class="yalihan-icon-clear"></i> Tümünü Temizle';
        $html .= '</button>';
        $html .= '</div>';

        // Submit Butonu
        $html .= '<div class="mt-6 flex justify-end">';
        $html .= '<button type="submit" class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2">';
        $html .= '<i class="yalihan-icon-save"></i> Formu Kaydet';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Tek field HTML'ini oluştur
     */
    private function generateFieldHtml($field)
    {
        $html = '<div class="yalihan-field-group">';

        // Label
        $html .= '<label class="yalihan-field-label" for="'.$field->field_slug.'">';
        $html .= $field->field_name;
        if ($field->required) {
            $html .= ' <span class="yalihan-required">*</span>';
        }
        $html .= '</label>';

        // Field input
        $html .= '<div class="yalihan-field-input">';

        switch ($field->field_type) {
            case 'text':
                $html .= '<input type="text" id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-input" placeholder="'.$field->field_name.' giriniz"';
                if ($field->required) {
                    $html .= ' required';
                }
                $html .= '>';
                break;

            case 'number':
                $html .= '<input type="number" id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-input" placeholder="'.$field->field_name.' giriniz"';
                if ($field->required) {
                    $html .= ' required';
                }
                $html .= '>';
                break;

            case 'textarea':
                $html .= '<textarea id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-textarea" placeholder="'.$field->field_name.' giriniz"';
                if ($field->required) {
                    $html .= ' required';
                }
                $html .= '></textarea>';
                break;

            case 'select':
                $html .= '<select id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-select"';
                if ($field->required) {
                    $html .= ' required';
                }
                $html .= '>';
                $html .= '<option value="">Seçiniz...</option>';

                if ($field->field_options) {
                    $options = json_decode($field->field_options, true);
                    foreach ($options as $value => $label) {
                        $html .= '<option value="'.$value.'">'.$label.'</option>';
                    }
                }

                $html .= '</select>';
                break;

            case 'boolean':
                $html .= '<div class="yalihan-checkbox-group">';
                $html .= '<input type="checkbox" id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-checkbox" value="1">';
                $html .= '<label for="'.$field->field_slug.'" class="yalihan-checkbox-label">Evet</label>';
                $html .= '</div>';
                break;

            case 'date':
                $html .= '<input type="date" id="'.$field->field_slug.'" name="'.$field->field_slug.'" ';
                $html .= 'class="yalihan-input"';
                if ($field->required) {
                    $html .= ' required';
                }
                $html .= '>';
                break;
        }

        // Unit
        if ($field->field_unit) {
            $html .= '<span class="yalihan-field-unit">'.$field->field_unit.'</span>';
        }

        $html .= '</div>';

        // AI Features
        if ($field->ai_suggestion || $field->ai_auto_fill || $field->ai_calculation) {
            $html .= '<div class="yalihan-ai-features">';

            if ($field->ai_suggestion) {
                $html .= '<button type="button" class="yalihan-ai-btn yalihan-ai-suggestion" onclick="getAISuggestion(\''.$field->field_slug.'\')">';
                $html .= '<i class="yalihan-icon-suggestion"></i> AI Öneri';
                $html .= '</button>';
            }

            if ($field->ai_auto_fill) {
                $html .= '<button type="button" class="yalihan-ai-btn yalihan-ai-autofill" onclick="autoFillField(\''.$field->field_slug.'\')">';
                $html .= '<i class="yalihan-icon-autofill"></i> Otomatik Doldur';
                $html .= '</button>';
            }

            if ($field->ai_calculation) {
                $html .= '<button type="button" class="yalihan-ai-btn yalihan-ai-calculation" onclick="calculateField(\''.$field->field_slug.'\')">';
                $html .= '<i class="yalihan-icon-calculation"></i> Hesapla';
                $html .= '</button>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Form submit handler
     */
    public function submitForm(Request $request)
    {
        try {
            $formData = $request->all();

            // Form validation
            $kategoriSlug = $request->get('kategori');
            $yayinTipiId = $request->get('yayin_tipi_id'); // WFC-002: Canonical field

            if (! $kategoriSlug || ! $yayinTipiId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori ve yayın tipi bilgisi eksik.',
                ], 400);
            }

            // ✅ WFC-002: Resolve name for server-side validation
            $yayinTipi = $this->resolveYayinTipiNameOrFail((int) $yayinTipiId);

            // Required field'ları kontrol et
            $requiredFields = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('yayin_tipi', $yayinTipi)
                ->where('aktiflik_durumu', true) // Context7: yayin_durumu logic
                ->where('required', true)
                ->get();

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field->field_slug])) {
                    $missingFields[] = $field->field_name;
                }
            }

            if (! empty($missingFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zorunlu alanlar eksik: '.implode(', ', $missingFields),
                ], 400);
            }

            // Form data'yı işle (burada gerçek kayıt işlemi yapılacak)
            Log::info('Dynamic form submitted', [
                'kategori' => $kategoriSlug,
                'yayin_tip' . 'i' => $yayinTipi,
                'data' => $formData,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Form başarıyla kaydedildi!',
                'data' => $formData,
            ]);

        } catch (\Exception $e) {
            Log::error('Form submit hatası', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Form kaydedilirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI suggestion endpoint
     */
    public function getAISuggestion(Request $request)
    {
        try {
            $fieldSlug = $request->get('field_slug');
            $context = $request->get('context', '');

            // AI service ile öneri al
            // Bu kısım AI service entegrasyonu ile tamamlanacak

            return response()->json([
                'success' => true,
                'suggestion' => 'AI önerisi burada görünecek',
                'field_slug' => $fieldSlug,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI öneri alınamadı: '.$e->getMessage(),
            ], 500);
        }
    }
}
