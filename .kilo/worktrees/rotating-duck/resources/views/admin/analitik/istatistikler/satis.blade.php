@extends('admin.layouts.admin')

@section('title', 'Satış İstatistikleri')

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
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">💰 Satış İstatistikleri</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Gelir, dönüşüm oranları ve tahsilat analizi
            </p>
        </div>
        <div class="flex items-center gap-2">
            <select class="px-3 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 transition-all duration-200 dark:text-slate-300">
                <option>Son 30 Gün</option>
                <option>Bu Ay</option>
                <option>Bu Çeyrek</option>
                <option>Bu Yıl</option>
            </select>
            <button onclick="window.location.reload()" 
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                Yenile
            </button>
        </div>
    </div>

    {{-- Revenue Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-green-100">Toplam Gelir</p>
            <p class="text-3xl font-bold mt-2">-</p>
            <p class="text-sm text-green-100 mt-2">+18.2% önceki döneme göre</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanan Satış</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Bu dönem</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Dönüşüm Oranı</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Ortalama</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama İşlem</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Per satış</p>
        </div>
    </div>

    {{-- Revenue Chart --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Gelir Trendi</h3>
        <div class="h-80 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">Gelir grafikleri yükleniyor...</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Chart.js veya ApexCharts entegrasyonu</p>
            </div>
        </div>
    </div>

    {{-- Sales by Category --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategoriye Göre Satışlar</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Veri yükleniyor...</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">-</p>
                        </div>
                    </div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">-</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">En İyi Performans</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/10 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            1
                        </div>
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Yükleniyor...</span>
                    </div>
                    <span class="text-sm font-bold text-green-600 dark:text-green-400">-</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
