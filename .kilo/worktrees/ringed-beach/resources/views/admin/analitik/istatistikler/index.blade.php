@extends('admin.layouts.admin')

@section('title', 'Analitik İstatistikler')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">📊 Analitik İstatistikler</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                2026'nın ilk verileri derleniyor... Cortex sisteminden canlı raporlar
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.location.reload()" 
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </button>
        </div>
    </div>

    {{-- Analytics Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        {{-- Genel İstatistikler --}}
        <a href="{{ route('admin.analitik.istatistikler.genel') }}" 
            class="group block bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
            <div class="p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Genel İstatistikler</h3>
                <p class="text-blue-100 text-sm">Sistem geneli özet veriler ve trendler</p>
            </div>
        </a>

        {{-- İlan İstatistikleri --}}
        <a href="{{ route('admin.analitik.istatistikler.ilan') }}" 
            class="group block bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
            <div class="p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">İlan İstatistikleri</h3>
                <p class="text-purple-100 text-sm">İlan performansı, kategori analizleri</p>
            </div>
        </a>

        {{-- Satış İstatistikleri --}}
        <a href="{{ route('admin.analitik.istatistikler.satis') }}" 
            class="group block bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
            <div class="p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Satış İstatistikleri</h3>
                <p class="text-green-100 text-sm">Gelir, dönüşüm oranları, tahsilat</p>
            </div>
        </a>

        {{-- Finans İstatistikleri --}}
        <a href="{{ route('admin.analitik.istatistikler.finans') }}" 
            class="group block bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
            <div class="p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Finans İstatistikleri</h3>
                <p class="text-amber-100 text-sm">Nakit akışı, karlılık, bütçe analizi</p>
            </div>
        </a>

        {{-- Müşteri İstatistikleri --}}
        <a href="{{ route('admin.analitik.istatistikler.musteri') }}" 
            class="group block bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
            <div class="p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Müşteri İstatistikleri</h3>
                <p class="text-pink-100 text-sm">Müşteri davranışları, segmentasyon</p>
            </div>
        </a>

    </div>

    {{-- Quick Stats Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-5 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İlan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ number_format($stats['total_ilanlar'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-5 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Kullanıcı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ number_format($stats['active_users'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-5 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aylık Yeni İlan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ number_format($stats['monthly_new_listings'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-5 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen İlan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ number_format($stats['pending_listings'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- System Info --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">YalıhanAI Cortex Sistemi</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">2026'nın ilk analitik verileri aktif</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-600 dark:text-gray-400">Sistem Versiyonu:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white dark:text-slate-100">Cortex v1.0</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Son Güncelleme:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ now()->format('d.m.Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Durum:</span>
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    <span class="w-1.5 h-1.5 bg-green-600 dark:bg-green-400 rounded-full mr-1.5"></span>
                    Aktif
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
