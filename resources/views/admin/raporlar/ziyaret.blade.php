@extends('admin.layouts.admin')

@section('title', 'Ziyaret Raporları')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">Ziyaret Raporları</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Son {{ $days }} günlük ilan görüntülenme istatistikleri</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Views -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Görüntülenme</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($totalViews) }}</h3>
                </div>
            </div>
        </div>

        <!-- Public Listings -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Yayında İlan</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($publicListings) }}</h3>
                </div>
            </div>
        </div>

        <!-- Avg Views Per Listing -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ort. Görüntülenme</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                        {{ $publicListings > 0 ? number_format($totalViews / $publicListings, 1) : '0' }}
                    </h3>
                </div>
            </div>
        </div>
        <!-- Top 10 Listings -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white dark:text-slate-100">En Çok Görüntülenen İlanlar (Son {{ $days }} Gün)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İlan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fiyat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Görüntülenme</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($topListings as $index => $listing)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($listing->ilan)
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ Str::limit($listing->ilan->baslik, 50) }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">İlan bulunamadı</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    @if($listing->ilan)
                                        {{ $listing->ilan->para_birimi ?? '₺' }} {{ number_format($listing->ilan->fiyat) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ number_format($listing->views) }} görüntülenme
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Henüz görüntülenme verisi bulunmamaktadır.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Device Stats -->
        @if($device->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow dark:shadow-none">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white dark:text-slate-100">Cihaz Bazında Görüntülenme</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($device as $dev)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ ucfirst($dev->cihaz) }}</span>
                                <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ number_format($dev->total) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full" style="width: {{ $totalViews > 0 ? ($dev->total / $totalViews * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
