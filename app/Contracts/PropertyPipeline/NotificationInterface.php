<?php

declare(strict_types=1);

namespace App\Contracts\PropertyPipeline;

/**
 * NotificationInterface — P01 Property Pipeline (Sprint 4.1)
 *
 * Port: Notification / Fake implementation.
 * Pipeline notification port.
 */
interface NotificationInterface
{
    /**
     * Send a pipeline completion notification.
     *
     * @param int $ilanId
     * @param string $status success|failure|pending
     * @param array $detail
     */
    public function send(int $ilanId, string $status, array $detail = []): void;
}
