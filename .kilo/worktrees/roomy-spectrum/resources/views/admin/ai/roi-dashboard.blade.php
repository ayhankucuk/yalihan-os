@extends('admin.layouts.admin')

@section('title', 'YalıhanAI ROI & Performance Dashboard')

@section('content')
<div class="p-6 space-y-8 bg-[#0a0a0c] dark:bg-[#0a0a0c] min-h-screen text-gray-100 dark:text-slate-100">
    <!-- Header -->
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-light tracking-tight text-white">Neural ROI <span class="text-indigo-500 font-medium">Dashboard</span></h1>
            <p class="text-gray-400 mt-2 font-light">Autonomous AI Efficiency & Business Intelligence</p>
        </div>
        <div class="text-right">
            <div class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-500 mb-1">Sistem Durumu</div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm font-medium text-green-400">Otonom Öğrenme Aktif</span>
            </div>
        </div>
    </div>

    <!-- Main KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Hero Metric: Time Saved -->
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900/40 to-black p-6 rounded-2xl border border-indigo-500/30 group hover:border-indigo-400/50 transition-all duration-500">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 dark:bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all"></div>
            <div class="text-xs uppercase tracking-widest text-indigo-400 font-semibold mb-4">Total Time Saved</div>
            <div class="flex items-baseline space-x-2">
                <span class="text-5xl font-light text-white tracking-tighter">{{ $stats['total_saved_hours'] }}</span>
                <span class="text-lg text-indigo-300">Saat</span>
            </div>
            <div class="mt-4 text-sm text-gray-400 dark:text-gray-400 leading-tight">İlan girişi ve görsel analiz süreçlerinde kazanılan otonom süre.</div>
        </div>

        <!-- Efficiency Score -->
        <div class="bg-[#111114] dark:bg-[#111114] p-6 rounded-2xl border border-white/5 dark:border-white/10 hover:border-white/10 transition-all">
            <div class="text-xs uppercase tracking-widest text-emerald-400 font-semibold mb-4">Efficiency Score</div>
            <div class="flex items-baseline space-x-2">
                <span class="text-5xl font-light text-white tracking-tighter">{{ $stats['efficiency_score'] }}</span>
                <span class="text-lg text-gray-500 dark:text-gray-400">/100</span>
            </div>
            <div class="mt-4 w-full h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.05);">
                <div class="h-full transition-all duration-1000" style="width: {{ $stats['efficiency_score'] }}%; background: #10b981;"></div>
            </div>
        </div>

        <!-- Accuracy Rate -->
        <div class="bg-[#111114] dark:bg-[#111114] p-6 rounded-2xl border border-white/5 dark:border-white/5">
            <div class="text-xs uppercase tracking-widest text-amber-400 dark:text-amber-400 font-semibold mb-4">AI Accuracy</div>
            <div class="flex items-baseline space-x-2">
                <span class="text-5xl font-light text-white tracking-tighter">{{ $stats['accuracy_rate'] }}</span>
                <span class="text-lg text-amber-200">%</span>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-500">Kullanıcı tarafından kabul edilen öneri oranı.</div>
        </div>

        <!-- Total Cost -->
        <div class="bg-[#111114] dark:bg-[#111114] p-6 rounded-2xl border border-white/5 dark:border-white/5">
            <div class="text-xs uppercase tracking-widest text-indigo-400 dark:text-indigo-400 font-semibold mb-4">Cost Performance</div>
            <div class="flex items-baseline space-x-2">
                <span class="text-5xl font-light text-white tracking-tighter">${{ $stats['total_cost_usd'] }}</span>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-500">Son 30 günlük toplam API yatırımı.</div>
        </div>
    </div>

    <!-- Experimental Lab & Categorical Data -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Experiments Table -->
        <div class="md:col-span-2 bg-[#111114] dark:bg-[#111114] rounded-2xl border border-white/5 dark:border-white/10 overflow-hidden">
            <div class="p-6 border-b border-white/5 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white">Neural Experiments <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">A/B Testing Engine</span></h3>
                <button class="px-4 py-2 bg-indigo-600 dark:bg-indigo-600 hover:bg-indigo-500 dark:hover:bg-indigo-500 text-white dark:text-white text-xs rounded-lg transition-all">Yeni Deney Başlat</button>
            </div>
            <div class="p-0">
                <table class="w-full text-left text-sm dark:text-slate-200">
                     <thead class="bg-gray-100 dark:bg-slate-900 text-gray-400 dark:text-slate-200 uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="px-6 py-4 font-medium">Experiment Name</th>
                            <th class="px-6 py-4 font-medium">Target</th>
                            <th class="px-6 py-4 font-medium">Samples</th>
                            <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Durum</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 dark:divide-white/10">
                        @foreach($experiments as $exp)
                         <tr class="hover:bg-gray-100 dark:hover:bg-indigo-900/20 transition-all">
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">{{ $exp->deney_adi }}</div>
                                <div class="text-[10px] text-gray-500 font-mono">{{ $exp->deney_slug }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-200/10 dark:bg-indigo-900/30 rounded text-xs text-gray-300 dark:text-indigo-200 capitalize">{{ $exp->hedef_kategori }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-400">{{ $exp->usages_count }}</td>
                             <td class="px-6 py-4">
                                @if($exp->aktiflik_durumu)
                                    <span class="text-emerald-400 dark:text-emerald-400 bg-emerald-400/10 dark:bg-emerald-900/20 px-2 py-1 rounded text-xs">Live</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 bg-gray-200/5 dark:bg-gray-700/50 px-2 py-1 rounded text-xs">Sealed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="text-indigo-400 dark:text-indigo-400 hover:text-white dark:hover:text-white transition-all">Analiz &rarr;</a>
                            </td>
                        </tr>
                        @endforeach
                        @if($experiments->isEmpty())
                        <tr>
                             <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 italic">Henüz aktif deney bulunmamaktadır.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick AI Control -->
        <div class="space-y-6">
            <div class="bg-indigo-600/10 border border-indigo-500/20 p-6 rounded-2xl">
                <h4 class="text-indigo-300 font-medium mb-4">Neural Control Unit</h4>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-300 dark:text-slate-200">Otonom Eşik Güncelleme</span>
                         <div class="w-10 h-5 bg-emerald-500/20 dark:bg-emerald-500/10 rounded-full relative">
                            <div class="absolute right-1 top-1 w-3 h-3 bg-emerald-500 dark:bg-emerald-400 rounded-full"></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center opacity-50">
                        <span class="text-sm text-gray-300 dark:text-gray-400">A/B Winner Auto-Promotion</span>
                         <div class="w-10 h-5 bg-gray-100 dark:bg-slate-900 rounded-full relative">
                            <div class="absolute left-1 top-1 w-3 h-3 bg-gray-500 dark:bg-gray-400 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[#111114] dark:bg-[#111114] border border-white/5 dark:border-white/10 p-6 rounded-2xl">
                <h4 class="text-white dark:text-white font-medium mb-2">Efficiency Insight</h4>
                <p class="text-xs text-gray-400 dark:text-gray-400 leading-relaxed italic">"AI destekli ilan girişleri, manuel süreçlere göre **%82 daha hızlı** tamamlanıyor. En yüksek verimlilik **Villa** kategorisinde gözlemlendi."</p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Avant-Garde UI Customizations */
    [tracking-tighter] { letter-spacing: -0.05em; }
    .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
</style>
@endsection
