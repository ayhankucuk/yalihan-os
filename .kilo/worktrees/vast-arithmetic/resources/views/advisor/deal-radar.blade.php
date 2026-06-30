@extends('admin.layouts.admin')

@section('title', 'Deal Radar - Fastest to Sell Engine')

@section('content')
    <div class="container mx-auto px-4 py-8 dark:bg-slate-900" x-data="dealRadarEngine()">

        <!-- Header -->
        <div
            class="mb-6 flex flex-col items-start justify-between rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:flex-row md:items-center">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <span class="material-symbols-outlined animate-pulse text-2xl text-blue-600 dark:text-blue-400">satellite_alt</span>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Deal Radar <span
                            class="font-normal text-slate-400 dark:text-slate-500">| Fastest to Sell Engine</span></h2>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">En hızlı satılma ihtimali olan ilanların öncelikli
                    radar görünümü.</p>
            </div>

            <div class="mt-4 flex space-x-3 md:mt-0">
                <div
                    class="rounded-lg border border-red-100 bg-red-50 px-4 py-2 text-center dark:border-red-900/30 dark:bg-red-900/20">
                    <span
                        class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Sıcak
                        Satış</span>
                    <span class="text-xl font-bold text-red-800 dark:text-red-300" x-text="stats.hot"></span>
                </div>
                <div
                    class="rounded-lg border border-orange-100 bg-orange-50 px-4 py-2 text-center dark:border-orange-900/30 dark:bg-orange-900/20">
                    <span
                        class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Hızlı
                        Hareket</span>
                    <span class="text-xl font-bold text-orange-800 dark:text-orange-300" x-text="stats.fast"></span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-2">
            <template x-for="option in ['HOT_DEAL', 'FAST_MOVING', 'WATCHLIST', 'LOW_SIGNAL']" :key="option">
                <button @click="toggleFilter(option)"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition-colors duration-200"
                    :class="{
                        'HOT_DEAL': {
                            selected: 'bg-red-600 text-white border-transparent',
                            unselected: 'bg-white text-red-600 dark:text-red-400 dark:bg-slate-900 border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/20'
                        },
                        'FAST_MOVING': {
                            selected: 'bg-orange-500 text-white border-transparent',
                            unselected: 'bg-white text-orange-600 dark:text-orange-400 dark:bg-slate-900 border border-orange-200 dark:border-orange-900/50 hover:bg-orange-50 dark:hover:bg-orange-900/20'
                        },
                        'WATCHLIST': {
                            selected: 'bg-yellow-500 text-white border-transparent',
                            unselected: 'bg-white text-yellow-600 dark:text-yellow-400 dark:bg-slate-900 border border-yellow-200 dark:border-yellow-900/50 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                        },
                        'LOW_SIGNAL': {
                            selected: 'bg-slate-600 text-white border-transparent',
                            unselected: 'bg-white text-slate-600 dark:text-slate-400 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'
                        }
                    } [option][filter === option ? 'selected' : 'unselected']">
                    <span x-text="option.replace('_', ' ')"></span>
                    <span x-show="filter === option" class="ml-1 text-xs">✕</span>
                </button>
            </template>
        </div>

        <!-- Content -->
        <!-- Loading State -->
        <div x-show="loading"
            class="flex flex-col items-center justify-center rounded-lg border border-transparent bg-white py-20 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600 dark:border-blue-400"></div>
            <p class="text-slate-500 dark:text-slate-400">Radar sinyalleri işleniyor...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && filteredListings.length === 0" style="display: none;"
            class="rounded-lg border border-slate-100 bg-white py-20 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div
                class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-full border border-transparent bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                <span class="material-symbols-outlined border-transparent text-slate-400 dark:text-slate-500" style="font-size:2rem">radar</span>
            </div>
            <h3 class="mb-2 text-lg font-medium text-slate-900 dark:text-slate-100">Sinyal Bulunamadı</h3>
            <p class="text-slate-500 dark:text-slate-400">Şu anda radar alanında ilan sinyali tespit edilemedi.</p>
        </div>

        <!-- Radar List -->
        <div class="grid grid-cols-1 gap-6 border-transparent lg:grid-cols-2"
            x-show="!loading && filteredListings.length > 0" style="display: none;">
            <template x-for="listing in filteredListings" :key="listing.listing_id">
                <div class="relative overflow-hidden rounded-xl border bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md dark:bg-slate-900"
                    :class="getTierBorderClass(listing.deal_tier)">

                    <!-- Tier Ribbon -->
                    <div class="absolute right-0 top-0 border-transparent">
                        <span x-text="listing.deal_tier.replace('_', ' ')"
                            class="rounded-bl-lg border-transparent px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm"
                            :class="getTierBgClass(listing.deal_tier)">
                        </span>
                    </div>

                    <div class="border-transparent p-6">
                        <div class="mb-4 flex items-start justify-between border-transparent">
                            <div class="border-transparent pr-12">
                                <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-slate-100"
                                    x-text="listing.listing_title"></h3>
                                <div class="flex items-center text-sm text-slate-500 dark:text-slate-400">
                                    <span class="material-symbols-outlined mr-1.5 text-slate-400 dark:text-slate-500">location_on</span>
                                    <span x-text="listing.location"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Score & Signals Grid -->
                        <div class="mb-6 grid grid-cols-3 gap-4 border-transparent">
                            <div
                                class="col-span-1 flex flex-col items-center justify-center rounded-lg border border-slate-100 bg-slate-50 py-3 dark:border-slate-700/50 dark:bg-slate-800/80">
                                <span
                                    class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Deal
                                    Score</span>
                                <span class="text-2xl font-black" :class="getScoreColorClass(listing.deal_score)"
                                    x-text="Math.round(listing.deal_score)"></span>
                            </div>

                            <div
                                class="col-span-2 flex flex-col justify-center rounded-lg border border-slate-100 bg-slate-50 py-2 pl-4 pr-2 dark:border-slate-700/50 dark:bg-slate-800/80">
                                <span
                                    class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Primary
                                    Signal</span>
                                <div class="flex items-center text-sm font-bold text-blue-700 dark:text-blue-400">
                                    <span class="material-symbols-outlined mr-2">bolt</span>
                                    <span x-text="listing.primary_signal"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Suggestion -->
                        <div
                            class="mt-4 rounded-xl border border-blue-100 bg-blue-50/50 p-4 dark:border-slate-700/50 dark:bg-slate-800/50">
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                <span class="material-symbols-outlined mr-1.5 border-transparent">smart_toy</span> Önerilen Aksiyon
                            </div>
                            <button
                                class="inline-flex w-full items-center justify-center rounded-lg border border-transparent px-4 py-2.5 text-sm font-bold uppercase tracking-widest text-white shadow-sm transition disabled:opacity-25"
                                :class="getActionBtnClass(listing.deal_tier)">
                                <span class="material-symbols-outlined mr-2" style="font-size:16px;vertical-align:middle" x-text="getActionIcon(listing.deal_tier)"></span>
                                <span x-text="listing.suggested_action"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dealRadarEngine', () => ({
                loading: true,
                listings: [],
                filter: '',

                init() {
                    this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const response = await fetch(`{{ route('advisor.deal-radar.fetch') }}`);
                        const data = await response.json();

                        if (data.success) {
                            this.listings = data.data;
                        }
                    } catch (error) {
                        console.error('Radar Hatası:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                toggleFilter(val) {
                    this.filter = this.filter === val ? '' : val;
                },

                get filteredListings() {
                    if (this.filter === '') return this.listings;
                    return this.listings.filter(i => i.deal_tier === this.filter);
                },

                get stats() {
                    return {
                        total: this.listings.length,
                        hot: this.listings.filter(i => i.deal_tier === 'HOT_DEAL').length,
                        fast: this.listings.filter(i => i.deal_tier === 'FAST_MOVING').length,
                    };
                },

                getTierBorderClass(tier) {
                    const map = {
                        'HOT_DEAL': 'border-red-200 dark:border-red-900/50 hover:border-red-300 dark:hover:border-red-600',
                        'FAST_MOVING': 'border-orange-200 dark:border-orange-900/50 hover:border-orange-300 dark:hover:border-orange-600',
                        'WATCHLIST': 'border-yellow-200 dark:border-yellow-900/50 hover:border-yellow-300 dark:hover:border-yellow-600',
                        'LOW_SIGNAL': 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'
                    };
                    return map[tier] || map['LOW_SIGNAL'];
                },

                getTierBgClass(tier) {
                    const map = {
                        'HOT_DEAL': 'bg-red-600 dark:bg-red-700',
                        'FAST_MOVING': 'bg-orange-500 dark:bg-orange-600',
                        'WATCHLIST': 'bg-yellow-500 dark:bg-yellow-600',
                        'LOW_SIGNAL': 'bg-slate-500 dark:bg-slate-600'
                    };
                    return map[tier] || map['LOW_SIGNAL'];
                },

                getScoreColorClass(score) {
                    if (score >= 85) return 'text-red-600 dark:text-red-400';
                    if (score >= 70) return 'text-orange-500 dark:text-orange-400';
                    if (score >= 55) return 'text-yellow-600 dark:text-yellow-400';
                    return 'text-slate-600 dark:text-slate-400';
                },

                getActionBtnClass(tier) {
                    const map = {
                        'HOT_DEAL': 'bg-red-600 hover:bg-red-500 focus:ring-red-200 dark:bg-red-700 dark:hover:bg-red-600',
                        'FAST_MOVING': 'bg-orange-600 hover:bg-orange-500 focus:ring-orange-200 dark:bg-orange-700 dark:hover:bg-orange-600',
                        'WATCHLIST': 'bg-slate-800 hover:bg-slate-700 focus:ring-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600',
                        'LOW_SIGNAL': 'bg-slate-400 hover:bg-slate-500 cursor-not-allowed dark:bg-slate-600',
                    };
                    return map[tier] || map['LOW_SIGNAL'];
                },

                getActionIcon(tier) {
                    const map = {
                        'HOT_DEAL': 'call',
                        'FAST_MOVING': 'send',
                        'WATCHLIST': 'visibility',
                        'LOW_SIGNAL': 'trending_up',
                    };
                    return map[tier] || map['LOW_SIGNAL'];
                }
            }));
        });
    </script>
@endpush
