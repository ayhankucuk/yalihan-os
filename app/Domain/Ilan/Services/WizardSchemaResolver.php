<?php

namespace App\Domain\Ilan\Services;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Wizard\EffectiveWizardSchemaResolver;
use App\Services\Wizard\WizardContextService;
use Illuminate\Support\Facades\Log;

/**
 * 📐 WizardSchemaResolver
 *
 * Sorumluluk: Sihirbaz adımlarının dinamik alan şemalarını, kategori ve şablon
 * hiyerarşisine göre tek bir kaynaktan deterministik biçimde çözer.
 */
class WizardSchemaResolver
{
    public function __construct(
        private readonly EffectiveWizardSchemaResolver $effectiveSchemaResolver,
        private readonly WizardContextService $contextService
    ) {}

    /**
     * Adım 2 (Özellikler) dinamik alan şemasını çözer.
     */
    public function resolveStep2Schema(int $kategoriId, int $yayinTipiId, ?int $ilanId = null): array
    {
        return $this->effectiveSchemaResolver->resolve($kategoriId, $yayinTipiId, $ilanId);
    }

    /**
     * Tüm sihirbaz bağlamını (Kategori + Şablon + Gruplu Özellikler) çözer.
     */
    public function resolveFullContext(int $kategoriId, int $yayinTipiId, array $options = []): array
    {
        return $this->contextService->resolve($kategoriId, $yayinTipiId, $options);
    }

    /**
     * Belirli bir adım için geçerli alanları ve zorunluluk kurallarını döndürür.
     */
    public function resolveStepDefinition(int $stepNumber, int $kategoriId, int $yayinTipiId): array
    {
        $kategori = IlanKategori::find($kategoriId);
        $sablon = YayinTipiSablonu::find($yayinTipiId);

        return [
            'step' => $stepNumber,
            'kategori_id' => $kategoriId,
            'kategori_adi' => $kategori?->name ?? $kategori?->kategori_adi ?? '',
            'yayin_tipi_id' => $yayinTipiId,
            'sablon_adi' => $sablon?->ad ?? '',
            'is_dynamic' => ($stepNumber === 2),
        ];
    }
}
