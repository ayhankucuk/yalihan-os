@extends('admin.layouts.admin')

@section('title', 'Sayfa Analizi - Yalıhan Emlak Pro')

@section('content')
    <!-- Page Analyzer Header -->
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    Akıllı Sayfa Analizi
                </h1>
                <p class="text-lg text-gray-600 mt-2">Sistem sayfalarının real-time analiz ve izleme merkezi</p>
            </div>
            <div class="flex gap-3">
                <button onclick="startAnalysis()" id="analyzeBtn" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none">
                    <i class="fas fa-play mr-2" id="analyzeIcon"></i>
                    <span id="analyzeText">Analizi Başlat</span>
                </button>
                <button onclick="refreshData()" id="refreshBtn" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-sync-alt mr-2" id="refreshIcon"></i>
                    <span id="refreshText">Yenile</span>
                </button>
                <button onclick="testModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-600 to-orange-600 rounded-lg hover:from-amber-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                    <i class="fas fa-bug mr-2"></i>
                    Test Modal
                </button>
                <a href="{{ route('admin.dashboard.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Dashboard'a Dön
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <!-- Real-time Status Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Pages -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-600" id="totalPages">75</h3>
                        <p class="text-sm text-gray-600 font-medium">Toplam Sayfa</p>
                    </div>
                </div>
            </div>

            <!-- High Score Pages -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-600" id="highScorePages">13</h3>
                        <p class="text-sm text-gray-600 font-medium">Mükemmel (8+)</p>
                    </div>
                </div>
            </div>

            <!-- Medium Score Pages -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-yellow-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-600" id="mediumScorePages">15</h3>
                        <p class="text-sm text-gray-600 font-medium">Orta (6-7.9)</p>
                    </div>
                </div>
            </div>

            <!-- Average Score -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-600" id="averageScore">6.2</h3>
                        <p class="text-sm text-gray-600 font-medium">Ortalama Skor</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Analysis Controls -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <i class="fas fa-chart-line text-blue-500 mr-3"></i>
                        Real-time Analiz Kontrolleri
                    </h2>
                    <p class="text-gray-600 mt-1">Akıllı sayfa analizi ve izleme sistemi</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Otomatik Yenileme:</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="autoRefresh" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                            </div>
                        </label>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        Son Güncelleme: <span id="lastUpdate">{{ now()->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Analysis Progress -->
            <div id="progressContainer" class="mb-6" style="display: none;">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Analiz İlerlemesi</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300" id="progressPercent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" id="progressBar"
                        style="width: 0%"></div>
                </div>
                <div class="mt-2 text-sm text-gray-600" id="progressStatus">Analiz başlatılıyor...</div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <button onclick="analyzeCategory('AI Sistemi')" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm text-left dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-robot text-blue-500 mr-2"></i>
                    AI Sistemi Analizi
                </button>
                <button onclick="analyzeCategory('İlan Yönetimi')" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm text-left dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-home text-green-500 mr-2"></i>
                    İlan Yönetimi Analizi
                </button>
                <button onclick="analyzeCategory('CRM')" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm text-left dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-users text-indigo-500 mr-2"></i>
                    CRM Analizi
                </button>
                <button onclick="generateReport()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm text-left dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                    Rapor Oluştur
                </button>
            </div>
        </div>

        <!-- Analysis Results -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                        <i class="fas fa-search text-purple-500 mr-3"></i>
                        Detaylı Analiz Sonuçları
                    </h2>
                    <p class="text-gray-600 mt-1">Kategori bazında sayfa performans analizi</p>
                </div>
                <div class="flex gap-2">
                    <select style="color-scheme: light dark;" id="categoryFilter" onchange="filterByCategory()"
                        class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">Tüm Kategoriler</option>
                    </select>
                    <select style="color-scheme: light dark;" id="sortBy" onchange="sortResults()"
                        class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="score">Skora Göre</option>
                        <option value="name">İsme Göre</option>
                        <option value="category">Kategoriye Göre</option>
                    </select>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-12" style="display: none;">
                <div
                    class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-lg text-blue-500 bg-blue-100 dark:shadow-none">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Analiz ediliyor...
                </div>
                <p class="mt-4 text-gray-600" id="loadingStatus">Veriler yükleniyor...</p>
            </div>

            <!-- Results Grid -->
            <div id="resultsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Results will be loaded here -->
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center dark:text-slate-200">
                <i class="fas fa-chart-bar text-green-500 mr-3"></i>
                Özet İstatistikler
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600" id="summaryHighScore">13</div>
                    <div class="text-sm text-gray-600">Yüksek Skor (8+)</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600" id="summaryMediumScore">15</div>
                    <div class="text-sm text-gray-600">Orta Skor (6-7.9)</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-red-600" id="summaryLowScore">43</div>
                    <div class="text-sm text-gray-600">Düşük Skor (&lt;6)</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600" id="summaryAverageScore">6.2</div>
                    <div class="text-sm text-gray-600">Ortalama Skor</div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">AI Önerileri</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="recommendations">
                    <!-- Recommendations will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Page Details Modal -->
    <div id="pageDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        style="display: none;" onclick="if(event.target === this) closeModal();">
        <div
            class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white modal-fade-in dark:bg-slate-900">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white" id="modalTitle">Sayfa Detayları</h3>
                    <button id="modalCloseBtn"
                        class="modal-close text-gray-400 hover:text-gray-600 p-2 rounded hover:bg-gray-100" type="button"
                        title="Kapat">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="mt-4">
                    <div class="space-y-4">
                        <!-- Page Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sayfa Adı</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="modalPageName" readonly
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm font-medium dark:bg-slate-900">
                                <button onclick="copyToClipboard('modalPageName')"
                                    class="px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Score -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Performans Skoru</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="modalScore" readonly
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm dark:bg-slate-900">
                                <button onclick="copyToClipboard('modalScore')"
                                    class="px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Kategori</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="modalCategory" readonly
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm dark:bg-slate-900">
                                <button onclick="copyToClipboard('modalCategory')"
                                    class="px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Details -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Detaylar</label>
                            <div class="flex items-start gap-2">
                                <textarea id="modalDetails" readonly rows="3"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm resize-none dark:bg-slate-900"></textarea>
                                <button onclick="copyToClipboard('modalDetails')"
                                    class="px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Issues -->
                        <div id="issuesSection" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Tespit Edilen Sorunlar</label>
                            <div class="flex items-start gap-2">
                                <textarea id="modalIssues" readonly rows="4"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm resize-none dark:bg-slate-900"></textarea>
                                <button onclick="copyToClipboard('modalIssues')"
                                    class="px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- All Details (Copy All) -->
                        <div class="pt-4 border-t border-gray-200 dark:border-slate-700">
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Tüm Detaylar (Kopyala)</label>
                            <div class="flex items-start gap-2">
                                <textarea id="modalAllDetails" readonly rows="6"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-sm resize-none dark:bg-slate-900"></textarea>
                                <button onclick="copyToClipboard('modalAllDetails')"
                                    class="px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button id="modalCloseFooterBtn" type="button"
                        class="modal-close px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-slate-300">
                        Kapat
                    </button>
                    <button onclick="copyAllDetails()"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-copy mr-2"></i>
                        Tümünü Kopyala
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .p-4 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 {
            @apply bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700;
            @apply border border-gray-200 dark:border-gray-600 rounded-xl p-6;
            @apply transition-all duration-300 hover:-translate-y-1 hover:shadow-lg;
        }

        .p-4 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 h3 {
            @apply border-b-2 border-gray-200 dark:border-gray-600 pb-3 mb-4;
        }

        /* Toast Notification Styles */
        .toast-notification {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Modal Animation */
        .modal-fade-in {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Modal Transition */
        #pageDetailsModal {
            transition: opacity 0.15s ease-in-out;
        }

        #pageDetailsModal .relative {
            transition: transform 0.15s ease-in-out;
        }

        /* Modal Button Styles */
        .modal-close {
            cursor: pointer;
            user-select: none;
        }

        .modal-close:hover {
            opacity: 0.8;
        }

        .modal-close:active {
            transform: scale(0.95);
        }
    </style>
@endpush

@push('scripts')
    <script>
        let analysisData = {};
        let refreshInterval = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadAnalysisData();
            setupAutoRefresh();
            setupModalEvents();
        });

        function setupModalEvents() {
            // ESC tuşu ile modal'ı kapat
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            // Tüm tıklamaları yakalamak için basit delegasyon
            document.body.addEventListener('click', function(event) {
                // Modal backdrop'a tıklama
                if (event.target.id === 'pageDetailsModal') {
                    closeModal();
                    return;
                }

                // Kapat butonları
                if (event.target.id === 'modalCloseBtn' ||
                    event.target.closest('#modalCloseBtn') ||
                    event.target.id === 'modalCloseFooterBtn' ||
                    event.target.closest('#modalCloseFooterBtn') ||
                    event.target.classList.contains('modal-close') ||
                    event.target.closest('.modal-close')) {

                    console.log('Kapat butonuna tıklandı:', event.target);
                    event.preventDefault();
                    event.stopPropagation();
                    closeModal();
                    return;
                }

                // FA icon'larına tıklama
                if (event.target.classList.contains('fa-times')) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeModal();
                    return;
                }
            });
        }



        async function loadAnalysisData() {
            try {
                showLoading(true);
                const response = await fetch('/api/admin/page-analyzer/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'complete',
                        format: 'json'
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    analysisData = data.results;
                    processAnalysisData(analysisData);
                    updateStats();
                    updateLastUpdate();
                } else {
                    console.error('API hatası:', response.status);
                    loadMockData();
                }
            } catch (error) {
                console.error('Analiz verisi yüklenemedi:', error);
                loadMockData();
            } finally {
                showLoading(false);
            }
        }

        function processAnalysisData(apiResults) {
            if (!apiResults || !apiResults.pages) {
                loadMockData();
                return;
            }

            const categorizedPages = {};

            apiResults.pages.forEach(page => {
                const category = getCategoryFromPage(page);

                if (!categorizedPages[category]) {
                    categorizedPages[category] = [];
                }

                categorizedPages[category].push({
                    name: page.name,
                    score: page.score,
                    details: getPageDetails(page),
                    category: category,
                    severity: page.severity,
                    issues: page.issues || []
                });
            });

            analysisData.categorizedPages = categorizedPages;
            renderResults(categorizedPages);
            updateCategoryFilter(Object.keys(categorizedPages));
        }

        function getCategoryFromPage(page) {
            const name = page.name.toLowerCase();

            if (name.includes('ai') || name.includes('danışman ai')) {
                return 'AI Sistemi';
            } else if (name.includes('ilan') || name.includes('property')) {
                return 'İlan Yönetimi';
            } else if (name.includes('crm') || name.includes('müşteri') || name.includes('kişi')) {
                return 'CRM';
            } else if (name.includes('blog')) {
                return 'Blog Yönetimi';
            } else if (name.includes('adres') || name.includes('lokasyon')) {
                return 'Adres Yönetimi';
            } else if (name.includes('sistem') || name.includes('ayar')) {
                return 'Sistem Ayarları';
            } else if (name.includes('takım') || name.includes('danışman')) {
                return 'Takım Yönetimi';
            } else if (name.includes('analitik') || name.includes('performans')) {
                return 'Analytics';
            } else {
                return 'Diğer';
            }
        }

        function getPageDetails(page) {
            if (page.issues && page.issues.length > 0) {
                return `${page.issues.length} sorun tespit edildi`;
            } else if (page.severity === 'success') {
                return 'Mükemmel statusda';
            } else if (page.severity === 'warning') {
                return 'Küçük sorunlar var';
            } else if (page.severity === 'error' || page.severity === 'critical') {
                return 'Kritik sorunlar mevcut';
            } else {
                // Varsayılan status: issue yoksa ve severity belirsizse
                return 'Tespit edilen sorun yok';
            }
        }

        function renderResults(categorizedPages) {
            const resultsGrid = document.getElementById('resultsGrid');
            resultsGrid.innerHTML = '';

            Object.keys(categorizedPages).forEach(categoryName => {
                const pages = categorizedPages[categoryName];

                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'p-4 rounded-lg border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700';

                categoryDiv.innerHTML = `
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
                        <i class="${getCategoryIcon(categoryName)} mr-2"></i>
                        ${categoryName}
                        <span class="ml-2 px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:text-slate-300 dark:bg-slate-900">
                            ${pages.length}
                        </span>
                    </h3>
                    <div class="space-y-3">
                        ${pages.slice(0, 5).map(page => `
                                                                                                                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors cursor-pointer dark:bg-slate-900 dark:border-slate-700"
                                                                                                                                         onclick="showPageDetails('${page.name.replace(/'/g, "&#39;")}', ${page.score}, '${page.details.replace(/'/g, "&#39;")}', '${page.category.replace(/'/g, "&#39;")}', '${encodeURIComponent(JSON.stringify(page.issues || []))}')">
                                                                                                                                                <div class="flex-1">
                                                                                                                                                    <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">${page.name}</div>
                                                                                                                                                    <div class="text-xs text-gray-500 mt-1">${page.details}</div>
                                                                                                                                                </div>
                                                                                                                                                <div class="flex items-center gap-2">
                                                                                                                                                    <span class="text-sm font-semibold ${getScoreColor(page.score)}">
                                                                                                                                                        ${page.score}/10
                                                                                                                                                    </span>
                                                                                                                                                    <div class="w-12 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                                                                                                        <div class="h-full rounded-full ${getScoreBarColor(page.score)}"
                                                                                                                                                             style="width: ${page.score * 10}%"></div>
                                                                                                                                                    </div>
                                                                                                                                                </div>
                                                                                                                                            </div>
                                                                                                                                        `).join('')}
                        ${pages.length > 5 ? `
                                                                                                                                            <div class="text-center">
                                                                                                                                                <button onclick="loadMorePages('${categoryName}')"
                                                                                                                                                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center justify-center w-full py-2">
                                                                                                                                                    <i class="fas fa-chevron-down mr-1"></i>
                                                                                                                                                    ${pages.length - 5} sayfa daha
                                                                                                                                                </button>
                                                                                                                                            </div>
                                                                                                                                        ` : ''}
                    </div>
                `;

                resultsGrid.appendChild(categoryDiv);
            });
        }

        function getCategoryIcon(categoryName) {
            const icons = {
                'AI Sistemi': 'fas fa-robot text-blue-500',
                'İlan Yönetimi': 'fas fa-home text-green-500',
                'CRM': 'fas fa-users text-indigo-500',
                'Blog Yönetimi': 'fas fa-blog text-orange-500',
                'Adres Yönetimi': 'fas fa-map-marker-alt text-red-500',
                'Sistem Ayarları': 'fas fa-cogs text-gray-500',
                'Takım Yönetimi': 'fas fa-user-friends text-teal-500',
                'Analytics': 'fas fa-chart-bar text-purple-500'
            };
            return icons[categoryName] || 'fas fa-folder text-gray-400';
        }

        function getScoreColor(score) {
            if (score >= 8) return 'text-green-600';
            if (score >= 6) return 'text-yellow-600';
            return 'text-red-600';
        }

        function getScoreBarColor(score) {
            if (score >= 8) return 'bg-green-500';
            if (score >= 6) return 'bg-yellow-500';
            return 'bg-red-500';
        }

        function updateStats() {
            if (!analysisData || !analysisData.pages) return;

            const allPages = analysisData.pages;
            const totalPages = allPages.length;
            const highScorePages = allPages.filter(p => p.score >= 8).length;
            const mediumScorePages = allPages.filter(p => p.score >= 6 && p.score < 8).length;
            const lowScorePages = allPages.filter(p => p.score < 6).length;
            const averageScore = (allPages.reduce((sum, p) => sum + p.score, 0) / totalPages).toFixed(1);

            document.getElementById('totalPages').textContent = totalPages;
            document.getElementById('highScorePages').textContent = highScorePages;
            document.getElementById('mediumScorePages').textContent = mediumScorePages;
            document.getElementById('averageScore').textContent = averageScore;

            document.getElementById('summaryHighScore').textContent = highScorePages;
            document.getElementById('summaryMediumScore').textContent = mediumScorePages;
            document.getElementById('summaryLowScore').textContent = lowScorePages;
            document.getElementById('summaryAverageScore').textContent = averageScore;
        }

        function updateCategoryFilter(categories) {
            const select = document.getElementById('categoryFilter');
            select.innerHTML = '<option value="">Tüm Kategoriler</option>';
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                select.appendChild(option);
            });
        }

        function updateLastUpdate() {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleString('tr-TR');
        }

        function showLoading(show) {
            document.getElementById('loadingState').style.display = show ? 'block' : 'none';
            document.getElementById('resultsGrid').style.display = show ? 'none' : 'grid';
        }

        function startAnalysis() {
            const btn = document.getElementById('analyzeBtn');
            const icon = document.getElementById('analyzeIcon');
            const text = document.getElementById('analyzeText');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressStatus = document.getElementById('progressStatus');

            btn.disabled = true;
            icon.classList.add('fa-spin');
            text.textContent = 'Analiz Ediliyor...';
            progressContainer.style.display = 'block';

            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressPercent.textContent = progress + '%';
                progressStatus.textContent = `Sayfa ${Math.floor(progress / 10)}/10 analiz ediliyor...`;

                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        loadAnalysisData();
                        btn.disabled = false;
                        icon.classList.remove('fa-spin');
                        text.textContent = 'Analizi Başlat';
                        progressContainer.style.display = 'none';
                    }, 500);
                }
            }, 500);
        }

        function refreshData() {
            const btn = document.getElementById('refreshBtn');
            const icon = document.getElementById('refreshIcon');
            const text = document.getElementById('refreshText');

            btn.disabled = true;
            icon.classList.add('fa-spin');
            text.textContent = 'Yenileniyor...';

            loadAnalysisData().finally(() => {
                btn.disabled = false;
                icon.classList.remove('fa-spin');
                text.textContent = 'Yenile';
            });
        }

        function setupAutoRefresh() {
            const autoRefreshCheckbox = document.getElementById('autoRefresh');
            autoRefreshCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    refreshInterval = setInterval(loadAnalysisData, 30000); // 30 seconds
                } else {
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                        refreshInterval = null;
                    }
                }
            });
        }

        function filterByCategory() {
            const selectedCategory = document.getElementById('categoryFilter').value;
            if (selectedCategory && analysisData.categorizedPages) {
                const filtered = {};
                filtered[selectedCategory] = analysisData.categorizedPages[selectedCategory];
                renderResults(filtered);
            } else {
                renderResults(analysisData.categorizedPages || {});
            }
        }

        function sortResults() {
            // Implement sorting logic here
            console.log('Sorting results...');
        }

        function analyzeCategory(category) {
            console.log(`Analyzing category: ${category}`);
            // Implement category-specific analysis
        }

        function generateReport() {
            console.log('Generating report...');
            // Implement report generation
        }

        window.showPageDetails = function(name, score, details, category = 'Diğer', issues = []) {

            // Decode issues if it's a URL-encoded JSON string
            let issuesList = issues;
            if (typeof issues === 'string' && issues) {
                try {
                    issuesList = JSON.parse(decodeURIComponent(issues));
                } catch (e) {
                    issuesList = [];
                }
            }

            // Modal alanlarını doldur
            document.getElementById('modalPageName').value = name;
            document.getElementById('modalScore').value = `${score}/10`;
            document.getElementById('modalCategory').value = category;
            document.getElementById('modalDetails').value = details;

            // Issues varsa göster
            if (issuesList && issuesList.length > 0) {
                document.getElementById('issuesSection').style.display = 'block';
                document.getElementById('modalIssues').value = issuesList.join('\n');
            } else {
                document.getElementById('issuesSection').style.display = 'none';
                document.getElementById('modalIssues').value = '';
            }

            // Tüm detayları birleştir
            const allDetails = `Sayfa Detayları
================

Sayfa Adı: ${name}
Performans Skoru: ${score}/10
Kategori: ${category}
Detaylar: ${details}

${issuesList && issuesList.length > 0 ? `Tespit Edilen Sorunlar:
                                        ${issuesList.map((issue, index) => `${index + 1}. ${issue}`).join('\n')}` : 'Tespit edilen sorun yok.'}

Analiz Tarihi: ${new Date().toLocaleString('tr-TR')}`;

            document.getElementById('modalAllDetails').value = allDetails;

            // Modal'ı göster
            const modal = document.getElementById('pageDetailsModal');
            if (!modal) {
                console.error('Modal element bulunamadı!');
                return;
            }

            console.log('Modal açılıyor...');
            modal.style.display = 'block';
            modal.style.opacity = '0';
            document.body.style.overflow = 'hidden'; // Scroll'u engelle

            // Fade in animasyonu
            setTimeout(() => {
                modal.style.opacity = '1';
                console.log('Modal açıldı');
            }, 10);
        } // Global scope'ta tanımla
        window.closeModal = function() {
            console.log('closeModal fonksiyonu çağrıldı');
            const modal = document.getElementById('pageDetailsModal');
            if (!modal) {
                console.log('Modal element bulunamadı');
                return;
            }

            console.log('Modal kapatılıyor...');
            // Önce fade out animasyonu ekle
            modal.style.transition = 'opacity 0.15s ease-in-out';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '1'; // Sonraki açılış için sıfırla
                document.body.style.overflow = 'auto'; // Scroll'u geri aç
            }, 150);
        }

        window.copyToClipboard = function(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999); // Mobil cihazlar için

            try {
                document.execCommand('copy');
                showToast('Panoya kopyalandı!', 'success');
            } catch (err) {
                // Modern clipboard API kullan
                navigator.clipboard.writeText(element.value).then(() => {
                    showToast('Panoya kopyalandı!', 'success');
                }).catch(() => {
                    showToast('Kopyalama başarısız!', 'error');
                });
            }
        }

        window.copyAllDetails = function() {
            const element = document.getElementById('modalAllDetails');
            element.select();
            element.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                showToast('Tüm detaylar panoya kopyalandı!', 'success');
            } catch (err) {
                navigator.clipboard.writeText(element.value).then(() => {
                    showToast('Tüm detaylar panoya kopyalandı!', 'success');
                }).catch(() => {
                    showToast('Kopyalama başarısız!', 'error');
                });
            }
        }

        function showToast(message, type = 'info') {
            // Toast notification oluştur
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 toast-notification shadow-lg ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;

            // İkon ekle
            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
            toast.innerHTML = `<span class="mr-2">${icon}</span>${message}`;

            document.body.appendChild(toast);

            // 3 saniye sonra kaldır
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        function loadMorePages(categoryName) {
            console.log(`Loading more pages for ${categoryName}`);
            // Implement load more functionality
        }

        function loadMockData() {
            const mockData = {
                pages: [{
                        name: 'Danışman AI Sistemi 🤖',
                        score: 6.4,
                        severity: 'warning',
                        issues: [
                            'API yanıt süresi yavaş (>2 saniye)',
                            'Cache sistemi optimize edilmeli',
                            'Bellek kullanımı %75 seviyesinde'
                        ]
                    },
                    {
                        name: 'İlan Yönetimi 🏠',
                        score: 10.0,
                        severity: 'success',
                        issues: []
                    },
                    {
                        name: 'CRM Dashboard 🏢',
                        score: 9.3,
                        severity: 'success',
                        issues: []
                    },
                    {
                        name: 'Feature Category Management',
                        score: 5.4,
                        severity: 'info', // Belirsiz severity
                        issues: [] // Hiç issue yok
                    }
                ]
            };
            analysisData = mockData;
            processAnalysisData(mockData);
            updateStats();
        }

        // Test modal fonksiyonu
        window.testModal = function() {
            const testIssues = ['API yanıt süresi yavaş (>2 saniye)', 'Cache sistemi optimize edilmeli',
                'Bellek kullanımı %75 seviyesinde'
            ];
            showPageDetails(
                'Danışman AI Sistemi 🤖',
                6.4,
                '3 sorun tespit edildi',
                'AI Sistemi',
                testIssues
            );
        }
    </script>
@endpush
