<?php

namespace App\Providers;

use App\Contracts\Governance\AuditLoggerInterface;
use App\Services\Governance\EloquentGovernanceAuditLogger;
use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Services\Governance\Telemetry\LogTelemetryPublisher;
use App\Contracts\Governance\GovernanceReadServiceInterface;
use App\Services\Governance\Diff\GovernanceReadService;
use Illuminate\Support\ServiceProvider;

final class GovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuditLoggerInterface::class,
            EloquentGovernanceAuditLogger::class
        );

        $this->app->bind(
            TelemetryPublisherInterface::class,
            LogTelemetryPublisher::class
        );

        $this->app->bind(
            GovernanceReadServiceInterface::class,
            GovernanceReadService::class
        );
    }
}
