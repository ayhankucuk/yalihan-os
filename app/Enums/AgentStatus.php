<?php

namespace App\Enums;

/**
 * AgentStatus — AI Workforce ajan sonuç durumları.
 *
 * Sprint 7.2 — AI Workforce Foundation
 */
enum AgentStatus: string
{
    case SUCCESS        = 'success';
    case FAILED         = 'failed';
    case PARTIAL_SUCCESS = 'partial_success';
    case RUNNING        = 'running';
    case PENDING        = 'pending';

    public function label(): string
    {
        return match($this) {
            self::SUCCESS         => 'Başarılı',
            self::FAILED           => 'Başarısız',
            self::PARTIAL_SUCCESS  => 'Kısmi Başarı',
            self::RUNNING          => 'Çalışıyor',
            self::PENDING          => 'Bekliyor',
        };
    }
}
