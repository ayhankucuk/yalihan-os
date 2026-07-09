<?php

namespace App\Contracts\Publishing;

use App\Contracts\Publishing\ChannelAdapterContract;
use App\DTOs\Publishing\ChannelPayloadDTO;
use App\DTOs\Publishing\PublishingDecisionDTO;

/**
 * Channel Adapter Contract — Sprint 6.5
 *
 * Tüm kanal adapterları bu interface'i implemente eder.
 *
 * Mimari kurallar:
 *   1. Adapter sadece dönüştürür — iş mantığı YALNIZCA PublishingIntelligenceOrchestrator'da olur.
 *   2. TenantScope her zaman aktif — adapter WITHOUT SCOPE yazmaz.
 *   3. Adapter hata fırlatmaz — ChannelPayloadDTO->errors'a yazar.
 *   4. Real API çağrısı YAZILMAZ.
 */
interface ChannelAdapterContract
{
    /**
     * Adapter adını döner.
     */
    public function name(): string;

    /**
     * Bu ilan bu kanala uygun mu?
     */
    public function supports(\App\Models\Ilan $ilan): bool;

    /**
     * Ilan + Vision verilerini kanal formatına dönüştürür.
     *
     * @param  \App\Models\Ilan  $ilan  TenantScope korunur (query zaten scoped)
     * @param  array  $visionData  vision_media JSON decode — AI Vision çıktısı
     * @param  PublishingDecisionDTO|null  $decision  Publish kararı (nullable, karar yoksa kabul et)
     * @return ChannelPayloadDTO
     *
     * @rule Adapter sadece transform yapar — iş mantığı YAZILMAZ.
     * @rule Hata fırlatamaz — ChannelPayloadDTO->errors'a yazar.
     */
    public function buildPayload(
        \App\Models\Ilan $ilan,
        array $visionData,
        ?PublishingDecisionDTO $decision = null,
    ): ChannelPayloadDTO;

    /**
     * Minimal validation — channel için zorunlu alanlar var mı?
     *
     * @return string[]  Boşsa = valid, değilse = eksik alan etiketleri
     */
    public function requiredFields(): array;

    /**
     * Validate — buildPayload çağrısından önce kontrol.
     *
     * @return string[]  Eksik alan etiketleri
     */
    public function validate(\App\Models\Ilan $ilan): array;
}
