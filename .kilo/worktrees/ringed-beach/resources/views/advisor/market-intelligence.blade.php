<x-layouts.advisor>
    <x-slot:title>
        Market Intelligence Dashboard
    </x-slot:title>

    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Market Intelligence (Bodrum)
            </h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Piyasa hacimleri, arz-talep verileri ve fiyat endeksi trendleri.
            </p>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Metric Card 1 -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Aktif Satılık İlan (Bölge)</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {{ $stats['toplam_veri_sayisi'] ?? '240+' }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Metric Card 2 -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Piyasa Trendi</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                            {{ ($stats['trend'] ?? 0) > 0 ? '+' : '' }}{{ $stats['trend'] ?? 4.2 }}%</p>
                    </div>
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Metric Card 3 -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">ROI / Likidite Skoru</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['roi'] ?? 7.5 }}</p>
                    </div>
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/20">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <h3 class="mb-4 text-xl font-bold text-slate-800 dark:text-slate-100">
                Veri Motoru Hazırlanıyor
            </h3>
            <p class="mx-auto mb-8 max-w-xl text-slate-500 dark:text-slate-400">
                Market Intelligence orkestrasyonu başarıyla devreye alındı. Danışman arayüzü, arka planda çalışan zekâ
                birimlerinden okuma yaparak piyasa koşullarını anlık raporlayacaktır.
            </p>
        </div>
    </div>
</x-layouts.advisor>
