@extends('admin.layouts.admin')

@section('title', 'Müşteri İstatistikleri')

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
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">👥 Müşteri İstatistikleri</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Müşteri davranışları, segmentasyon ve aktivite analizi
            </p>
        </div>
        <div class="flex items-center gap-2">
            <select class="px-3 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 transition-all duration-200 dark:text-slate-300">
                <option>Tüm Müşteriler</option>
                <option>Aktif Müşteriler</option>
                <option>Yeni Müşteriler</option>
                <option>VIP Müşteriler</option>
            </select>
            <button onclick="window.location.reload()" 
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                Yenile
            </button>
        </div>
    </div>

    {{-- Customer Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-pink-100">Toplam Müşteri</p>
            <p class="text-3xl font-bold mt-2">-</p>
            <p class="text-sm text-pink-100 mt-2">Kayıtlı kullanıcılar</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Müşteri</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">Son 30 gün</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Yeni Kayıt</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Bu ay</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Elde Tutma</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">-%</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Retention rate</p>
        </div>
    </div>

    {{-- Customer Segmentation --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Müşteri Segmentasyonu</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Segmentasyon grafikleri yükleniyor...</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Aktivite Dağılımı</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Aktivite grafikleri yükleniyor...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Insights --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">En Aktif Müşteriler</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg dark:bg-slate-900">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-pink-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        1
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Yükleniyor...</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">- aktivite</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">VIP Müşteriler</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Yükleniyor...</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Premium seviye</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Risk Altında</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Yükleniyor...</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Aktivite düşük</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Journey --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Müşteri Yolculuğu</h3>
        <div class="flex items-center justify-between gap-4">
            <div class="flex-1 text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">-</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Keşif</p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="flex-1 text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">-</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Değerlendirme</p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="flex-1 text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">-</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Karar</p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="flex-1 text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">-</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Sadakat</p>
            </div>
        </div>
    </div>
</div>
@endsection
