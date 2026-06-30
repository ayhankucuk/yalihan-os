@extends('admin.layouts.admin')

@section('title', 'Page Analyzer Dashboard')

@push('meta')
    <meta name="description" content="EmlakPro Page Analyzer - Real-time analysis and monitoring dashboard">
@endpush

@section('content')
    <div x-data="pageAnalyzer()" x-init="init()">
        <!-- Header -->
        <div class="content-header mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        🔍 Page Analyzer Dashboard
                    </h1>
                    <p class="text-gray-600 mt-2">Real-time analysis and monitoring of admin pages</p>
                </div>
                <div class="flex space-x-3">
                    <button @click="refreshMetrics()" :disabled="loading"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 touch-target-optimized">
                        <svg class="w-4 h-4 mr-2" :class="loading ? 'animate-spin' : ''" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Refresh
                    </button>
                    <button @click="runFullAnalysis()" :disabled="analyzing"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized">
                        <svg class="w-4 h-4 mr-2" :class="analyzing ? 'animate-spin' : ''" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span x-text="analyzing ? 'Analyzing...' : 'Run Analysis'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Overall Health Status -->
        <div class="bg-white dark:bg-slate-900 shadow-sm p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2 dark:text-slate-200">🏥 System Health</h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                                class="w-3 h-3 rounded-full mr-2 {{ ($healthData['score'] ?? 0) >= 70 ? 'bg-green-500' : (($healthData['score'] ?? 0) >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}">
                            </div>
                            <span class="text-lg font-semibold">{{ $healthData['score'] ?? 0 }}/100</span>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-sm font-medium
                            {{ $healthData['status'] === 'excellent'
                                ? 'bg-green-100 text-green-800'
                                : ($healthData['status'] === 'good'
                                    ? 'bg-blue-100 text-blue-800'
                                    : ($healthData['status'] === 'fair'
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-red-100 text-red-800')) }}">
                            {{ ucfirst($healthData['status'] ?? 'Unknown') }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Last Updated</p>
                    <p class="text-sm font-medium">{{ now()->format('H:i:s') }}</p>
                </div>
            </div>
        </div>

        <!-- 🚀 Analyzer Yenilikleri -->
        <div class="bg-white dark:bg-slate-900 shadow-sm p-6 mb-8 bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-purple-800 flex items-center">
                    🚀 Analyzer Yenilikleri
                    <span
                        class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full font-medium animate-pulse">NEW</span>
                </h2>
                <div class="text-sm text-purple-600">5 yeni özellik aktif</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- CSS Compliance Check -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">🎨</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">CSS Compliance Check</h3>
                            <p class="text-sm text-gray-600">Neo Design System vs Legacy CSS detection</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>

                <!-- Innovation Detection -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">🔍</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">Innovation Detection</h3>
                            <p class="text-sm text-gray-600">AI/ML, Real-time, PWA features detection</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>

                <!-- Smart Categorization -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">📊</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">Smart Categorization</h3>
                            <p class="text-sm text-gray-600">AI Sistemi, İlan Yönetimi, CRM kategorileri</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>

                <!-- Intelligent Scoring -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">🧠</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">Intelligent Scoring</h3>
                            <p class="text-sm text-gray-600">Context-aware scoring with bonus points</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>

                <!-- Smart Recommendations -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">💡</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">Smart Recommendations</h3>
                            <p class="text-sm text-gray-600">Priority-based actionable insights</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>

                <!-- Analytics Dashboard -->
                <div class="bg-white p-4 rounded-lg border border-purple-100 hover:shadow-md transition-shadow dark:bg-slate-900">
                    <div class="flex items-center mb-2">
                        <span class="text-2xl mr-3">📈</span>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-slate-200">Enhanced Analytics</h3>
                            <p class="text-sm text-gray-600">CSS compliance rates, innovation tracking</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">NEW</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center text-purple-800">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">Bu yenilikler sayesinde sayfalar artık daha akıllı analiz ediliyor ve
                        CSS compliance, innovation detection gibi gelişmiş özellikler aktif!</span>
                </div>
            </div>
        </div>

        <!-- Critical Issues Alert -->
        <div x-show="criticalIssues.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Critical Issues Detected</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <template x-for="(issue, index) in criticalIssues" :key="'issue-' + index">
                                <li x-text="issue.page + ': ' + (issue.issue || issue.description || 'Unknown issue')">
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Performance Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Telegram Bot -->
            <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">🤖 Telegram Bot</h3>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Success Rate</span>
                        <span
                            class="font-medium text-green-600">{{ $performanceData['telegram_bot']['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Response Time</span>
                        <span class="font-medium">{{ $performanceData['telegram_bot']['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Active Users</span>
                        <span class="font-medium">{{ $performanceData['telegram_bot']['active_users'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Address Management -->
            <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">🏠 Adres Yönetimi</h3>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Success Rate</span>
                        <span
                            class="font-medium text-green-600">{{ $performanceData['adres_yonetimi']['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Response Time</span>
                        <span
                            class="font-medium">{{ $performanceData['adres_yonetimi']['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Error</span>
                        <span
                            class="text-sm text-green-600">{{ $performanceData['adres_yonetimi']['last_error'] ?? 'None' }}</span>
                    </div>
                </div>
            </div>

            <!-- My Listings -->
            <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">🏘️ My Listings</h3>
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Success Rate</span>
                        <span
                            class="font-medium text-red-600">{{ $performanceData['my_listings']['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Response Time</span>
                        <span class="font-medium">{{ $performanceData['my_listings']['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status</span>
                        <span class="text-sm text-red-600">Not Implemented</span>
                    </div>
                </div>
            </div>

            <!-- Analytics -->
            <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">📊 Analytics</h3>
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Success Rate</span>
                        <span
                            class="font-medium text-red-600">{{ $performanceData['analytics']['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Response Time</span>
                        <span class="font-medium">{{ $performanceData['analytics']['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status</span>
                        <span class="text-sm text-red-600">Not Implemented</span>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">📢 Notifications</h3>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Success Rate</span>
                        <span
                            class="font-medium text-yellow-600">{{ $performanceData['notifications']['success_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Response Time</span>
                        <span
                            class="font-medium">{{ $performanceData['notifications']['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Active Users</span>
                        <span class="font-medium">{{ $performanceData['notifications']['active_users'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 💡 Smart Recommendations -->
        <div class="bg-white dark:bg-slate-900 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                    💡 Smart Recommendations
                    <span
                        class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">AI-Powered</span>
                </h2>
                <div class="text-sm text-gray-600">{{ count($recommendations ?? []) }} adet öneri</div>
            </div>

            @if (isset($recommendations) && count($recommendations) > 0)
                <div class="space-y-4">
                    @foreach ($recommendations as $index => $recommendation)
                        @php
                            // Handle both string and array recommendations
                            $rec = is_string($recommendation)
                                ? ['title' => $recommendation, 'priority' => 'MEDIUM', 'icon' => '🔧']
                                : $recommendation;

                            // Determine priority from title/content
                            $priority = 'MEDIUM';
                            $icon = '🔧';

                            if (str_contains(strtolower($rec['title']), 'critical')) {
                                $priority = 'URGENT';
                                $icon = '🚨';
                            } elseif (str_contains(strtolower($rec['title']), 'missing')) {
                                $priority = 'HIGH';
                                $icon = '⚠️';
                            } elseif (str_contains(strtolower($rec['title']), 'context7')) {
                                $priority = 'INNOVATION';
                                $icon = '🚀';
                            }
                        @endphp

                        <div
                            class="bg-gradient-to-r {{ $priority === 'URGENT' ? 'from-red-50 to-red-100 border-red-200' : ($priority === 'HIGH' ? 'from-orange-50 to-orange-100 border-orange-200' : ($priority === 'INNOVATION' ? 'from-purple-50 to-purple-100 border-purple-200' : 'from-blue-50 to-blue-100 border-blue-200')) }} p-4 rounded-lg border">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <div class="text-2xl">{{ $icon }}</div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800 mb-1 dark:text-slate-200">{{ $rec['title'] }}</h3>
                                        <p class="text-sm text-gray-600 mb-2">
                                            Sistem iyileştirmesi için önerilen aksiyon
                                        </p>

                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <span class="flex items-center bg-white px-2 py-1 rounded-full dark:bg-slate-900">
                                                <svg class="w-3 h-3 mr-1 text-gray-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ $priority === 'URGENT' ? '2-4 saat' : '1-2 saat' }}
                                            </span>

                                            <span class="flex items-center bg-white px-2 py-1 rounded-full dark:bg-slate-900">
                                                <svg class="w-3 h-3 mr-1 text-gray-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                                Öncelik: {{ $priority }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-400 text-5xl mb-4">🤖</div>
                    <p class="text-gray-600">Henüz akıllı öneri üretilmedi</p>
                    <p class="text-sm text-gray-500 mt-2">Analiz tamamlandıktan sonra burada öneriler görünecek</p>
                </div>
            @endif

            <!-- CSS Compliance & Innovation Stats -->
            @if (isset($cssCompliance) || isset($innovationStats))
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">📊 Detaylı İstatistikler</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if (isset($cssCompliance))
                            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 p-4 rounded-lg border border-blue-200">
                                <h4 class="font-semibold text-blue-800 mb-2 flex items-center">
                                    🎨 CSS Compliance
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Neo Classes:</span>
                                        <span
                                            class="font-medium text-green-600">{{ $cssCompliance['neo_count'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Legacy Classes:</span>
                                        <span
                                            class="font-medium text-red-600">{{ $cssCompliance['legacy_count'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between font-semibold">
                                        <span class="text-gray-800 dark:text-slate-200">Compliance Rate:</span>
                                        <span class="text-blue-600">{{ $cssCompliance['compliance_rate'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (isset($innovationStats))
                            <div
                                class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-200">
                                <h4 class="font-semibold text-purple-800 mb-2 flex items-center">
                                    🚀 Innovation Tracking
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">AI Features:</span>
                                        <span
                                            class="font-medium text-purple-600">{{ $innovationStats['ai_features'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Modern CSS:</span>
                                        <span
                                            class="font-medium text-blue-600">{{ $innovationStats['modern_css'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PWA Features:</span>
                                        <span
                                            class="font-medium text-green-600">{{ $innovationStats['pwa_features'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Real-time:</span>
                                        <span
                                            class="font-medium text-orange-600">{{ $innovationStats['real_time'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
             @endif

             <!-- Emlak Yönetimi Analizi -->
             @if(isset($emlakResults) && count($emlakResults['pages']) > 0)
                 <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
                     <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center dark:text-slate-200">
                         <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                             <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                             </svg>
                         </div>
                         🏠 Emlak Yönetimi Analizi
                     </h3>

                     <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                         <!-- Emlak Özet Kartları -->
                         <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-xl border border-green-200">
                             <div class="flex items-center justify-between">
                                 <div>
                                     <p class="text-green-600 text-sm font-medium">Toplam Emlak Sayfası</p>
                                     <p class="text-3xl font-bold text-green-700">{{ $emlakResults['total_pages'] }}</p>
                                 </div>
                                 <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                     <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                     </svg>
                                 </div>
                             </div>
                         </div>

                         <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 rounded-xl border border-blue-200">
                             <div class="flex items-center justify-between">
                                 <div>
                                     <p class="text-blue-600 text-sm font-medium">AI Özellikli Sayfalar</p>
                                     <p class="text-3xl font-bold text-blue-700">{{ $emlakResults['emlak_specific']['ai_features_count'] ?? 0 }}</p>
                                 </div>
                                 <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                     <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                     </svg>
                                 </div>
                             </div>
                         </div>

                         <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-200">
                             <div class="flex items-center justify-between">
                                 <div>
                                     <p class="text-purple-600 text-sm font-medium">Ortalama Skor</p>
                                     <p class="text-3xl font-bold text-purple-700">{{ $emlakResults['average_score'] }}/10</p>
                                 </div>
                                 <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                     <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                     </svg>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Emlak Sayfa Detayları -->
                     <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-slate-900 dark:border-slate-700 dark:shadow-none">
                         <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                             <h4 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Emlak Sayfaları Detayları</h4>
                         </div>
                         <div class="overflow-x-auto">
                             <table class="w-full">
                                 <thead class="bg-gray-50 dark:bg-slate-900">
                                     <tr>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sayfa</th>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Öncelik</th>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor</th>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Özellikler</th>
                                     </tr>
                                 </thead>
                                 <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                                     @foreach($emlakResults['pages'] as $pageKey => $page)
                                         <tr class="hover:bg-gray-50">
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <div class="flex items-center">
                                                     <div class="w-2 h-2 rounded-full mr-3 {{ ($page['severity'] ?? '') === 'success' ? 'bg-green-400' : (($page['severity'] ?? '') === 'warning' ? 'bg-yellow-400' : 'bg-red-400') }}"></div>
                                                     <div>
                                                         <div class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $page['page'] }}</div>
                                                         <div class="text-sm text-gray-500">{{ $page['type'] }}</div>
                                                     </div>
                                                 </div>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">{{ $page['category'] }}</td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ ($page['priority'] ?? '') === 'CRITICAL' ? 'bg-red-100 text-red-800' : (($page['priority'] ?? '') === 'HIGH' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800') }}">
                                                     {{ $page['priority'] }}
                                                 </span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <div class="flex items-center">
                                                     <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                         <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $page['score'] * 10 }}%"></div>
                                                     </div>
                                                     <span class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $page['score'] }}/10</span>
                                                 </div>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ ($page['severity'] ?? '') === 'success' ? 'bg-green-100 text-green-800' : (($page['severity'] ?? '') === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                     {{ ucfirst($page['severity'] ?? 'unknown') }}
                                                 </span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                                                 {{ $page['ai_features']['count'] ?? 0 }} özellik
                                                 @if(count($page['ai_features']['features'] ?? []) > 0)
                                                     <div class="text-xs text-gray-500">
                                                         @foreach($page['ai_features']['features'] as $feature)
                                                             <span class="inline-block bg-blue-100 text-blue-800 text-xs px-1 py-0.5 rounded mr-1 mb-1">{{ $feature }}</span>
                                                         @endforeach
                                                     </div>
                                                 @endif
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>

                     <!-- Emlak Kategorileri Breakdown -->
                     @if(isset($emlakResults['category_breakdown']) && count($emlakResults['category_breakdown']) > 0)
                         <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6 dark:bg-slate-900 dark:border-slate-700 dark:shadow-none">
                             <h4 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Kategori Bazında Analiz</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                 @foreach($emlakResults['category_breakdown'] as $category => $data)
                                     <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-lg border border-gray-200 dark:border-slate-700">
                                         <div class="flex justify-between items-center mb-2">
                                             <h5 class="font-medium text-gray-800 dark:text-slate-200">{{ $category }}</h5>
                                             <span class="text-sm text-gray-600">{{ $data['count'] }} sayfa</span>
                                         </div>
                                         <div class="flex items-center">
                                             <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                 <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $data['avg_score'] * 10 }}%"></div>
                                             </div>
                                             <span class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $data['avg_score'] }}/10</span>
                                         </div>
                                     </div>
                                 @endforeach
                             </div>
                         </div>
                     @endif
                 </div>
             @endif
         </div>
     </div>
@endsection

@push('scripts')
    <script>
        function pageAnalyzer() {
            return {
                loading: false,
                analyzing: false,
                health: {
                    score: 0,
                    status: 'loading'
                },
                performance: {
                    telegram_bot: {
                        success_rate: 0,
                        avg_response_time: 0,
                        active_users: 0
                    },
                    adres_yonetimi: {
                        success_rate: 0,
                        avg_response_time: 0,
                        active_users: 0,
                        last_error: 'Loading...'
                    },
                    my_listings: {
                        success_rate: 0,
                        avg_response_time: 0,
                        active_users: 0
                    },
                    analytics: {
                        success_rate: 0,
                        avg_response_time: 0,
                        active_users: 0
                    },
                    notifications: {
                        success_rate: 0,
                        avg_response_time: 0,
                        active_users: 0
                    }
                },
                criticalIssues: [],
                recommendations: [],
                lastUpdated: '',

                init() {
                    this.refreshMetrics();
                    // Auto-refresh every 30 seconds
                    setInterval(() => {
                        this.refreshMetrics();
                    }, 30000);
                },

                async refreshMetrics() {
                    this.loading = true;
                    try {
                        const [metricsResponse, healthResponse] = await Promise.all([
                            fetch('/admin/page-analyzer/metrics'),
                            fetch('/admin/page-analyzer/health')
                        ]);

                        const metrics = await metricsResponse.json();
                        const healthData = await healthResponse.json();

                        // Correct data structure mapping
                        this.performance = metrics.performance || {};
                        this.health = healthData || {};
                        this.criticalIssues = Array.isArray(healthData.critical_issues) ? healthData.critical_issues :
                        [];
                        this.recommendations = Array.isArray(healthData.recommendations) ? healthData.recommendations
                            .map(rec => {
                                if (typeof rec === 'string') {
                                    return {
                                        action: rec,
                                        priority: 'medium',
                                        estimated_time: '1-2 hours',
                                        impact: 'Medium'
                                    };
                                }
                                return rec;
                            }) : [];
                        this.lastUpdated = new Date().toLocaleString();
                    } catch (error) {
                        console.error('Error fetching metrics:', error);
                        // Set fallback values on error
                        this.performance = {};
                        this.health = {
                            score: 0,
                            status: 'error'
                        };
                        this.criticalIssues = [];
                        this.recommendations = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async runFullAnalysis() {
                    this.analyzing = true;
                    try {
                        const response = await fetch('/admin/page-analyzer/analyze', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        const result = await response.json();
                        if (result.success) {
                            await this.refreshMetrics();
                            this.showNotification('Analysis completed successfully', 'success');
                        }
                    } catch (error) {
                        console.error('Error running analysis:', error);
                        this.showNotification('Error running analysis', 'error');
                    } finally {
                        this.analyzing = false;
                    }
                },

                getHealthColor(score) {
                    if (score >= 90) return 'bg-green-500';
                    if (score >= 75) return 'bg-blue-500';
                    if (score >= 60) return 'bg-yellow-500';
                    if (score >= 40) return 'bg-orange-500';
                    return 'bg-red-500';
                },

                getHealthBadgeClass(status) {
                    const classes = {
                        'excellent': 'bg-green-100 text-green-800',
                        'good': 'bg-blue-100 text-blue-800',
                        'fair': 'bg-yellow-100 text-yellow-800',
                        'poor': 'bg-orange-100 text-orange-800',
                        'critical': 'bg-red-100 text-red-800'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-800';
                },

                getStatusColor(successRate) {
                    if (successRate >= 95) return 'bg-green-500';
                    if (successRate >= 80) return 'bg-yellow-500';
                    return 'bg-red-500';
                },

                getPriorityBorderClass(priority) {
                    const classes = {
                        'critical': 'border-red-500',
                        'high': 'border-orange-500',
                        'medium': 'border-yellow-500',
                        'low': 'border-green-500'
                    };
                    return classes[priority] || 'border-gray-300';
                },

                getPriorityBadgeClass(priority) {
                    const classes = {
                        'critical': 'bg-red-100 text-red-800',
                        'high': 'bg-orange-100 text-orange-800',
                        'medium': 'bg-yellow-100 text-yellow-800',
                        'low': 'bg-green-100 text-green-800'
                    };
                    return classes[priority] || 'bg-gray-100 text-gray-800';
                },

                showNotification(message, type) {
                    // Implementation for showing notifications
                    console.log(`${type}: ${message}`);
                }
            }
        }
    </script>
@endpush
