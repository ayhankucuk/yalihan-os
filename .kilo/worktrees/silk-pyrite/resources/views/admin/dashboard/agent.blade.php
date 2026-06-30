@extends('admin.layouts.app')

@section('title', 'Danışman Kokpiti')

@section('content')
<div class="p-6 space-y-6">

    <!-- Header & Welcome -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                Hoş Geldin, {{ $user->name }} 👋
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                Bugün verimli bir gün olsun. İşte performans özetin.
            </p>
        </div>
        <div class="flex items-center gap-3">
             <button class="px-4 py-2 text-sm font-medium border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ring-offset-white dark:ring-offset-gray-900
                bg-white text-gray-700 border-gray-300 hover:bg-gray-50
                dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900 dark:shadow-none dark:focus:ring-indigo-500">
                <i class="fas fa-sync-alt mr-2"></i> Yenile
            </button>
            <a href="{{ route('admin.ilanlar.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:hover:bg-indigo-500 dark:focus:ring-offset-gray-900 dark:focus:ring-indigo-500 dark:shadow-none">
                <i class="fas fa-plus mr-2"></i> Yeni İlan Ekle
            </a>
        </div>
    </div>

    <!-- AI Insights (Cortex Advisor) -->
    @if(count($insights) > 0)
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl p-4 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fas fa-brain text-6xl text-indigo-600"></i>
            </div>
            <div class="relative z-10">
                <h3 class="flex items-center text-indigo-700 dark:text-indigo-300 font-semibold mb-3">
                    <i class="fas fa-sparkles mr-2 animate-pulse"></i> Cortex AI Önerileri
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($insights as $insight)
                        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur rounded-lg p-3 border border-indigo-100 dark:border-indigo-900 flex items-start gap-3">
                            <div class="shrink-0 mt-1">
                                @if($insight['type'] == 'opportunity')
                                    <span class="text-green-500"><i class="fas fa-arrow-trend-up"></i></span>
                                @else
                                    <span class="text-amber-500"><i class="fas fa-exclamation-circle"></i></span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $insight['message'] }}</p>
                                @if(isset($insight['action_url']))
                                    <a href="{{ $insight['action_url'] }}" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 mt-1 inline-block">
                                        İlgilen <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Listings -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow dark:shadow-none">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam İlan</p>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $stats['total_listings'] }}</h4>
                </div>
                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                    <i class="fas fa-home text-lg"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500">
                <span class="text-green-500 font-medium flex items-center mr-2">
                    <i class="fas fa-arrow-up mr-1"></i> %{{ rand(2,8) }}
                </span>
                <span>geçen aya göre</span>
            </div>
        </div>

        <!-- Active Listings -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow dark:shadow-none">
             <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Aktif Yayında</p>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $stats['active_listings'] }}</h4>
                </div>
                <div class="p-2 bg-green-50 dark:bg-green-900/30 rounded-lg text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
            </div>
            <div class="mt-4 relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                @php
                    $percentage = $stats['total_listings'] > 0 ? ($stats['active_listings'] / $stats['total_listings']) * 100 : 0;
                @endphp
                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        <!-- Leads -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow dark:shadow-none">
             <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni Müşteriler</p>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $stats['new_leads'] }}</h4>
                </div>
                <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-purple-600 dark:text-purple-400">
                    <i class="fas fa-users text-lg"></i>
                </div>
            </div>
             <div class="mt-4 flex items-center text-xs text-gray-500">
                <span class="text-purple-500 font-medium flex items-center mr-2">
                    12 Bekleyen
                </span>
                <span>takip araması</span>
            </div>
        </div>

        <!-- ROI / Performance -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow dark:shadow-none">
             <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tahmini ROI</p>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">%{{ $stats['roi_month'] }}</h4>
                </div>
                <div class="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg text-orange-600 dark:text-orange-400">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
            </div>
             <div class="mt-4 text-xs text-gray-500">
                Portfolio Değeri: <span class="font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ number_format($stats['portfolio_value'], 0, ',', '.') }} ₺</span>
            </div>
        </div>
    </div>

    <!-- Main Content Area: Tasks & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Task List (2 Columns) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
            <div class="p-5 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    <i class="fas fa-tasks mr-2 text-indigo-500"></i> Görev listesi
                </h3>
                <button class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                    Tümünü Gör
                </button>
            </div>
            <div class="p-5">
                @if(count($tasks) > 0)
                    <div class="space-y-4">
                        @foreach($tasks as $task)
                            <div class="flex items-start gap-3 group p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
                                <div class="mt-1">
                                    <input type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
                                </div>
                                <div class="flex-1">
                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors dark:text-slate-100">
                                        {{ $task['title'] }}
                                    </h5>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="text-xs text-gray-500 flex items-center">
                                            <i class="far fa-clock mr-1"></i> {{ $task['due_date'] }}
                                        </span>
                                        @if($task['priority'] == 'high')
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-medium uppercase tracking-wide">Yüksek</span>
                                        @elseif($task['priority'] == 'medium')
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 font-medium uppercase tracking-wide">Orta</span>
                                        @endif
                                    </div>
                                </div>
                                <button class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-3 text-green-100"></i>
                         <p>Harika! Bekleyen göreviniz yok.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Performance / Targets (1 Column) -->
        <div class="space-y-6">
            <!-- Monthly Target Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 dark:bg-gray-900/10 rounded-full blur-2xl dark:bg-slate-800/40"></div>
                <h4 class="text-indigo-100 text-sm font-medium mb-1">Aylık Hedef</h4>
                <div class="flex items-end gap-2 mb-4">
                    <span class="text-3xl font-bold">2/5</span>
                    <span class="text-indigo-200 text-sm mb-1">Satış</span>
                </div>

                <div class="relative w-full bg-black/20 rounded-full h-2 mb-2">
                    <div class="bg-white/90 dark:bg-gray-600 h-2 rounded-full shadow-lg shadow-white/20 dark:shadow-none dark:bg-slate-900/90" style="width: 40%"></div>
                </div>
                <p class="text-xs text-indigo-200">Hedefe ulaşmak için 3 satış daha.</p>

                <div class="mt-6 pt-4 border-t border-white/10 flex justify-between items-center">
                     <div>
                        <p class="text-indigo-200 text-xs">Potansiyel Komisyon</p>
                        <p class="font-semibold">~45.000 ₺</p>
                     </div>
                     <button class="p-2 bg-white/10 hover:bg-white/20 dark:bg-gray-900/30 dark:hover:bg-gray-900/50 rounded-lg transition-colors dark:bg-slate-800/40">
                        <i class="fas fa-arrow-right"></i>
                     </button>
                </div>
            </div>

            <!-- Recent Activity Widget -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-100 dark:border-slate-800 shadow-sm p-5 dark:shadow-none">
                 <h3 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm dark:text-slate-100">
                    <i class="fas fa-history mr-2 text-gray-400"></i> Son Aktiviteler
                </h3>
                <div class="space-y-4">
                    <!-- Activity Items (Mock) -->
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs shadow-sm ring-2 ring-white dark:ring-gray-800 dark:shadow-none">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-slate-200"><span class="font-semibold">Mehmet Demir</span> ile görüşüldü.</p>
                            <p class="text-xs text-gray-500">2 saat önce</p>
                        </div>
                    </div>
                     <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 text-xs shadow-sm ring-2 ring-white dark:ring-gray-800 dark:shadow-none">
                             <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-slate-200">İlan <span class="font-semibold">#9821</span> yayına alındı.</p>
                            <p class="text-xs text-gray-500">5 saat önce</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 text-xs shadow-sm ring-2 ring-white dark:ring-gray-800 dark:shadow-none dark:bg-slate-900">
                             <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-slate-200"><span class="font-semibold">Bodrum Villa</span> fiyat güncellendi.</p>
                            <p class="text-xs text-gray-500">Dün</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
