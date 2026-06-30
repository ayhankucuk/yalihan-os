{{--
    🏗️ Context7 Valuation Dashboard
    Version: 1.0.0 - Enterprise Edition
    Context7 Standard: C7-VALUATION-DASHBOARD-2025-01-30

    🎯 Hedefler:
    - %100 Neo Design System entegrasyonu
    - Real-time valuation data
    - Interactive dashboard
    - Responsive design
    - Dark mode support
    - Enterprise features
--}}
@extends('admin.layouts.admin')

@section('title', 'Valuation Dashboard')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">💰 Valuation Dashboard</h1>
                    <p class="text-blue-100 mt-2 text-lg">Enterprise seviye arsa değerleme ve analiz sistemi</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-200">Version 1.0.0</div>
                    <div class="text-xs text-blue-300">Enterprise Edition</div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="openParcelSearch()">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Parsel Sorgu</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">TKGM'den parsel bilgisi al</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="openValuation()">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Değerleme</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Arsa değerleme hesapla</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="openReports()">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Raporlar</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Finansal raporlar oluştur</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="openAnalytics()">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Analitik</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Market analizi görüntüle</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Değerleme</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="total-valuations">0</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium" id="total-valuations-change">+0%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">Bu ay</span>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ortalama Değer</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="average-value">0 TL</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium" id="average-value-change">+0%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">Bu ay</span>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Değer</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="total-value">0 TL</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium" id="total-value-change">+0%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">Bu ay</span>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Başarı Oranı</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="success-rate">0%</p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium" id="success-rate-change">+0%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">Bu ay</span>
                </div>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Recent Valuations --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Son Değerlemeler</h3>
                    <button class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" onclick="viewAllValuations()">
                        Tümünü Gör
                    </button>
                </div>
                <div class="space-y-4" id="recent-valuations">
                    {{-- Recent valuations will be loaded here --}}
                </div>
            </div>

            {{-- Market Trends --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Market Trendleri</h3>
                    <button class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" onclick="viewMarketTrends()">
                        Detaylı Analiz
                    </button>
                </div>
                <div class="space-y-4" id="market-trends">
                    {{-- Market trends will be loaded here --}}
                </div>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-6 flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-900 dark:text-white dark:text-slate-100">Yükleniyor...</span>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/valuation-dashboard.js') }}"></script>
@endsection
