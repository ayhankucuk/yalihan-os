<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Exceptions\TemplateCategoryMismatchException;
use App\Exceptions\TemplateNotFoundException;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Rules\CoordinateRequiredRule;
use App\Services\Category\CategoryTreeService;
use App\Services\Ilan\IlanCrudService;
use App\Services\Wizard\FieldEngine\FieldResolver;
use App\Services\Wizard\WizardDraftService;
use App\Services\Response\ResponseService;
use App\Services\Wizard\DynamicFieldValueHydrator;
use App\Services\Wizard\DynamicFieldValueMapper;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\EffectiveWizardSchemaResolver;
use App\Services\Wizard\WizardGateService;
use App\Services\Wizard\WizardAIAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🧙 İlan Sihirbazı Controller - 5 Aşamalı Validasyon
 *
 * ✅ SAB L5 Compliance: Pure delegation — no DB::transaction
 * ✅ SAB Phase 17B: Template guard on submit
 * ✅ UPS: Publication type policy validation on submit
 * Controller = orchestration only. Service = atomic boundary.
 */
class IlanWizardController extends Controller
{
    public function __construct(
        private readonly WizardGateService $gateService,
        private readonly EffectiveListingTypeResolver $listingTypeResolver,
        private readonly EffectiveWizardSchemaResolver $schemaResolver,
        private readonly DynamicFieldValueMapper $fieldMapper,
        private readonly DynamicFieldValueHydrator $fieldHydrator,
        private readonly IlanCrudService $ilanCrudService,
        private readonly CategoryTreeService $categoryTreeService,
        private readonly FieldResolver $fieldResolver,
        private readonly WizardDraftService $draftService,
        private readonly WizardAIAssistantService $aiAssistant,
    ) {}

