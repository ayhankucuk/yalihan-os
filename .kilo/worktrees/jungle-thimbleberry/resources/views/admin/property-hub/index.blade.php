@extends('admin.layouts.admin')

@section('title', 'Property Configuration Hub')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-6" x-data="propertyHub()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                        🏠 Property Configuration Hub
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Tüm emlak yapılandırmalarını tek yerden yönetin
                    </p>
                </div>

                <!-- Health Score Badge -->
                <div class="flex items-center gap-4">
                    <div
                        @class([
                            'px-4 py-2 rounded-full transition-all duration-200 font-semibold',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $healthScore >= 80,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $healthScore >= 60 && $healthScore < 80,
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $healthScore < 60,
                        ])>
                        Sistem Sağlığı: {{ $healthScore }}/100
                    </div>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Features -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-md dark:shadow-none">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Özellik</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $stats['total_features'] }}</p>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-green-600 dark:text-green-400">
                        {{ $stats['active_features'] }} aktif
                    </div>
                </div>

                <!-- Total Assignments -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-md dark:shadow-none">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Atama</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $stats['total_assignments'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature Packs -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-md dark:shadow-none">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Özellik Paketleri</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $stats['total_packs'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Orphaned Features -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-md dark:shadow-none">
                    <div class="flex items-center">
                        <div
                            class="p-3 rounded-full {{ $stats['orphaned_features'] > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }}">
                            <svg class="w-6 h-6 {{ $stats['orphaned_features'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kullanılmayan</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $stats['orphaned_features'] }}
                            </p>
                        </div>
                    </div>
                    @if ($stats['orphaned_features'] > 0)
                        <div class="mt-3">
                            <a href="{{ route('admin.property-hub.analytics.index') }}"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-all duration-200">
                                Detayları gör →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Main Navigation Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Kategori Yönetimi (NEW) -->
                <a href="{{ route('admin.ilan-kategorileri.index') }}" id="category-manager-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">📁</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-all duration-200 dark:text-slate-100">
                            Kategori Yönetimi
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            İlan kategorilerini ve ağaç yapısını yönetin
                        </p>
                    </div>
                </a>

                <!-- Yayın Tipi Yönetimi (NEW) -->
                <a href="{{ route('admin.property_types.index') }}" id="property-type-manager-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-pink-300 dark:hover:border-pink-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">🏷️</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-all duration-200 dark:text-slate-100">
                            Yayın Tipi Yönetimi
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Kategori bazlı yayın tipi ve alan bağımlılıkları
                        </p>
                    </div>
                </a>

                <!-- Feature Pool -->
                <a href="{{ route('admin.property-hub.features.index') }}" id="feature-pool-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">🧩</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-all duration-200 dark:text-slate-100">
                            Global Feature Pool
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Tüm özellikleri tanımla ve yönet
                        </p>
                    </div>
                </a>

                <!-- Feature Packs -->
                <a href="{{ route('admin.property-hub.packs.index') }}" id="feature-packs-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">📦</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-all duration-200 dark:text-slate-100">
                            Özellik Paketleri
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Hazır paketlerle hızlı atama
                        </p>
                    </div>
                </a>

                <!-- Template Manager -->
                <a href="{{ route('admin.property-hub.templates.index') }}" id="template-manager-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-green-300 dark:hover:border-green-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">🎯</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-all duration-200 dark:text-slate-100">
                            Template Manager
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Kategori bazlı özellik ataması
                        </p>
                    </div>
                </a>

                <!-- Analytics -->
                <a href="{{ route('admin.property-hub.analytics.index') }}" id="analytics-card"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800
                      hover:shadow-lg hover:border-orange-300 dark:hover:border-orange-600 transition-all duration-200">
                    <div class="text-center">
                        <div class="text-4xl mb-3">📊</div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-all duration-200 dark:text-slate-100">
                            Analytics
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Kullanım istatistikleri ve raporlar
                        </p>
                    </div>
                </a>
            </div>

            <!-- Quick Actions Panel -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 mb-8 dark:shadow-none"
                id="quick-actions">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">⚡ Hızlı İşlemler</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.property-hub.features.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                          transition-all duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Yeni Özellik Ekle
                    </a>

                    <button @click="showApplyPackModal = true"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg
                               transition-all duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Paket Uygula
                    </button>

                    <button @click="showImportModal = true"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg
                               transition-all duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Template İçe Aktar
                    </button>

                    <button @click="exportTemplates()"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg
                               transition-all duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Dışa Aktar
                    </button>

                    <a href="{{ route('admin.ups.governance.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg
                          transition-all duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                        UPS Governance
                    </a>
                </div>
            </div>

            <!-- Recent Changes Timeline -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">📜 Son Değişiklikler</h3>
                <div class="flow-root">
                    <ul class="-mb-8">
                        @forelse($recentChanges as $change)
                            <li>
                                <div class="relative pb-8">
                                    @if (!$loop->last)
                                        <span
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span
                                                @class([
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-800 transition-all duration-200',
                                                    'bg-green-500' => $change->action === 'create',
                                                    'bg-blue-500' => $change->action === 'update',
                                                    'bg-red-500' => $change->action === 'delete',
                                                    'bg-purple-500' => $change->action === 'assign',
                                                    'bg-orange-500' => $change->action === 'unassign',
                                                    'bg-gray-500' => !in_array($change->action, ['create', 'update', 'delete', 'assign', 'unassign']),
                                                ])>
                                                @if ($change->action === 'create')
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                @elseif($change->action === 'update')
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                        </path>
                                                    </svg>
                                                @elseif($change->action === 'delete')
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                @elseif($change->action === 'assign')
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                                        </path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-slate-200">
                                                    {{ $change->description ?? 'İşlem yapıldı' }}
                                                </p>
                                            </div>
                                            <div
                                                class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                <span>{{ $change->user->name ?? 'Sistem' }}</span>
                                                <span
                                                    class="block text-xs">{{ $change->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Henüz değişiklik kaydı yok
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Apply Pack Modal -->
        <div x-show="showApplyPackModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showApplyPackModal = false"></div>

                <div
                    class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-xl shadow-xl transform transition-all">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">📦 Paket Uygula</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Mevcut paketler için lütfen Özellik Paketleri sayfasını ziyaret edin.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showApplyPackModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200 dark:text-slate-300">
                            İptal
                        </button>
                        <a href="{{ route('admin.property-hub.packs.index') }}"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200">
                            Paketlere Git
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showImportModal = false">
                </div>

                <div
                    class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-xl shadow-xl transform transition-all">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">📤 Template İçe Aktar</h3>

                    <form @submit.prevent="importTemplates()" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-100 mb-2">
                                JSON Dosyası Seçin
                            </label>
                            <input type="file" accept=".json" x-ref="importFile"
                                class="block w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100
                                      dark:file:bg-blue-900 dark:file:text-blue-300">
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showImportModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200 dark:text-slate-300">
                                İptal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200">
                                İçe Aktar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function propertyHub() {
                return {
                    showApplyPackModal: false,
                    showImportModal: false,

                    async exportTemplates() {
                        try {
                            const response = await fetch('{{ route('admin.property-hub.export') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'ups-templates-{{ now()->format('Y-m-d') }}.json';
                            a.click();

                            if (window.toast) {
                                window.toast.success('Template\'ler başarıyla dışa aktarıldı');
                            }
                        } catch (error) {
                            if (window.toast) {
                                window.toast.error('Dışa aktarma başarısız: ' + error.message);
                            }
                        }
                    },

                    async importTemplates() {
                        const file = this.$refs.importFile.files[0];
                        if (!file) {
                            if (window.toast) {
                                window.toast.error('Lütfen bir dosya seçin');
                            }
                            return;
                        }

                        const formData = new FormData();
                        formData.append('file', file);

                        try {
                            const response = await fetch('{{ route('admin.property-hub.import') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: formData
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.toast) {
                                    window.toast.success(data.message);
                                }
                                this.showImportModal = false;
                                location.reload();
                            } else {
                                if (window.toast) {
                                    window.toast.error(data.message);
                                }
                            }
                        } catch (error) {
                            if (window.toast) {
                                window.toast.error('İçe aktarma başarısız: ' + error.message);
                            }
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection
