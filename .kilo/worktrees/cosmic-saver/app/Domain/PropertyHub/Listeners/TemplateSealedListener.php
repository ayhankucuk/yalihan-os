<?php

namespace App\Domain\PropertyHub\Listeners;

use App\Domain\PropertyHub\Events\TemplateSealedEvent;
use App\Models\TemplateChangeLog;
use App\Services\Ups\UpsCacheService;

/**
 * TemplateSealedListener
 *
 * [SAB ENFORCEMENT]: Event-Driven Cache Invalidation
 * Template muhurlendikten sonra:
 * 1. Cache invalidate edilir (UpsCacheService otoritesi)
 * 2. TemplateChangeLog yazilir
 *
 * Prosedural $cacheService->invalidate() cagrisi yerine
 * Domain Event uzerinden decouple edilmis invalidation.
 */
class TemplateSealedListener
{
    public function __construct(
        private UpsCacheService $cacheService
    ) {}

    public function handle(TemplateSealedEvent $event): void
    {
        // 1. Cache Invalidation — tek nokta, tüm cache katmanlarını kapsar:
        //    ups:templates, ups:assignments, ups:stats, ups:resolver, ups:feature_grouped
        $this->cacheService->invalidateForJunction(
            junctionId: $event->junction->id,
            kategoriId: $event->template->kategori_id,
            yayinTipiId: $event->template->yayin_tipi_id
        );

        // 2. Change Log (Audit Trail — V2: yayin_tipi_sablonu_id)
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'ups_template_apply',
            'yayin_tipi_sablonu_id' => $event->junction->id,
            'ups_template_id' => $event->template->id,
            'entity_type' => get_class($event->template),
            'entity_id' => $event->template->id,
            'aciklama' => "Yeni UPS Sablonu Uygulandi (v{$event->template->template_version})",
            'user_id' => $event->userId,
            'yeni_degerler' => [
                'hash' => $event->template->template_hash,
                'version' => $event->template->template_version,
            ],
        ]);
    }
}
