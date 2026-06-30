@extends('admin.layouts.admin')

@section('title', 'Context7 Kontrol Paneli')

@section('content')
<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Dakikalık İstek</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['rpm'] ?? null) ? number_format($metrics['rpm']) : '-' }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Ortalama Yanıt (ms)</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['avg_ms'] ?? null) ? number_format($metrics['avg_ms']) : '-' }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Uptime (sn)</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['uptime'] ?? null) ? number_format($metrics['uptime']) : '-' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">p95 (ms)</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['p95_ms'] ?? null) ? number_format($metrics['p95_ms']) : '-' }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">p99 (ms)</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['p99_ms'] ?? null) ? number_format($metrics['p99_ms']) : '-' }}</div>
        </div>
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Trend RPM (15/60dk)</div>
            <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ is_numeric($metrics['rpm15'] ?? null) ? number_format($metrics['rpm15']) : '-' }} / {{ is_numeric($metrics['rpm60'] ?? null) ? number_format($metrics['rpm60']) : '-' }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Ort. Yanıt (ms): {{ is_numeric($metrics['avg15'] ?? null) ? number_format($metrics['avg15']) : '-' }} / {{ is_numeric($metrics['avg60'] ?? null) ? number_format($metrics['avg60']) : '-' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">Context7 Kurallar</h3>
                <a href="{{ route('admin.ilanlar.api.context7.rules') }}" class="text-sm text-blue-600 dark:text-blue-400">JSON</a>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Version</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ safe_html($rules['version'] ?? '-') }}</div>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Forbidden</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ (int)($rules['forbidden_count'] ?? 0) }}</div>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Required</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ (int)($rules['required_count'] ?? 0) }}</div>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Forbidden Patterns</h4>
                    <ul class="text-sm text-gray-700 dark:text-slate-200 list-disc pl-5 space-y-1 dark:text-slate-300">
                        @forelse(($rules['forbidden'] ?? []) as $f)
                            <li>{{ safe_html($f) }}</li>
                        @empty
                            <li>Tanımlı değil</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Required Patterns</h4>
                    <ul class="text-sm text-gray-700 dark:text-slate-200 list-disc pl-5 space-y-1 dark:text-slate-300">
                        @forelse(($rules['required'] ?? []) as $r)
                            <li>{{ safe_html($r) }}</li>
                        @empty
                            <li>Tanımlı değil</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">Hızlı Erişim</h3>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('admin.ilanlar.api.health') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-all">Health</a>
                <a href="{{ route('admin.ilanlar.api.stats') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all dark:text-slate-300">Stats</a>
                <a href="{{ route('admin.ilanlar.api.performance') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all dark:text-slate-300">Performance</a>
                <a href="{{ route('admin.ilanlar.api.metrics') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all dark:text-slate-300">Metrics</a>
            </div>
        </div>
    </div>
</div>
@endsection