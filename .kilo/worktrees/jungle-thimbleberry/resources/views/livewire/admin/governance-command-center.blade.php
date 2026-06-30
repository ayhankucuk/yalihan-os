<div class="bg-slate-900 min-h-screen p-8 text-slate-100 font-mono" wire:poll.10s>
    <!-- Header: Authority Health Gauge -->
    <div class="flex justify-between items-center border-b border-slate-700 pb-6 mb-8">
        <div>
            <h1 class="text-3xl font-bold tracking-tighter text-blue-400">YALIHAN GCC v2026</h1>
            <p class="text-slate-400 text-sm mt-1 uppercase tracking-widest">Governance Command Center — Secure Inference Monitoring</p>
        </div>
        
        <div class="flex space-x-4 items-center">
            <div class="text-right">
                <span class="text-xs text-slate-500 uppercase block">Active Authority Mode</span>
                <span class="text-lg font-bold {{ $authorityMode === 'forced_sql' ? 'text-red-500' : ($authorityMode === 'forced_ai' ? 'text-green-500' : 'text-blue-500') }}">
                    🛡️ {{ strtoupper($authorityMode) }}
                </span>
            </div>
            <div class="w-12 h-12 rounded-full border-4 {{ $authorityMode === 'forced_sql' ? 'border-red-500' : 'border-blue-500' }} animate-pulse"></div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Stats Card: Savings -->
        <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl overflow-hidden relative">
            <div class="absolute top-0 right-0 p-2 text-slate-700 opacity-20">
                <i class="fas fa-dollar-sign text-8xl"></i>
            </div>
            <h3 class="text-slate-400 text-xs uppercase font-bold mb-2">Savings Tracker (USD)</h3>
            <div class="text-4xl font-bold text-green-400">${{ number_format($stats['savings_usd'], 2) }}</div>
            <p class="text-slate-500 text-xs mt-4 italic">Computed by Adaptive Sampling & Caching</p>
        </div>

        <!-- Stats Card: Drift Rate -->
        <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl">
            <h3 class="text-slate-400 text-xs uppercase font-bold mb-2">Drift Health (24h)</h3>
            <div class="text-4xl font-bold {{ $stats['drift_count'] > 0 ? 'text-yellow-500' : 'text-blue-400' }}">
                {{ $stats['drift_count'] }} / {{ $stats['total_decisions'] }}
            </div>
            <div class="w-full bg-slate-700 h-2 mt-4 rounded-full overflow-hidden">
                <div class="bg-blue-400 h-full" style="width: {{ $stats['total_decisions'] > 0 ? ($stats['drift_count'] / $stats['total_decisions'] * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- Kill-Switch Panel -->
        <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl">
            <h3 class="text-slate-400 text-xs uppercase font-bold mb-4 italic">Authority Override (Kill-Switch)</h3>
            <div class="grid grid-cols-1 gap-2">
                <button wire:click="setAuthorityMode('auto')" class="px-4 py-2 rounded text-xs uppercase font-bold transition-all {{ $authorityMode === 'auto' ? 'bg-blue-600 text-white ring-2 ring-blue-400' : 'bg-slate-700 hover:bg-slate-600' }}">
                    🔵 Auto (Optimized)
                </button>
                <button wire:click="setAuthorityMode('forced_sql')" class="px-4 py-2 rounded text-xs uppercase font-bold transition-all {{ $authorityMode === 'forced_sql' ? 'bg-red-600 text-white ring-2 ring-red-400' : 'bg-slate-700 hover:bg-slate-600' }}">
                    🔴 Emergency Fallback (Forced SQL)
                </button>
                <button wire:click="setAuthorityMode('forced_ai')" class="px-4 py-2 rounded text-xs uppercase font-bold transition-all {{ $authorityMode === 'forced_ai' ? 'bg-green-600 text-white ring-2 ring-green-400' : 'bg-slate-700 hover:bg-slate-600' }}">
                    🟢 Fast-Track (Forced AI)
                </button>
            </div>
        </div>
    </div>

    <!-- Drift Heatmap Section -->
    <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl mb-8">
        <h3 class="text-slate-400 text-xs uppercase font-bold mb-6 flex items-center">
            <span class="mr-2 h-2 w-2 bg-red-500 rounded-full animate-ping"></span>
            Category Drift Heatmap (Model Training Priorities)
        </h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($heatmap as $category => $score)
                <div class="bg-slate-900 p-4 rounded border-l-4 {{ $score > 0.3 ? 'border-red-500' : 'border-blue-500' }}">
                    <div class="text-xs text-slate-500 uppercase mb-1">{{ $category }}</div>
                    <div class="text-xl font-bold {{ $score > 0.3 ? 'text-red-400' : 'text-slate-100' }}">{{ $score * 100 }}% Drift</div>
                    @if($score > 0.3)
                        <div class="text-[10px] text-red-500 mt-2 font-bold blink">RE-TRAINING REQUIRED</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Footer Status Bar -->
    <div class="flex justify-between items-center text-[10px] text-slate-500 uppercase tracking-widest border-t border-slate-800 pt-4">
        <div>System Node: {{ gethostname() }}</div>
        <div class="flex items-center">
            <span class="mr-2 h-1 w-1 bg-green-500 rounded-full"></span>
            Telemetry Stream Active (Live Updates Every 10s)
        </div>
        <div>Last Update: {{ now()->toDateTimeString() }}</div>
    </div>

    <style>
        .blink { animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { visibility: hidden; } }
    </style>
</div>
