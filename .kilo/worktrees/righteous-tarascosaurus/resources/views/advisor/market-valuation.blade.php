@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="valuationEngine()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Market Valuation Engine</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Yapay Zeka Destekli Otomatik Değerleme ve Trend Analizi
                </p>
            </div>
        </div>

        <!-- Query Box -->
        <div
            class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Değerleme Sorgusu</h2>

            <form @submit.prevent="fetchValuation" class="grid grid-cols-1 items-end gap-4 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">İl</label>
                    <input type="text" x-model="form.il"
                        class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        required placeholder="Örn: Muğla">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">İlçe</label>
                    <input type="text" x-model="form.ilce"
                        class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        required placeholder="Örn: Bodrum">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mahalle</label>
                    <input type="text" x-model="form.mahalle"
                        class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        required placeholder="Örn: Bitez">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Metrekare (m²)</label>
                    <input type="number" x-model="form.m2"
                        class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        required placeholder="Örn: 1000">
                </div>
                <div>
                    <button type="submit"
                        class="flex w-full items-center justify-center rounded bg-blue-600 px-4 py-2 font-medium text-white shadow hover:bg-blue-700"
                        :disabled="loading">
                        <span x-show="!loading"><span class="material-symbols-outlined mr-2">search</span> Analiz Et</span>
                        <span x-show="loading"><span class="material-symbols-outlined mr-2">progress_activity</span> Hesaplanıyor...</span>
                    </button>
                </div>
            </form>

            <!-- Error Alert -->
            <div x-show="error"
                class="mt-4 border-l-4 border-red-500 bg-red-100 p-4 text-red-700 dark:bg-red-900/20 dark:text-red-400"
                role="alert" x-text="error" style="display: none;"></div>
        </div>

        <!-- Result Dashboard -->
        <div x-show="report" x-transition.opacity style="display: none;" class="space-y-6">

            <!-- Header Metrics -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="rounded border-t-4 border-blue-500 bg-white p-4 shadow dark:bg-slate-900 dark:shadow-slate-950/20">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">LOKASYON</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white"
                        x-text="report.location_il + ' / ' + report.location_ilce"></h3>
                    <p class="max-w-full truncate text-sm text-gray-600 dark:text-gray-300"
                        x-text="report.location_mahalle + ' Mah.'"></p>
                </div>

                <div
                    class="rounded border-t-4 border-green-500 bg-white p-4 shadow dark:bg-slate-900 dark:shadow-slate-950/20">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">TAHMİNİ DEĞER</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"
                        x-text="formatCurrency(report.estimated_value)"></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Ortalama m²: <span
                            x-text="formatCurrency(report.median_m2_price)"></span></p>
                </div>

                <div
                    class="rounded border-t-4 border-purple-500 bg-white p-4 shadow dark:bg-slate-900 dark:shadow-slate-950/20">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BEKLENEN ARALIK</p>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"
                        x-text="formatCurrency(report.price_range_low) + ' - '"></h3>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"
                        x-text="formatCurrency(report.price_range_high)"></h3>
                </div>

                <div
                    class="flex flex-col items-center justify-center rounded border-t-4 border-orange-500 bg-white p-4 shadow dark:bg-slate-900 dark:shadow-slate-950/20">
                    <p class="w-full text-left text-sm font-medium text-gray-500 dark:text-gray-400">GÜVEN SKORU</p>
                    <div class="relative mt-2 h-20 w-20">
                        <svg class="h-full w-full" viewBox="0 0 36 36">
                            <path class="text-gray-200 dark:text-gray-700" stroke-width="3" stroke="currentColor"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-blue-500" stroke-width="3"
                                :stroke-dasharray="report.confidence_score + ', 100'" stroke="currentColor" fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-bold text-gray-700 dark:text-gray-200"
                                x-text="report.confidence_score + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <div class="flex items-center">
                        <div class="rounded-full bg-blue-100 p-3 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                            <span class="material-symbols-outlined" style="font-size:1.25rem">layers</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Analiz Edilen Emsal (Filtrelenmiş)</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white"><span
                                    x-text="report.comparable_count"></span> İlan</p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <div class="flex items-center">
                        <div class="rounded-full bg-green-100 p-3 text-green-600 dark:bg-green-900 dark:text-green-300">
                            <span class="material-symbols-outlined" style="font-size:1.25rem">trending_up</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Piyasa Trendi (Son 90 Gün)</p>
                            <p class="flex items-center text-xl font-semibold"
                                :class="report.market_trend > 0 ? 'text-green-600' : (report.market_trend < 0 ? 'text-red-600' :
                                    'text-gray-900 dark:text-white')">
                                <i class="fas"
                                    :class="report.market_trend > 0 ? 'fa-arrow-up mr-1' : (report.market_trend < 0 ?
                                        'fa-arrow-down mr-1' : 'fa-minus mr-1')"></i>
                                <span
                                    x-text="report.market_trend > 0 ? '+' + report.market_trend + '%' : report.market_trend + '%'"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <div class="flex items-center">
                        <div class="rounded-full bg-yellow-100 p-3 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300">
                            <span class="material-symbols-outlined" style="font-size:1.25rem">water</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Likidite (Satış Hızı) Skoru</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white"
                                x-text="report.liquidity_score"></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('valuationEngine', () => ({
                    form: {
                        il: 'Muğla',
                        ilce: 'Bodrum',
                        mahalle: 'Bitez',
                        m2: 1000,
                        asset_type: 'Tarla'
                    },
                    loading: false,
                    error: null,
                    report: null, // projection data

                    formatCurrency(value) {
                        return new Intl.NumberFormat('tr-TR', {
                            style: 'currency',
                            currency: 'TRY',
                            maximumFractionDigits: 0
                        }).format(value);
                    },

                    async fetchValuation() {
                        this.loading = true;
                        this.error = null;
                        this.report = null;

                        try {
                            const response = await fetch(
                                '{{ route('advisor.market-valuation.fetch') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(this.form)
                                });

                            const result = await response.json();

                            if (!response.ok) {
                                throw new Error(result.message || 'Değerleme işlemi başarısız oldu.');
                            }

                            if (result.success) {
                                this.report = result.data;
                            } else {
                                throw new Error(result.message);
                            }
                        } catch (err) {
                            this.error = err.message;
                        } finally {
                            this.loading = false;
                        }
                    }
                }));
            });
        </script>
    @endpush
@endsection
