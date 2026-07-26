<?php

declare(strict_types=1);

namespace App\Application\Listing\Commands;

/**
 * SubmitIlanWizardCommand
 *
 * Sprint 12C Wave 2: IlanWizardController migration
 *
 * Application-layer command object for wizard submission.
 * HTTP-independent — does not accept Request or Session.
 *
 * All wizard step data is provided as structured arrays.
 * Actor identification is explicit and server-side.
 */
final readonly class SubmitIlanWizardCommand
{
    /**
     * @param array{
     *   kategori_id: int,
     *   ana_kategori_id: int|null,
     *   alt_kategori_id: int|null,
     *   yayin_tipi_id: int|null,
     *   proje_id: int|null,
     *   baslik: string,
     *   aciklama: string,
     *   fiyat: float|int
     * } $step1
     * @param array{
     *   features?: array<string, mixed>,
     *   periods?: array<array{
     *     sezon_tipi: string,
     *     baslangic_tarihi: string,
     *     bitis_tarihi: string,
     *     gunluk_fiyat: float|int,
     *     minimum_konaklama: int
     *   }>,
     *   yazlik_fiyatlandirma_json?: string
     * } $step2
     * @param array{
     *   il_id: int,
     *   ilce_id: int,
     *   mahalle_id: int|null,
     *   adres: string,
     *   lat: float,
     *   lng: float
     * } $step3
     * @param array{
     *   fotolar?: array<string>,
     *   video_url?: string|null
     * } $step4
     * @param array{
     *   yayin_durumu: string,
     *   premium_ilan?: bool,
     *   one_cikan?: bool,
     *   lansman_fiyati?: float|int|null,
     *   lansman_bitis_tarihi?: string|null,
     *   lansman_kotasi?: int|null
     * } $step5
     */
    public function __construct(
        public int $actorId,
        public ?int $workspaceId,
        public array $step1,
        public array $step2,
        public array $step3,
        public array $step4,
        public array $step5,
        public ?string $submissionToken = null,
    ) {}

    /**
     * Build canonical payload for ListingCrudBridge::store()
     *
     * Merges all wizard steps into a single payload.
     * Includes photos and seasonal pricing for atomic persistence.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = array_merge(
            $this->step1,
            $this->step2,
            $this->step3,
            $this->step5,
        );

        // Add actor identification (server-side, not from client)
        $payload['user_id'] = $this->actorId;

        // Add photos for IlanCrudService::uploadPhotos()
        if (!empty($this->step4['fotolar'])) {
            $payload['fotograflar'] = $this->step4['fotolar'];
        }

        // Add seasonal pricing for IlanCrudService
        if (!empty($this->step2['periods'])) {
            $payload['periods'] = $this->step2['periods'];
        } elseif (!empty($this->step2['yazlik_fiyatlandirma_json'])) {
            $payload['yazlik_fiyatlandirma_json'] = $this->step2['yazlik_fiyatlandirma_json'];
        }

        // Add location data
        $payload['il_id'] = $this->step3['il_id'];
        $payload['ilce_id'] = $this->step3['ilce_id'];
        if (!empty($this->step3['mahalle_id'])) {
            $payload['mahalle_id'] = $this->step3['mahalle_id'];
        }
        $payload['adres'] = $this->step3['adres'];
        $payload['lat'] = $this->step3['lat'];
        $payload['lng'] = $this->step3['lng'];

        return $payload;
    }

    /**
     * Get category IDs for policy validation
     */
    public function getCategoryIds(): array
    {
        $kategoriId = (int) ($this->step1['kategori_id'] ?? 0);
        $anaKategoriId = !empty($this->step1['ana_kategori_id'])
            ? (int) $this->step1['ana_kategori_id']
            : null;
        $altKategoriId = !empty($this->step1['alt_kategori_id'])
            ? (int) $this->step1['alt_kategori_id']
            : $kategoriId;

        $mainCatId = $anaKategoriId ?? $altKategoriId;
        $subCatId = $anaKategoriId ? $altKategoriId : null;

        return [
            'main' => $mainCatId,
            'sub' => $subCatId,
        ];
    }

    /**
     * Get publication type ID
     */
    public function getYayinTipiId(): ?int
    {
        return !empty($this->step1['yayin_tipi_id'])
            ? (int) $this->step1['yayin_tipi_id']
            : null;
    }

    /**
     * Generate content fingerprint for idempotency
     */
    public function getFingerprint(): string
    {
        return md5(json_encode([$this->step1, $this->step3]));
    }
}
