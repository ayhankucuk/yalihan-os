@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100 mb-2 dark:text-slate-200">UPS Analiz Paneli</h1>
        <p class="text-gray-600 dark:text-gray-400">Template kullanım istatistikleri ve sistem performansı.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Templates -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 flex items-center justify-between dark:shadow-none">
            <div>
                <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Toplam Template</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-slate-100 dark:text-slate-200">{{ $stats['total_templates'] }}</div>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <i class="fas fa-layer-group text-2xl text-blue-500 dark:text-blue-400"></i>
            </div>
        </div>

        <!-- Active Templates -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 flex items-center justify-between dark:shadow-none">
            <div>
                <div class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide mb-1">Aktif Template</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-slate-100 dark:text-slate-200">{{ $stats['active_templates'] }}</div>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                <i class="fas fa-check-circle text-2xl text-green-500 dark:text-green-400"></i>
            </div>
        </div>

        <!-- Total Listings -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 flex items-center justify-between dark:shadow-none">
            <div>
                <div class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-1">Toplam İlan</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-slate-100 dark:text-slate-200">{{ $stats['total_listings'] }}</div>
            </div>
            <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                <i class="fas fa-home text-2xl text-purple-500 dark:text-purple-400"></i>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    Detaylı analiz modülü geliştirilme aşamasındadır. Yakında kullanım oranları, en popüler özellikler ve AI öneri başarı oranları eklenecektir.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
