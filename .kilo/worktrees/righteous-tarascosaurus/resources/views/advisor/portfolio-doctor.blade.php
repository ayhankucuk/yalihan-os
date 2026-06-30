@extends('admin.layouts.admin')

@section('title', 'AI Portfolio Doctor')

@push('styles')
    <style>
        /* Minimal custom CSS. Rely on Tailwind. */
        .doctor-glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .dark .doctor-glass {
            background: rgba(15, 23, 42, 0.8);
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="portfolioDoctor()">
        <!-- Header Area -->
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-3xl font-bold text-transparent dark:from-blue-400 dark:to-indigo-400">
                    <span class="material-symbols-outlined mr-2 text-blue-500">stethoscope</span> AI Portfolio Doctor
                </h1>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">Bu ilan neden satılmıyor ve ne
                    yapmalıyım?</p>
            </div>
            <div>
                <button @click="fetchPortfolio()"
                    class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700">
                    <span class="material-symbols-outlined">sync</span>
                    <span x-text="loading ? 'Analiz Ediliyor...' : 'Yeniden Analiz Et'"></span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-2">
            <template x-for="option in filterOptions" :key="option">
                <button @click="filter === option ? filter = '' : filter = option; fetchPortfolio()"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-all duration-200"
                    :class="{
                        'OVERPRICED': {
                            selected: 'bg-red-600 text-white border-transparent shadow-md shadow-red-500/30',
                            unselected: 'bg-slate-50 text-red-600 dark:text-red-400 dark:bg-slate-950 border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/20'
                        },
                        'LOW_VISIBILITY': {
                            selected: 'bg-orange-500 text-white border-transparent shadow-md shadow-orange-500/30',
                            unselected: 'bg-slate-50 text-orange-600 dark:text-orange-400 dark:bg-slate-950 border-orange-200 dark:border-orange-900/50 hover:bg-orange-50 dark:hover:bg-orange-900/20'
                        },
                        'LOW_IMAGE_QUALITY': {
                            selected: 'bg-yellow-500 text-white border-transparent shadow-md shadow-yellow-500/30',
                            unselected: 'bg-slate-50 text-yellow-600 dark:text-yellow-400 dark:bg-slate-950 border-yellow-200 dark:border-yellow-900/50 hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                        },
                        'LOW_DEMAND_AREA': {
                            selected: 'bg-blue-600 text-white border-transparent shadow-md shadow-blue-500/30',
                            unselected: 'bg-slate-50 text-blue-600 dark:text-blue-400 dark:bg-slate-950 border-blue-200 dark:border-blue-900/50 hover:bg-blue-50 dark:hover:bg-blue-900/20'
                        },
                        'STALE_LISTING': {
                            selected: 'bg-slate-600 text-white border-transparent shadow-md shadow-slate-500/30',
                            unselected: 'bg-slate-50 text-slate-600 dark:text-slate-400 dark:bg-slate-950 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'
                        }
                    } [option][filter === option ? 'selected' : 'unselected']">
                    <span x-text="option.replace(/_/g, ' ')"></span>
                    <span x-show="filter === option" class="ml-1 text-xs">✕</span>
                </button>
            </template>
        </div>

        <!-- Doctor List Container -->
        <div class="space-y-4">

            <!-- Empty State / Loading -->
            <div x-show="loading"
                class="doctor-glass rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                <span class="material-symbols-outlined mb-4 animate-pulse text-indigo-400" style="font-size:3rem">stethoscope</span>
                <h3 class="text-xl font-medium text-slate-700 dark:text-slate-300">Portföyünüz Teşhis Ediliyor...</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-500">Yapay zeka tüm piyasa sinyallerini taramaktadır.
                </p>
            </div>

            <div x-show="!loading && listings.length === 0"
                class="doctor-glass rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30"
                x-cloak>
                <span class="material-symbols-outlined mb-4 text-emerald-500 opacity-80" style="font-size:3rem">check_circle</span>
                <h3 class="text-xl font-medium text-slate-700 dark:text-slate-300">Harika Haber!</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-500">Portföyünüzde majör problem tespit edilen
                    herhangi bir ilan yok. (Veya filtrenize uyan bir ilan bulunamadı.)</p>
            </div>

            <!-- Listing Items -->
            <template x-for="item in listings" :key="item.listing_id">
                <div
                    class="doctor-glass group relative flex flex-col items-start gap-6 overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow duration-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30 md:flex-row">

                    <!-- Severity Line -->
                    <div class="absolute bottom-0 left-0 top-0 w-1.5"
                        :class="getBadgeColors(item.primary_problem).split(' ')[0]"></div>

                    <!-- Info Block -->
                    <div class="flex-1 pl-2">
                        <div class="mb-2 flex items-center gap-3">
                            <span class="rounded-md px-2.5 py-1 text-xs font-bold"
                                :class="getBadgeColors(item.primary_problem)"
                                x-text="item.primary_problem.replace(/_/g, ' ')"></span>
                            <span class="text-xs font-medium text-slate-400 dark:text-slate-500"><span class="material-symbols-outlined">tag</span> <span x-text="item.listing_id"></span></span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100" x-text="item.listing_title"></h3>
                        <p class="mt-1 font-bold text-indigo-600 dark:text-indigo-400"
                            x-text="new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(item.price)">
                        </p>
                    </div>

                    <!-- Health Score Area -->
                    <div
                        class="flex min-w-[140px] flex-col items-center justify-center rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Health Score</div>
                        <div class="text-4xl font-black" :class="getScoreColor(item.listing_health_score)"
                            x-text="item.listing_health_score"></div>
                        <div class="mt-1 text-[10px] text-slate-400">/ 100 Tam Puan</div>
                    </div>

                    <!-- Diagnosis & Action Block -->
                    <div class="w-full md:w-[35%]">
                        <div
                            class="flex h-full flex-col justify-between rounded-lg border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-800/50 dark:bg-indigo-900/20">
                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-indigo-500">bolt</span>
                                    <span
                                        class="text-xs font-bold uppercase tracking-widest text-indigo-800 dark:text-indigo-300">Önerilen
                                        Aksiyon</span>
                                </div>
                                <p class="text-sm font-medium leading-snug text-slate-700 dark:text-slate-300"
                                    x-text="item.suggested_actions.description"></p>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <a :href="'/admin/ilanlar/' + item.listing_id + '/edit'"
                                    class="text-xs font-bold uppercase text-indigo-600 underline decoration-2 underline-offset-4 transition-colors hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    Optimizasyonu Uygula <span class="material-symbols-outlined ml-1">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </div>

    <script>
        function portfolioDoctor() {
            return {
                loading: true,
                filter: '',
                filterOptions: ['OVERPRICED', 'LOW_VISIBILITY', 'LOW_DEMAND_AREA', 'LOW_IMAGE_QUALITY', 'STALE_LISTING'],
                listings: [],

                async init() {
                    await this.fetchPortfolio();
                },

                async fetchPortfolio() {
                    this.loading = true;
                    this.listings = [];
                    try {
                        const url = new URL('/advisor/portfolio-doctor/fetch', window.location.origin);
                        if (this.filter) {
                            url.searchParams.append('problem_category', this.filter);
                        }
                        const res = await fetch(url);
                        const data = await res.json();
                        if (data.success) {
                            this.listings = data.data.portfolio_health;
                        }
                    } catch (e) {
                        console.error('Portfolio Doctor Error:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                getBadgeColors(problem) {
                    switch (problem) {
                        case 'OVERPRICED':
                            return 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300';
                        case 'LOW_VISIBILITY':
                            return 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300';
                        case 'LOW_IMAGE_QUALITY':
                            return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300';
                        case 'LOW_DEMAND_AREA':
                            return 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300';
                        case 'STALE_LISTING':
                            return 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300';
                        case 'HIGH_DEMAND_LOW_CONVERSION':
                            return 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300';
                        case 'NO_BUYER_MATCH':
                            return 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300';
                        case 'HEALTHY':
                            return 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300';
                        default:
                            return 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300';
                    }
                },

                getScoreColor(score) {
                    if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
                    if (score >= 60) return 'text-yellow-500 dark:text-yellow-400';
                    return 'text-red-600 dark:text-red-400';
                }
            }
        }
    </script>
@endsection
