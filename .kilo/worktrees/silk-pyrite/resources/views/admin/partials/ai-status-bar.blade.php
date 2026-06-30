{{-- SAB5+SAB6: Global AI Status Bar — included in admin layout between header and main --}}
@php
    $aiStatus = app(\App\Services\Intelligence\OperatorIntelligenceService::class)->getSystemStatus();
    $autonomyService = app(\App\Services\Intelligence\AutonomyService::class);
    $aiPaused = $autonomyService->isSystemPaused();
    $aiAutonomyLevel = $autonomyService->getAutonomyLevel();
    $aiDryRun = config('governance.dry_run', false);
    $riskConfig = match($aiStatus['risk_level']) {
        'high' => ['bg-red-600 dark:bg-red-700', 'text-white', 'bg-red-500/20', '🔴 Yüksek Risk'],
        'medium' => ['bg-yellow-500 dark:bg-yellow-600', 'text-white', 'bg-yellow-400/20', '🟡 Dikkat'],
        'safe_mode' => ['bg-blue-600 dark:bg-blue-700', 'text-white', 'bg-blue-500/20', '🔵 Güvenli Mod'],
        default => ['bg-green-600 dark:bg-green-700', 'text-white', 'bg-green-500/20', '🟢 Düşük'],
    };
@endphp
<a href="{{ route('admin.governance.intelligence-center') }}"
   class="block {{ $riskConfig[0] }} transition-colors hover:brightness-110">
    <div class="flex items-center justify-between px-4 py-1.5 text-xs font-medium {{ $riskConfig[1] }}">
        <div class="flex items-center gap-4">
            <span class="font-semibold tracking-wide uppercase">AI Sistem</span>
            <span class="flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    @if($aiStatus['watcher_status'] !== 'idle')
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                    @endif
                    <span class="relative inline-flex h-2 w-2 rounded-full {{ $aiStatus['watcher_status'] === 'idle' ? 'bg-gray-300' : 'bg-white' }}"></span>
                </span>
                Watcher: {{ $aiStatus['watcher_status'] === 'running' ? 'Aktif' : ($aiStatus['watcher_status'] === 'idle' ? 'Beklemede' : ucfirst($aiStatus['watcher_status'])) }}
            </span>
            <span>Agent: {{ $aiStatus['active_agents'] }}/5</span>
        </div>

        <div class="flex items-center gap-4">
            @if($aiStatus['pending_decisions'] > 0)
                <span class="rounded-full {{ $riskConfig[2] }} px-2 py-0.5">
                    Bekleyen: {{ $aiStatus['pending_decisions'] }}
                </span>
            @endif
            <span>Bugün Oto: {{ $aiStatus['auto_run_today'] }}</span>
            @if($aiStatus['blocked_count'] > 0)
                <span class="rounded-full bg-white/20 px-2 py-0.5">Engellenen: {{ $aiStatus['blocked_count'] }}</span>
            @endif
            @if($aiStatus['rollbacks_today'] > 0)
                <span class="rounded-full bg-white/20 px-2 py-0.5">Geri Alınan: {{ $aiStatus['rollbacks_today'] }}</span>
            @endif
            <span class="rounded-full {{ $riskConfig[2] }} px-2 py-0.5 font-semibold">{{ $riskConfig[3] }}</span>
            @if($aiPaused)
                <a href="{{ route('admin.governance.autonomy-panel') }}" class="rounded-full bg-red-300/40 px-2 py-0.5 font-bold uppercase tracking-wider animate-pulse">🛑 DURDURULDU</a>
            @endif
            @if($aiDryRun)
                <span class="rounded-full bg-amber-300/30 px-2 py-0.5 font-semibold">🧪 Simülasyon</span>
            @endif
            <a href="{{ route('admin.governance.autonomy-panel') }}" class="rounded-full bg-white/10 px-2 py-0.5">L{{ $aiAutonomyLevel }}</a>
            @if($aiStatus['safe_mode'])
                <span class="rounded-full bg-white/30 px-2 py-0.5 font-bold uppercase tracking-wider">Güvenli Mod</span>
            @endif
        </div>
    </div>
</a>
