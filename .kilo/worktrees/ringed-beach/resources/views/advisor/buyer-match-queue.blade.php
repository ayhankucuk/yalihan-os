@extends('admin.layouts.admin')

@section('title', 'Deal Maker - AI Buyer Match Queue')

@section('content')
    <div class="container mx-auto px-4 py-8 dark:bg-slate-900" x-data="buyerMatchQueue('{{ $listing->id }}')">

        <!-- Header -->
        <div
            class="mb-6 flex flex-col items-start justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30 md:flex-row md:items-center">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">handshake</span>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Deal Maker <span
                            class="font-normal text-slate-400 dark:text-slate-500">| Match Queue</span></h2>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    İlan: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $listing->baslik }}</span>
                    <span class="mx-2 text-slate-300 dark:text-slate-600">|</span>
                    ID: <span class="text-slate-700 dark:text-slate-300">{{ $listing->id }}</span>
                </p>
            </div>

            <div class="mt-4 flex space-x-3 md:mt-0">
                <div
                    class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-2 text-center dark:border-slate-700 dark:bg-slate-800">
                    <span
                        class="mb-1 block text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Aday
                        Alıcı</span>
                    <span class="text-xl font-bold text-blue-800 dark:text-blue-300" x-text="stats.total"></span>
                </div>
                <div
                    class="rounded-lg border border-red-100 bg-red-50 px-4 py-2 text-center dark:border-slate-700 dark:bg-slate-800">
                    <span
                        class="mb-1 block text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">HOT
                        Eşleşme</span>
                    <span class="text-xl font-bold text-red-800 dark:text-red-300" x-text="stats.hot"></span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-2">
            <button @click="setFilter('')"
                :class="{ 'bg-slate-800 text-white dark:bg-slate-200 dark:text-slate-800 border-transparent': filter === '', 'bg-white text-slate-700 dark:text-slate-300 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800': filter !== '' }"
                class="flex items-center rounded-full px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                Tümü
            </button>
            <button @click="setFilter('HOT')"
                :class="{ 'bg-red-600 text-white border-transparent': filter === 'HOT', 'bg-white text-red-600 dark:text-red-400 dark:bg-slate-900 border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/20': filter !== 'HOT' }"
                class="flex items-center rounded-full px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                <span class="material-symbols-outlined mr-1.5">local_fire_department</span> Sadece HOT
            </button>
            <button @click="setFilter('HIGH_INTENT')"
                :class="{ 'bg-orange-500 text-white border-transparent': filter === 'HIGH_INTENT', 'bg-white text-orange-600 dark:text-orange-400 dark:bg-slate-900 border border-orange-200 dark:border-orange-900/50 hover:bg-orange-50 dark:hover:bg-orange-900/20': filter !== 'HIGH_INTENT' }"
                class="flex items-center rounded-full px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                <span class="material-symbols-outlined mr-1.5">bolt</span> Yüksek Niyet (Intent)
            </button>
            <button @click="setFilter('AT_RISK')"
                :class="{ 'bg-purple-600 text-white border-transparent': filter === 'AT_RISK', 'bg-white text-purple-600 dark:text-purple-400 dark:bg-slate-900 border border-purple-200 dark:border-purple-900/50 hover:bg-purple-50 dark:hover:bg-purple-900/20': filter !== 'AT_RISK' }"
                class="flex items-center rounded-full px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                <span class="material-symbols-outlined mr-1.5">warning</span> Kaybedilme Riski (Risk)
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loading"
            class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-20 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <div class="mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600 dark:border-blue-400"></div>
            <p class="font-medium text-slate-500 dark:text-slate-400">Satış zekası sinyalleri işleniyor...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && filteredMatches.length === 0"
            class="rounded-xl border border-slate-200 bg-white py-20 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30"
            style="display: none;">
            <div
                class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-full border border-transparent bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                <span class="material-symbols-outlined border-transparent text-slate-400 dark:text-slate-500" style="font-size:2rem">person_off</span>
            </div>
            <h5 class="mb-2 text-xl font-bold text-slate-800 dark:text-white">Aday Bulunamadı</h5>
            <p class="mx-auto max-w-md text-slate-500 dark:text-slate-400">Seçili filtrelere uygun potansiyel alıcı
                bulunmuyor. İlan özelliklerini veya fiyatını güncellemeyi değerledirebilirsiniz.</p>
        </div>

        <!-- Matches List -->
        <div class="space-y-4 border-transparent" x-show="!loading && filteredMatches.length > 0" style="display: none;">
            <template x-for="(match, index) in filteredMatches" :key="match.buyer_id">

                <div
                    class="relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:border-blue-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30 dark:hover:border-slate-600">

                    <!-- Priority Ribbon -->
                    <div x-show="match.contact_priority <= 2" class="absolute right-0 top-0 border-transparent">
                        <div
                            class="rounded-bl-lg border-transparent bg-red-600 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm dark:bg-red-700">
                            Öncelikli Temas
                        </div>
                    </div>

                    <div class="border-transparent p-5">
                        <div class="flex flex-col gap-6 border-transparent lg:flex-row">

                            <!-- Left Col: Buyer Info & Score -->
                            <div
                                class="flex flex-col items-center justify-center border-b border-slate-100 pb-4 dark:border-slate-800 lg:w-1/4 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-6">

                                <div class="relative mb-3 border-transparent">
                                    <svg class="h-24 w-24 -rotate-90 transform border-transparent" viewBox="0 0 36 36">
                                        <path class="text-slate-100 dark:text-slate-800" stroke-width="3"
                                            stroke="currentColor" fill="none"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path :class="getScoreColorStr(match.match_score)" stroke-width="3"
                                            :stroke-dasharray="match.match_score + ', 100'" stroke="currentColor"
                                            fill="none"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <div
                                        class="absolute inset-0 flex flex-col items-center justify-center border-transparent">
                                        <span class="text-2xl font-black text-slate-800 dark:text-white"
                                            x-text="match.match_score"></span>
                                    </div>
                                </div>

                                <span
                                    class="rounded border border-transparent px-2 py-1 text-xs font-bold tracking-widest shadow-sm"
                                    :class="getTierBadgeClass(match.match_tier)"
                                    x-text="match.match_tier + ' MATCH'"></span>

                                <div class="mt-4 w-full border-transparent text-center">
                                    <h4 class="truncate px-2 text-lg font-bold text-slate-900 dark:text-white"
                                        :title="match.buyer_name" x-text="match.buyer_name"></h4>
                                    <div class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                                        <span class="material-symbols-outlined mr-1 border-transparent text-slate-400 dark:text-slate-500">call</span>
                                        <span x-text="match.buyer_phone || 'Gizli'"></span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-400 dark:text-slate-500">ID: <span
                                            class="text-slate-500 dark:text-slate-400" x-text="match.buyer_id"></span></div>
                                </div>
                            </div>

                            <!-- Mid Col: Reasons -->
                            <div class="flex flex-col justify-center border-transparent lg:w-2/4">

                                <!-- Urgency Tag -->
                                <div class="mb-4 flex items-center border-transparent">
                                    <span
                                        class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase tracking-wide"
                                        :class="getUrgencyBadgeClass(match.urgency_signal)">
                                        <span class="material-symbols-outlined mr-1.5" style="font-size:14px" x-text="getUrgencyIcon(match.urgency_signal)"></span> <span
                                            x-text="formatUrgency(match.urgency_signal)"></span>
                                    </span>
                                </div>

                                <div class="mb-4 border-transparent">
                                    <h5
                                        class="mb-2 flex items-center text-sm font-bold uppercase tracking-widest text-slate-700 dark:text-slate-300">
                                        <span class="material-symbols-outlined mr-2 text-blue-500 dark:text-blue-400">track_changes</span> Ana Eşleşme
                                        Nedeni
                                    </h5>
                                    <p class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-base font-medium text-slate-800 dark:border-slate-700/50 dark:bg-slate-800/80 dark:text-slate-200"
                                        x-text="match.primary_reason"></p>
                                </div>

                                <div class="border-transparent">
                                    <h5
                                        class="mb-2 text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                        Destekleyici Sinyaller</h5>
                                    <ul class="space-y-1">
                                        <template x-for="reason in match.match_reasons" :key="reason">
                                            <li
                                                class="flex items-start border-transparent text-sm text-slate-600 dark:text-slate-400">
                                                <span class="material-symbols-outlined mr-2 mt-1 text-[10px] text-green-500 dark:text-green-400">check</span>
                                                <span x-text="reason"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <!-- Right Col: Suggested Action -->
                            <div
                                class="flex flex-col rounded-xl border border-blue-100 bg-blue-50/50 p-4 dark:border-slate-700/50 dark:bg-slate-800/50 lg:w-1/4">
                                <h5
                                    class="mb-3 flex items-center text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">
                                    <span class="material-symbols-outlined mr-1.5 border-transparent">smart_toy</span> Önerilen Aksiyon
                                </h5>

                                <p class="mb-6 flex-grow text-sm font-semibold text-slate-800 dark:text-slate-200"
                                    x-text="match.suggested_action"></p>

                                <div class="mt-auto flex flex-col gap-2 border-transparent">
                                    <a :href="'tel:' + match.buyer_phone" x-show="match.buyer_phone"
                                        class="inline-flex w-full items-center justify-center rounded-lg border border-transparent bg-green-600 px-4 py-2 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-green-500 focus:border-green-700 focus:outline-none focus:ring focus:ring-green-200 active:bg-green-600 disabled:opacity-25 dark:bg-green-700 dark:hover:bg-green-600">
                                        <span class="material-symbols-outlined mr-2 border-transparent">call</span> Hemen Ara
                                    </a>
                                    <a :href="'/admin/kisiler/' + match.buyer_id"
                                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-700 shadow-sm transition hover:bg-slate-50 focus:border-blue-300 focus:outline-none focus:ring focus:ring-blue-200 active:text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        Profili İncele
                                    </a>
                                </div>
                            </div>

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
            Alpine.data('buyerMatchQueue', (listingId) => ({
                matches: [],
                loading: true,
                filter: '',
                listingId: listingId,
                stats: {
                    total: 0,
                    hot: 0,
                    avg_score: 0
                },

                init() {
                    this.fetchData();
                },

                setFilter(val) {
                    this.filter = val;
                },

                get filteredMatches() {
                    if (!this.filter) return this.matches;

                    return this.matches.filter(match => {
                        if (this.filter === 'HOT') return match.match_tier === 'HOT';
                        if (this.filter === 'HIGH_INTENT') return match.urgency_signal ===
                            'HIGH_INTENT';
                        if (this.filter === 'AT_RISK') return match.urgency_signal ===
                            'AT_RISK';
                        return true;
                    });
                },

                async fetchData() {
                    this.loading = true;

                    try {
                        const response = await fetch(
                            `/advisor/listings/${this.listingId}/buyer-matches/fetch`);
                        const json = await response.json();

                        if (json.success) {
                            this.matches = json.data.matches;
                            this.stats.total = json.data.total_matches;
                            this.stats.hot = json.data.hot_matches;
                            this.stats.avg_score = json.data.average_match_score;
                        } else {
                            console.error(json.message);
                        }
                    } catch (error) {
                        console.error('Error fetching matches:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                getScoreColorStr(score) {
                    if (score >= 85) return 'text-red-500 dark:text-red-400';
                    if (score >= 70) return 'text-orange-500 dark:text-orange-400';
                    if (score >= 55) return 'text-yellow-500 dark:text-yellow-400';
                    return 'text-blue-500 dark:text-blue-400';
                },

                getTierBadgeClass(tier) {
                    const map = {
                        'HOT': 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300',
                        'WARM': 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300',
                        'WATCH': 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300',
                        'LOW': 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                    };
                    return map[tier] ||
                        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300';
                },

                getUrgencyBadgeClass(urgency) {
                    const map = {
                        'HIGH_INTENT': 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/50',
                        'ACTIVE_SEARCH': 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-900/50',
                        'PASSIVE': 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                        'AT_RISK': 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900/50'
                    };
                    return map[urgency] ||
                        'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                },

                getUrgencyIcon(urgency) {
                    const map = {
                        'HIGH_INTENT': 'bolt',
                        'ACTIVE_SEARCH': 'search',
                        'PASSIVE': 'local_cafe',
                        'AT_RISK': 'warning'
                    };
                    return map[urgency] || 'info';
                },

                formatUrgency(urgency) {
                    const map = {
                        'HIGH_INTENT': 'Yüksek Alım Niyeti',
                        'ACTIVE_SEARCH': 'Aktif Arayışta',
                        'PASSIVE': 'Pasif İzleyici',
                        'AT_RISK': 'Kaybedilme Riski'
                    };
                    return map[urgency] || urgency;
                }
            }));
        });
    </script>
@endpush
