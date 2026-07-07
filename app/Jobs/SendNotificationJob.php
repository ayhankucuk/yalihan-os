<?php

namespace App\Jobs;

use App\Contracts\Notification\NotificationContract;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * N1-B: Async Notification Delivery Job
 */
class SendNotificationJob implements ShouldQueue, \App\Queue\Contracts\TenantAwareJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public ?int $tenantId;
    public ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected NotificationContract $notification,
        protected int $auditId,
        ?int $tenantId = null,
        ?int $userId = null
    ) {
        $this->tenantId = $tenantId ?? (app(\App\Services\SaaS\TenantContextService::class)->hasTenant() 
            ? app(\App\Services\SaaS\TenantContextService::class)->getTenant()->id 
            : null);
        $this->userId = $userId ?? auth()->id();
        $this->onQueue('notifications');
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function middleware(): array
    {
        return [new \App\Queue\Middleware\RestoreTenantContext(app(\App\Services\SaaS\TenantContextService::class))];
    }

    /**
     * Execute the job.
     */
    public function handle(
        \App\Services\Notification\NotificationDispatcher $dispatcher,
        \App\Services\Notification\NotificationRetryService $retryService
    ): void {
        $audit = \App\Models\Notification\OutboundNotification::find($this->auditId);
        
        if (!$audit || !$retryService->canRetry($audit)) {
            return;
        }

        // 1. Mark as processing (N2: Atomic state guard)
        $retryService->markAsProcessing($audit);

        // 2. Dispatch
        $success = $dispatcher->routeToAdapter($this->notification, $this->auditId);

        if (!$success) {
            // 3. Schedule retry if within limits
            if ($this->attempts() < $this->tries) {
                $retryService->scheduleRetry($audit, "Adapter returned failure.");
                $this->release($this->backoff);
            } else {
                $retryService->markAsFailed($audit, "Max attempts reached.");
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $audit = \App\Models\Notification\OutboundNotification::find($this->auditId);
        if ($audit) {
            app(\App\Services\Notification\NotificationRetryService::class)->markAsFailed(
                $audit, 
                "System Failure: " . $exception->getMessage()
            );
        }
    }
}
