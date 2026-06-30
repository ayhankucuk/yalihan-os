@extends('admin.layouts.admin')

@section('title', 'Cortex v2 - Analytics Dashboard')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <!-- Header -->
    <div class="px-6 py-8 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm dark:shadow-none dark:bg-slate-900">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    📊 Analytics Dashboard <span class="text-blue-600 dark:text-blue-400">v2.0</span>
                </h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400 font-medium">
                    Real-time property performance & Cortex ROI metrics.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 text-xs font-bold rounded-full border border-green-200 dark:border-green-700 animate-pulse">
                    ● {{ $metrik_durumu }}
                </span>
                <a href="{{ route('admin.analytics.yenile') }}" class="px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-semibold hover:scale-105 active:scale-95 transition-all shadow-lg text-sm">
                    🔄 Refresh Cache
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-10">
        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Total Listings -->
            <div class="group bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 dark:shadow-none dark:bg-slate-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl group-hover:rotate-12 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
                <h3 class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-wider">Total Listings</h3>
                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($istatistikler['toplam_ilan']) }}</p>
            </div>

            <!-- Active Listings -->
            <div class="group bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 dark:shadow-none dark:bg-slate-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl group-hover:rotate-12 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <h3 class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-wider">Active Status</h3>
                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($istatistikler['aktif_ilanlar']) }}</p>
            </div>

            <!-- Total Views -->
            <div class="group bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 dark:shadow-none dark:bg-slate-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-xl group-hover:rotate-12 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
                <h3 class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-wider">Total Views</h3>
                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($istatistikler['toplam_goruntulenme']) }}</p>
            </div>

            <!-- Analysis Date -->
            <div class="group bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 dark:shadow-none dark:bg-slate-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-xl group-hover:rotate-12 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <h3 class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-wider">Last Analysis</h3>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-2">{{ \Carbon\Carbon::parse($istatistikler['analiz_tarihi'])->diffForHumans() }}</p>
            </div>
        </div>

        <!-- Charts & Lists -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- ROI Ranking -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden dark:shadow-none dark:bg-slate-900">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        🔥 Top ROI Performers
                    </h2>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Cortex AI Ranking</span>
                </div>
                <div class="p-0">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="px-8 py-4">Property</th>
                                <th class="px-8 py-4">ROI Score</th>
                                <th class="px-8 py-4">Price</th>
                                <th class="px-8 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($istatistikler['en_yuksek_roi'] as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors group">
                                <td class="px-8 py-5">
                                    <p class="font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ $item['baslik'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">ID: #{{ $item['id'] }}</p>
                                </td>
                                <td class="px-8 py-5 font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                    %{{ number_format($item['roi_skoru'], 1) }}
                                </td>
                                <td class="px-8 py-5 font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format($item['fiyat']) }} {{ $item['para_birimi'] }}
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="{{ route('admin.analytics.show', $item['id']) }}" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 dark:text-blue-400 hover:gap-3 transition-all">
                                        Details <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Distribution / Summary -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden group">
                <div class="relative z-10 h-full flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-black mb-2 italic">Intelligence Summary</h2>
                        <p class="text-blue-100 text-sm leading-relaxed mb-8">
                            Our Cortex AI engine has analyzed all active listings. High ROI potential detected in {{ count($istatistikler['en_yuksek_roi']) }} key properties.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2 uppercase tracking-widest text-blue-200">
                                <span>Automation Integrity</span>
                                <span>100%</span>
                            </div>
                            <div class="h-2 bg-white/20 dark:bg-slate-700/50 rounded-full overflow-hidden">
                                <div class="h-full bg-white dark:bg-slate-200 w-full shadow-[0_0_15px_rgba(255,255,255,0.5)] dark:bg-slate-900"></div>
                            </div>
                        </div>
                        
                        <div class="pt-6 border-t border-white/10">
                            <p class="text-xs font-medium text-blue-200 mb-1">Sealed Protocol</p>
                            <p class="text-sm font-mono bg-black/20 dark:bg-black/40 p-3 rounded-xl border border-white/5">
                                Context7 Authority v2.1
                                <br>
                                Status: SEALED 🛡️
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Abstract Decorations -->
                <div class="absolute -right-16 -bottom-16 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700 dark:bg-slate-900/10 dark:bg-slate-800/40"></div>
                <div class="absolute -left-16 -top-16 w-48 h-48 bg-blue-400/20 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
            </div>
        </div>
    </div>
</div>
@endsection
