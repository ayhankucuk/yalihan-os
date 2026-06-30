@extends('admin.layouts.admin')

@section('title', 'Raporlar')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Raporlar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Sistem raporları ve analizler
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if(Illuminate\Support\Facades\Route::has('admin.reports.kisiler'))
            <a href="{{ route('admin.reports.kisiler') }}"
                class="block bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Kişi Raporları</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Müşteri ve kişi analizleri</p>
            </a>
            @endif

            @if(Illuminate\Support\Facades\Route::has('admin.reports.performance'))
            <a href="{{ route('admin.reports.performance') }}"
                class="block bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 hover:border-green-500 dark:hover:border-green-400 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Performans Raporları</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sistem ve danışman performans metrikleri</p>
            </a>
            @endif
        </div>
    </div>
@endsection
