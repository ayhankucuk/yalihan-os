@extends('admin.layouts.admin')

@section('title', 'AI Seller Strategy Engine')

@push('styles')
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .dark .glass-card {
            background: rgba(2, 6, 23, 0.8);
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="sellerStrategy()">
        <!-- Header -->
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    <span class="material-symbols-outlined text-indigo-500">trending_up</span>
                    AI Seller Strategy
                </h1>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">Data-driven pricing intelligence &
                    sale
                    velocity optimization</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="fetchData()"
                    class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700">
                    <span class="material-symbols-outlined">sync</span>
                    <span x-text="loading ? 'Analiz Ediliyor...' : 'Yenile Gözlem'"></span>
                </button>
            </div>
        </div>

        <template x-if="error">
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
                <div class="flex items-center gap-3 text-red-700 dark:text-red-400">
                    <span class="material-symbols-outlined">error</span>
                    <span class="font-bold" x-text="error"></span>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3" x-show="!loading && data" x-cloak>
            <!-- Left Column: Strategy Score & Assessment -->
            <div class="space-y-8 lg:col-span-1">
                <div
                    class="glass-card overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Price Strategy
                        Score</h3>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-6xl font-black text-slate-900 dark:text-white"
                            x-text="data.price_strategy_score"></span>
                        <span class="mb-2 text-xl font-bold text-slate-400">/100</span>
                    </div>

                    <!-- Strategy Badge -->
                    <div class="mt-6">
                        <span class="rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest shadow-sm"
                            :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': data
                                    .pricing_strategy.includes('AGGRESSIVE') || data.pricing_strategy === 'competitive',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400': data
                                    .pricing_strategy.includes('BALANCED') || data.pricing_strategy === 'balanced',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': data
                                    .pricing_strategy.includes('MATCH') || data.pricing_strategy === 'premium',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': data
                                    .pricing_strategy.includes('OVERPRICED') || data.pricing_strategy === 'overpriced'
                            }"
                            x-text="data.pricing_strategy"></span>
                    </div>

                    <p class="mt-6 text-sm leading-relaxed text-slate-600 dark:text-slate-300"
                        x-text="data.advisor_recommendation.strategy_rationale || data.advisor_recommendation"></p>
                </div>

                <!-- Risk Signals -->
                <div
                    class="glass-card rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Risk Signals
                    </h3>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="{
                                'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400': data
                                    .risk_signal ===
                                    'high',
                                'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400': data
                                    .risk_signal ===
                                    'moderate',
                                'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400': data
                                    .risk_signal === 'low'
                            }">
                            <span class="material-symbols-outlined">security</span>
                        </div>
                        <div>
                            <p class="text-sm font-black uppercase text-slate-900 dark:text-white"
                                x-text="data.risk_signal + ' risk alert'"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Liquidity & stay duration assessment</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle/Right Columns: Detailed Insights -->
            <div class="space-y-8 lg:col-span-2">
                <!-- Recommendations Grid -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div
                        class="glass-card rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                        <h4 class="text-xs font-black uppercase text-slate-400">Recommended Price Range</h4>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 dark:text-white"
                                x-text="formatCurrency(data.recommended_price_range.min)"></span>
                            <span class="text-slate-400">—</span>
                            <span class="text-2xl font-black text-slate-900 dark:text-white"
                                x-text="formatCurrency(data.recommended_price_range.max)"></span>
                        </div>
                    </div>

                    <div
                        class="glass-card rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                        <h4 class="text-xs font-black uppercase text-slate-400">Est. Sale Velocity</h4>
                        <div class="mt-3 flex items-center gap-2">
                            <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400"
                                x-text="data.estimated_sale_velocity.avg_days || data.estimated_sale_velocity"></span>
                            <span class="font-bold text-slate-500 dark:text-slate-400">Days</span>
                            <template x-if="data.estimated_sale_velocity.confidence_score">
                                <span class="ml-auto text-xs font-bold text-slate-400"
                                    x-text="'Confidence: ' + data.estimated_sale_velocity.confidence_score + '%'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Advisor Actions -->
                <div
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <div
                        class="border-b border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/50">
                        <h3 class="flex items-center gap-2 font-black text-slate-900 dark:text-white">
                            <span class="material-symbols-outlined text-indigo-500">auto_fix_high</span>
                            Strategic Advisor Actions
                        </h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-4">
                            <template x-if="data.advisor_recommendation.immediate_actions">
                                <template x-for="action in data.advisor_recommendation.immediate_actions"
                                    :key="action">
                                    <li class="flex items-start gap-3">
                                        <div class="mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
                                            x-text="'✓'"></div>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                            x-text="action"></span>
                                    </li>
                                </template>
                            </template>
                            <template x-if="!data.advisor_recommendation.immediate_actions">
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
                                        x-text="'✓'"></div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                        x-text="data.advisor_recommendation"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- Market Signal Transparency -->
                <div
                    class="glass-card rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Market
                        Signals
                    </h3>
                    <div class="mt-6 flex flex-wrap gap-4">
                        <div class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 shadow-sm dark:bg-slate-900">
                            <span class="text-xs font-bold text-slate-500">Market Demand</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white"
                                x-text="(data.signals.market_demand || data.signals.market_demand_score) + '/100'"></span>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 shadow-sm dark:bg-slate-900">
                            <span class="text-xs font-bold text-slate-500">Price Deviation</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white"
                                x-text="(data.signals.price_deviation || data.signals.price_advantage_score) + '%'"></span>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 shadow-sm dark:bg-slate-900">
                            <span class="text-xs font-bold text-slate-500">Velocity Score</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white"
                                x-text="(data.signals.velocity_score || data.signals.listing_view_velocity) + '/100'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div class="flex h-96 flex-col items-center justify-center space-y-4" x-show="loading">
            <div class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-indigo-600"></div>
            <p class="animate-pulse font-bold text-slate-500">AI Pricing Intelligence Engines Running...</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function sellerStrategy() {
            const listingId = window.location.pathname.split('/')[3];
            return {
                loading: false,
                data: null,
                error: null,
                init() {
                    this.fetchData();
                },
                async fetchData() {
                    this.loading = true;
                    this.error = null;
                    try {
                        const response = await fetch(`/advisor/listings/${listingId}/seller-strategy/fetch`);
                        const result = await response.json();

                        if (result.success) {
                            this.data = result.data;
                        } else {
                            this.error = "Data fetch failed: " + (result.message || 'Unknown');
                        }
                    } catch (e) {
                        this.error = "System error contacting AI Service";
                    } finally {
                        this.loading = false;
                    }
                },
                formatCurrency(val) {
                    return new Intl.NumberFormat('tr-TR', {
                        style: 'currency',
                        currency: 'TRY',
                        maximumFractionDigits: 0
                    }).format(val);
                }
            }
        }
    </script>
@endpush
