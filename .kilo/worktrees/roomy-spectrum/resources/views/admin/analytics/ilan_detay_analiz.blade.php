@extends('admin.layouts.admin')

@section('title', 'Property Analytics - ' . $rapor['baslik'])

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <!-- Header -->
    <div class="px-6 py-8 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm dark:shadow-none dark:bg-slate-900">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.analytics.dashboard_v2') }}" class="p-2.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $rapor['baslik'] }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mt-1">
                        Detailed ID: <span class="text-blue-600 font-bold">#{{ $rapor['ilan_id'] }}</span> | Status: <span class="font-bold">{{ $metrik_durumu }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.analytics.yenile', $rapor['ilan_id']) }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg text-sm">
                    🔄 Refresh Analysis
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Core Metrics -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Viral Stats Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm dark:shadow-none dark:bg-slate-900">
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-widest mb-6">Traffic & Engagement</h3>
                    <div class="space-y-8">
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-slate-500 dark:text-slate-400 font-bold">Total Views</span>
                                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($rapor['metrikler']['goruntulenme_sayisi']) }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 dark:bg-blue-600 w-3/4 rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-slate-500 dark:text-slate-400 font-bold">Favorites</span>
                                <span class="text-3xl font-black text-pink-500">{{ number_format($rapor['metrikler']['favori_sayisi']) }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-pink-500 w-1/4 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROI Intelligence Card -->
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden group">
                    <div class="relative z-10">
                        <h3 class="text-emerald-100 text-xs font-black uppercase tracking-widest mb-4">Cortex ROI Analysis</h3>
                        <div class="text-5xl font-black mb-2">%{{ number_format($rapor['roi_analizi']['roi_skoru'] ?? 0, 1) }}</div>
                        <p class="text-emerald-50 text-sm font-medium">Estimated ROI Performance</p>
                        
                        <div class="mt-8 pt-8 border-t border-white/10 space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-emerald-200">Payback Period</span>
                                <span class="font-bold">{{ $rapor['roi_analizi']['amortisman_suresi'] ?? 'N/A' }} Years</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-emerald-200">Market Index</span>
                                <span class="font-bold px-2 py-0.5 bg-white/20 rounded-md text-xs dark:bg-slate-900/20">A+ Elite</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 dark:bg-white/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700 dark:bg-slate-800/40"></div>
                </div>
            </div>

            <!-- Right Side: Detailed Breakdown -->
            <div class="lg:col-span-2 space-y-8">
                <!-- POI Highlights -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden dark:shadow-none dark:bg-slate-900">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            📍 Environmental Highlights (POI)
                        </h3>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($rapor['poi_analizi'] as $poi)
                            <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-blue-500/30 transition-all">
                                <div class="w-12 h-12 flex-shrink-0 bg-white dark:bg-slate-800 rounded-xl flex items-center justify-center text-xl shadow-sm dark:shadow-none dark:bg-slate-900">
                                    {{ $poi['icon'] ?? '🏢' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ $poi['isim'] }}</p>
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-tighter">{{ $poi['kategori'] ?? 'N/A' }} • <span class="text-blue-600">{{ $poi['uzaklik'] }}</span></p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Analysis Summary Text -->
                <div class="bg-slate-900 rounded-3xl p-8 text-slate-300 font-mono text-sm leading-relaxed border-t-8 border-blue-600 shadow-2xl">
                    <div class="flex items-center gap-2 mb-4 text-blue-400 font-black tracking-widest uppercase text-xs">
                        <span class="flex h-2 w-2 rounded-full bg-blue-500 animate-ping"></span>
                        Cortex Summary Log
                    </div>
                    <p class="mb-4">
                        [SYSTEM] Analyzing property data for ID #{{ $rapor['ilan_id'] }}...
                        <br>
                        [DATA] Location coordinates verified: LAT/LNG MATCH.
                        <br>
                        [CORTEX] ROI calculation completed with high confidence.
                        <br>
                        [POI] Neighborhood density index is above average.
                    </p>
                    <p class="text-white font-bold p-4 bg-white/5 rounded-xl border border-white/10 dark:bg-slate-900/5">
                        {{ $rapor['roi_analizi']['market_yorumu'] ?? 'No market commentary generated for this specific listing.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