    /**
     * 🚀 Aşama 1: Temel Bilgiler
     *
     * Session tabanlı yapıdan DB tabanlı Draft yapısına geçiş yapıldı.
     */
    public function validateAsama1(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|exists:ilan_kategorileri,id',
            'ana_kategori_id' => 'nullable|integer|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'nullable|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|integer|exists:yayin_tipi_sablonlari,id',
            'proje_id' => 'nullable|exists:projeler,id',
            'baslik' => 'required|string|min:10|max:100',
            'aciklama' => 'required|string|min:50|max:5000',
            'fiyat' => 'required|numeric|min:1000',
        ]);

        try {
            // DB-backed Draft oluştur veya mevcut olanı bul
            $draft = $this->draftService->createDraft(
                auth()->id() ?? 0,
                (int) $validated['kategori_id'],
                $request->filled('yayin_tipi_id') ? (int) $validated['yayin_tipi_id'] : null
            );

            // İlk verileri payload olarak kaydet
            foreach ($validated as $key => $value) {
                // Burada her alan bireysel validasyon kurallarından geçer (Phase 2.5 Engine)
                $this->draftService->updateField($draft->id, $key, $value);
            }
            
            session(['wizard_step_1' => $validated]);

            return ResponseService::success([
                'draft_id' => $draft->id,
                'next_step' => 2
            ], 'Temel bilgiler taslak olarak kaydedildi.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::error('Validasyon hatası.', 422, $e->errors());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Wizard Step 1 draft creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseService::error('Taslak oluşturulurken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 🔧 Aşama 2: Özellikler (Features) — Schema-driven
     */
    public function validateAsama2(Request $request): JsonResponse
    {
        $kategoriId = (int) $request->input('kategori_id');
        $yayinTipiId = (int) $request->input('yayin_tipi_id', session('wizard.yayin_tipi_id', 0));

        IlanKategori::findOrFail($kategoriId);

        // Schema-driven validation: rules built from feature_assignments
        $rules = ['kategori_id' => 'required|integer'];

        if ($yayinTipiId && config('copilot.wizard_schema_driven_step2', false)) {
            // Dependency-aware: evaluate rules against current features payload
            $featuresPayload = $request->input('features', []);
            $schemaRules = $this->schemaResolver->buildDependencyAwareRules(
                $kategoriId,
                $yayinTipiId,
                $featuresPayload
            );
            $rules = array_merge($rules, $schemaRules);
        }

        $rules['yazlik_fiyatlandirma_json'] = 'nullable|json';
        $rules['periods'] = 'nullable|array';

        $validated = $request->validate($rules);

        session(['wizard_step_2' => $validated]);

        return ResponseService::success($validated, 'Özellikler kaydedildi.');
    }

    /**
     * 📋 Schema API: Returns field schema for Step 2 dynamic rendering.
     *
     * Query params: kategori_id, yayin_tipi_id
     * Returns: Schema contract JSON (template_id, fields, meta)
     */
    public function schema(Request $request): JsonResponse
    {
        $request->validate([
            'kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            'ilan_id' => 'nullable|integer|exists:ilanlar,id',
        ]);

        $kategoriId = (int) $request->input('kategori_id');
        $yayinTipiId = (int) $request->input('yayin_tipi_id');
        $ilanId = $request->filled('ilan_id') ? (int) $request->input('ilan_id') : null;

        $schema = $this->schemaResolver->resolve($kategoriId, $yayinTipiId);

        if ($ilanId) {
            $schema = $this->fieldHydrator->hydrate($ilanId, $schema);
        }

        return response()->json(['data' => $schema]);
    }

    /**
     * 📍 Aşama 3: Konum & Koordinatlar
     */
    public function validateAsama3(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'adres' => 'required|string|min:10|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if (!$this->validateCoordinates($validated['lat'], $validated['lng'])) {
            return ResponseService::error('Koordinatlar geçersiz. Lütfen harita üzerinden seçiniz.', 422);
        }

        session(['wizard_step_3' => $validated]);

        return ResponseService::success([
            'koordinat' => [
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ],
        ], 'Konum ve koordinatlar kaydedildi.');
    }

    /**
     * 📸 Aşama 4: Medya
     */
    public function validateAsama4(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fotolar' => 'required|array|min:1|max:20',
            'fotolar.*' => 'image|mimes:jpeg,png,webp|max:10240',
            'video_url' => 'nullable|url|regex:/^(https?:\/\/)?(www\.)?(youtube|vimeo)\.com/i',
        ]);

        $uploadedPhotos = [];
        if ($request->hasFile('fotolar')) {
            $files = $request->file('fotolar');
            $filesArray = is_array($files) ? $files : [$files];
            $uploadedPhotos = array_map(fn($photo) => $photo->store('ilanlar', 'public'), $filesArray);
        }

        $validated['fotolar'] = $uploadedPhotos;
        session(['wizard_step_4' => $validated]);

        return ResponseService::success(null, count($uploadedPhotos) . ' fotoğraf yüklendi.');
    }

    /**
     * 🚀 Aşama 5: Yayın & Son Kontrol
     */
    public function validateAsama5(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yayin_durumu' => 'required|in:yayinda,taslak,duvarsa,satildi',
            'premium_ilan' => 'boolean',
            'one_cikan' => 'boolean',
            'lansman_fiyati' => 'nullable|numeric|min:0',
            'lansman_bitis_tarihi' => 'nullable|date|after:today',
            'lansman_kotasi' => 'nullable|integer|min:1',
        ]);

        session(['wizard_step_5' => $validated]);

        return ResponseService::success(['yayin_durumu' => $validated['yayin_durumu']], 'Yayın ayarları kaydedildi.');
    }

    /**
     * 💾 Tüm Aşamaları Tamamlayıp İlan Oluştur
     *
     * ✅ SAB L5: Pure delegation
     * ✅ SAB Phase 17B: Template guard — mapping yoksa ilan oluşturulamaz
     */
    public function submitWizard(Request $request): JsonResponse
    {
        // ✅ Server-side idempotency guard: prevent duplicate submissions
        $submissionToken = $request->input('_submission_token');
        $sessionTokenKey = 'wizard_submission_token';

        if ($submissionToken) {
            $usedToken = session($sessionTokenKey);
            if ($usedToken === $submissionToken) {
                return ResponseService::error('Bu form zaten gönderildi. Lütfen sayfayı yenileyiniz.', 409);
            }
        }

        // Fingerprint-based dedup: hash step data to prevent identical submissions
        $step1 = session('wizard_step_1');
        $step2 = session('wizard_step_2');
        $step3 = session('wizard_step_3');
        $step4 = session('wizard_step_4');
        $step5 = session('wizard_step_5');

        if (!$step1 || !$step3) {
            return ResponseService::error('Tüm zorunlu aşamalar tamamlanmamıştır.', 422);
        }

        // ✅ Content fingerprint dedup: prevent identical ilan creation within same session
        $fingerprint = md5(json_encode([$step1, $step3]));
        $lastFingerprint = session('wizard_last_fingerprint');
        if ($lastFingerprint === $fingerprint) {
            return ResponseService::error('Aynı ilan bilgileriyle tekrar gönderim yapılamaz.', 409);
        }

        // ✅ SAB Phase 17B: Template Guard on Submit
        $yayinTipiId = $step1['yayin_tipi_id'] ?? session('wizard.yayin_tipi_id');
        $kategoriId = $step1['kategori_id'] ?? null;
        $anaKategoriId = $step1['ana_kategori_id'] ?? session('wizard.ana_kategori_id');
        $altKategoriId = $step1['alt_kategori_id'] ?? session('wizard.alt_kategori_id') ?? $kategoriId;

        // ✅ UPS Policy Guard: Validate category + yayın tipi combination
        if ($yayinTipiId && $altKategoriId) {
            $mainCatId = $anaKategoriId ? (int) $anaKategoriId : (int) $altKategoriId;
            $subCatId = $anaKategoriId ? (int) $altKategoriId : null;
            if (!$this->listingTypeResolver->isAllowed($mainCatId, $subCatId, (int) $yayinTipiId)) {
                return ResponseService::error(
                    'Seçilen yayın tipi bu kategori için izin verilmiyor.',
                    422
                );
            }
        }

        if ($yayinTipiId) {
            try {
                $this->gateService->dogrulaWizardGirisi((int) $yayinTipiId, $kategoriId ? (int) $kategoriId : null);
            } catch (TemplateNotFoundException $e) {
                return ResponseService::error('Geçerli bir yayın tipi şablonu bulunamadı. İlan oluşturulamaz.', yanitKodu: 422);
            } catch (TemplateCategoryMismatchException $e) {
                return ResponseService::error('Seçilen şablon bu kategori ile uyuşmuyor.', yanitKodu: 422);
            }
        }

        try {
            // Phase3-WA: delegated to IlanCrudService as single write authority
            // Wizard prepares and normalizes payload — persistence goes through authority
            $ilanData = array_merge(
                $step1 ?? [],
                $step2 ?? [],
                $step3 ?? [],
                $step5 ?? [],
            );

            $ilan = $this->ilanCrudService->store($ilanData);

            // ✅ Lock submission token to prevent duplicate
            if ($submissionToken) {
                session([$sessionTokenKey => $submissionToken]);
            }
            session(['wizard_last_fingerprint' => $fingerprint]);

            // Fotoğraf yükleme
            if (!empty($step4['fotolar'])) {
                foreach ($step4['fotolar'] as $index => $path) {
                    $ilan->fotograflar()->create([
                        'dosya_adi' => basename($path),
                        'dosya_yolu' => $path,
                        'display_order' => $index + 1,
                    ]);
                }
            }

            // Yazlık Fiyatlandırma (Periods)
            if (!empty($step2['periods'])) {
                foreach ($step2['periods'] as $period) {
                    \App\Models\YazlikFiyatlandirma::create(array_merge($period, [
                        'ilan_id' => $ilan->id,
                        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::AKTIF
                    ]));
                }
            } elseif (!empty($step2['yazlik_fiyatlandirma_json'])) {
                $periods = json_decode($step2['yazlik_fiyatlandirma_json'], true);
                if (is_array($periods)) {
                    foreach ($periods as $period) {
                        \App\Models\YazlikFiyatlandirma::create([
                            'ilan_id' => $ilan->id,
                            'sezon_tipi' => $period['season_type'] ?? $period['sezon_tipi'] ?? 'low',
                            'baslangic_tarihi' => $period['start_date'] ?? $period['baslangic_tarihi'],
                            'bitis_tarihi' => $period['end_date'] ?? $period['bitis_tarihi'],
                            'gunluk_fiyat' => $period['price'] ?? $period['gunluk_fiyat'],
                            'minimum_konaklama' => $period['min_stay'] ?? $period['minimum_konaklama'] ?? 1,
                            'aktiflik_durumu' => \App\Enums\AktiflikDurumu::AKTIF
                        ]);
                    }
                }
            }

            // Session temizle
            session()->forget(['wizard_step_1', 'wizard_step_2', 'wizard_step_3', 'wizard_step_4', 'wizard_step_5', 'wizard_submission_token', 'wizard_last_fingerprint']);

            return ResponseService::success(['id' => $ilan->id], 'İlan başarıyla oluşturuldu.', 201);
        } catch (\Exception $e) {
            return ResponseService::serverError('İlan oluşturulamadı: ' . $e->getMessage(), $e);
        }
    }

    /**
     * � Quick Selections: Resolver-validated curated category combos
     *
     * Returns only combos that pass EffectiveListingTypeResolver policy.
     * Eliminates phantom slugs and invalid combinations from UI.
     */
    public function quickSelections(): JsonResponse
    {
        $curated = [
            [
                'ana_slug' => 'konut', 'alt_slug' => 'daire', 'yayin_tipi_slug' => 'satilik',
                'label' => 'Satılık Daire', 'icon' => 'fas fa-building', 'color' => 'blue',
            ],
            [
                'ana_slug' => 'konut', 'alt_slug' => 'daire', 'yayin_tipi_slug' => 'kiralik',
                'label' => 'Kiralık Daire', 'icon' => 'fas fa-key', 'color' => 'emerald',
            ],
            [
                'ana_slug' => 'arsa-arazi', 'alt_slug' => 'arsa-konut-villa', 'yayin_tipi_slug' => 'satilik',
                'label' => 'Satılık Arsa', 'icon' => 'fas fa-map-marked-alt', 'color' => 'orange',
            ],
            [
                'ana_slug' => 'konut', 'alt_slug' => 'villa', 'yayin_tipi_slug' => 'satilik',
                'label' => 'Satılık Villa', 'icon' => 'fas fa-home', 'color' => 'indigo',
            ],
            [
                'ana_slug' => 'yazlik-kiralama', 'alt_slug' => 'villa-tipi', 'yayin_tipi_slug' => 'gunluk-kiralama',
                'label' => 'Günlük Villa', 'icon' => 'fas fa-swimming-pool', 'color' => 'rose',
            ],
            [
                'ana_slug' => 'isyeri', 'alt_slug' => 'dukkan', 'yayin_tipi_slug' => 'satilik',
                'label' => 'Satılık Dükkan', 'icon' => 'fas fa-store', 'color' => 'amber',
            ],
        ];

        $result = [];

        foreach ($curated as $item) {
            $ana = IlanKategori::where('slug', $item['ana_slug'])
                ->where('aktiflik_durumu', true)
                ->first();
            $alt = IlanKategori::where('slug', $item['alt_slug'])
                ->where('aktiflik_durumu', true)
                ->first();
            $yt = YayinTipiSablonu::where('slug', $item['yayin_tipi_slug'])
                ->where('aktiflik_durumu', true)
                ->first();

            if (!$ana || !$alt || !$yt) {
                continue; // Skip phantom combos
            }

            if (!$this->listingTypeResolver->isAllowed($ana->id, $alt->id, $yt->id)) {
                continue; // Skip policy-invalid combos
            }

            $result[] = [
                'label' => $item['label'],
                'icon' => $item['icon'],
                'color' => $item['color'],
                'ana_kategori_id' => $ana->id,
                'alt_kategori_id' => $alt->id,
                'yayin_tipi_id' => $yt->id,
                'ana_slug' => $ana->slug,
                'alt_slug' => $alt->slug,
                'yayin_tipi_slug' => $yt->slug,
            ];
        }

        return response()->json(['data' => $result]);
    }

    /**
     * 🏗️ Schema-Driven Field Schema API (Wizard Engine V2)
     *
     * GET /api/v1/wizard/field-schema?kategori_id=5&yayin_tipi_id=2
     *
     * Returns schema contract for dynamic Step 2 rendering.
     * SSOT: KategoriYayinTipiFieldDependency table.
     */
    public function fieldSchema(Request $request): JsonResponse
    {
        $request->validate([
            'kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
        ]);

        $kategoriId = (int) $request->input('kategori_id');
        $yayinTipiId = (int) $request->input('yayin_tipi_id');

        // FieldResolver: DB → FieldDefinition[] → Schema Contract
        $schema = $this->fieldResolver->resolveSchemaContract($kategoriId, $yayinTipiId);

        return response()->json(['data' => $schema]);
    }

    /**
     * 🌳 Category Tree API (Step 1 cascading selection)
     *
     * GET /api/v1/wizard/category-tree
     *
     * Returns cached hierarchical category tree: Ana → Alt → Yayın Tipi
     */
    public function categoryTree(): JsonResponse
    {
        $tree = $this->categoryTreeService->getTree();

        return response()->json(['data' => $tree]);
    }

    /**
     * 💾 Atomic Field Update (Auto-save)
     *
     * POST /api/v1/wizard/update-field
     * Payload: { draft_id, field, value }
     */
    public function updateField(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_id' => 'required|integer|exists:ilan_taslaklar,id',
            'field' => 'required|string|max:100',
            'value' => 'nullable',
        ]);

        try {
            $draft = $this->draftService->updateField(
                (int) $validated['draft_id'],
                $validated['field'],
                $validated['value']
            );

            // Phase 4.7: Hybrid Success Response
            // Veri kaydedildi, ancak içinde validation hataları olabilir.
            $validation = $draft->payload['_validation'] ?? ['is_valid' => true];

            return ResponseService::success([
                'draft_id' => $draft->id,
                'version' => $draft->version,
                'payload' => $draft->payload,
                'validation' => $validation
            ], $validation['is_valid'] ? 'Alan başarıyla kaydedildi.' : 'Alan kaydedildi (Doğrulama uyarısı var).');

        } catch (\Exception $e) {
            Log::error('Wizard updateField failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ResponseService::error('Alan kaydedilirken bir hata oluştu.', 500);
        }
    }

    /**
     * 📂 Load Draft
     *
     * GET /api/v1/wizard/draft/{id}
     */
    public function getDraft(int $id): JsonResponse
    {
        try {
            $draft = $this->draftService->getDraft($id);
            
            // Check ownership
            if ($draft->user_id !== auth()->id()) {
                return ResponseService::error('Bu taslağa erişim yetkiniz yok.', 403);
            }

            return ResponseService::success($draft, 'Taslak yüklendi.');
        } catch (\Exception $e) {
            return ResponseService::error('Taslak bulunamadı.', 404);
        }
    }

    /**
     * 🤖 AI Assistant: Get field suggestions for Step 2 based on title/description.
     */
    public function getAiSuggestions(int $id): JsonResponse
    {
        try {
            $draft = $this->draftService->getDraft($id);
            
            // Security: Check ownership
            if ($draft->user_id !== (auth()->id() ?? 0) && !auth()->user()?->hasRole('admin')) {
                return ResponseService::error('Bu taslağa erişim yetkiniz yok.', 403);
            }

            $suggestions = $this->aiAssistant->getSuggestions($draft);

            return ResponseService::success([
                'suggestions' => $suggestions
            ], 'AI önerileri başarıyla getirildi.');

        } catch (\Exception $e) {
            return ResponseService::error('AI önerileri alınırken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 🔍 Koordinatları Doğrula (Türkiye sınırları)
     */
    protected function validateCoordinates(float $lat, float $lng): bool
    {
        return $lat >= 36.1 && $lat <= 42.1 && $lng >= 26.1 && $lng <= 44.8;
    }
}
