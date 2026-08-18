<?php

namespace App\Jobs\Reservation;

use App\Services\Reservation\AccessCredentialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * ResetAccessCredentialJob — Wave 2: Cleanup expired access credentials.
 *
 * Scheduled: Daily (Kernel schedules this job)
 *
 * Logic:
 * 1. Find all active credentials where expires_at < now()
 * 2. Mark them as inactive + requires_reset = true
 * 3. Log the count of cleaned credentials
 *
 * INV-W2-S3: Credentials expire after checkout + 24h
 *
 * Queue-safe: idempotent, no tenant issues (cleanup is stateless).
 *
 * CHECKIN_CHECKOUT Wave 2
 */
class ResetAccessCredentialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function handle(AccessCredentialService $credentialService): void
    {
        Log::info('ResetAccessCredentialJob: starting');

        $count = $credentialService->cleanupExpiredCredentials();

        Log::info('ResetAccessCredentialJob: completed', [
            'expired_credentials_cleaned' => $count,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ResetAccessCredentialJob: all retries exhausted', [
            'error' => $exception->getMessage(),
        ]);
    }
}
