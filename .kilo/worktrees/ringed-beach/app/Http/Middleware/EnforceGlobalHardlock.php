<?php

namespace App\Http\Middleware;

use App\Domain\Core\Security\GlobalHardlockManagerContract;
use App\Domain\Core\Security\GlobalHardlockRegistryContract;
use App\Exceptions\Governance\GlobalHardlockException;
use Closure;
use Illuminate\Http\Request;

/**
 * Class EnforceGlobalHardlock
 * @package App\Http\Middleware
 * @description Phase 20: Her istekte çekirdek bütünlüğünü denetleyen ve kilitli durumdaki kiracıyı/sistemi bloke eden anayasal middleware.
 */
class EnforceGlobalHardlock
{
    /**
     * EnforceGlobalHardlock constructor.
     */
    public function __construct(
        private readonly GlobalHardlockManagerContract $hardlockManager,
        private readonly GlobalHardlockRegistryContract $hardlockRegistry
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws GlobalHardlockException
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Her istekte çekirdek dosya bütünlüğünü doğrula (Self-Defensive Integrity Check)
        $this->hardlockRegistry->verifySystemIntegrity();

        // 2. Sistem genelinde veya aktif kiracı bağlamında hardlock aktif mi denetle
        $tenantId = $request->input('tenant_id') ?? ($request->user()?->tenant_id ?? null);

        if ($this->hardlockManager->isHardlocked($tenantId ? (int)$tenantId : null)) {
            // Yönetici kurtarma yollarını muaf tut
            if ($request->is('admin/governance*') || $request->is('governance/recover*')) {
                return $next($request);
            }
            throw new GlobalHardlockException("🚨 SAB SYSTEM HARDLOCK: The platform is currently locked down due to an integrity/security incident.");
        }

        return $next($request);
    }
}
