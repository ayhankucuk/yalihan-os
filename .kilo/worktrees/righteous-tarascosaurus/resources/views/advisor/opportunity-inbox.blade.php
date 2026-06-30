@extends('admin.layouts.admin')

@section('title', 'AI Opportunity Inbox')

@section('content')
    <div class="container mx-auto px-4 py-8" x-data="opportunityInbox()">
        <div class="mb-6 flex flex-col items-center justify-between md:flex-row">
            <div>
                <h3 class="flex items-center text-2xl font-bold text-gray-800 dark:text-white">
                    <span class="material-symbols-outlined mr-2 text-blue-600 dark:text-blue-400">auto_fix_high</span> AI Opportunity Inbox
                </h3>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Sistemdeki pasif durumdaki veya potansiyeli yüksek ilanlar
                    için AI önerileri.</p>
            </div>

            <div class="mt-4 flex items-center space-x-3 md:mt-0">
                <span
                    class="flex items-center rounded-full bg-blue-100 px-3 py-1.5 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    <span class="material-symbols-outlined mr-1.5">layers</span> <span x-text="stats.total"></span> Fırsat
                </span>
                <span
                    class="flex items-center rounded-full bg-green-100 px-3 py-1.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                    <span class="material-symbols-outlined mr-1.5">star</span> Ort. Skor: <span x-text="stats.avg_score"></span>
                </span>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm dark:bg-slate-800">
            <div class="flex flex-wrap gap-2">
                <button @click="setFilter('')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === '', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== '' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    Tümü
                </button>
                <button @click="setFilter('UNDERPRICED_LISTING')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === 'UNDERPRICED_LISTING', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== 'UNDERPRICED_LISTING' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined mr-1">label</span> Fiyat Fırsatı
                </button>
                <button @click="setFilter('HIGH_BUYER_MATCH')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === 'HIGH_BUYER_MATCH', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== 'HIGH_BUYER_MATCH' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined mr-1">group</span> Yüksek Eşleşme
                </button>
                <button @click="setFilter('SEO_OPTIMIZATION')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === 'SEO_OPTIMIZATION', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== 'SEO_OPTIMIZATION' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined mr-1">search</span> SEO Fırsatı
                </button>
                <button @click="setFilter('LOW_QUALITY_HIGH_POTENTIAL')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === 'LOW_QUALITY_HIGH_POTENTIAL', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== 'LOW_QUALITY_HIGH_POTENTIAL' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined mr-1">photo_camera</span> Kalite İyileştirmesi
                </button>
                <button @click="setFilter('STALE_LISTING_RECOVERY')"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-500': filter === 'STALE_LISTING_RECOVERY', 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-400 dark:hover:bg-slate-700': filter !== 'STALE_LISTING_RECOVERY' }"
                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined mr-1">sync</span> Durağan İlan
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="py-12 text-center">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400" style="font-size:2rem">progress_activity</span>
            <p class="mt-3 text-gray-500 dark:text-gray-400">AI verileri işleniyor...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && opportunities.length === 0"
            class="rounded-lg bg-white py-16 text-center shadow-sm dark:bg-slate-800" style="display: none;">
            <span class="material-symbols-outlined mb-4 text-gray-300 dark:text-gray-600" style="font-size:4rem">inbox</span>
            <h5 class="text-xl font-bold text-gray-800 dark:text-white">Şu an için yeni bir fırsat bulunmuyor.</h5>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Portföyünüz şu an ideal konumunda görünüyor.</p>
        </div>

        <!-- Opportunities List -->
        <div class="grid grid-cols-1 gap-4" x-show="!loading && opportunities.length > 0" style="display: none;">
            <template x-for="opp in opportunities" :key="opp.id">
                <div class="rounded-lg border-l-4 bg-white shadow-sm transition-shadow hover:shadow-md dark:bg-slate-800"
                    :class="getBorderColorClass(opp.opportunity_score)">
                    <div class="flex flex-col items-start p-5 md:flex-row md:items-center">

                        <!-- Score -->
                        <div class="mb-4 mr-6 hidden flex-shrink-0 text-center md:mb-0 md:block">
                            <div class="justify-content-center mx-auto flex h-16 w-16 items-center rounded-full text-xl font-bold text-white shadow-sm"
                                :class="getScoreBadgeClass(opp.opportunity_score)">
                                <span x-text="opp.opportunity_score" class="mx-auto my-auto"></span>
                            </div>
                            <span
                                class="mt-2 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Skor</span>
                        </div>

                        <!-- Details -->
                        <div class="min-w-0 flex-grow pr-4">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="rounded px-2 py-1 text-xs font-semibold"
                                    :class="getTypeBadgeClass(opp.opportunity_type)"
                                    x-text="formatType(opp.opportunity_type)"></span>
                                <span
                                    class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">ID:
                                    <span x-text="opp.listing_id"></span></span>
                                <!-- Mobile Score -->
                                <span class="rounded px-2 py-1 text-xs font-bold text-white md:hidden"
                                    :class="getScoreBadgeClass(opp.opportunity_score)">Skor: <span
                                        x-text="opp.opportunity_score"></span></span>
                            </div>
                            <h4 class="mb-3 truncate text-lg font-bold text-gray-900 dark:text-white">
                                <a :href="'/admin/ilanlar/' + opp.listing_id"
                                    class="transition-colors hover:text-blue-600 dark:hover:text-blue-400"
                                    x-text="opp.title"></a>
                            </h4>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="rounded-md bg-slate-50 p-3 dark:bg-slate-900/50">
                                    <p
                                        class="mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        <span class="material-symbols-outlined mr-1 text-blue-500">info</span> Fırsat Nedeni
                                    </p>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="opp.reason"></p>
                                </div>
                                <div
                                    class="rounded-md border border-green-100 bg-green-50 p-3 dark:border-green-800/30 dark:bg-green-900/20">
                                    <p
                                        class="mb-1 text-xs font-bold uppercase tracking-wide text-green-600 dark:text-green-500">
                                        <span class="material-symbols-outlined mr-1 text-green-500">auto_fix_high</span> Önerilen Aksiyon
                                    </p>
                                    <p class="text-sm font-bold text-green-700 dark:text-green-400"
                                        x-text="opp.suggested_action"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div
                            class="ml-0 mt-4 w-full flex-shrink-0 border-t border-gray-100 pt-4 text-center dark:border-slate-700 md:ml-4 md:mt-0 md:w-40 md:border-l md:border-t-0 md:pl-6 md:pt-0 md:text-right">
                            <div class="mb-3 text-xl font-bold text-gray-900 dark:text-white"
                                x-text="formatCurrency(opp.price)"></div>
                            <a :href="'/admin/ilanlar/' + opp.listing_id"
                                class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                                Yönet <span class="material-symbols-outlined ml-2 text-gray-400">arrow_forward</span>
                            </a>
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
            Alpine.data('opportunityInbox', () => ({
                opportunities: [],
                loading: true,
                filter: '',
                stats: {
                    total: 0,
                    avg_score: 0
                },

                init() {
                    this.fetchData();
                },

                setFilter(val) {
                    this.filter = val;
                    this.fetchData();
                },

                async fetchData() {
                    this.loading = true;

                    try {
                        let url = '/advisor/opportunities/fetch';
                        if (this.filter) {
                            url += `?opportunity_type=${this.filter}`;
                        }

                        const response = await fetch(url);
                        const json = await response.json();

                        if (json.success) {
                            this.opportunities = json.data.opportunities;
                            this.stats.total = json.data.total_opportunities;
                            this.stats.avg_score = json.data.average_score;
                        }
                    } catch (error) {
                        console.error('Error fetching opportunities:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                getScoreBadgeClass(score) {
                    if (score >= 80) return 'bg-green-500';
                    if (score >= 60) return 'bg-yellow-500';
                    return 'bg-red-500';
                },

                getBorderColorClass(score) {
                    if (score >= 80) return 'border-green-500';
                    if (score >= 60) return 'border-yellow-500';
                    return 'border-red-500';
                },

                getTypeBadgeClass(type) {
                    const map = {
                        'UNDERPRICED_LISTING': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                        'HIGH_BUYER_MATCH': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                        'SEO_OPTIMIZATION': 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
                        'LOW_QUALITY_HIGH_POTENTIAL': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                        'STALE_LISTING_RECOVERY': 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300'
                    };
                    return map[type] ||
                    'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300';
                },

                formatType(type) {
                    const map = {
                        'UNDERPRICED_LISTING': 'Fiyat Fırsatı',
                        'HIGH_BUYER_MATCH': 'Yüksek Alıcı Eşleşmesi',
                        'SEO_OPTIMIZATION': 'SEO Geliştirmesi',
                        'LOW_QUALITY_HIGH_POTENTIAL': 'Kalite İyileştirme',
                        'STALE_LISTING_RECOVERY': 'Durağan İlan Kurtarma'
                    };
                    return map[type] || type;
                },

                formatCurrency(value) {
                    if (!value) return '₺0';
                    return new Intl.NumberFormat('tr-TR', {
                        style: 'currency',
                        currency: 'TRY',
                        maximumFractionDigits: 0
                    }).format(value);
                }
            }));
        });
    </script>
@endpush
