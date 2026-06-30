@extends('admin.layouts.admin')

@section('title', 'Genel İstatistikler')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.analitik.istatistikler.index') }}" 
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">📊 Genel İstatistikler</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Sistem geneli özet veriler ve trendler
            </p>
        </div>
        <div class="flex items-center gap-2">
            <select class="px-3 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 transition-all duration-200 dark:text-slate-300">
                <option>Son 7 Gün</option>
                <option>Son 30 Gün</option>
                <option>Son 90 Gün</option>
                <option>Bu Yıl</option>
            </select>
            <button onclick="window.location.reload()" 
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </button>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Ziyaret</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                <span class="font-medium">+12.5%</span> önceki döneme göre
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Yeni Üyeler</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                <span class="font-medium">+8.3%</span> önceki döneme göre
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanan İşlem</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                <span class="font-medium">-2.1%</span> önceki döneme göre
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Değer</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                <span class="font-medium">+15.7%</span> önceki döneme göre
            </p>
        </div>
    </div>

    {{-- Charts Placeholder --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Ziyaretçi Trendi</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Grafik yükleniyor...</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategori Dağılımı</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Grafik yükleniyor...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Feed --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Son Aktiviteler</h3>
        <div class="space-y-3">
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <span>Veri yükleniyor...</span>
            </div>
        </div>
    </div>
</div>
@endsection
