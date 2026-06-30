@extends('admin.layouts.admin')

@section('title', 'Talep-Portföy Eşleştirme')
@section('meta_description',
    'AI destekli talep-portföy eşleştirme sistemi - müşteri taleplerini uygun portföylerle
    akıllı şekilde analiz eder ve önerir.')
@section('meta_keywords', 'talep, portföy, eşleştirme, analiz, yapay zeka, emlak')

@push('styles')
    <style>
        /* Modal z-index fix - sayfa header'ının üstünde görünmesi için */
        #onerilerModal {
            z-index: 1060 !important;
        }

        #onerilerModal .modal-dialog {
            z-index: 1061 !important;
            margin-top: 2rem;
        }

        #onerilerModal .modal-content {
            z-index: 1062 !important;
            position: relative;
            overflow: hidden;
        }

        #onerilerModal .modal-header {
            z-index: 1063 !important;
            position: relative !important;
            top: auto !important;
            background: #2563eb !important;
            border-bottom: none !important;
        }

        .modal-backdrop {
            z-index: 1059 !important;
        }

        /* Modal açıkken sayfa header'ını gizle */
        body.modal-open header[class*="sticky"],
        body.modal-open header[class*="top-0"] {
            z-index: 30 !important;
        }

        /* Modal header'ını izole et - sayfa header öğelerinin karışmasını engelle */
        #onerilerModal .modal-header {
            isolation: isolate;
            overflow: hidden;
        }

        /* Modal header içindeki tüm öğeleri kontrol et */
        #onerilerModal .modal-header * {
            position: relative;
            z-index: 10;
        }

        /* Modal header dışındaki öğeleri gizle */
        #onerilerModal .modal-header input[type="search"]:not(.modal-header input),
        #onerilerModal .modal-header button[aria-label="Tema"]:not(.modal-header button[data-bs-dismiss]),
        #onerilerModal .modal-header .relative[x-data*="o"]:not(.modal-header > *) {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div x-data="{
        showAiAnalizModal: false,
        showTopluAnalizModal: false,
        showOnerilerModal: false,
        aiAnalizContent: '',
        topluAnalizContent: '',
        onerilerContent: ''
    }" class="min-h-screen bg-gray-50 dark:bg-slate-900">
        <!-- ✅ SAB: Page Header - Tailwind CSS -->
        <div
            class="mb-8 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Talep-Portföy Eşleştirme</h1>
                    <p class="text-gray-600 dark:text-gray-400">AI destekli analiz ve akıllı öneriler</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white"
                        onclick="window.location.reload()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yenile
                    </button>
                    <button type="button"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white"
                        onclick="cacheTemizle()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Cache Temizle
                    </button>
                    <button type="button"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                        onclick="topluAnaliz()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Toplu AI Analiz
                    </button>
                </div>
            </div>
        </div>

        <!-- ✅ SAB: Content Wrapper - Tailwind CSS -->
        <div class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <!-- Toplam Talep -->
                <div class="p-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 rounded-xl text-white shadow-md dark:shadow-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ $talepStats['toplam_talep'] ?? 0 }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Toplam Talep</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-green-600 dark:text-green-400 font-medium">↗ %12</span>
                        <span class="text-gray-600 dark:text-gray-400">Bu hafta</span>
                    </div>
                </div>

                <!-- Aktif Talep -->
                <div class="p-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 rounded-xl text-white shadow-md dark:shadow-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                                {{ $talepStats['status_talep'] ?? 0 }}</div>
                            <div class="text-sm text-green-600 dark:text-green-400 font-medium">Aktif Talep</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-green-600 dark:text-green-400 font-medium">↗ %8</span>
                        <span class="text-gray-600 dark:text-gray-400">Bu hafta</span>
                    </div>
                </div>

                <!-- Acil Talep -->
                <div class="p-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-500 rounded-xl text-white shadow-md dark:shadow-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">
                                {{ $talepStats['acil_talep'] ?? 0 }}</div>
                            <div class="text-sm text-amber-600 dark:text-amber-400 font-medium">Acil Talep</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-red-600 dark:text-red-400 font-medium">↗ %3</span>
                        <span class="text-gray-600 dark:text-gray-400">Son 24 saat</span>
                    </div>
                </div>

                <!-- Toplam Portföy -->
                <div class="p-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-500 rounded-xl text-white shadow-md dark:shadow-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                                {{ $portfolyoStats['toplam_ilan'] ?? 0 }}</div>
                            <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Toplam Portföy</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-green-600 dark:text-green-400 font-medium">↗ %5</span>
                        <span class="text-gray-600 dark:text-gray-400">Bu ay</span>
                    </div>
                </div>
            </div>

            <!-- Aktif Talepler Tablosu -->
            <!-- ✅ SAB: Card - Tailwind CSS -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3 dark:border-slate-700">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-slate-900 rounded-lg">
                        <input type="checkbox" id="selectAll"
                            class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer"
                            onchange="toggleSelectAll()">
                        <label for="selectAll"
                            class="text-sm font-medium text-gray-900 dark:text-white cursor-pointer dark:text-slate-100">Tümünü
                            Seç</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 hidden lg:inline">AI analizine hazır
                            müşteriler</span>
                        <button
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white"
                            onclick="window.location.reload()">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Listele
                        </button>
                    </div>
                </div>
                <!-- ✅ SAB: Card Body - Tailwind CSS -->
                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Seç</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Müşteri Bilgileri</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Talep Detayı</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Kategori</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Durum</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        AI İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($talepler as $talep)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                class="talep-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600"
                                                value="{{ $talep->id }}">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-semibold">
                                                    <span>{{ strtoupper(substr($talep->kisi->ad ?? '?', 0, 1)) }}</span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                        {{ $talep->kisi->ad ?? '—' }} {{ $talep->kisi->soyad ?? '' }}
                                                    </div>
                                                    <div
                                                        class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                        </svg>
                                                        {{ $talep->kisi->telefon ?? '—' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        ID: #{{ $talep->id }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="max-w-xs">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                                    {{ Str::limit($talep->aciklama ?? 'Açıklama yok', 60) }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                                    {{ $talep->created_at ? $talep->created_at->format('d.m.Y H:i') : '—' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                {{ ucfirst($talep->talep_tipi ?? 'Genel') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $currentStatus = $talep->talep_durumu ?? 'normal';
                                                $statusStyle = match ($currentStatus) {
                                                    'acil'
                                                        => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                    'beklemede'
                                                        => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                    default
                                                        => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                };
                                                $dotStyle = match ($currentStatus) {
                                                    'acil' => 'bg-red-500',
                                                    'beklemede' => 'bg-yellow-500',
                                                    default => 'bg-green-500',
                                                };
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusStyle }}">
                                                <div class="w-2 h-2 rounded-full mr-2 {{ $dotStyle }}"></div>
                                                {{ ucfirst($talep->talep_durumu ?? 'Aktif') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors duration-150"
                                                    onclick="talepAnaliz({{ $talep->id }})">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                    AI Analiz
                                                </button>
                                                <button
                                                    class="inline-flex items-center px-4 py-2.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-slate-900 dark:text-gray-400 dark:hover:bg-gray-600 focus:ring-2 focus:ring-blue-500 transition-all duration-150 dark:text-slate-300"
                                                    onclick="portfoyOner({{ $talep->id }})">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                    </svg>
                                                    Öneriler
                                                </button>
                                                <a class="inline-flex items-center px-4 py-2.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 focus:ring-2 focus:ring-blue-500 transition-all duration-150"
                                                    href="{{ route('admin.talep-portfolyo.show', $talep->id) }}">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Detay
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12">
                                            <div class="text-center">
                                                <div
                                                    class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                                                    <svg class="w-10 h-10 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </div>
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Aktif
                                                    talep bulunamadı</h3>
                                                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                                    Henüz analiz edilmeyi bekleyen talep bulunmuyor. Yeni talepler otomatik
                                                    olarak burada görünecek.
                                                </p>
                                                <div class="flex items-center justify-center gap-4">
                                                    <button onclick="window.location.reload()"
                                                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 dark:hover:from-blue-800 dark:hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-gray-800">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Listeyi Yenile
                                                    </button>
                                                    <a href="{{ url('admin/talepler') }}"
                                                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        Yeni Talep Ekle
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($talepler->hasPages())
                        <div
                            class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $talepler->firstItem() }}-{{ $talepler->lastItem() }} arası, toplam
                                    {{ $talepler->total() }} talep
                                </div>
                                <div class="flex items-center gap-2">
                                    {{ $talepler->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Analiz Modal - Alpine.js -->
            <div x-show="showAiAnalizModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @keydown.escape.window="showAiAnalizModal = false"
                class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showAiAnalizModal = false"></div>

                <!-- Modal -->
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl max-w-5xl w-full border border-gray-200 dark:border-slate-800 dark:border-slate-700"
                        @click.away="showAiAnalizModal = false">
                        <!-- Header -->
                        <div class="bg-blue-600 text-white p-4 rounded-t-xl">
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <h2 class="text-xl font-bold">AI Talep Analizi</h2>
                                </div>
                                <button type="button" @click="showAiAnalizModal = false"
                                    class="text-white dark:text-white hover:bg-white/20 dark:hover:bg-white/30 rounded-lg dark:rounded-lg p-2 dark:p-2 transition-colors dark:transition-colors duration-200 dark:duration-200"
                                    aria-label="Close">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <div x-html="aiAnalizContent || '<div class=\'py-8 text-center\'><div class=\'inline-flex items-center gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-700 dark:text-blue-300\'><div class=\'animate-spin\'><svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15\' /></svg></div><span class=\'font-medium\'>AI analizi yapılıyor, lütfen bekleyin...</span></div></div>'"
                                class="py-8 text-center"></div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-slate-800 rounded-b-xl dark:border-slate-700 dark:bg-slate-900">
                            <button type="button" @click="showAiAnalizModal = false"
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Kapat
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toplu Analiz Modal - Alpine.js -->
            <div x-show="showTopluAnalizModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @keydown.escape.window="showTopluAnalizModal = false"
                class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showTopluAnalizModal = false"></div>

                <!-- Modal -->
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-slate-800 dark:border-slate-700"
                        @click.away="showTopluAnalizModal = false">
                        <!-- Header -->
                        <div class="bg-blue-600 text-white p-4 rounded-t-xl">
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <h2 class="text-xl font-bold">Toplu AI Analiz</h2>
                                </div>
                                <button type="button" @click="showTopluAnalizModal = false"
                                    class="text-white dark:text-white hover:bg-white/20 dark:hover:bg-white/30 rounded-lg dark:rounded-lg p-2 dark:p-2 transition-colors dark:transition-colors duration-200 dark:duration-200"
                                    aria-label="Close">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <div class="text-center mb-6">
                                <div
                                    class="w-16 h-16 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Toplu Analiz Onayı
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">Seçili talepleri AI ile analiz etmek
                                    istediğinizden emin misiniz?</p>
                            </div>

                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div
                                    class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100" id="selectedCount">0
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Seçili Talep</div>
                                </div>
                                <div
                                    class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">~2-5</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Dakika Süre</div>
                                </div>
                                <div
                                    class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">99%</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Başarı Oranı</div>
                                </div>
                            </div>

                            <div
                                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/30 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <div class="p-1 bg-yellow-500 rounded-lg text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-1">Analiz Süreci
                                        </h4>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                            • Her talep için portföy eşleştirmesi yapılacak<br>
                                            • AI skorlama ve öneri sistemi çalışacak<br>
                                            • Sonuçlar otomatik olarak kaydedilecek
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-slate-800 rounded-b-xl dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="showTopluAnalizModal = false"
                                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    İptal
                                </button>
                                <button type="button" @click="topluAnalizBaslat(); showTopluAnalizModal = false"
                                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Analizi Başlat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                function talepAnaliz(talepId) {
                    // Alpine.js modal state'i güncelle
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        const alpineData = alpineEl._x_dataStack[0];
                        alpineData.showAiAnalizModal = true;
                        alpineData.aiAnalizContent =
                            '<div class="py-4 text-center"><div class="inline-flex items-center gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-700 dark:text-blue-300"><div class="animate-spin"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg></div><span class="font-medium">Yükleniyor...</span></div></div>';
                    }

                    fetch(`${"{{ url('admin/talep-portfolyo') }}"}/${talepId}/analiz`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                ai_analiz: true
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            const alpineEl = document.querySelector('[x-data]');
                            if (alpineEl && alpineEl._x_dataStack) {
                                const alpineData = alpineEl._x_dataStack[0];
                                if (data.success) {
                                    const a = data.analiz ?? {};
                                    alpineData.aiAnalizContent =
                                        `
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200"><i class="text-gray-400 text-gray-400-check-circle"></i> Talep analizi tamamlandı</div>
                    <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto font-mono text-sm mt-4">${JSON.stringify(a, null, 2)}</pre>`;
                                } else {
                                    alpineData.aiAnalizContent =
                                        `
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200"><i class="text-gray-400 text-gray-400-alert-triangle"></i> ${data.error ?? data.message ?? 'Analiz başarısız'}</div>`;
                                }
                            }
                        })
                        .catch(() => {
                            const alpineEl = document.querySelector('[x-data]');
                            if (alpineEl && alpineEl._x_dataStack) {
                                alpineEl._x_dataStack[0].aiAnalizContent =
                                    `
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200"><i class="text-gray-400 text-gray-400-alert-triangle"></i> Sunucu hatası</div>`;
                            }
                        });
                }

                function updateSelectedCount() {
                    const selected = document.querySelectorAll('.talep-checkbox:checked');
                    const el = document.getElementById('selectedCount');
                    if (el) el.textContent = selected.length;
                }

                function topluAnaliz() {
                    const selected = document.querySelectorAll('.talep-checkbox:checked');
                    if (selected.length === 0) {
                        alert('Lütfen analiz edilecek talepleri seçin');
                        return;
                    }
                    updateSelectedCount();
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        alpineEl._x_dataStack[0].showTopluAnalizModal = true;
                    }
                }

                function topluAnalizBaslat() {
                    const selected = document.querySelectorAll('.talep-checkbox:checked');
                    const talepIds = Array.from(selected).map(cb => cb.value);

                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        const alpineData = alpineEl._x_dataStack[0];
                        alpineData.showAiAnalizModal = true;
                        alpineData.aiAnalizContent =
                            '<div class="py-4 text-center"><div class="inline-flex items-center gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-700 dark:text-blue-300"><div class="animate-spin"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg></div><span class="font-medium">Analiz ediliyor...</span></div><p class="mt-4 text-gray-600 dark:text-gray-400">' +
                            talepIds.length + ' talep analiz ediliyor...</p></div>';
                    }

                    fetch("{{ route('admin.talep-portfolyo.toplu-analiz') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                talep_ids: talepIds
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            const alpineEl = document.querySelector('[x-data]');
                            if (alpineEl && alpineEl._x_dataStack) {
                                const alpineData = alpineEl._x_dataStack[0];
                                if (data.success) {
                                    alpineData.aiAnalizContent =
                                        `
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200"><i class="text-gray-400 text-gray-400-check-circle"></i> ${data.message}</div>
                    <div class="text-center mt-4"><button class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none" onclick="location.reload()">Sayfayı Yenile</button></div>`;
                                } else {
                                    alpineData.aiAnalizContent =
                                        `<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200">${data.message ?? 'Toplu analiz başarısız'}</div>`;
                                }
                            }
                        })
                        .catch(() => {
                            const alpineEl = document.querySelector('[x-data]');
                            if (alpineEl && alpineEl._x_dataStack) {
                                alpineEl._x_dataStack[0].aiAnalizContent =
                                    `<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200">Sunucu hatası</div>`;
                            }
                        });
                }

                // Öneriler modalı - status ve render
                window._onerilerState = {
                    raw: [],
                    talepId: null,
                    filters: {
                        minScore: 0,
                        sortDir: 'desc',
                        priceMin: '',
                        priceMax: '',
                        il: '',
                        ilce: '',
                        highOnly: false,
                        currency: ''
                    }
                };

                function renderOneriler() {
                    const container = document.getElementById('onerilerContent');
                    const f = window._onerilerState.filters || {};
                    let list = Array.isArray(window._onerilerState.raw) ? [...window._onerilerState.raw] : [];
                    const originalCount = list.length;

                    // Skor filtresi (hızlı: ≥90)
                    const effMinScore = f.highOnly ? 90 : (Number(f.minScore) || 0);
                    list = list.filter(o => (o.score ?? 0) >= effMinScore);
                    // Fiyat filtresi
                    if (f.priceMin != null && f.priceMin !== '') list = list.filter(o => (Number(o.fiyat) || 0) >= Number(f
                        .priceMin));
                    if (f.priceMax != null && f.priceMax !== '') list = list.filter(o => (Number(o.fiyat) || 0) <= Number(f
                        .priceMax));
                    // Konum filtresi
                    if (f.il) list = list.filter(o => (o.adres_il || '').toString() === String(f.il));
                    if (f.ilce) list = list.filter(o => (o.adres_ilce || '').toString() === String(f.ilce));
                    // Para birimi filtresi
                    if (f.currency) list = list.filter(o => (o.para_birimi || '').toString().toUpperCase() === String(f.currency)
                        .toUpperCase());

                    // Sıralama
                    list.sort((a, b) => (f.sortDir === 'asc' ? (a.score ?? 0) - (b.score ?? 0) : (b.score ?? 0) - (a.score ?? 0)));

                    // Filtre özeti güncelle
                    updateFilterSummary(f, originalCount, list.length);

                    if (list.length === 0) {
                        container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="text-gray-400 text-gray-400-search-x text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Hiç öneri bulunamadı</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Filtreleri gevşeterek daha fazla sonuç bulabilirsiniz</p>
                        <button class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-150" onclick="resetOneriFilters()">
                            <i class="text-gray-400 text-gray-400-refresh-ccw w-4 h-4 mr-2"></i>
                            Filtreleri Sıfırla
                        </button>
                    </div>
                `;
                        return;
                    }

                    const rows = list.map((o, index) => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-sm font-semibold">
                                ${index + 1}
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">#${o.id ?? '-'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="max-w-xs">
                            <div class="font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">${escapeHtml(o.baslik ?? '-')}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500">${o.created_at ? new Date(o.created_at).toLocaleDateString('tr-TR') : ''}</div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                            ${o.fiyat ? (Number(o.fiyat).toLocaleString('tr-TR') + ' ' + (o.para_birimi ?? '')) : '-'}
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            <i class="text-gray-400 text-gray-400-map-pin w-3 h-3 mr-1 text-red-500"></i>
                            ${escapeHtml([o.adres_il, o.adres_ilce].filter(Boolean).join(' / ') || '-')}
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${badgeClass(o)}">${escapeHtml(o.etiket ?? 'Uygun')}</span>
                            <div class="flex items-center gap-1">
                                <div class="w-12 h-2 bg-gray-200 dark:bg-slate-900 rounded-full overflow-hidden">
                                    <div class="h-full ${getScoreColor(o.score ?? 0)} transition-all duration-300" style="width: ${Math.min(o.score ?? 0, 100)}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white dark:text-slate-100">${o.score ?? 0}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <a href="${"{{ url('admin/ilanlar') }}"}/${o.id}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 rounded-lg transition-colors duration-150">
                                <i class="text-gray-400 text-gray-400-eye w-3 h-3 mr-1"></i>
                                Detay
                            </a>
                            <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50 rounded-lg transition-colors duration-150" onclick="sendToClient(${o.id})">
                                <i class="text-gray-400 text-gray-400-send w-3 h-3 mr-1"></i>
                                Gönder
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

                    container.innerHTML = `
                <div class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sıra</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Emlak Detayı</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fiyat</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Konum</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">AI Uygunluğu</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
                }

                function updateFilterSummary(filters, originalCount, filteredCount) {
                    const summaryEl = document.getElementById('filterSummary');
                    const countEl = document.getElementById('resultCount');

                    let summary = [];
                    if (filters.highOnly) summary.push('Premium seçim');
                    if (filters.minScore > 0) summary.push(`Min skor: ${filters.minScore}`);
                    if (filters.il) summary.push(`İl: ${filters.il}`);
                    if (filters.priceMin) summary.push(`Min: ${Number(filters.priceMin).toLocaleString('tr-TR')}`);
                    if (filters.priceMax) summary.push(`Max: ${Number(filters.priceMax).toLocaleString('tr-TR')}`);

                    if (summaryEl) {
                        summaryEl.textContent = summary.length > 0 ? summary.join(', ') : 'Tüm öneriler gösteriliyor';
                    }
                    if (countEl) {
                        countEl.textContent = `${filteredCount} / ${originalCount} sonuç`;
                    }
                }

                function getScoreColor(score) {
                    if (score >= 90) return 'bg-green-500';
                    if (score >= 80) return 'bg-yellow-500';
                    if (score >= 70) return 'bg-orange-500';
                    return 'bg-red-500';
                }

                function sendToClient(ilanId) {
                    // Müşteriye öneri gönderme fonksiyonu
                    alert(`İlan #${ilanId} müşteriye gönderilecek (bu özellik geliştirme aşamasında)`);
                }

                function exportResults() {
                    // Sonuçları dışa aktarma fonksiyonu
                    alert('Sonuçlar Excel/PDF olarak dışa aktarılacak (bu özellik geliştirme aşamasında)');
                }

                function badgeClass(o) {
                    const s = o.score ?? 0;
                    if (s >= 90) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                    if (s >= 80) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
                    if (s >= 70) return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
                    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                }

                function escapeHtml(str) {
                    return String(str).replace(/[&<>"]/g, (c) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;'
                    } [c]));
                }

                function saveOneriFilters() {
                    try {
                        localStorage.setItem('tp_oneri_filters', JSON.stringify(window._onerilerState.filters));
                    } catch {}
                }

                function loadOneriFilters() {
                    try {
                        const saved = JSON.parse(localStorage.getItem('tp_oneri_filters') || '{}');
                        if (saved && typeof saved === 'object') {
                            window._onerilerState.filters = Object.assign(window._onerilerState.filters, saved);
                        }
                    } catch {}
                    // Inputlara yansıt
                    const f = window._onerilerState.filters || {};
                    const minScoreEl = document.getElementById('minScore');
                    const sortDirEl = document.getElementById('sortDir');
                    const priceMinEl = document.getElementById('priceMin');
                    const priceMaxEl = document.getElementById('priceMax');
                    const ilEl = document.getElementById('filterIl');
                    const ilceEl = document.getElementById('filterIlce');
                    const highOnlyEl = document.getElementById('highOnly');
                    const currencyEl = document.getElementById('currencyFilter');
                    if (minScoreEl) minScoreEl.value = f.minScore ?? 0;
                    if (sortDirEl) sortDirEl.value = f.sortDir ?? 'desc';
                    if (priceMinEl) priceMinEl.value = f.priceMin ?? '';
                    if (priceMaxEl) priceMaxEl.value = f.priceMax ?? '';
                    if (ilEl) ilEl.value = f.il ?? '';
                    if (ilceEl) ilceEl.value = f.ilce ?? '';
                    if (highOnlyEl) highOnlyEl.checked = !!f.highOnly;
                    if (currencyEl) currencyEl.value = f.currency ?? '';
                    // Currency ve il listeleri yeni populate edilmiş olabilir, seçimi koru
                }

                function onOneriFilterChange() {
                    const minScore = parseInt(document.getElementById('minScore')?.value || '0', 10);
                    const sortDir = document.getElementById('sortDir')?.value || 'desc';
                    const priceMinVal = document.getElementById('priceMin')?.value;
                    const priceMaxVal = document.getElementById('priceMax')?.value;
                    const il = document.getElementById('filterIl')?.value || '';
                    const ilce = document.getElementById('filterIlce')?.value || '';
                    const highOnly = !!document.getElementById('highOnly')?.checked;
                    const currency = document.getElementById('currencyFilter')?.value || '';
                    window._onerilerState.filters = {
                        minScore,
                        sortDir,
                        priceMin: priceMinVal !== '' ? Number(priceMinVal) : '',
                        priceMax: priceMaxVal !== '' ? Number(priceMaxVal) : '',
                        il,
                        ilce,
                        highOnly,
                        currency
                    };
                    saveOneriFilters();
                    renderOneriler();
                }

                function onHighOnlyToggle() {
                    const cb = document.getElementById('highOnly');
                    if (!cb) return;
                    window._onerilerState.filters.highOnly = !!cb.checked;
                    saveOneriFilters();
                    renderOneriler();
                }

                function populateIlOptions() {
                    const ilSelect = document.getElementById('filterIl');
                    if (!ilSelect) return;
                    const uniqueIller = Array.from(new Set((window._onerilerState.raw || [])
                        .map(o => (o.adres_il || '').toString())
                        .filter(Boolean)));
                    ilSelect.innerHTML = '<option value="">Tümü</option>' + uniqueIller.map(v =>
                        `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
                    // Para birimi seçeneklerini önerilerden türet
                    const currencyEl = document.getElementById('currencyFilter');
                    if (currencyEl) {
                        const uniqueCurrencies = Array.from(new Set((window._onerilerState.raw || [])
                            .map(o => (o.para_birimi || '').toString().toUpperCase())
                            .filter(Boolean)));
                        currencyEl.innerHTML = '<option value="">Tümü</option>' + uniqueCurrencies.map(c =>
                            `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
                    }
                }

                function onOneriIlChange() {
                    const il = document.getElementById('filterIl')?.value || '';
                    const ilceSelect = document.getElementById('filterIlce');
                    if (ilceSelect) {
                        const uniqueIlceler = Array.from(new Set((window._onerilerState.raw || [])
                            .filter(o => (o.adres_il || '').toString() === il)
                            .map(o => (o.adres_ilce || '').toString())
                            .filter(Boolean)));
                        ilceSelect.innerHTML = '<option value="">Tümü</option>' + uniqueIlceler.map(v =>
                            `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
                    }
                    onOneriFilterChange();
                }

                function resetOneriFilters() {
                    window._onerilerState.filters = {
                        minScore: 0,
                        sortDir: 'desc',
                        priceMin: '',
                        priceMax: '',
                        il: '',
                        ilce: '',
                        highOnly: false,
                        currency: ''
                    };
                    saveOneriFilters();
                    loadOneriFilters();
                    renderOneriler();
                }

                function portfoyOner(talepId) {
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        alpineEl._x_dataStack[0].showOnerilerModal = true;
                    }
                    const container = document.getElementById('onerilerContent');
                    container.innerHTML =
                        '<div class="text-center py-4"><div class="inline-flex items-center gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-700 dark:text-blue-300 mx-auto"><div class="animate-spin"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg></div><span class="font-medium">Yükleniyor...</span></div></div>';

                    fetch(`${"{{ url('admin/talep-portfolyo') }}"}/${talepId}/portfolyo-oner`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (!data.success) {
                                container.innerHTML =
                                    `<div class=\"rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200\"><i class=\"text-gray-400 text-gray-400-alert-triangle\"></i> ${data.message ?? 'Öneriler alınamadı'}</div>`;
                                return;
                            }
                            window._onerilerState.raw = data.oneriler || [];
                            window._onerilerState.talepId = talepId;
                            // Filtreleri yükle ve seçenekleri doldur
                            populateIlOptions();
                            loadOneriFilters();
                            onOneriIlChange();
                            renderOneriler();
                        })
                        .catch(() => {
                            container.innerHTML =
                                '<div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200 border-red-200 bg-red-50 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200"><i class="text-gray-400 text-gray-400-alert-triangle"></i> Sunucu hatası</div>';
                        });
                }

                function cacheTemizle() {
                    if (!confirm('AI cache\'ini temizlemek istiyor musunuz?')) return;
                    fetch("{{ route('admin.talep-portfolyo.cache-temizle') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) alert('Cache başarıyla temizlendi');
                            else alert('Cache temizleme hatası: ' + (data.message || 'Bilinmeyen'));
                        })
                        .catch(() => alert('Cache temizleme sırasında hata oluştu'));
                }

                function toggleSelectAll() {
                    const selectAll = document.getElementById('selectAll');
                    document.querySelectorAll('.talep-checkbox').forEach(cb => cb.checked = selectAll.checked);
                    updateSelectedCount();
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.talep-checkbox').forEach(cb => cb.addEventListener('change',
                        updateSelectedCount));
                    // Modal açıldığında varsa kaydedilmiş filtreleri inputlara basabilmek için önceden yükle
                    loadOneriFilters();
                });
            </script>
        @endpush

        <!-- AI Portföy Önerileri Modal - Alpine.js -->
        <div x-show="showOnerilerModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @keydown.escape.window="showOnerilerModal = false"
            class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showOnerilerModal = false"></div>

            <!-- Modal -->
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="relative bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-2xl max-w-7xl w-full border border-gray-200 dark:border-slate-800 dark:border-slate-700"
                    @click.away="showOnerilerModal = false">
                    <!-- Modern Header - İzole edilmiş, sadece modal içeriği -->
                    <div class="bg-blue-600 text-white border-0 p-4 relative isolate rounded-t-2xl"
                        style="background: #2563eb !important;">
                        <div class="flex items-center justify-between w-full relative z-10">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <h2 class="text-xl font-bold whitespace-nowrap">AI Portföy Önerileri</h2>
                            </div>
                            <button type="button" @click="showOnerilerModal = false"
                                class="text-white dark:text-white hover:bg-white/20 dark:hover:bg-white/30 rounded-lg dark:rounded-lg p-2 dark:p-2 transition-colors dark:transition-colors duration-200 dark:duration-200 flex-shrink-0 dark:flex-shrink-0 ml-4 dark:ml-4"
                                aria-label="Close">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-0">
                        <!-- Filtre Paneli -->
                        <div class="p-6 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700 dark:bg-slate-900">

                            <!-- Filtre Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <!-- Minimum Skor -->
                                <div
                                    class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                    <div class="p-3">
                                        <label for="minScore"
                                            class="flex items-center gap-1 text-sm font-medium mb-2 text-gray-900 dark:text-white dark:text-slate-100">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                            Minimum Skor
                                        </label>
                                        <input id="minScore" type="number" min="0" max="100"
                                            value="0"
                                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-slate-100"
                                            oninput="onOneriFilterChange()" />
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">0-100 arası</div>
                                    </div>
                                </div>

                                <!-- Sıralama -->
                                <div
                                    class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                    <div class="p-3">
                                        <label for="sortDir"
                                            class="flex items-center gap-1 text-sm font-medium mb-2 text-gray-900 dark:text-white dark:text-slate-100">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                            Sıralama
                                        </label>
                                        <select style="color-scheme: light dark;" id="sortDir"
                                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                            onchange="onOneriFilterChange()">
                                            <option value="desc">Skor: Yüksek → Düşük</option>
                                            <option value="asc">Skor: Düşük → Yüksek</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Fiyat Aralığı -->
                                <div
                                    class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                    <div class="p-3">
                                        <label
                                            class="flex items-center gap-1 text-sm font-medium mb-2 text-gray-900 dark:text-white dark:text-slate-100">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Fiyat Aralığı
                                        </label>
                                        <div class="mb-2">
                                            <input id="priceMin" type="number" min="0" step="1000"
                                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-slate-100"
                                                placeholder="Min fiyat" oninput="onOneriFilterChange()" />
                                        </div>
                                        <input id="priceMax" type="number" min="0" step="1000"
                                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-slate-100"
                                            placeholder="Max fiyat" oninput="onOneriFilterChange()" />
                                    </div>
                                </div>

                                <!-- Konum -->
                                <div
                                    class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                    <div class="p-3">
                                        <label
                                            class="flex items-center gap-1 text-sm font-medium mb-2 text-gray-900 dark:text-white dark:text-slate-100">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Konum
                                        </label>
                                        <div class="mb-2">
                                            <select style="color-scheme: light dark;" id="filterIl"
                                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                                onchange="onOneriIlChange()">
                                                <option value="">Tüm İller</option>
                                            </select>
                                        </div>
                                        <select style="color-scheme: light dark;" id="filterIlce"
                                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                            onchange="onOneriFilterChange()">
                                            <option value="">Tüm İlçeler</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Gelişmiş Filtreler -->
                                <div
                                    class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                    <div class="p-3">
                                        <label
                                            class="flex items-center gap-1 text-sm font-medium mb-3 text-gray-900 dark:text-white dark:text-slate-100">
                                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                            Özel Filtreler
                                        </label>
                                        <div class="mb-3">
                                            <label
                                                class="inline-flex items-center gap-2 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                <input id="highOnly" type="checkbox"
                                                    class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer"
                                                    onchange="onHighOnlyToggle()" />
                                                <span>Premium Seçim (≥90)</span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <select style="color-scheme: light dark;" id="currencyFilter"
                                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                                onchange="onOneriFilterChange()">
                                                <option value="">Tüm Para Birimleri</option>
                                            </select>
                                        </div>
                                        <button type="button"
                                            class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 dark:text-white"
                                            onclick="resetOneriFilters()">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Sıfırla
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtre Özeti -->
                            <div
                                class="mt-4 rounded-xl border border-blue-200 dark:border-blue-800/30 bg-blue-50 dark:bg-blue-900/20 p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span id="filterSummary">Tüm öneriler gösteriliyor</span>
                                    </div>
                                    <div class="text-sm font-medium text-blue-900 dark:text-blue-100" id="resultCount">0
                                        sonuç
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sonuçlar Alanı -->
                        <div class="p-6">
                            <div id="onerilerContent" style="min-height: 400px;">
                                <div class="py-10 text-center">
                                    <div
                                        class="inline-flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-full w-16 h-16 mb-4">
                                        <div class="animate-spin text-blue-600 dark:text-blue-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </div>
                                    </div>
                                    <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">AI Analizi Yapılıyor
                                    </h5>
                                    <p class="text-gray-600 dark:text-gray-400">Portföy önerileri hazırlanıyor, lütfen
                                        bekleyin...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer - Düzeltilmiş Flex Yapısı -->
                    <div
                        class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-slate-800 rounded-b-2xl dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex flex-row items-center justify-between w-full gap-4">
                            <!-- Sol Taraf: Son Güncelleme Bilgisi -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 flex-shrink-0">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="whitespace-nowrap">Son güncelleme: <span class="font-medium">Az
                                        önce</span></span>
                            </div>

                            <!-- Sağ Taraf: Butonlar -->
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <button type="button" @click="showOnerilerModal = false"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-gray-800 whitespace-nowrap dark:text-white"
                                    aria-label="Kapat">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span>Kapat</span>
                                </button>
                                <button type="button"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 whitespace-nowrap"
                                    onclick="exportResults()" aria-label="Sonuçları İndir">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Sonuçları İndir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
