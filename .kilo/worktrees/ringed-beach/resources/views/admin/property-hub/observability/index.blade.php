@extends('admin.layouts.admin')

@section('content')
<div x-data="governanceObserver" class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-12 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-black tracking-tight dark:text-slate-100">Governance <span class="text-blue-600">Control Room</span></h1>
                <p class="text-slate-500 mt-2">Observability layer for State Machine, Drift, and Security Incidents.</p>
            </div>
            <div class="flex gap-4">
                <div class="px-6 py-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-bold uppercase text-slate-400 block">System State</span>
                    <span class="text-emerald-500 font-bold flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        PROTECTED
                    </span>
                </div>
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- D1: Governance Timeline (Audit Logs) -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h2 class="text-xl font-bold dark:text-slate-100">Audit Timeline (D1)</h2>
                        <i class="fas fa-history text-slate-400"></i>
                    </div>
                    <div class="p-0 overflow-y-auto max-h-[600px] custom-scrollbar">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-8 py-4 text-left text-slate-500 font-bold uppercase tracking-wider">Event</th>
                                    <th class="px-8 py-4 text-left text-slate-500 font-bold uppercase tracking-wider">User</th>
                                    <th class="px-8 py-4 text-left text-slate-500 font-bold uppercase tracking-wider">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="log in timeline" :key="log.id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-8 py-4">
                                            <div class="font-bold dark:text-slate-100" x-text="log.islem_tipi"></div>
                                            <div class="text-xs text-slate-500" x-text="log.ek_bilgiler ? JSON.parse(log.ek_bilgiler).description : ''"></div>
                                        </td>
                                        <td class="px-8 py-4" x-text="log.kullanici_adi || 'System'"></td>
                                        <td class="px-8 py-4 text-slate-500" x-text="new Date(log.olusturma_tarihi).toLocaleString()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- D2: Drift Telemetry (Visualized) placeholder -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
                     <h2 class="text-xl font-bold dark:text-slate-100 mb-6">Drift Telemetry (D2)</h2>
                     <div class="h-48 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center">
                        <i class="fas fa-chart-line text-4xl text-slate-300 dark:text-slate-700 mb-4"></i>
                        <p class="text-slate-500 text-sm">Real-time drift chart integration pending.</p>
                     </div>
                </div>
            </div>

            <!-- D3 Side Panel: Security Incidents & Health Summary -->
            <div class="space-y-8">
                <!-- Incidents Panel -->
                <div class="bg-slate-900 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
                    <div class="px-8 py-6 border-b border-slate-800 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white">Security Threats (D3)</h2>
                        <span class="px-2 py-1 bg-rose-500 text-white text-[10px] font-black rounded uppercase">Live</span>
                    </div>
                    <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto">
                        <template x-if="incidents.length === 0">
                            <div class="text-center py-12">
                                <i class="fas fa-shield-check text-emerald-500 text-4xl mb-4"></i>
                                <p class="text-slate-400">No security incidents detected.</p>
                            </div>
                        </template>
                        <template x-for="incident in incidents" :key="incident.id">
                            <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-rose-400 font-bold text-xs" x-text="incident.severity"></span>
                                    <span class="text-slate-500 text-[10px]" x-text="new Date(incident.olusturma_tarihi).toLocaleTimeString()"></span>
                                </div>
                                <p class="text-sm text-slate-300" x-text="incident.reason"></p>
                                <div class="mt-2 text-[10px] font-mono text-slate-500" x-text="incident.version_hash"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Quick Actions / Health -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
                     <h3 class="font-bold dark:text-slate-100 mb-4">Governance Actions</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <a href="{{ route('admin.property-hub.versions.index') }}" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                           <div class="flex items-center gap-3">
                               <i class="fas fa-layer-group text-blue-500"></i>
                               <span class="text-sm font-bold">Manage Versions</span>
                           </div>
                           <i class="fas fa-chevron-right text-slate-300 group-hover:text-blue-500 transition-colors"></i>
                        </a>
                        <a href="{{ route('admin.ups.health') }}" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                           <div class="flex items-center gap-3">
                               <i class="fas fa-heartbeat text-emerald-500"></i>
                               <span class="text-sm font-bold">Health Matrix</span>
                           </div>
                           <i class="fas fa-chevron-right text-slate-300 group-hover:text-emerald-500 transition-colors"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('governanceObserver', () => ({
            timeline: [],
            drift: [],
            incidents: [],
            async init() {
                this.fetchData();
                setInterval(() => this.fetchData(), 30000); // 30s auto-refresh
            },
            async fetchData() {
                try {
                    const [tRes, dRes, iRes] = await Promise.all([
                        fetch('{{ route("admin.property-hub.versions.observability.timeline") }}'),
                        fetch('{{ route("admin.property-hub.versions.observability.drift") }}'),
                        fetch('{{ route("admin.property-hub.versions.observability.incidents") }}')
                    ]);

                    const tData = await tRes.json();
                    const dData = await dRes.json();
                    const iData = await iRes.json();

                    if (tData.success) this.timeline = tData.data;
                    if (dData.success) this.drift = dData.data;
                    if (iData.success) this.incidents = iData.data;
                } catch (e) {
                    console.error('Governance fetch failure:', e);
                }
            }
        }))
    })
</script>

@endsection
