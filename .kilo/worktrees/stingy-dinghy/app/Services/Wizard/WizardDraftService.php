<?php

namespace App\Services\Wizard;

use App\Models\IlanKategori;
use App\Models\IlanTaslak;
use App\Models\YayinTipi;
use App\Services\Wizard\FieldEngine\DependencyEvaluator;
use App\Services\Wizard\FieldEngine\FieldSchemaResolver;
use App\Services\Wizard\FieldEngine\SchemaValidationRuleGenerator;
use App\Services\Wizard\Validation\WizardValidationService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * WizardDraftService — Production-Level State Engine
 *
 * Şema tabanlı bağımlılıkları yöneten, otomatik veri temizleyen
 * ve atomik validasyon yapan merkezi sihirbaz servisi.
 */
class WizardDraftService
{
    use GuardsAgentWrites;
    public function __construct(
        protected FieldSchemaResolver $fieldSchemaResolver,
        protected DependencyEvaluator $dependencyEvaluator,
        protected WizardValidationService $validationService,
    ) {}

    /**
     * Yeni bir ilan taslağı oluşturur.
     */
    public function createDraft(int $userId, int $categoryId, ?int $yayinTipiId = null): IlanTaslak
    {
        $this->blockAgentWrite('createDraft');

        return IlanTaslak::create([
            'user_id'       => $userId,
            'category_id'   => $categoryId,
            'yayin_tipi_id' => $yayinTipiId,
            'payload'       => [],
            'taslak_durumu' => 0, // Incomplete
            'version'       => 1
        ]);
    }

    /**
     * Mevcut taslağı getirir.
     */
    public function getDraft(int $draftId): IlanTaslak
    {
        return IlanTaslak::findOrFail($draftId);
    }

    /**
     * Taslak üzerindeki tek bir alanı günceller (Auto-save).
     *
     * @throws ValidationException
     */
    public function updateField(int $draftId, string $field, mixed $value): IlanTaslak
    {
        $this->blockAgentWrite('updateField');

        return DB::transaction(function () use ($draftId, $field, $value) {
            $draft = IlanTaslak::lockForUpdate()->findOrFail($draftId);

            // 1. Veriyi hazırla (Alias 'data' -> 'payload' eşleşmesini kullanır)
            $data = $draft->data ?? [];
            Arr::set($data, $field, $value);

            // 2. Şemayı çöz
            $categorySlug = $this->getCategorySlug($draft->category_id);
            $yayinTipiSlug = $this->getYayinTipiSlug($draft->yayin_tipi_id);

            $fieldDefinitions = $this->fieldSchemaResolver->resolveByContext($categorySlug, $yayinTipiSlug);

            // 3. Görünürlük Temizliği (Cleanup)
            // Eğer bir seçim başka alanları gizlediyse, o alanların verilerini otomatik siler.
            $data = $this->dependencyEvaluator->applyVisibilityCleanup($fieldDefinitions, $data);

            // 4. Hibrit Validasyon (Non-blocking)
            // Veri her durumda kaydedilir (UX), ancak geçerlilik durumu takip edilir.
            $validationResult = $this->validationService->check($fieldDefinitions, $data);

            // Doğrulama durumunu payload içinde sakla
            $data['_validation'] = $validationResult->toArray();

            // 5. Kaydet ve Versiyon Yükselt
            $draft->update([
                'payload' => $data,
                'version' => $draft->version + 1
            ]);

            return $draft->fresh();
        });
    }

    /**
     * Mevcut bir adımı valide eder.
     */
    public function validateStep(int $draftId, int $step): bool
    {
        $draft = $this->getDraft($draftId);

        $categorySlug = $this->getCategorySlug($draft->category_id);
        $yayinTipiSlug = $this->getYayinTipiSlug($draft->yayin_tipi_id);

        $fieldDefinitions = $this->fieldSchemaResolver->resolveByContext($categorySlug, $yayinTipiSlug);

        $this->validationService->validate($fieldDefinitions, $draft->data ?? []);

        return true;
    }

    /**
     * ID'den kategori slug'ını çözer.
     */
    protected function getCategorySlug(int $categoryId): string
    {
        return cache()->remember("cat_slug_{$categoryId}", 3600, function () use ($categoryId) {
            return IlanKategori::where('id', $categoryId)->value('slug') ?? 'unknown';
        });
    }

    /**
     * ID'den yayın tipi slug'ını çözer.
     */
    protected function getYayinTipiSlug(?int $yayinTipiId): string
    {
        if (!$yayinTipiId) return 'unknown';

        return cache()->remember("yt_slug_{$yayinTipiId}", 3600, function () use ($yayinTipiId) {
            return YayinTipi::where('id', $yayinTipiId)->value('slug') ?? 'unknown';
        });
    }
}
