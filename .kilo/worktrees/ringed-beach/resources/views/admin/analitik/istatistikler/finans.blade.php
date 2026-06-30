@extends('admin.layouts.admin')

@section('title', 'Finans İstatistikleri')

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
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">💳 Finans İstatistikleri</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Nakit akışı, karlılık ve bütçe analizi
            </p>
        </div>
        <div class="flex items-center gap-2">
            <select class="px-3 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 transition-all duration-200 dark:text-slate-300">
                <option>Bu Ay</option>
                <option>Bu Çeyrek</option>
                <option>Bu Yıl</option>
                <option>Tüm Zamanlar</option>
            </select>
            <button onclick="window.location.reload()" 
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                Yenile
            </button>
        </div>
    </div>

    {{-- Financial Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 dark:bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-amber-100">Toplam Nakit</p>
            <p class="text-3xl font-bold mt-2">₺ -</p>
            <p class="text-sm text-amber-100 mt-2">Mevcut bakiye</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gelirler</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">₺ -</p>
            <p class="text-sm text-green-600 dark:text-green-400 mt-2">+12% bu ay</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Giderler</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">₺ -</p>
            <p class="text-sm text-red-600 dark:text-red-400 mt-2">-5% bu ay</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Kar</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">₺ -</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Kar marjı</p>
        </div>
    </div>

    {{-- Cash Flow Chart --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Nakit Akışı</h3>
        <div class="h-80 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">Nakit akışı grafikleri yükleniyor...</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Gelir-Gider karşılaştırması</p>
            </div>
        </div>
    </div>

    {{-- Financial Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Gelir Kaynakları</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Yükleniyor...</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">₺ -</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Gider Kategorileri</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Yükleniyor...</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">₺ -</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Budget Status --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Bütçe Durumu</h3>
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Pazarlama</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">-% / 100%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Operasyon</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">-% / 100%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Teknoloji</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">-% / 100%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
