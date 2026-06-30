@extends('admin.layouts.admin')

@section('title', 'AI Command Center')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        🤖 AI Command Center
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Yapay zeka sistem durumu, fırsat akışı ve aktivite istatistikleri
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <!-- Refresh -->
                    <button onclick="refreshDashboard()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none"
                        title="Dashboard'u yenile (30s otomatik)" aria-label="Refresh dashboard">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span class="hidden sm:inline">Yenile</span>
                    </button>

                    <!-- Mobile Menu Toggle -->
                    <button onclick="toggleMobileMenu()" class="sm:hidden p-2 dark:text-slate-300" aria-label="Toggle menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Export Dropdown -->
                    <div class="relative group">
                        <button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none"
                            title="Raporu indir" aria-label="Export dashboard data">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="hidden sm:inline">Export</span>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 rounded-lg shadow-xl z-10 border border-gray-200 dark:border-slate-800">
                            <button onclick="exportDashboardData('csv')" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-900 dark:text-slate-100">
                                📊 CSV Export
                            </button>
                            <button onclick="exportDashboardData('json')" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-900 dark:text-slate-100">
                                📄 JSON Export
                            </button>
                            <button onclick="exportDashboardData('pdf')" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-900 dark:text-slate-100">
                                🔴 PDF Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Panel -->
        <div id="mobile-menu" class="hidden sm:block mb-8 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-purple-200 dark:border-slate-800 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚡ Hızlı İşlemler</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <button onclick="quickAction('restart-ollama')"
                    class="flex items-center justify-center px-4 py-3 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg border border-gray-200 dark:border-slate-700 transition-all duration-200 text-gray-900 dark:text-white font-medium"
                    title="Ollama hizmetini yeniden başlat" aria-label="Restart Ollama">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581"></path>
                    </svg>
                    Ollama Yeniden Başlat
                </button>
                <button onclick="quickAction('start-queue')"
                    class="border-2 border-dashed border-slate-700 rounded-xl p-6 bg-white dark:bg-slate-900 transition-all duration-200 text-gray-900 dark:text-white font-medium" aria-label="Start queue worker">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                    Queue Worker Başlat
                </button>
                <button onclick="quickAction('view-logs')"
                    class="flex items-center justify-center px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 transition-all duration-200 text-gray-900 dark:text-white font-medium"
                    title="Detaylı logları görüntüle" aria-label="View logs">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Logları Görüntüle
                </button>
                <button onclick="quickAction('system-config')"
                    class="flex items-center justify-center px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 transition-all duration-200 text-gray-900 dark:text-white font-medium"
                    title="Sistem konfigürasyonu" aria-label="System configuration">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Ayarlar
                </button>
            </div>
        </div>

        <!-- System Health Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" role="region" aria-label="Sistem sağlık durumu" aria-live="polite" aria-atomic="false">
            <!-- Cortex Brain -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cortex Brain</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['cortex_brain']['description'] }}</p>
                    </div>
                    <div class="relative">
                        <div id="cortex-durumu" class="w-4 h-4 rounded-full {{ $systemHealth['cortex_brain']['servis_durumu'] === 'online' ? 'bg-green-500' : 'bg-red-500' }} animate-pulse" role="presentation" aria-label="Cortex brain durumu: {{ $systemHealth['cortex_brain']['servis_durumu'] }}"></div>
                        <div class="absolute inset-0 w-4 h-4 rounded-full {{ $systemHealth['cortex_brain']['servis_durumu'] === 'online' ? 'bg-green-500' : 'bg-red-500' }} opacity-75 animate-ping"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ $systemHealth['cortex_brain']['servis_durumu'] === 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ ucfirst($systemHealth['cortex_brain']['servis_durumu']) }}
                    </span>
                    <a href="{{ $systemHealth['cortex_brain']['url'] }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                        {{ parse_url($systemHealth['cortex_brain']['url'], PHP_URL_HOST) }}
                    </a>
                </div>
            </div>

            <!-- LLM Engine -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">LLM Engine</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['llm_engine']['description'] }}</p>
                    </div>
                    <div class="relative">
                        <div id="llm-durumu" class="w-4 h-4 rounded-full {{ $systemHealth['llm_engine']['servis_durumu'] === 'online' ? 'bg-green-500' : 'bg-red-500' }} animate-pulse" role="presentation" aria-label="LLM engine durumu: {{ $systemHealth['llm_engine']['servis_durumu'] }}"></div>
                        <div class="absolute inset-0 w-4 h-4 rounded-full {{ $systemHealth['llm_engine']['servis_durumu'] === 'online' ? 'bg-green-500' : 'bg-red-500' }} opacity-75 animate-ping"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ $systemHealth['llm_engine']['servis_durumu'] === 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ ucfirst($systemHealth['llm_engine']['servis_durumu']) }}
                    </span>
                    @if(($systemHealth['llm_engine']['response_time'] ?? null))
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $systemHealth['llm_engine']['response_time'] }}ms
                        </span>
                    @endif
                </div>
            </div>

            <!-- Knowledge Base -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Knowledge Base</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['knowledge_base']['description'] }}</p>
                    </div>
                    <div class="relative">
                        <div id="kb-durumu" class="w-4 h-4 rounded-full {{ $systemHealth['knowledge_base']['servis_durumu'] === 'online' ? 'bg-green-500' : ($systemHealth['knowledge_base']['servis_durumu'] === 'not_configured' ? 'bg-yellow-500' : 'bg-red-500') }} animate-pulse" role="presentation" aria-label="Knowledge base durumu: {{ $systemHealth['knowledge_base']['servis_durumu'] }}"></div>
                        <div class="absolute inset-0 w-4 h-4 rounded-full {{ $systemHealth['knowledge_base']['servis_durumu'] === 'online' ? 'bg-green-500' : ($systemHealth['knowledge_base']['servis_durumu'] === 'not_configured' ? 'bg-yellow-500' : 'bg-red-500') }} opacity-75 animate-ping"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ $systemHealth['knowledge_base']['servis_durumu'] === 'online' ? 'text-green-600 dark:text-green-400' : ($systemHealth['knowledge_base']['servis_durumu'] === 'not_configured' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ ucfirst(str_replace('_', ' ', $systemHealth['knowledge_base']['servis_durumu'])) }}
                    </span>
                    @if(($systemHealth['knowledge_base']['response_time'] ?? null))
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $systemHealth['knowledge_base']['response_time'] }}ms
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bölüm B & C: Fırsat Akışı ve AI Aktivitesi -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Bölüm B: Fırsat Akışı (Sol Geniş Alan) -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">⚡ Fırsat Akışı</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Son 24 saat</span>
                </div>

                @if(count($opportunityStream) > 0)
                    <div class="space-y-4">
                        @foreach($opportunityStream as $opportunity)
                            <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md transition-all duration-200 {{ $opportunity['score'] >= 90 ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700' : 'dark:border-slate-700' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-semibold px-2 py-1 rounded {{ $opportunity['score'] >= 90 ? 'bg-yellow-500 text-white' : 'bg-green-500' }}">
                                                Skor: {{ $opportunity['score'] }}
                                            </span>
                                            @if($opportunity['score'] >= 90)
                                                <span class="text-xs font-semibold px-2 py-1 rounded bg-red-500 text-white animate-pulse">
                                                    ⚠️ ACİL
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                @if($opportunity['type'] === 'ilan_match')
                                                    İlan Eşleşmesi:
                                                @else
                                                    Talep Eşleşmesi:
                                                @endif
                                            </span>
                                            {{ $opportunity['ilan_baslik'] ?? $opportunity['talep_baslik'] ?? 'Bilinmeyen' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            ⏰ {{ $opportunity['time_ago'] }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-2 ml-4">
                                        @if($opportunity['type'] === 'ilan_match' && isset($opportunity['ilan_id']))
                                            <a href="{{ route('admin.ilanlar.edit', $opportunity['ilan_id']) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                                                Detay Gör
                                            </a>
                                        @elseif($opportunity['type'] === 'talep_match' && isset($opportunity['talep_id']))
                                            <a href="{{ route('admin.talepler.show', $opportunity['talep_id']) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                                                Detay Gör
                                            </a>
                                        @endif
                                        <button onclick="assignToConsultant({{ $opportunity['ilan_id'] ?? $opportunity['talep_id'] }}, '{{ $opportunity['type'] }}')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-green-600 hover:bg-green-700 text-white transition-all duration-200">
                                            Danışmana Ata
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Son 24 saatte yüksek skorlu eşleşme bulunamadı.</p>
                    </div>
                @endif
            </div>

            <!-- Bölüm C: AI Aktivitesi (Sağ Dar Alan) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">📊 AI Aktivitesi</h2>

                <div class="space-y-4">
                    <!-- İmar Analizi -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-indigo-50 dark:bg-indigo-900/20">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">İmar Analizi</span>
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $usageStats['imar_analizi'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bugün yapılan analiz</p>
                    </div>

                    <!-- İlan Açıklaması -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-green-50 dark:bg-green-900/20 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">İlan Açıklaması</span>
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $usageStats['ilan_aciklama'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bugün üretilen açıklama</p>
                    </div>

                    <!-- Fiyat Hesaplama -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-purple-50 dark:bg-purple-900/20 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Fiyat Hesaplama</span>
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $usageStats['fiyat_hesaplama'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bugün hesaplanan fiyat</p>
                    </div>

                    <!-- Token Kullanımı -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-orange-50 dark:bg-orange-900/20 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Token Kullanımı</span>
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $usageStats['formatted_tokens'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bugün harcanan token</p>
                    </div>

                    <!-- Başarı Oranı -->
                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-blue-50 dark:bg-blue-900/20 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Başarı Oranı</span>
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $usageStats['success_rate'] }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $usageStats['total_requests'] }} toplam istek</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bölüm D: Queue Worker & Telegram Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <!-- Queue Worker Status -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">🔄 Queue Worker</h2>
                    <div class="relative">
                        <div id="queue-durumu" class="w-4 h-4 rounded-full {{ $queueStatus['servis_durumu'] === 'running' ? 'bg-green-500' : ($queueStatus['servis_durumu'] === 'stopped' ? 'bg-red-500' : 'bg-yellow-500') }} animate-pulse" role="presentation" aria-hidden="true"></div>
                        <div class="absolute inset-0 w-4 h-4 rounded-full {{ $queueStatus['servis_durumu'] === 'running' ? 'bg-green-500' : ($queueStatus['servis_durumu'] === 'stopped' ? 'bg-red-500' : 'bg-yellow-500') }} opacity-75 animate-ping"></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Durum</span>
                        <span class="text-sm font-semibold {{ $queueStatus['servis_durumu'] === 'running' ? 'text-green-600 dark:text-green-400' : ($queueStatus['servis_durumu'] === 'stopped' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400') }}">
                            {{ $queueStatus['servis_durumu'] === 'running' ? 'Çalışıyor' : ($queueStatus['servis_durumu'] === 'stopped' ? 'Durdurulmuş' : 'Bilinmiyor') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Bekleyen İşler</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $queueStatus['pending_jobs'] }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Son 5 Dakikada İşlenen</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $queueStatus['processed_last_5min'] }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Başarısız (24 saat)</span>
                        <span class="text-sm font-semibold {{ $queueStatus['failed_last_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $queueStatus['failed_last_24h'] }}
                        </span>
                    </div>

                    @if(isset($queueStatus['error']))
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $queueStatus['error'] }}</p>
                        </div>
                    @endif

                    @if($queueStatus['servis_durumu'] === 'stopped')
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                ⚠️ Queue worker çalışmıyor. Bildirimler işlenmeyecek!
                            </p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                Başlatmak için: <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">php artisan queue:work --queue=cortex-notifications</code>
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Telegram Notification Stats -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">📱 Telegram Bildirimleri</h2>
                    <div class="relative">
                        <div class="w-4 h-4 rounded-full {{ $telegramStats['is_configured'] ? 'bg-green-500' : 'bg-yellow-500' }} animate-pulse"></div>
                        <div class="absolute inset-0 w-4 h-4 rounded-full {{ $telegramStats['is_configured'] ? 'bg-green-500' : 'bg-yellow-500' }} opacity-75 animate-ping"></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Yapılandırma</span>
                        <span class="text-sm font-semibold {{ $telegramStats['is_configured'] ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                            {{ $telegramStats['is_configured'] ? 'Hazır' : 'Eksik' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Bugün Gönderilen</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $telegramStats['sent_today'] }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Son 24 Saat</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $telegramStats['sent_last_24h'] }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Başarısız (24 saat)</span>
                        <span class="text-sm font-semibold {{ $telegramStats['failed_last_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $telegramStats['failed_last_24h'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Başarı Oranı</span>
                        <span class="text-sm font-semibold {{ $telegramStats['success_rate'] >= 95 ? 'text-green-600 dark:text-green-400' : ($telegramStats['success_rate'] >= 80 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $telegramStats['success_rate'] }}%
                        </span>
                    </div>

                    @if(!$telegramStats['is_configured'])
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                ⚠️ Telegram bot yapılandırılmamış.
                            </p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                .env dosyasında <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">TELEGRAM_BOT_TOKEN</code> ve <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">TELEGRAM_ADMIN_CHAT_ID</code> tanımlayın.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 📊 Real-time Metrics Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            <!-- Matches Trend Chart -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📈 Maç Eşleştirme Trendi (24h)</h2>
                <canvas id="metricsChart" height="80"></canvas>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    Son 24 saatteki başarılı ve başarısız eşleştirme sayıları
                </p>
            </div>

            <!-- System Health Summary -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">💡 Sistem Sağlığı Özeti</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                        <span class="text-sm font-medium text-green-900 dark:text-green-100">✅ Cortex Brain</span>
                        <span class="text-xs bg-green-200 dark:bg-green-700 text-green-900 dark:text-green-100 px-2 py-1 rounded">Online</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-slate-700 dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">🤖 LLM Engine</span>
                        <span class="text-xs text-gray-600 dark:text-slate-200"id="llm-summary">Kontrol ediliyor...</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-slate-700 dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">📚 Knowledge Base</span>
                        <span class="text-xs text-gray-600 dark:text-slate-200" id="kb-summary">Kontrol ediliyor...</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-slate-700 dark:bg-slate-900">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">🔄 Queue Worker</span>
                        <span class="text-xs text-gray-600 dark:text-slate-200" id="queue-summary">Kontrol ediliyor...</span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <p class="text-xs text-blue-900 dark:text-blue-100">
                        💡 Dashboard 30 saniyede bir otomatik yenilenir. Sorunları çözmek için hızlı işlemleri kullanın.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" />

    <script>
        // 🔄 AJAX Auto-Refresh (30 saniye interval)
        let dashboardRefreshInterval;

        function initAutoRefresh() {
            dashboardRefreshInterval = setInterval(refreshDashboardAjax, 30000);

        }

        function refreshDashboard() {
            refreshDashboardAjax();
        }

        async function refreshDashboardAjax() {
            try {
                const response = await fetch('/api/v1/ai/system-health');
                if (!response.ok) throw new Error('API Error');

                const data = await response.json();
                updateDashboardUI(data);

            } catch (error) {
                console.error('Dashboard refresh error:', error);
                showToast('error', 'Dashboard yenileme hatası', error.message);
            }
        }

        function updateDashboardUI(data) {
            // Cortex Brain status update
            const cortexDurumu = data.services.laravel === 'ok' ? 'online' : 'offline';
            guncelleDurumGostergesi('cortex-durumu', cortexDurumu);

            // LLM Engine status update
            guncelleDurumGostergesi('llm-durumu', data.services.ollama);

            // Knowledge Base status update
            guncelleDurumGostergesi('kb-durumu', data.services.anythingllm);

            // Queue status update
            guncelleDurumGostergesi('queue-durumu', data.services.queue);

            // Smart Alerts
            checkAndAlertThresholds(data);
        }

        function guncelleDurumGostergesi(elementId, durum) {
            const element = document.getElementById(elementId);
            if (!element) return;

            const durumMap = {
                'ok': { color: 'bg-green-500', text: 'Online' },
                'online': { color: 'bg-green-500', text: 'Online' },
                'offline': { color: 'bg-red-500', text: 'Offline' },
                'stopped': { color: 'bg-red-500', text: 'Stopped' },
                'running': { color: 'bg-green-500', text: 'Running' },
                'not_configured': { color: 'bg-yellow-500', text: 'Not Configured' }
            };

            const durumBilgisi = durumMap[durum] || durumMap['offline'];
            element.className = `w-4 h-4 rounded-full ${durumBilgisi.color} animate-pulse`;
        }

        // 💡 Smart Alerts & Thresholds
        function checkAndAlertThresholds(data) {
            const details = data.details || {};
            const systemHealth = details.system_health || {};
            const llmEngine = systemHealth.llm_engine || {};
            const queueStatus = details.queue_status || {};

            // Ollama latency check
            if (llmEngine.response_time && llmEngine.response_time > 1000) {
                showToast('warning', '⚠️ Ollama Yavaş',
                    `Latency: ${llmEngine.response_time}ms (optimal < 1000ms)`);
            }

            // Queue worker down
            if (queueStatus.servis_durumu === 'stopped') {
                showToast('error', '🚨 Queue Worker Durdurulmuş',
                    'Bildirimler işlenmeyecek! Başlatmak için: php artisan queue:work');
            }

            // Failed jobs alert
            if (queueStatus.failed_last_24h && queueStatus.failed_last_24h > 10) {
                showToast('warning', '⚠️ Başarısız İşler',
                    `Son 24 saatte ${queueStatus.failed_last_24h} iş başarısız oldu`);
            }
        }

        // 🔔 Toast Notification System
        function showToast(type, title, message) {
            const toastHtml = `
                <div class="fixed bottom-4 right-4 p-4 rounded-lg shadow-lg text-white
                    ${getToastColor(type)} animate-fade-in-up z-50 max-w-md">
                    <div class="font-semibold">${title}</div>
                    <div class="text-sm opacity-90">${message}</div>
                </div>
            `;

            const container = document.getElementById('toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.innerHTML = toastHtml;
            container.appendChild(toast.firstElementChild);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                const toastEl = container.lastElementChild;
                if (toastEl) toastEl.remove();
            }, 5000);
        }

        function getToastColor(type) {
            const colors = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            };
            return colors[type] || colors['info'];
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'space-y-2';
            document.body.appendChild(container);
            return container;
        }

        // 📊 Real-time Charts Initialization (Chart.js)
        function initCharts() {
            const ctx = document.getElementById('metricsChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: generateHourlyLabels(24),
                    datasets: [
                        {
                            label: 'Successful Matches',
                            data: generateMockData(24, 50, 150),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3
                        },
                        {
                            label: 'Failed Matches',
                            data: generateMockData(24, 5, 15),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: true, position: 'bottom' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        function generateHourlyLabels(count) {
            return Array.from({length: count}, (_, i) => {
                const h = new Date();
                h.setHours(h.getHours() - (count - i - 1));
                return h.getHours() + ':00';
            });
        }

        function generateMockData(count, min, max) {
            return Array.from({length: count}, () =>
                Math.floor(Math.random() * (max - min + 1) + min)
            );
        }

        // ✅ Quick Actions
        function quickAction(action) {
            switch(action) {
                case 'restart-ollama':
                    if (confirm('Ollama hizmetini yeniden başlatmak istiyor musunuz?')) {
                        fetch('/api/v1/ai/quick-action', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({action: 'restart-ollama'})
                        }).then(r => r.json())
                         .then(d => showToast('success', 'Ollama Yeniden Başlatılıyor', d.message || 'İşlem başlatıldı'));
                    }
                    break;
                case 'start-queue':
                    showToast('info', 'Terminal Komutu',
                        'php artisan queue:work --queue=cortex-notifications');
                    break;
                case 'view-logs':
                    window.location.href = '/admin/ai/logs';
                    break;
                case 'system-config':
                    window.location.href = '/admin/ai-settings';
                    break;
            }
        }

        function assignToConsultant(id, type) {
            // TODO: Danışmana atama modalı aç
            window.Toast?.fire?.({
                icon: 'info',
                title: 'Danışmana atama özelliği yakında eklenecek.'
            }) || showToast('info', 'Danışmana Atama', 'Yakında kullanılabilir');
        }

        // 📱 Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu) menu.classList.toggle('hidden');
        }

        // 📥 Export Dashboard Data
        function exportDashboardData(format) {
            const timestamp = new Date().toLocaleString('tr-TR');
            const data = {
                timestamp,
                exported_at: new Date().toISOString(),
                format: format
            };

            if (format === 'csv') {
                downloadCSV(data);
            } else if (format === 'pdf') {
                downloadPDF(data);
            } else if (format === 'json') {
                downloadJSON(data);
            }

            showToast('success', 'Export Başarılı', `${format.toUpperCase()} dosyası indirildi`);
        }

        function downloadCSV(data) {
            const csv = `Dashboard Export,${data.timestamp}\nFormat,${data.format}\n`;
            downloadFile(csv, `dashboard_${Date.now()}.csv`, 'text/csv');
        }

        function downloadJSON(data) {
            downloadFile(JSON.stringify(data, null, 2),
                `dashboard_${Date.now()}.json`, 'application/json');
        }

        function downloadPDF(data) {
            showToast('info', 'PDF Export', 'PDF funktionalitesi yakında eklenecek');
        }

        function downloadFile(content, filename, type) {
            const blob = new Blob([content], { type });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initAutoRefresh();
            initCharts();

            // Add fade-in animation
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .animate-fade-in-up {
                    animation: fadeInUp 0.3s ease-in-out;
                }
            `;
            document.head.appendChild(style);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (dashboardRefreshInterval) clearInterval(dashboardRefreshInterval);
        });
    </script>
@endsection



