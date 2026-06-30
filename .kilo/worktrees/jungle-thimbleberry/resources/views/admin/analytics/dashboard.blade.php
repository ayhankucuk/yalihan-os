@extends('admin.layouts.admin')

@section('title', 'Context7 Analytics Dashboard - Yalıhan Bekçi')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-all duration-200 ease-in-out">
        <!-- Header Section -->
        <div
            class="bg-white dark:bg-slate-900 shadow-sm border-b border-gray-200 dark:border-slate-800 transition-all duration-200 ease-in-out dark:shadow-none dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white transition-all duration-200 ease-in-out dark:text-slate-100">
                            📊 Context7 Analytics Dashboard
                        </h1>
                        <p class="text-gray-600 dark:text-slate-200 mt-2 transition-all duration-200 ease-in-out">
                            Yalıhan Bekçi - Real-time Project Analytics & AI Learning
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button id="refresh-data"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200 ease-in-out hover:scale-105 shadow-lg">
                            🔄 Yenile
                        </button>
                        <button id="recalculate-health"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 active:scale-95 transition-all duration-200 ease-in-out hover:scale-105 shadow-lg">
                            🔬 Sağlık Hesapla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <!-- Real-time Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Overall Health Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-3xl">🎯</div>
                        <div id="health-trend"
                            class="text-sm font-semibold px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                            📈 Improving
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Genel Sağlık</h3>
                    <div class="flex items-end space-x-2">
                        <span id="health-score" class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            85.2
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    <p id="health-durumu" class="text-sm text-gray-600 dark:text-slate-200 mt-2 capitalize">
                        Good Health
                    </p>
                </div>

                <!-- Context7 Compliance Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-3xl">⚖️</div>
                        <div class="text-sm text-green-600 dark:text-green-400 font-semibold">Context7</div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Uyumluluk</h3>
                    <div class="flex items-end space-x-2">
                        <span id="context7-score" class="text-3xl font-bold text-green-600 dark:text-green-400">
                            92.8
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-slate-200 mt-2">
                        <span id="active-violations" class="font-semibold text-red-600 dark:text-red-400">3</span> aktif
                        ihlal
                    </p>
                </div>

                <!-- Today's Activity Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-3xl">⚡</div>
                        <div class="text-sm text-purple-600 dark:text-purple-400 font-semibold">Bugün</div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Aktivite</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Commit</span>
                            <span id="commits-today" class="font-semibold text-purple-600 dark:text-purple-400">12</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Build</span>
                            <span id="builds-today" class="font-semibold text-purple-600 dark:text-purple-400">8</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Test</span>
                            <span id="tests-today" class="font-semibold text-purple-600 dark:text-purple-400">45</span>
                        </div>
                    </div>
                </div>

                <!-- AI Learning Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-lg transition-all duration-200 ease-in-out hover:scale-105 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-3xl">🧠</div>
                        <div class="text-sm text-orange-600 dark:text-orange-400 font-semibold">AI Bekçi</div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Öğrenme</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Oturum</span>
                            <span id="ai-sessions" class="font-semibold text-orange-600 dark:text-orange-400">7</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Kalıp</span>
                            <span id="patterns-learned" class="font-semibold text-orange-600 dark:text-orange-400">15</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-slate-200">Fikir</span>
                            <span id="ideas-generated" class="font-semibold text-orange-600 dark:text-orange-400">23</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Analytics Section -->
            <div class="mb-6">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-4 dark:shadow-none dark:border-slate-700">
                    <h5 class="mb-4 font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        <i class="fas fa-search mr-2 text-orange-500"></i>
                        Arama Performans Paneli
                    </h5>
                    <div>
                        <div class="row">
                            <!-- Search Performance -->
                            <div class="col-md-6 mb-4">
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-tachometer-alt mr-1"></i>
                                    Arama Performansı
                                </h6>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <div class="flex-1">
                                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 mb-2">
                                                <div class="h-2 rounded-full bg-blue-500" id="search-accuracy"
                                                    style="width: 0%" role="progressbar"></div>
                                            </div>
                                            <small class="text-sm text-gray-500 dark:text-gray-400">
                                                Doğruluk Oranı: <span id="search-accuracy-percentage">0%</span>
                                            </small>
                                        </div>
                                        <div class="ml-3 text-right">
                                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                                id="successful-searches">0</div>
                                            <small class="text-sm text-gray-500 dark:text-gray-400">Başarılı</small>
                                        </div>
                                    </div>
                                    <div class="flex items-center mt-2">
                                        <div class="flex-1">
                                            <small class="text-sm text-gray-500 dark:text-gray-400">Başarısız</small>
                                        </div>
                                        <div class="ml-3 text-right">
                                            <div class="text-2xl font-bold text-red-600 dark:text-red-400"
                                                id="failed-searches">0</div>
                                            <small class="text-sm text-gray-500 dark:text-gray-400">Başarısız</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Behavior -->
                            <div class="col-md-6 mb-4">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-users mr-1"></i>
                                    Kullanıcı Davranışları
                                </h6>
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 text-center gap-4">
                                        <div>
                                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                                id="avg-search-time">0</div>
                                            <small class="text-sm text-gray-500 dark:text-gray-400">Ort. Arama Süresi
                                                (sn)</small>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                                id="avg-results-clicked">0</div>
                                            <small class="text-sm text-gray-500 dark:text-gray-400">Ort. Sonuç
                                                Tıklama</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="mb-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-4 dark:shadow-none dark:border-slate-700">
                <h6 class="mb-4 font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    <i class="fas fa-tachometer-alt mr-2 text-orange-500"></i>
                    Performance Metrics
                </h6>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        class="text-center p-3 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="page-load-time">
                            {{ number_format($analytics['form_analytics']['performance_metrics']['page_load_time'] ?? 0, 0) }}ms
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Page Load Time</div>
                    </div>
                    <div
                        class="text-center p-3 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="api-response-time">
                            {{ number_format($analytics['form_analytics']['performance_metrics']['api_response_time'] ?? 0, 0) }}ms
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">API Response Time</div>
                    </div>
                    <div
                        class="text-center p-3 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="error-rate">
                            {{ number_format($analytics['form_analytics']['performance_metrics']['error_rate'] ?? 0, 2) }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Error Rate</div>
                    </div>
                    <div
                        class="text-center p-3 rounded-lg bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="uptime">
                            {{ number_format($analytics['form_analytics']['performance_metrics']['uptime'] ?? 0, 2) }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Uptime</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Behavior -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Device Usage -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-3 dark:border-slate-700">
                    <h6 class="font-semibold text-blue-600 dark:text-blue-400">
                        <i class="fas fa-mobile-alt mr-2"></i>
                        Device Usage
                    </h6>
                </div>
                <div class="p-4">
                    <canvas id="device-usage-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Browser Usage -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-3 dark:border-slate-700">
                    <h6 class="font-semibold text-blue-600 dark:text-blue-400">
                        <i class="fas fa-globe mr-2"></i>
                        Browser Usage
                    </h6>
                </div>
                <div class="p-4">
                    <canvas id="browser-usage-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Session Duration -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-3 dark:border-slate-700">
                    <h6 class="font-semibold text-blue-600 dark:text-blue-400">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        Session Duration
                    </h6>
                </div>
                <div class="p-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" id="avg-session-duration">
                            {{ number_format($analytics['form_analytics']['user_behavior']['session_duration'] ?? 0, 1) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Average Minutes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Activity -->
        <div class="mb-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-3 dark:border-slate-700">
                    <h6 class="font-semibold text-blue-600 dark:text-blue-400">
                        <i class="fas fa-broadcast-tower mr-2"></i>
                        Real-time Activity
                    </h6>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="current-sessions">
                                {{ number_format($analytics['real_time_analytics']['current_sessions'] ?? 0) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Current Sessions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="recent-submissions">
                                {{ number_format($analytics['real_time_analytics']['recent_submissions'] ?? 0) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Recent Submissions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" id="cpu-usage">
                                {{ number_format($analytics['real_time_analytics']['system_health']['cpu_usage'] ?? 0) }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">CPU Usage</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="memory-usage">
                                {{ number_format($analytics['real_time_analytics']['system_health']['memory_usage'] ?? 0) }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Memory Usage</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EmlakLoc v3.0 Analytics Section -->
    <div class="mb-6">
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
            <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-3 dark:border-slate-700">
                <h5 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    EmlakLoc v3.0 Performance Dashboard
                </h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Top Neighborhoods -->
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 mb-3">
                            <i class="fas fa-star mr-1"></i>
                            En Popüler Mahalleler
                        </h6>
                        <div id="top-neighborhoods-chart" class="h-[300px]">
                            <!-- Chart will be loaded here -->
                        </div>
                    </div>

                    <!-- API Response Times -->
                    <div>
                        <h6 class="text-green-600 dark:text-green-400 mb-3">
                            <i class="fas fa-tachometer-alt mr-1"></i>
                            API Response Times
                        </h6>
                        <div id="response-times-chart" class="h-[300px]">
                            <!-- Chart will be loaded here -->
                        </div>
                    </div>

                    <!-- Cache Performance -->
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 mb-3">
                            <i class="fas fa-database mr-1"></i>
                            Cache Performance
                        </h6>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 mb-2">
                                        <div class="h-2 rounded-full bg-green-500" id="cache-hit-rate" style="width: 0%"
                                            role="progressbar"></div>
                                    </div>
                                    <small class="text-sm text-gray-500 dark:text-gray-400">
                                        Hit Rate: <span id="cache-hit-percentage">0%</span>
                                    </small>
                                </div>
                                <div class="ml-3 text-right">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="cache-hits">0
                                    </div>
                                    <small class="text-sm text-gray-500 dark:text-gray-400">Hits</small>
                                </div>
                            </div>
                            <div class="flex items-center mt-2">
                                <div class="flex-1">
                                    <small class="text-sm text-gray-500 dark:text-gray-400">Misses</small>
                                </div>
                                <div class="ml-3 text-right">
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="cache-misses">0
                                    </div>
                                    <small class="text-sm text-gray-500 dark:text-gray-400">Misses</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Behavior -->
                    <div>
                        <h6 class="text-yellow-600 dark:text-yellow-400 mb-3">
                            <i class="fas fa-users mr-1"></i>
                            Kullanıcı Davranışları
                        </h6>
                        <div class="grid grid-cols-2 text-center gap-4">
                            <div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                    id="avg-session-duration">0</div>
                                <small class="text-sm text-gray-500 dark:text-gray-400">Ort. Oturum Süresi (dk)</small>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="avg-interactions">0
                                </div>
                                <small class="text-sm text-gray-500 dark:text-gray-400">Ort. Etkileşim/Session</small>
                            </div>
                        </div>
                        {{-- ✅ FIX: "Misses" alanı kaldırıldı - Cache Performance'a ait, burada gereksiz --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Export Modal (Alpine) -->
    <div x-data="{ exportOpen: false }" x-show="exportOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="exportOpen=false"></div>
        <div
            class="relative bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xl w-full max-w-md dark:border-slate-700">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 px-4 py-2.5 dark:border-slate-700">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Export Analytics Data</h5>
                <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    @click="exportOpen=false" aria-label="Kapat">
                    <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-4 py-4 space-y-4">
                <div>
                    <label for="export-type" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Data
                        Type</label>
                    <select style="color-scheme: light dark;" id="export-type"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="form_analytics">Form Analytics</option>
                        <option value="conversion_analytics">Conversion Analytics</option>
                        <option value="performance_metrics">Performance Metrics</option>
                    </select>
                </div>
                <div>
                    <label for="export-period"
                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Time Period</label>
                    <select style="color-scheme: light dark;" id="export-period"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                        <option value="1y">Last Year</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-slate-800 px-4 py-2.5 dark:border-slate-700">
                <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300"
                    @click="exportOpen=false">Cancel</button>
                <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50"
                    :disabled="loading" @click="loading = true; downloadExport()">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                    <span x-text="loading ? 'İndiriliyor...' : 'Download'"></span>
                </button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
@endpush

@push('scripts')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
    <script>
        let charts = {};

        function isDark() {
            return document.body.classList.contains('dark');
        }

        function applyChartTheme() {
            const text = isDark() ? '#e5e7eb' : '#374151';
            const grid = isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
            const border = isDark() ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)';
            // Global defaults
            Chart.defaults.color = text;
            Chart.defaults.borderColor = border;
            Chart.defaults.plugins.legend = Chart.defaults.plugins.legend || {};
            Chart.defaults.plugins.legend.labels = Chart.defaults.plugins.legend.labels || {};
            Chart.defaults.plugins.legend.labels.color = text;

            // Update existing charts
            Object.values(charts).forEach((chart) => {
                chart.options.scales && Object.values(chart.options.scales).forEach((scale) => {
                    scale.grid = scale.grid || {};
                    scale.grid.color = grid;
                    scale.ticks = scale.ticks || {};
                    scale.ticks.color = text;
                });
                if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                    chart.options.plugins.legend.labels.color = text;
                }
                chart.update('none');
            });
        }

        // Observe dark mode changes on <body>
        const neoDarkObserver = new MutationObserver(() => applyChartTheme());
        document.addEventListener('DOMContentLoaded', function() {
            neoDarkObserver.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
            initializeCharts();
            applyChartTheme();
            loadAlerts();
            startRealTimeUpdates();
        });

        // Initialize all charts
        function initializeCharts() {
            // Conversion Funnel Chart
            const funnelCtx = document.getElementById('conversion-funnel-chart').getContext('2d');
            charts.funnel = new Chart(funnelCtx, {
                type: 'bar',
                data: {
                    labels: ['Step 1', 'Step 2', 'Step 3', 'Step 4', 'Step 5', 'Completed'],
                    datasets: [{
                        label: 'Users',
                        data: [
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['step_1'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['step_2'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['step_3'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['step_4'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['step_5'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['conversion_funnel']['completed'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e',
                            '#e74a3b',
                            '#858796'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.06)'
                            },
                            ticks: {
                                color: Chart.defaults.color
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.06)'
                            },
                            ticks: {
                                color: Chart.defaults.color
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: Chart.defaults.color
                            }
                        }
                    }
                }
            });

            // Step Abandonment Chart
            const abandonmentCtx = document.getElementById('step-abandonment-chart').getContext('2d');
            charts.abandonment = new Chart(abandonmentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Step 1', 'Step 2', 'Step 3', 'Step 4', 'Step 5', 'Step 6'],
                    datasets: [{
                        data: [
                            {{ $analytics['form_analytics']['step_abandonment']['step_1'] ?? 0 }},
                            {{ $analytics['form_analytics']['step_abandonment']['step_2'] ?? 0 }},
                            {{ $analytics['form_analytics']['step_abandonment']['step_3'] ?? 0 }},
                            {{ $analytics['form_analytics']['step_abandonment']['step_4'] ?? 0 }},
                            {{ $analytics['form_analytics']['step_abandonment']['step_5'] ?? 0 }},
                            {{ $analytics['form_analytics']['step_abandonment']['step_6'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#e74a3b',
                            '#f6c23e',
                            '#36b9cc',
                            '#1cc88a',
                            '#4e73df',
                            '#858796'
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // Device Usage Chart
            const deviceCtx = document.getElementById('device-usage-chart').getContext('2d');
            charts.device = new Chart(deviceCtx, {
                type: 'pie',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: [
                            {{ $analytics['form_analytics']['user_behavior']['device_usage']['Desktop'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['device_usage']['Mobile'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['device_usage']['Tablet'] ?? 0 }}
                        ],
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // Browser Usage Chart
            const browserCtx = document.getElementById('browser-usage-chart').getContext('2d');
            charts.browser = new Chart(browserCtx, {
                type: 'pie',
                data: {
                    labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'],
                    datasets: [{
                        data: [
                            {{ $analytics['form_analytics']['user_behavior']['browser_usage']['Chrome'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['browser_usage']['Firefox'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['browser_usage']['Safari'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['browser_usage']['Edge'] ?? 0 }},
                            {{ $analytics['form_analytics']['user_behavior']['browser_usage']['Other'] ?? 0 }}
                        ],
                        backgroundColor: ['#4285f4', '#ff6b35', '#4caf50', '#2196f3', '#9c27b0']
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }

        // Load alerts
        function loadAlerts() {
            fetch('/admin/analytics/alerts')
                .then(response => response.json())
                .then(alerts => {
                    const alertsSection = document.getElementById('alerts-section');
                    alertsSection.innerHTML = '';

                    Object.entries(alerts).forEach(([key, alert]) => {
                        if (alert.active) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = `alert alert-${alert.severity} alert-dismissible fade show`;
                            alertDiv.innerHTML = `
                        <strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${alert.message}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    `;
                            alertsSection.appendChild(alertDiv);
                        }
                    });
                })
                .catch(error => console.error('Error loading alerts:', error));
        }

        // Start real-time updates
        function startRealTimeUpdates() {
            setInterval(() => {
                updateRealTimeMetrics();
            }, 30000); // Update every 30 seconds
        }

        // Update real-time metrics
        function updateRealTimeMetrics() {
            fetch('/admin/analytics/real-time')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('active-users').textContent = data.active_users.toLocaleString();
                    document.getElementById('current-sessions').textContent = data.current_sessions.toLocaleString();
                    document.getElementById('recent-submissions').textContent = data.recent_submissions
                        .toLocaleString();
                    document.getElementById('cpu-usage').textContent = data.system_health.cpu_usage + '%';
                    document.getElementById('memory-usage').textContent = data.system_health.memory_usage + '%';

                    // Search analytics updates
                    if (data.context7_analytics) {
                        updateSearchMetrics(data.context7_analytics);
                    }
                })
                .catch(error => console.error('Error updating real-time metrics:', error));
        }

        // Update search metrics
        function updateSearchMetrics(context7Data) {
            // Update search accuracy
            const accuracyPercentage = context7Data.search_accuracy || 0;
            document.getElementById('search-accuracy').style.width = accuracyPercentage + '%';
            document.getElementById('search-accuracy-percentage').textContent = accuracyPercentage + '%';

            // Update search counts
            document.getElementById('successful-searches').textContent = (context7Data.successful_searches || 0)
                .toLocaleString();
            document.getElementById('failed-searches').textContent = (context7Data.failed_searches || 0).toLocaleString();

            // Update user behavior metrics
            document.getElementById('avg-search-time').textContent = (context7Data.avg_search_time || 0).toFixed(1);
            document.getElementById('avg-results-clicked').textContent = (context7Data.avg_results_clicked || 0).toFixed(1);

            // Update total searches
            if (document.getElementById('space-y-2')) {
                document.getElementById('space-y-2').textContent = (context7Data.total_searches || 0)
                    .toLocaleString();
            }
        }

        // Refresh analytics
        function refreshAnalytics() {
            const period = document.getElementById('period-selector').value;

            fetch(`/admin/analytics/form-analytics?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    updateMetrics(data);
                    updateCharts(data);

                    // Update search analytics
                    if (data.context7_analytics) {
                        updateSearchMetrics(data.context7_analytics);
                    }
                })
                .catch(error => console.error('Error refreshing analytics:', error));
        }

        // Update metrics
        function updateMetrics(data) {
            document.getElementById('total-submissions').textContent = data.total_submissions.toLocaleString();
            document.getElementById('completion-rate').textContent = data.completion_rate.toFixed(1) + '%';
            document.getElementById('avg-completion-time').textContent = data.average_completion_time.toFixed(1) + ' min';
            document.getElementById('page-load-time').textContent = data.performance_metrics.page_load_time.toFixed(0) +
                'ms';
            document.getElementById('api-response-time').textContent = data.performance_metrics.api_response_time.toFixed(
                0) + 'ms';
            document.getElementById('error-rate').textContent = data.performance_metrics.error_rate.toFixed(2) + '%';
            document.getElementById('uptime').textContent = data.performance_metrics.uptime.toFixed(2) + '%';
            document.getElementById('avg-session-duration').textContent = data.user_behavior.session_duration.toFixed(1);
        }

        // Update charts
        function updateCharts(data) {
            // Update funnel chart
            charts.funnel.data.datasets[0].data = [
                data.user_behavior.conversion_funnel.step_1,
                data.user_behavior.conversion_funnel.step_2,
                data.user_behavior.conversion_funnel.step_3,
                data.user_behavior.conversion_funnel.step_4,
                data.user_behavior.conversion_funnel.step_5,
                data.user_behavior.conversion_funnel.completed
            ];
            charts.funnel.update();

            // Update abandonment chart
            charts.abandonment.data.datasets[0].data = [
                data.step_abandonment.step_1,
                data.step_abandonment.step_2,
                data.step_abandonment.step_3,
                data.step_abandonment.step_4,
                data.step_abandonment.step_5,
                data.step_abandonment.step_6
            ];
            charts.abandonment.update();
        }

        // Export analytics - Alpine.js ile modal açma
        function exportAnalytics() {
            // Alpine.js component'ini bul ve exportOpen'ı true yap
            if (window.Alpine) {
                const modalElement = document.querySelector('[x-data*="exportOpen"]');
                if (modalElement && window.Alpine.$data) {
                    const component = window.Alpine.$data(modalElement);
                    if (component) {
                        component.exportOpen = true;
                        return;
                    }
                }
            }
            // Fallback: CustomEvent ile modal açma
            window.dispatchEvent(new CustomEvent('open-export-modal'));
        }

        // Download export - Alpine.js ile modal kapatma
        function downloadExport() {
            const type = document.getElementById('export-type').value;
            const period = document.getElementById('export-period').value;

            window.location.href = `/admin/analytics/export?type=${type}&period=${period}`;

            // Alpine.js component'ini bul ve exportOpen'ı false yap
            if (window.Alpine) {
                const modalElement = document.querySelector('[x-data*="exportOpen"]');
                if (modalElement && window.Alpine.$data) {
                    const component = window.Alpine.$data(modalElement);
                    if (component) {
                        component.exportOpen = false;
                    }
                }
            }
        }

        // CustomEvent listener (fallback için)
        window.addEventListener('open-export-modal', function() {
            if (window.Alpine) {
                const modalElement = document.querySelector('[x-data*="exportOpen"]');
                if (modalElement && window.Alpine.$data) {
                    const component = window.Alpine.$data(modalElement);
                    if (component) {
                        component.exportOpen = true;
                    }
                }
            }
        });

        // Period selector change
        document.getElementById('period-selector').addEventListener('change', function() {
            refreshAnalytics();
        });
    </script>
@endpush
