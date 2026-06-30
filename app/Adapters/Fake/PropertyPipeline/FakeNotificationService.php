<?php

declare(strict_types=1);

namespace App\Adapters\Fake\PropertyPipeline;

use App\Contracts\PropertyPipeline\NotificationInterface;
use Illuminate\Support\Facades\Log;

/**
 * FakeNotificationService — P01 Sprint 4.1
 *
 * No real notifications. Logs pipeline events.
 */
class FakeNotificationService implements NotificationInterface
{
    public function send(int $ilanId, string $status, array $detail = []): void
    {
        Log::channel('info')->info('Pipeline notification', [
            'ilan_id' => $ilanId,
            'status' => $status,
            'detail' => $detail,
            'fake' => true,
        ]);
    }
}
