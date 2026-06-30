@extends('admin.layouts.admin')

@section('title', 'AI Command Center')

@push('styles')
    <style>
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .dark .glass-card {
            background: rgba(15, 23, 42, 0.8);
        }

        /* Custom Scrollbar for inner lists */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.3);
            border-radius: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="commandCenter()">

        <!-- Top Header & Filter -->
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    <span class="material-symbols-outlined animate-pulse text-indigo-500">satellite_alt</span>
                    AI Command Center
                </h1>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">Unified AI Sales Intelligence &
                    Advisor Actions</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Quick Filters -->
                <button @click="togglePriorityFilter()" class="rounded-lg border px-4 py-2 text-sm font-bold transition-all"
                    :class="filters.priority_filter === 'today' ?
                        'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400' :
                        'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined mr-1">local_fire_department</span> Today Priority
                </button>

                <button @click="refreshData()"
                    class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700">
                    <span class="material-symbols-outlined">sync</span>
                    <span x-text="loading ? 'Updating...' : 'Refresh Sinyal'"></span>
                </button>
            </div>
        </div>

        <!-- MAIN GRID -->
        <div class="relative grid grid-cols-1 gap-6 lg:grid-cols-12">

            <!-- LOADING OVERLAY -->
            <div x-show="loading && initialLoadDone"
                class="absolute inset-0 z-50 flex items-center justify-center rounded-2xl bg-slate-50/60 backdrop-blur-sm dark:bg-slate-950/60"
                x-cloak>
                <span class="material-symbols-outlined text-indigo-500" style="font-size:3rem">progress_activity</span>
            </div>

            <!-- LEFT COLUMN (KPIs & Unified Action List) -->
            <div class="space-y-6 lg:col-span-4">

                <!-- KPI Strip (Vertical on desktop) -->
                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="glass-card group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="absolute -right-4 -top-4 opacity-10 transition-transform group-hover:scale-110 dark:opacity-5">
                            <span class="material-symbols-outlined text-red-500" style="font-size:5rem">workspace_premium</span>
                        </div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hot
                            Deals</p>
                        <p class="text-3xl font-black text-slate-800 dark:text-slate-100" x-text="kpis.total_hot_deals"></p>
                    </div>
                    <div
                        class="glass-card group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="absolute -right-4 -top-4 opacity-10 transition-transform group-hover:scale-110 dark:opacity-5">
                            <span class="material-symbols-outlined text-yellow-500" style="font-size:5rem">bolt</span>
                        </div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Opportunities</p>
                        <p class="text-3xl font-black text-slate-800 dark:text-slate-100" x-text="kpis.total_opportunities">
                        </p>
                    </div>
                    <div
                        class="glass-card group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="absolute -right-4 -top-4 opacity-10 transition-transform group-hover:scale-110 dark:opacity-5">
                            <span class="material-symbols-outlined text-emerald-500" style="font-size:5rem">how_to_reg</span>
                        </div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">High
                            Intent</p>
                        <p class="text-3xl font-black text-slate-800 dark:text-slate-100" x-text="kpis.high_intent_buyers">
                        </p>
                    </div>
                    <div
                        class="glass-card group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="absolute -right-4 -top-4 opacity-10 transition-transform group-hover:scale-110 dark:opacity-5">
                            <span class="material-symbols-outlined text-orange-500" style="font-size:5rem">warning</span>
                        </div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Port.
                            Issues</p>
                        <p class="text-3xl font-black text-slate-800 dark:text-slate-100"
                            x-text="kpis.critical_portfolio_issues"></p>
                    </div>
                </div>

                <!-- Global Priority Actions Panel -->
                <div
                    class="glass-card flex h-[600px] flex-col rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div
                        class="flex items-center justify-between rounded-t-xl border-b border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                        <h3 class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-100">
                            <span class="material-symbols-outlined text-indigo-500">format_list_numbered</span> Priority Actions
                        </h3>
                        <span
                            class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                            x-text="priorityActions.length"></span>
                    </div>
                    <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto p-4">

                        <div x-show="priorityActions.length === 0" class="py-10 text-center" x-cloak>
                            <span class="material-symbols-outlined mb-3 text-4xl text-slate-300 dark:text-slate-600">done_all</span>
                            <p class="text-sm font-medium text-slate-500">No actions required.</p>
                        </div>

                        <template x-for="act in priorityActions" :key="act.action_source + act.listing_id">
                            <div class="group rounded-lg border p-4 transition-colors hover:border-indigo-300 dark:hover:border-indigo-700"
                                :class="{ 'CRITICAL': 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-900/30', 'HIGH': 'bg-orange-50 dark:bg-orange-900/10 border-orange-100 dark:border-orange-900/30', 'MEDIUM': 'bg-slate-50 dark:bg-slate-800 border-slate-100 dark:border-slate-700', 'LOW': 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-700 opacity-70' }
                                [act.execution_priority]">

                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest"
                                        :class="getPriorityColor(act.execution_priority)"
                                        x-text="act.execution_priority"></span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500"
                                        x-text="formatSource(act.action_source)"></span>
                                </div>

                                <p class="mb-1 line-clamp-2 text-sm font-bold leading-snug text-slate-800 dark:text-slate-200"
                                    x-text="act.title"></p>
                                <p class="mb-3 text-xs text-slate-500 dark:text-slate-400" x-text="act.reason"></p>

                                <div
                                    class="flex items-center justify-between rounded border border-indigo-100 bg-slate-50 p-2 text-xs font-bold text-indigo-700 dark:border-indigo-900/30 dark:bg-slate-900 dark:text-indigo-400">
                                    <span x-text="act.action_label"></span>
                                    <span class="material-symbols-outlined text-[10px] opacity-70">chevron_right</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (Intelligence Panels) -->
            <div class="flex flex-col gap-6 lg:col-span-8">

                <div class="grid h-[400px] grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Deal Radar -->
                    <div
                        class="glass-card flex flex-col rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="rounded-t-xl border-b border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100"><span class="material-symbols-outlined mr-1 text-red-500">radar</span> Deal Radar (Hot Deals)</h3>
                        </div>
                        <div class="custom-scrollbar flex-1 space-y-2 overflow-y-auto p-3">
                            <template x-for="item in data.hot_deals" :key="item.listing_id">
                                <div
                                    class="mb-2 border-b border-slate-100 pb-2 text-sm last:mb-0 last:border-0 last:pb-0 dark:border-slate-800/50">
                                    <div class="flex items-start justify-between">
                                        <span class="truncate font-semibold text-slate-700 dark:text-slate-300"
                                            x-text="item.listing_title"></span>
                                        <span class="ml-2 shrink-0 rounded-md px-2 py-0.5 text-xs font-bold"
                                            :class="getBadgeClass(item.deal_tier)"
                                            x-text="item.deal_tier.replace('_', ' ')"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400" x-text="item.primary_signal"></p>
                                </div>
                            </template>
                            <div x-show="data.hot_deals && data.hot_deals.length === 0"
                                class="py-4 text-center text-xs text-slate-400">Sinyal yok.</div>
                        </div>
                    </div>

                    <!-- Buyer Match Queue -->
                    <div
                        class="glass-card flex flex-col rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="rounded-t-xl border-b border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100"><span class="material-symbols-outlined mr-1 text-emerald-500">group</span> Top Buyer Matches</h3>
                        </div>
                        <div class="custom-scrollbar flex-1 space-y-2 overflow-y-auto p-3">
                            <template x-for="item in data.buyer_matches" :key="item.buyer_id + item.listing_id">
                                <div
                                    class="mb-2 border-b border-slate-100 pb-2 text-sm last:mb-0 last:border-0 last:pb-0 dark:border-slate-800/50">
                                    <div class="flex items-start justify-between">
                                        <span class="truncate font-semibold text-slate-700 dark:text-slate-300"
                                            x-text="item.buyer_name"></span>
                                        <span class="ml-2 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                            :class="getBadgeClass(item.urgency_signal)"
                                            x-text="item.urgency_signal.replace('_', ' ')"></span>
                                    </div>
                                    <p class="truncate text-xs text-slate-500" x-text="item.listing_title"></p>
                                    <p class="mt-1 text-[10px] font-bold text-indigo-500"
                                        x-text="'SC: ' + item.match_score + ' - ' + item.primary_reason"></p>
                                </div>
                            </template>
                            <div x-show="data.buyer_matches && data.buyer_matches.length === 0"
                                class="py-4 text-center text-xs text-slate-400">Sinyal yok.</div>
                        </div>
                    </div>
                </div>

                <div class="grid h-[400px] grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Opportunity Inbox -->
                    <div
                        class="glass-card flex flex-col rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="rounded-t-xl border-b border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100"><span class="material-symbols-outlined mr-1 text-yellow-500">inbox</span> Opportunity Inbox</h3>
                        </div>
                        <div class="custom-scrollbar flex-1 space-y-2 overflow-y-auto p-3">
                            <template x-for="item in data.opportunities" :key="item.id">
                                <div
                                    class="mb-2 border-b border-slate-100 pb-2 text-sm last:mb-0 last:border-0 last:pb-0 dark:border-slate-800/50">
                                    <div class="flex items-start justify-between">
                                        <span class="truncate font-semibold text-slate-700 dark:text-slate-300"
                                            x-text="item.title"></span>
                                        <span
                                            class="ml-2 shrink-0 rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                            x-text="item.opportunity_type"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500" x-text="item.reason"></p>
                                </div>
                            </template>
                            <div x-show="data.opportunities && data.opportunities.length === 0"
                                class="py-4 text-center text-xs text-slate-400">Fırsat yok.</div>
                        </div>
                    </div>

                    <!-- Portfolio Doctor -->
                    <div
                        class="glass-card flex flex-col rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div
                            class="flex items-center justify-between rounded-t-xl border-b border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100"><span class="material-symbols-outlined mr-1 text-blue-500">stethoscope</span> Portfolio Health</h3>
                        </div>
                        <div class="custom-scrollbar flex-1 space-y-2 overflow-y-auto p-3">
                            <template x-for="item in data.portfolio_health" :key="item.listing_id">
                                <div
                                    class="mb-2 flex items-center justify-between border-b border-slate-100 pb-2 text-sm last:mb-0 last:border-0 last:pb-0 dark:border-slate-800/50">
                                    <div class="flex-1 truncate pr-2">
                                        <span class="block truncate font-semibold text-slate-700 dark:text-slate-300"
                                            x-text="item.listing_title"></span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="rounded px-2 py-0.5 text-[10px] font-bold"
                                                :class="getBadgeClass(item.primary_problem)"
                                                x-text="item.primary_problem"></span>
                                            <span class="text-xs text-slate-400"
                                                x-text="item.listing_health_score + '/100'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="data.portfolio_health && data.portfolio_health.length === 0"
                                class="py-4 text-center text-xs text-slate-400">Portföy sağlıklı.</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        function commandCenter() {
            return {
                loading: true,
                initialLoadDone: false,
                filters: {
                    priority_filter: ''
                },
                data: {
                    hot_deals: [],
                    opportunities: [],
                    portfolio_health: [],
                    buyer_matches: []
                },
                kpis: {
                    total_hot_deals: 0,
                    total_opportunities: 0,
                    critical_portfolio_issues: 0,
                    high_intent_buyers: 0,
                    today_priority_actions: 0
                },
                priorityActions: [],

                async init() {
                    await this.fetchData();
                    this.initialLoadDone = true;
                },

                togglePriorityFilter() {
                    this.filters.priority_filter = this.filters.priority_filter === 'today' ? '' : 'today';
                    this.fetchData();
                },

                async refreshData() {
                    await this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const url = new URL('/advisor/command-center/fetch', window.location.origin);
                        if (this.filters.priority_filter) {
                            url.searchParams.append('priority_filter', this.filters.priority_filter);
                        }
                        const res = await fetch(url);
                        const json = await res.json();

                        if (json.success) {
                            this.data = {
                                hot_deals: json.data.hot_deals,
                                opportunities: json.data.opportunities,
                                portfolio_health: json.data.portfolio_health,
                                buyer_matches: json.data.buyer_matches
                            };
                            this.kpis = json.data.kpis;
                            this.priorityActions = json.data.priority_actions;
                        }
                    } catch (e) {
                        console.error('Command Center Error:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                formatSource(source) {
                    return source.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                },

                getPriorityColor(priority) {
                    switch (priority) {
                        case 'CRITICAL':
                            return 'text-red-700 dark:text-red-400';
                        case 'HIGH':
                            return 'text-orange-600 dark:text-orange-400';
                        case 'MEDIUM':
                            return 'text-blue-600 dark:text-blue-400';
                        case 'LOW':
                            return 'text-slate-500 dark:text-slate-400';
                        default:
                            return 'text-slate-500';
                    }
                },

                getBadgeClass(key) {
                    switch (key) {
                        case 'HOT_DEAL':
                        case 'OVERPRICED':
                            return 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300';
                        case 'FAST_MOVING':
                        case 'LOW_VISIBILITY':
                            return 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300';
                        case 'WATCHLIST':
                            return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300';
                        case 'HIGH_INTENT':
                        case 'HEALTHY':
                            return 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400';
                        case 'AT_RISK':
                            return 'bg-rose-100 dark:bg-rose-900 text-rose-700 dark:text-rose-300';
                        case 'LOW_SIGNAL':
                        case 'STALE_LISTING':
                            return 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300';
                        case 'HIGH_DEMAND_LOW_CONVERSION':
                            return 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300';
                        case 'NO_BUYER_MATCH':
                            return 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300';
                        default:
                            // Catch all grey base for unmapped like OPP signals
                            return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
                    }
                }
            }
        }
    </script>
@endsection
