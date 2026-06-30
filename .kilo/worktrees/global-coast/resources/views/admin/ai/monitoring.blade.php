@extends('admin.layouts.admin')

@section('title', 'Cortex Monitoring')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        🛡️ Cortex Guardian Monitoring
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Sistem sağlığı, performans metrikleri ve operasyonel izleme
                    </p>
                </div>
                <div class="flex space-x-3">
                    <select onchange="window.location.href='?hours='+this.value" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100">
                        <option value="1" {{ request('hours') == 1 ? 'selected' : '' }}>Son 1 Saat</option>
                        <option value="24" {{ request('hours', 24) == 24 ? 'selected' : '' }}>Son 24 Saat</option>
                        <option value="168" {{ request('hours') == 168 ? 'selected' : '' }}>Son 7 Gün</option>
                    </select>
                    <button onclick="location.reload()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Yenile
                    </button>
                </div>
            </div>
        </div>

        <!-- General Metrics -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📊 Genel Metrikler</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total API Calls -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">API Çağrıları</h3>
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($metrics['total_api_calls'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Son {{ request('hours', 24) }} saat</p>
                </div>

                <!-- Average Response Time -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Yanıt</h3>
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($metrics['avg_response_time'] ?? 0) }}ms</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yanıt süresi</p>
                </div>

                <!-- Success Rate -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Başarı Oranı</h3>
                        <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($metrics['success_rate'] ?? 0, 1) }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Başarılı istekler</p>
                </div>

                <!-- Error Rate -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Hata Oranı</h3>
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($metrics['error_rate'] ?? 0, 1) }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Hatalı istekler</p>
                </div>
            </div>
        </div>

        <!-- Publish Metrics -->
        @if(isset($publishMetrics) && !empty($publishMetrics))
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🚀 Yayınlama Metrikleri</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Attempts -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Toplam Deneme</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ number_format($publishMetrics['total_attempts'] ?? 0) }}</p>
                </div>

                <!-- Blocked -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Engellenen</h3>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($publishMetrics['blocked'] ?? 0) }}</p>
                </div>

                <!-- Approved -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Onaylanan</h3>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($publishMetrics['approved'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- System Status -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">💚 Sistem Durumu</h2>
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="space-y-4">
                    <!-- Cortex Scoring -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Cortex Scoring Engine</span>
                        </div>
                        <span class="text-sm text-green-600 dark:text-green-400">Çalışıyor</span>
                    </div>

                    <!-- Feature Resolution -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Feature Resolution Service</span>
                        </div>
                        <span class="text-sm text-green-600 dark:text-green-400">Çalışıyor</span>
                    </div>

                    <!-- Quality Check -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">AI Quality Check</span>
                        </div>
                        <span class="text-sm text-green-600 dark:text-green-400">Çalışıyor</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Endpoint -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">JSON API Endpoint</p>
                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                        <code class="bg-blue-100 dark:bg-blue-900/40 px-2 py-1 rounded">
                            GET {{ route('admin.ai.monitoring.json', ['hours' => request('hours', 24)]) }}
                        </code>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
