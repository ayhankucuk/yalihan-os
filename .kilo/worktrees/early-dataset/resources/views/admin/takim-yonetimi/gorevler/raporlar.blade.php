@extends('admin.layouts.admin')

@section('title', 'Görev Raporları')

@push('styles')
<style>
.stat-card{position:relative;overflow:hidden;transition:transform .25s cubic-bezier(.34,1.56,.64,1),box-shadow .25s ease}
.stat-card::before{content:'';position:absolute;inset:0;opacity:0;transition:opacity .3s;border-radius:inherit}
.stat-card:hover{transform:translateY(-4px)}
.stat-card:hover::before{opacity:1}
.stat-card-blue::before{background:radial-gradient(circle at 30% 50%,rgba(99,102,241,.12),transparent 70%)}
.stat-card-green::before{background:radial-gradient(circle at 30% 50%,rgba(16,185,129,.12),transparent 70%)}
.stat-card-amber::before{background:radial-gradient(circle at 30% 50%,rgba(245,158,11,.12),transparent 70%)}
.stat-card-red::before{background:radial-gradient(circle at 30% 50%,rgba(239,68,68,.12),transparent 70%)}
@keyframes countUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.stat-number{animation:countUp .6s cubic-bezier(.16,1,.3,1) both}
.stat-card:nth-child(1) .stat-number{animation-delay:.05s}
.stat-card:nth-child(2) .stat-number{animation-delay:.12s}
.stat-card:nth-child(3) .stat-number{animation-delay:.19s}
.stat-card:nth-child(4) .stat-number{animation-delay:.26s}
.stat-orb{position:absolute;right:-20px;top:-20px;width:80px;height:80px;border-radius:50%;opacity:.07;transition:opacity .3s}
.stat-card:hover .stat-orb{opacity:.14}
.progress-bar-fill{width:0%;animation:progressFill 1s cubic-bezier(.16,1,.3,1) forwards;animation-delay:.5s}
@keyframes progressFill{to{width:var(--target-width)}}
.chart-card{transition:box-shadow .25s ease}
.chart-card:hover{box-shadow:0 8px 32px -4px rgba(0,0,0,.12)}
.dark .chart-card:hover{box-shadow:0 8px 32px -4px rgba(0,0,0,.4)}
.perf-row{transition:background-color .15s ease}
.export-btn{position:relative;overflow:hidden;transition:all .2s cubic-bezier(.34,1.56,.64,1)}
.export-btn:hover{transform:translateY(-2px) scale(1.02)}
.avatar-ring{box-shadow:0 0 0 2px white,0 0 0 3.5px rgba(99,102,241,.35)}
.dark .avatar-ring{box-shadow:0 0 0 2px #1e293b,0 0 0 3.5px rgba(99,102,241,.45)}
@media print{.no-print{display:none!important}.stat-card{box-shadow:none!important;border:1px solid #e5e7eb!important}}
</style>
@endpush

@section('content')

{{-- SAYFA BAŞLIĞI --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
    <div>
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/25 flex-shrink-0">
                <x-icon name="grafik" class="w-5 h-5 text-white" />
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-50 leading-tight">Görev Raporları</h1>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Takım performansı & görev analizi</p>
            </div>
        </div>
        <nav aria-label="breadcrumb" class="mt-3">
            <ol class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-slate-500">
                <li><a href="{{ route('admin.dashboard.index') }}" class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors font-medium">Dashboard</a></li>
                <li><x-icon name="sag-chevron" class="w-3 h-3 opacity-50" /></li>
                <li><a href="{{ route('admin.takim.gorevler.index') }}" class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors font-medium">Görevler</a></li>
                <li><x-icon name="sag-chevron" class="w-3 h-3 opacity-50" /></li>
                <li class="text-gray-600 dark:text-slate-300 font-semibold">Raporlar</li>
            </ol>
        </nav>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0 no-print">
        <button type="button" onclick="window.print()" class="export-btn inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm hover:border-indigo-300 dark:hover:border-slate-500 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <x-icon name="indir" class="w-4 h-4" /><span class="hidden sm:inline">Yazdır</span>
        </button>
        <button type="button" onclick="exportToPDF()" class="export-btn inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm hover:border-red-300 dark:hover:border-slate-500 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
            <x-icon name="kutu" class="w-4 h-4 text-red-400" /><span class="hidden sm:inline">PDF</span>
        </button>
        <button type="button" onclick="exportToExcel()" class="export-btn inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-violet-600 border border-transparent rounded-xl shadow-md shadow-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <x-icon name="grafik" class="w-4 h-4" /><span class="hidden sm:inline">Excel</span>
        </button>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $toplam      = $stats['toplam_gorev']      ?? 0;
    $tamamlanan  = $stats['tamamlanan_gorev']  ?? 0;
    $devamEden   = $stats['devam_eden_gorev']  ?? 0;
    $geciken     = $stats['geciken_gorev']     ?? 0;
    $tamamOrani  = $toplam > 0 ? round(($tamamlanan / $toplam) * 100) : 0;
    $tehlike     = $geciken > 0;
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card stat-card-blue bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-5 shadow-sm">
        <div class="stat-orb bg-indigo-500"></div>
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Toplam Görev</p>
                <p class="stat-number text-3xl font-extrabold text-gray-900 dark:text-slate-50 leading-none">{{ $toplam }}</p>
                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium mt-2 flex items-center gap-1"><x-icon name="liste" class="w-3.5 h-3.5" />Tüm görevler</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0 ml-3">
                <x-icon name="liste" class="w-6 h-6 text-indigo-500 dark:text-indigo-400" />
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-green bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-5 shadow-sm">
        <div class="stat-orb bg-emerald-500"></div>
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Tamamlanan</p>
                <p class="stat-number text-3xl font-extrabold text-gray-900 dark:text-slate-50 leading-none">{{ $tamamlanan }}</p>
                <p class="text-xs text-emerald-500 dark:text-emerald-400 font-medium mt-2 flex items-center gap-1"><x-icon name="onay-daire" class="w-3.5 h-3.5" />%{{ $tamamOrani }} başarı</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 ml-3">
                <x-icon name="onay-daire" class="w-6 h-6 text-emerald-500 dark:text-emerald-400" />
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-amber bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-5 shadow-sm">
        <div class="stat-orb bg-amber-500"></div>
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Devam Eden</p>
                <p class="stat-number text-3xl font-extrabold text-gray-900 dark:text-slate-50 leading-none">{{ $devamEden }}</p>
                <p class="text-xs text-amber-500 dark:text-amber-400 font-medium mt-2 flex items-center gap-1"><x-icon name="saat" class="w-3.5 h-3.5" />Aktif görev</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0 ml-3">
                <x-icon name="saat" class="w-6 h-6 text-amber-500 dark:text-amber-400" />
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-red bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-5 shadow-sm">
        <div class="stat-orb bg-red-500"></div>
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Geciken</p>
                <p class="stat-number text-3xl font-extrabold text-gray-900 dark:text-slate-50 leading-none">{{ $geciken }}</p>
                <p class="text-xs font-medium mt-2 flex items-center gap-1 {{ $tehlike ? 'text-red-500 dark:text-red-400' : 'text-emerald-500 dark:text-emerald-400' }}">
                    <x-icon name="{{ $tehlike ? 'uyari' : 'onay' }}" class="w-3.5 h-3.5" />{{ $tehlike ? 'Dikkat gerekli' : 'Temiz durum' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $tehlike ? 'bg-red-50 dark:bg-red-900/30' : 'bg-emerald-50 dark:bg-emerald-900/30' }} flex items-center justify-center flex-shrink-0 ml-3">
                <x-icon name="uyari" class="w-6 h-6 {{ $tehlike ? 'text-red-500 dark:text-red-400' : 'text-emerald-500 dark:text-emerald-400' }}" />
            </div>
        </div>
    </div>
</div>

{{-- GRAFİKLER --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

    {{-- Doughnut --}}
    <div class="chart-card lg:col-span-2 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm overflow-hidden">
        <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-slate-700/60 flex items-center justify-between">
            <div>
                <h5 class="text-sm font-semibold text-gray-800 dark:text-slate-200">Durum Dağılımı</h5>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Görev kategorileri</p>
            </div>
            <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <x-icon name="grafik" class="w-4 h-4 text-indigo-500" />
            </div>
        </div>
        <div class="p-6 flex flex-col items-center">
            <div class="relative w-full max-w-[220px]">
                <canvas id="gorevDurumChart" width="220" height="220"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-extrabold text-gray-900 dark:text-slate-50">{{ $toplam }}</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500 font-medium">toplam</span>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 w-full">
                @php
                    $legend = [
                        ['label'=>'Tamamlanan','color'=>'#10b981','value'=>$tamamlanan],
                        ['label'=>'Devam Eden', 'color'=>'#f59e0b','value'=>$devamEden],
                        ['label'=>'Beklemede',  'color'=>'#6366f1','value'=>$stats['beklemede_gorev'] ?? 0],
                        ['label'=>'Geciken',    'color'=>'#ef4444','value'=>$geciken],
                    ];
                @endphp
                @foreach($legend as $item)
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $item['color'] }}"></span>
                    <span class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ $item['label'] }}</span>
                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 ml-auto">{{ $item['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Line Trend --}}
    <div class="chart-card lg:col-span-3 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm overflow-hidden">
        <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-slate-700/60 flex items-center justify-between">
            <div>
                <h5 class="text-sm font-semibold text-gray-800 dark:text-slate-200">Aylık Görev Trendi</h5>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Son 6 aylık görünüm</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 flex-shrink-0"></span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">Oluşturulan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">Tamamlanan</span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <canvas id="gorevTrendChart" height="200"></canvas>
        </div>
    </div>
</div>

{{-- PERFORMANS TABLOSU --}}
<div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h5 class="text-sm font-semibold text-gray-800 dark:text-slate-200">Danışman Performans Raporu</h5>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Kişi başı görev analizi ve başarı oranları</p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-xs font-medium text-indigo-600 dark:text-indigo-400">
            <x-icon name="kullanicilar" class="w-3.5 h-3.5" />{{ count($danismanPerformans ?? []) }} danışman
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50/70 dark:bg-slate-900/40">
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Danışman</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Toplam</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Tamamlanan</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider min-w-[180px]">Başarı Oranı</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Ort. Süre</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Geciken</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                @forelse($danismanPerformans ?? [] as $idx => $p)
                @php
                    $oran = $p['basarı_oranı'] ?? 0;
                    $renkBar  = $oran >= 80 ? '#10b981' : ($oran >= 50 ? '#f59e0b' : '#ef4444');
                    $renkText = $oran >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($oran >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                    $medals = ['🥇','🥈','🥉'];
                @endphp
                <tr class="perf-row hover:bg-indigo-50/30 dark:hover:bg-slate-700/30 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $p['avatar'] ?? asset('images/default-avatar.png') }}" alt="{{ $p['ad'] }}" class="avatar-ring w-9 h-9 rounded-full object-cover">
                                @if($idx < 3)<span class="absolute -top-1.5 -right-1.5 text-sm leading-none">{{ $medals[$idx] }}</span>@endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 leading-tight">{{ $p['ad'] }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $p['email'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ $p['toplam_gorev'] }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $p['tamamlanan_gorev'] }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden min-w-[100px]">
                                <div class="progress-bar-fill h-2 rounded-full" style="--target-width:{{ $oran }}%;background:{{ $renkBar }}"></div>
                            </div>
                            <span class="text-xs font-bold w-10 text-right {{ $renkText }}">%{{ $oran }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-slate-400">
                            <x-icon name="saat" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500" />{{ $p['ortalama_sure'] }} gün
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($p['geciken_gorev'] > 0)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                            <x-icon name="uyari" class="w-3 h-3" />{{ $p['geciken_gorev'] }}
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <x-icon name="onay" class="w-3 h-3" />Yok
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-2xl bg-gray-50 dark:bg-slate-900/50 flex items-center justify-center">
                                <x-icon name="bilgi" class="w-8 h-8 text-gray-300 dark:text-slate-600" />
                            </div>
                            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Henüz performans verisi bulunmuyor</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">Görevler tamamlandıkça burada görünecek</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" />
<script>
(function () {
    'use strict';
    const isDark = () => document.documentElement.classList.contains('dark');
    const gridC  = () => isDark() ? 'rgba(148,163,184,.10)' : 'rgba(107,114,128,.08)';
    const tickC  = () => isDark() ? '#94a3b8' : '#6b7280';

    /* Doughnut */
    const el1 = document.getElementById('gorevDurumChart');
    if (el1) {
        new Chart(el1.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Tamamlanan','Devam Eden','Beklemede','Geciken'],
                datasets: [{
                    data: [{{ $tamamlanan }},{{ $devamEden }},{{ $stats['beklemede_gorev'] ?? 0 }},{{ $geciken }}],
                    backgroundColor: ['#10b981','#f59e0b','#6366f1','#ef4444'],
                    hoverBackgroundColor: ['#059669','#d97706','#4f46e5','#dc2626'],
                    borderWidth: 3,
                    borderColor: isDark() ? '#1e293b' : '#ffffff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '68%',
                animation: { animateRotate: true, duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f1f5f9',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                                const pct = total > 0 ? Math.round((ctx.parsed/total)*100) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed + ' (%' + pct + ')';
                            }
                        }
                    }
                }
            }
        });
    }

    /* Line Trend */
    const el2 = document.getElementById('gorevTrendChart');
    if (el2) {
        const c2 = el2.getContext('2d');
        const grad = (color) => {
            const g = c2.createLinearGradient(0, 0, 0, 200);
            g.addColorStop(0, color.replace('1)', '.18)'));
            g.addColorStop(1, color.replace('1)', '0)'));
            return g;
        };
        new Chart(c2, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendData['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Oluşturulan',
                        data: {!! json_encode($trendData['olusturulan'] ?? []) !!},
                        borderColor: '#6366f1',
                        backgroundColor: grad('rgba(99,102,241,1)'),
                        borderWidth: 2.5,
                        tension: 0.45,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: isDark() ? '#1e293b' : '#ffffff',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Tamamlanan',
                        data: {!! json_encode($trendData['tamamlanan'] ?? []) !!},
                        borderColor: '#10b981',
                        backgroundColor: grad('rgba(16,185,129,1)'),
                        borderWidth: 2.5,
                        tension: 0.45,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: isDark() ? '#1e293b' : '#ffffff',
                        pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                animation: { duration: 900, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f1f5f9',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridC(), drawBorder: false },
                        ticks: { color: tickC(), font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridC(), drawBorder: false },
                        ticks: { color: tickC(), font: { size: 11 }, precision: 0 },
                        border: { display: false },
                    }
                }
            }
        });
    }

    /* Export */
    window.exportToPDF   = () => window.print();
    window.exportToExcel = () => {
        const btn = document.querySelector('[onclick="exportToExcel()"]');
        if (!btn) return;
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 11-8 8z"></path></svg> <span>Hazırlanıyor…</span>';
        btn.disabled = true;
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2000);
    };

})();
</script>
@endpush

@endsection
