@extends('admin.layouts.admin')

@section('content')
    <div class="min-h-screen bg-slate-50 p-8 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        AI Usage Analytics
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Sistem genelindeki yapay zeka performansı, intent dağılımı ve kullanım trendleri.
                    </p>
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider">Live Telemetry Active</span>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Toplam
                        Sorgu</p>
                    <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $successCount }}</h3>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hata Sayısı
                    </p>
                    <h3 class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ $failureCount }}</h3>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Değerleme
                        Güven</p>
                    <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                        %{{ number_format($avgConfidence, 1) }}</h3>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Başarı
                        Oranı</p>
                    <h3 class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                        %{{ $successCount > 0 ? number_format(($successCount / ($successCount + $failureCount)) * 100, 1) : 100 }}
                    </h3>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Intent Distribution -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <h3 class="mb-6 text-center text-lg font-bold text-slate-900 dark:text-white">Intent Dağılımı</h3>
                    <div class="space-y-4">
                        @foreach ($intentDistribution as $intent)
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span
                                        class="font-medium text-slate-700 dark:text-slate-300">{{ $intent->intent_detected }}</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ $intent->count }}</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-2 rounded-full bg-blue-500"
                                        style="width: {{ ($intent->count / max($successCount, 1)) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Top Locations -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
                    <h3 class="mb-6 text-center text-lg font-bold text-slate-900 dark:text-white">Sorgulanan Lokasyonlar
                    </h3>
                    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                        <table
                            class="w-full bg-white text-left text-sm text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                            <thead
                                class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3">Lokasyon</th>
                                    <th class="px-6 py-3 text-right">Sorgu Sayısı</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach ($topLocations as $loc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                                            {{ $loc->location_ilce }} / {{ $loc->location_mahalle ?? 'Genel' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300">
                                            {{ $loc->count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Volume Trend (Placeholder for Chart) -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30 lg:col-span-2">
                    <h3 class="mb-6 text-lg font-bold text-slate-900 dark:text-white">Haftalık Kullanım Trendi</h3>
                    <div class="flex h-48 items-end justify-between gap-1">
                        @foreach ($queryTrend as $trend)
                            <div class="group relative flex flex-1 flex-col items-center">
                                <div
                                    class="absolute -top-8 hidden rounded bg-slate-800 px-2 py-1 text-xs text-white group-hover:block">
                                    {{ $trend->count }}</div>
                                <div class="w-full rounded-t-lg bg-blue-500/20 transition-all hover:bg-blue-500/40 dark:bg-blue-600/10 dark:hover:bg-blue-600/30"
                                    style="height: {{ min(($trend->count / max($successCount, 1)) * 100, 100) }}%"></div>
                                <p class="mt-2 text-[10px] font-medium uppercase text-slate-400">
                                    {{ date('M d', strtotime($trend->date)) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
