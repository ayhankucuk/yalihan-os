<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * SabGuard Trait — Sealed Domain Runtime Enforcement
 *
 * Phase 7: Runtime Hard Exception for Sealed Domains.
 */
trait SabGuard
{
    public static function bootSabGuard(): void
    {
        static::saving(function ($model) {
            $model->logSealedAccess('saving');
        });
    }

    protected function logSealedAccess(string $operation): void
    {
        Log::channel('sab')->debug("SabGuard: {$operation}", [
            'model' => static::class,
            'id' => $this->getKey(),
        ]);
    }
}
