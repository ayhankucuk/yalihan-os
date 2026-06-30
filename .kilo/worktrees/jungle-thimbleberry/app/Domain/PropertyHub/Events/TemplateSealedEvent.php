<?php

namespace App\Domain\PropertyHub\Events;

use App\Models\UpsTemplate;
use App\Models\YayinTipiSablonu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TemplateSealedEvent
 *
 * [SAB ENFORCEMENT]: Domain Event
 * Template muhurlendikten sonra dispatch edilir.
 * Bu event'i dinleyen Listener'lar:
 * - Cache invalidation
 * - TemplateChangeLog kaydi
 * - Cortex bildirim (gelecekte)
 */
class TemplateSealedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UpsTemplate $template,
        public readonly YayinTipiSablonu $junction,
        public readonly ?int $userId = null
    ) {}
}
