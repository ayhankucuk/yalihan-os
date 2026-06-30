@extends('admin.layouts.admin')

@section('title', 'İlan Yönetimi')

@section('content')
    <div class="space-y-6" x-data="ilanFilter()">
        {{-- Page Header --}}
        <div
            class="flex items-center justify-between bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    İlan Yönetimi
                </h1>
                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                    Tüm ilanlarınızı yönetin ve düzenleyin
                </p>
            </div>

            <a href="{{ route('admin.ilanlar.create-wizard') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 font-medium dark:shadow-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Yeni İlan Oluştur
            </a>
        </div>

        {{-- Statistics Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Toplam İlan --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Toplam İlan</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ number_format($stats['total'] ?? $ilanlar->total()) }}</h3>
                    </div>
                </div>
            </div>

            {{-- Aktif İlanlar --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Aktif İlanlar</p>
                            <h3 class="text-sm font-medium opacity-90">Aktif İlan</h3>
                            <p class="text-3xl font-bold mt-1">{{ $istatistikler['aktif'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Bu Ay --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-purple-50 dark:bg-purple-900/20 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Bu Ay</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ number_format($stats['this_month'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            {{-- Bekleyen İlanlar --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-orange-50 dark:bg-orange-900/20 rounded-lg flex items-center justify-center text-orange-600 dark:text-orange-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Bekleyen İlanlar</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ number_format($stats['pending'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🛰️ CORTEX TAB NAVIGATOR --}}
        <div
            class="flex items-center p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-800 w-fit">
            @php $activeTab = request('tab', 'active'); @endphp
            @php $counts = $tabCounts ?? []; @endphp

            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'active'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'active' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                AKTİF <span class="ml-1 opacity-50">({{ $counts['active'] ?? 0 }})</span>
            </a>
            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'expired'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'expired' ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                SÜRESİ DOLAN <span class="ml-1 opacity-50">({{ $counts['expired'] ?? 0 }})</span>
            </a>
            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'passive'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'passive' ? 'bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                PASİF <span class="ml-1 opacity-50">({{ $counts['passive'] ?? 0 }})</span>
            </a>
            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'office'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'office' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                OFİS <span class="ml-1 opacity-50">({{ $counts['office'] ?? 0 }})</span>
            </a>
            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'drafts'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'drafts' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                TASLAK <span class="ml-1 opacity-50">({{ $counts['drafts'] ?? 0 }})</span>
            </a>
            <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'deleted'])) }}"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'deleted' ? 'bg-white dark:bg-slate-800 text-slate-400 shadow-lg scale-105' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                SİLİNEN <span class="ml-1 opacity-50">({{ $counts['deleted'] ?? 0 }})</span>
            </a>
        </div>

        {{-- 🔍 CORTEX SMART SEARCH --}}
        <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-200 dark:border-slate-700 shadow-xl p-8 dark:bg-slate-900">
            <form @submit.prevent="applyFilters()" method="GET" action="{{ route('admin.ilanlar.filter') }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <div class="md:col-span-12 lg:col-span-12 flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative group">
                            <div
                                class="absolute inset-y-4 left-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-orange-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" x-model="filters.search"
                                @input.debounce.500ms="applyFilters()"
                                placeholder="İlan başlığı, sahibi veya Site/Bina adı..."
                                class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-orange-500 dark:focus:border-orange-500 focus:ring-0 transition-all duration-300">
                        </div>

                        <button type="button" @click="showFilters = true"
                            class="flex items-center gap-3 px-6 py-4 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-2xl hover:border-blue-500 transition-all group dark:bg-slate-900">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span
                                class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-blue-600">Gelişmiş
                                Filtreler</span>
                        </button>
                    </div>

                    <div class="md:col-span-12 lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Durum -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                        <select name="yayin_durumu" x-model="filters.yayin_durumu" @change="applyFilters()"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tüm Durumlar</option>
                            <option value="yayinda" {{ request('yayin_durumu') == 'yayinda' ? 'selected' : '' }}>Yayında</option>
                            <option value="beklemede" {{ request('yayin_durumu') == 'beklemede' ? 'selected' : '' }}>Beklemede</option>
                            <option value="taslak" {{ request('yayin_durumu') == 'taslak' ? 'selected' : '' }}>Taslak</option>
                            <option value="pasif" {{ request('yayin_durumu') == 'pasif' ? 'selected' : '' }}>Pasif</option>
                            <option value="arsiv" {{ request('yayin_durumu') == 'arsiv' ? 'selected' : '' }}>Arşiv</option>
                        </select>
                    </div>

                        <div>
                            <label
                                class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3 ml-1">Kategori</label>
                            <select name="kategori_id" x-model="filters.kategori_id" @change="applyFilters()"
                                class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white focus:border-orange-500 dark:focus:border-orange-500 focus:ring-0 transition-all duration-300 cursor-pointer">
                                <option value="">Tümü</option>
                                @if (isset($kategoriler))
                                    @foreach ($kategoriler as $kategori)
                                        <option value="{{ $kategori->id }}"
                                            {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                            {{ $kategori->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3 ml-1">Kiralama</label>
                            <select name="kiralama_turu" x-model="filters.kiralama_turu" @change="applyFilters()"
                                class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white focus:border-orange-500 dark:focus:border-orange-500 focus:ring-0 transition-all duration-300 cursor-pointer">
                                <option value="">Tümü</option>
                                <option value="gunluk" {{ request('kiralama_turu') == 'gunluk' ? 'selected' : '' }}>Günlük
                                </option>
                                <option value="haftalik" {{ request('kiralama_turu') == 'haftalik' ? 'selected' : '' }}>
                                    Haftalık</option>
                                <option value="aylik" {{ request('kiralama_turu') == 'aylik' ? 'selected' : '' }}>Aylık
                                </option>
                                <option value="uzun_donem"
                                    {{ request('kiralama_turu') == 'uzun_donem' ? 'selected' : '' }}>Uzun Dönem</option>
                                <option value="sezonluk" {{ request('kiralama_turu') == 'sezonluk' ? 'selected' : '' }}>
                                    Sezonluk</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3 ml-1">Sıralama</label>
                            <select name="sort" x-model="filters.sort" @change="applyFilters()"
                                class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white focus:border-orange-500 dark:focus:border-orange-500 focus:ring-0 transition-all duration-300 cursor-pointer">
                                <option value="created_desc" {{ request('sort') === 'created_desc' ? 'selected' : '' }}>En
                                    Yeni</option>
                                <option value="created_asc" {{ request('sort') === 'created_asc' ? 'selected' : '' }}>En
                                    Eski</option>
                                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Fiyat
                                    (v-^)</option>
                                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Fiyat
                                    (^-v)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-8 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="clearFilters()"
                        class="px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 font-black rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300 uppercase tracking-tighter text-xs">
                        Sıfırla
                    </button>
                    <button type="button" @click="applyFilters()" :disabled="loading"
                        class="px-10 py-4 bg-orange-600 text-white font-black rounded-2xl shadow-xl shadow-orange-500/20 hover:bg-orange-700 hover:scale-105 active:scale-95 transition-all duration-300 uppercase tracking-tighter text-xs disabled:opacity-50">
                        <div class="flex items-center gap-3">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="loading ? 'İşleniyor...' : 'Aramayı Başlat'"></span>
                        </div>
                    </button>
                </div>
            </form>
        </div>

        {{-- 🧠 CORTEX NEURAL COMMAND CENTER --}}
        <div class="relative bg-gradient-to-br from-slate-900 to-indigo-950 dark:from-slate-900 dark:to-indigo-950 rounded-[2rem] border border-white/10 shadow-2xl p-6 mb-10 overflow-hidden group"
            x-data="aiQuickActions()" x-show="$store.bulkActions?.selectedIds?.length > 0 || selectedIds?.length > 0"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100">

            {{-- Neural Background Effect --}}
            <div
                class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay">
            </div>
            <div
                class="absolute top-0 right-0 w-64 h-64 bg-purple-500/20 dark:bg-purple-600/10 rounded-full blur-[100px] animate-pulse">
            </div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/20 dark:bg-blue-600/10 rounded-full blur-[80px]">
            </div>

            <div class="relative flex flex-col lg:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div
                        class="relative w-16 h-16 bg-slate-100/10 dark:bg-slate-800/40 backdrop-blur-xl rounded-2xl flex items-center justify-center border border-white/20 dark:border-white/10 shadow-2xl shadow-slate-200/20 dark:shadow-slate-950/80 group-hover:rotate-12 transition-transform duration-500">
                        <svg class="w-8 h-8 text-purple-400 dark:text-purple-300 animate-pulse" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <div
                            class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-slate-900 dark:border-slate-800 animate-ping">
                        </div>
                    </div>
                    <div>
                        <h4
                            class="text-xl font-black text-white dark:text-slate-100 italic tracking-tighter uppercase mb-1">
                            Cortex™ Nöral İşlemci
                        </h4>
                        <div class="flex items-center gap-3">
                            <span
                                class="px-3 py-1 bg-slate-100/10 dark:bg-slate-800/40 rounded-full text-[10px] font-black text-purple-300 uppercase tracking-widest border border-white/5 dark:border-white/10"
                                x-text="`${($store.bulkActions?.selectedIds?.length || selectedIds?.length || 0)} İLAN SEÇİLDİ`"></span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 font-medium tracking-tight">Toplu yapay
                                zeka operasyonları hazır.</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button @click="analyzeListings('comprehensive')" :disabled="processing"
                        class="px-6 py-3 bg-white dark:bg-slate-100 text-slate-900 text-xs font-black rounded-xl hover:bg-purple-100 hover:scale-105 transition-all duration-300 uppercase tracking-tighter flex items-center gap-2 shadow-[0_0_20px_rgba(255,255,255,0.1)] dark:shadow-none dark:text-slate-100 dark:bg-slate-900">
                        <svg x-show="!processing" class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <svg x-show="processing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="processing ? 'ANALİZ EDİLİYOR...' : 'TAM ANALİZ BAŞLAT'"></span>
                    </button>

                    <button @click="suggestPrices()" :disabled="processing"
                        class="px-6 py-3 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-black rounded-xl hover:bg-emerald-500/30 transition-all duration-300 uppercase tracking-tighter flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        FİYAT REVİZYONU
                    </button>

                    <button @click="optimizeTitles()" :disabled="processing"
                        class="px-6 py-3 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-xs font-black rounded-xl hover:bg-blue-500/30 transition-all duration-300 uppercase tracking-tighter flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        METİN OPTİMİZASYON
                    </button>
                </div>
            </div>
        </div>

        <!-- İlan Listesi -->
        <div
            class="bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">İlan Listesi</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400"
                    x-text="`${totalCount} ilan`">{{ $ilanlar->total() }} ilan</span>
            </div>

            <div class="p-6" x-data="bulkActionsManager()" id="ilanlar-list-container">
                <x-admin.meta-info title="İlanlar" :meta="[
                    'total' => $ilanlar->total(),
                    'current_page' => $ilanlar->currentPage(),
                    'last_page' => $ilanlar->lastPage(),
                    'per_page' => $ilanlar->perPage(),
                ]" :show-per-page="true" :per-page-options="[20, 50, 100]" listId="ilanlar"
                    listEndpoint="/api/admin/api/v1/ilanlar" />
                @if ($ilanlar->count() > 0)
                    {{-- Bulk Actions Toolbar --}}
                    <div x-show="selectedIds.length > 0" x-transition
                        class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 px-6 py-4 flex items-center justify-between mb-4 rounded-lg">

                        <div class="flex items-center text-sm font-medium text-blue-800 dark:text-blue-300">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="`${selectedIds.length} ilan seçildi`"></span>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Activate Button --}}
                            <button type="button" @click="bulkAction('activate')" :disabled="processing"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 hover:scale-105 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-show="!processing">Aktif Yap</span>
                                <span x-show="processing">İşleniyor...</span>
                            </button>

                            {{-- Deactivate Button --}}
                            <button type="button" @click="bulkAction('deactivate')" :disabled="processing"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 hover:scale-105 focus:ring-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pasif Yap
                            </button>

                            {{-- Delete Button --}}
                            <button type="button" @click="confirmBulkDelete()" :disabled="processing"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 hover:scale-105 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Sil
                            </button>

                            {{-- Clear Selection --}}
                            <button type="button" @click="clearSelection()"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">
                                Seçimi Temizle
                            </button>
                        </div>
                    </div>

                    {{-- 📱 MOBILE ADAPTIVE CARDS --}}
                    <div class="md:hidden space-y-4">
                        @foreach ($ilanlar as $ilan)
                            <div
                                class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] shadow-xl border border-slate-100 dark:border-white/5 overflow-hidden relative group">
                                <div class="absolute top-0 right-0 p-4">
                                    {{-- YAYIN DURUMU BADGE --}}
                                    <div x-data="yayinDurumuToggle({{ $ilan->id }}, '{{ $ilan->yayin_durumu ?? 'taslak' }}')" class="relative">
                                        <button @click="open = !open" type="button" :disabled="updating"
                                            class="px-5 py-2 text-[10px] font-black uppercase tracking-[0.15em] rounded-full shadow-xl dark:shadow-none hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer border-2 border-transparent"
                                            :class="getYayinDurumuClasses()">
                                            <span x-text="getYayinDurumuLabel(currentYayinDurumu)"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-4">
                                    <div class="flex items-center gap-3">
                                        @include('admin.ilanlar.partials.referans-badge', [
                                            'ilan' => $ilan,
                                        ])
                                        <h3
                                            class="font-black text-slate-900 dark:text-white truncate tracking-tighter uppercase italic">
                                            {{ $ilan->baslik ?? 'İlan #' . $ilan->id }}
                                        </h3>
                                    </div>

                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <div class="flex items-center gap-2 text-slate-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $ilan->ilce->ilce_adi ?? '' }}
                                        </div>
                                        <div
                                            class="text-orange-600 dark:text-orange-400 text-lg font-black tracking-tighter">
                                            {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-2 pt-4 border-t border-slate-100 dark:border-white/5">
                                        <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                            class="flex-1 px-4 py-3 bg-slate-900 dark:bg-slate-50 text-white dark:text-slate-900 text-center text-[10px] font-black rounded-xl uppercase tracking-widest shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50 active:scale-95 transition-all">
                                            Görüntüle
                                        </a>
                                        <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                                            class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl hover:scale-110 active:scale-90 transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        {{-- Gelişmiş Filtreler Butonu --}}
                                        <button type="button" @click="showFilters = true"
                                            class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                            </svg>
                                            <span class="text-sm font-semibold hidden sm:inline">Gelişmiş Filtre</span>
                                        </button>
                                    </div>
                                </div>
                        @endforeach
                    </div>

                    {{-- 🖥️ DESKTOP QUANTUM TABLE --}}
                    <div class="hidden md:block w-full overflow-hidden rounded-[2.5rem] border border-slate-100 dark:border-white/5 bg-white dark:bg-slate-900 shadow-2xl shadow-slate-200/50 dark:shadow-black/20"
                        id="ilanlar-table-container">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-900">
                                    <th class="px-6 py-6 w-12 border-b border-slate-100 dark:border-white/5">
                                        <input type="checkbox" id="select-all"
                                            class="w-5 h-5 rounded-lg border-2 border-slate-300 dark:border-slate-600 text-orange-500 focus:ring-orange-500 bg-transparent transition-all"
                                            x-model="selectAll" @change="toggleSelectAll()">
                                    </th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        İlan & Varlık Analizi</th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        Klasifikasyon</th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        Değerleme</th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        Responsibilite</th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        Yayın Durumu</th>
                                    <th
                                        class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-white/5">
                                        Operasyon</th>
                                </tr>
                            </thead>
                            <tbody id="ilanlar-tbody" class="divide-y divide-slate-50 dark:divide-white/5">
                                @foreach ($ilanlar as $ilan)
                                    <tr
                                        class="group/row hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all duration-300">
                                        {{-- Checkbox Column --}}
                                        <td class="px-6 py-8">
                                            <input type="checkbox"
                                                class="w-5 h-5 rounded-lg border-2 border-slate-300 dark:border-slate-600 text-orange-500 focus:ring-orange-500 bg-transparent transition-all"
                                                value="{{ $ilan->id }}" x-model="selectedIds"
                                                @change="updateSelectAll()">
                                        </td>
                                        <td class="px-6 py-8">
                                            <div class="flex flex-col gap-4">
                                                {{-- BAŞLIK & TELEMETRİ --}}
                                                <div class="flex items-center gap-3">
                                                    @include('admin.ilanlar.partials.referans-badge', [
                                                        'ilan' => $ilan,
                                                    ])

                                                    <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                                        class="text-sm font-black text-slate-900 dark:text-white uppercase italic tracking-tighter hover:text-orange-600 dark:hover:text-amber-500 transition-colors duration-300">
                                                        {{ $ilan->baslik ?? 'İlan #' . $ilan->id }}
                                                    </a>

                                                    {{-- 🧠 Nöral Telemetri --}}
                                                    <div
                                                        class="flex items-center gap-1.5 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700">
                                                        @if ($ilan->islendi)
                                                            <span
                                                                class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                                                        @endif
                                                       <!-- Status Badge -->
                            <div class="mb-3">
                                @if ($ilan->yayindami)
                                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-eye mr-1"></i> Aktif
                                    </span>
                                @else
                                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 px-2 py-1 rounded-full text-xs font-medium dark:bg-slate-900">
                                        <i class="fas fa-eye-slash mr-1"></i> Pasif
                                    </span>
                                @endif
                 {{-- VARLIK ÖNİZLEME --}}
                                                <div class="flex items-start gap-5">
                                                    <div class="relative flex-shrink-0 group/img">
                                                        <div
                                                            class="absolute -inset-1 bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl blur opacity-25 group-hover/img:opacity-50 transition duration-500">
                                                        </div>
                                                        <div
                                                            class="relative w-48 h-32 rounded-xl overflow-hidden shadow-2xl border-2 border-white dark:border-slate-800">
                                                            @php
                                                                $firstPhoto = $ilan->fotograflar?->first();
                                                                $photoPath = $firstPhoto?->dosya_yolu;
                                                            @endphp
                                                            @if ($photoPath && file_exists(storage_path('app/public/' . $photoPath)))
                                                                <img class="w-full h-full object-cover group-hover/img:scale-110 transition-transform duration-700"
                                                                    src="{{ asset('storage/' . $photoPath) }}"
                                                                    alt="Varlık">
                                                            @else
                                                                <div
                                                                    class="w-full h-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                                                    <svg class="w-10 h-10 text-slate-300" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                </div>
                                                            @endif

                                                            {{-- Context7: Accessor üzerinden durum erişimi --}}
                                @php
                                    $ilanDurumu = $ilan->yayin_durumu ?? 'Taslak';
                                @endphp
                                @if ($ilanDurumu == 'satildi')
                                    <span
                                        class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium ml-2">
                                        <i class="fas fa-handshake mr-1"></i> Satıldı
                                    </span>
                                @elseif ($ilanDurumu == 'kiralandi')
                                    <span
                                        class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium ml-2">
                                        <i class="fas fa-key mr-1"></i> Kiralandı
                                    </span>
                                @endif
                                                            {{-- Price Overlay on Hover --}}
                                                            <div
                                                                class="absolute inset-0 bg-slate-900/60 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity duration-300 backdrop-blur-sm">
                                                                <span
                                                                    class="text-white font-black text-sm tracking-tighter">İNCELE</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex-1 space-y-3">
                                                        <div
                                                            class="flex items-center gap-2 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">
                                                            <svg class="w-4 h-4 text-orange-500" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5"
                                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            </svg>
                                                            {{ $ilan->il->il_adi ?? '-' }},
                                                            {{ $ilan->ilce->ilce_adi ?? '-' }}
                                                        </div>
                                                        @if ($ilan->ilanSahibi)
                                                            <div
                                                                class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 dark:bg-slate-800/50 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 border border-slate-200 dark:border-slate-700">
                                                                <span class="text-orange-500">OWNER:</span>
                                                                {{ $ilan->ilanSahibi->ad }} {{ $ilan->ilanSahibi->soyad }}
                                                            </div>
                                                        @endif

                                                        {{-- Nöral Not Preview --}}
                                                        @if ($ilan->anahtar_notlari)
                                                            <p
                                                                class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 italic font-serif leading-relaxed">
                                                                "{{ Str::limit($ilan->anahtar_notlari, 120) }}"
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-8">
                                            <div class="flex flex-col gap-2">
                                                <span
                                                    class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-blue-100 dark:border-blue-800 w-fit">
                                                    {{ $ilan->yayinTipi?->name ?? 'DİĞER' }}
                                                </span>
                                                <div
                                                    class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter">
                                                    {{ $ilan->anaKategori?->name }}
                                                    @if ($ilan->altKategori)
                                                        <span class="mx-1">/</span> {{ $ilan->altKategori->name }}
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-8">
                                            <div class="flex flex-col gap-2">
                                                <div
                                                    class="text-lg font-black text-slate-900 dark:text-white tracking-tighter italic">
                                                    {{ number_format($ilan->fiyat ?? 0, 0, ',', '.') }}
                                                    <span
                                                        class="text-xs ml-1 text-orange-500">{{ $ilan->para_birimi ?? 'TRY' }}</span>
                                                </div>

                                                @if ($ilan->kiralama_turu)
                                                    <span
                                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                        / {{ strtoupper($ilan->kiralama_turu) }}
                                                    </span>
                                                @endif

                                                {{-- Smart Market Indicator --}}
                                                <div
                                                    class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 rounded-full w-fit">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse">
                                                    </div>
                                                    <span class="text-[9px] font-black uppercase tracking-widest">Optimized
                                                        Price</span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-8">
                                            @if ($ilan->userDanisman)
                                                <div class="flex items-center gap-3">
                                                    <div class="relative">
                                                        <div
                                                            class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center border-2 border-white dark:border-slate-800 shadow-lg">
                                                            <span
                                                                class="text-xs font-black text-white uppercase">{{ substr($ilan->userDanisman->name, 0, 2) }}</span>
                                                        </div>
                                                        <div
                                                            class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white dark:border-slate-800">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div
                                                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                                                            {{ $ilan->userDanisman->name }}</div>
                                                        <div
                                                            class="text-[10px] font-medium text-slate-400 dark:text-slate-500">
                                                            EXPERT AGENT</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600">--</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-8">
                                            <div x-data="yayinDurumuToggle({{ $ilan->id }}, '{{ $ilan->yayin_durumu ?? 'taslak' }}')" @click.outside="open = false"
                                                class="relative inline-block">
                                                <button @click="open = !open" type="button" :disabled="updating"
                                                    class="px-5 py-2 text-[10px] font-black uppercase tracking-[0.15em] rounded-full shadow-xl dark:shadow-none hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer disabled:opacity-50 border-2 border-transparent"
                                                    :class="getYayinDurumuClasses()">
                                                    <div class="flex items-center gap-2">
                                                        <span x-text="getYayinDurumuLabel(currentYayinDurumu)"></span>
                                                        <svg class="w-3 h-3 transition-transform duration-300"
                                                            :class="{ 'rotate-180': open }" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="3" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </div>
                                                </button>

                                                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 translate-y-4"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    class="absolute z-50 mt-3 w-48 rounded-2xl shadow-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 py-2 overflow-hidden dark:bg-slate-900">

                                                    <template x-for="dt in durumSecenekleri" :key="dt.value">
                                                        <button @click="changeYayinDurumu(dt.value)" type="button"
                                                            class="w-full text-left px-5 py-3 text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-3 transition-colors"
                                                            :class="{
                                                                'text-orange-500': currentYayinDurumu === dt.value,
                                                                'text-slate-600 dark:text-slate-400': currentYayinDurumu !== dt.value
                                                            }">
                                                            <span class="w-2 h-2 rounded-full"
                                                                :class="{
                                                                    'bg-emerald-500': dt.value === 'yayinda',
                                                                    'bg-amber-500': dt.value === 'beklemede',
                                                                    'bg-slate-400': dt.value === 'taslak',
                                                                    'bg-red-500': dt.value === 'pasif',
                                                                    'bg-indigo-500': dt.value === 'arsiv'
                                                                }"></span>
                                                            <span x-text="dt.label"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-8">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                                    class="p-3 bg-slate-900 dark:bg-slate-50 text-white dark:text-slate-900 rounded-xl shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50 hover:scale-110 active:scale-95 transition-all duration-300 group/btn"
                                                    title="Hipersayfa">
                                                    <svg class="w-5 h-5 group-hover/btn:scale-125 transition-transform"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>

                                                <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                                                    class="p-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg dark:shadow-none hover:scale-110 active:scale-95 transition-all duration-300 dark:bg-slate-900"
                                                    title="Modifikasyon">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>

                                                <div x-data="{ open: false }" @click.outside="open = false"
                                                    class="relative">
                                                    <button @click="open = !open"
                                                        class="p-3 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="open" x-transition
                                                        class="absolute right-0 z-50 mt-3 w-56 rounded-2xl shadow-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 overflow-hidden py-2 text-xs font-bold uppercase tracking-tighter dark:bg-slate-900">
                                                        <button
                                                            class="w-full text-left px-5 py-3 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-3">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2"
                                                                    stroke-width="2" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                            KOPYALA
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('admin.ilanlar.destroy', $ilan->id) }}"
                                                            class="inline"
                                                            @submit.prevent="if(confirm('EMİN MİSİNİZ? Veri geri getirilemez.')) $el.submit()">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full text-left px-5 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center gap-3">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                                        stroke-width="2" stroke-linecap="round"
                                                                        stroke-linejoin="round" />
                                                                </svg>
                                                                TERMİNASYON
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6" id="ilanlar-pagination">
                        {{ $ilanlar->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">İlan Bulunamadı</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Arama kriterlerinize uygun ilan
                            bulunmamaktadır.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.ilanlar.create') }}"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Yeni İlan Ekle
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Advanced Filter Drawer --}}
    @include('admin.ilanlar.components.advanced-filter-drawer')

    @push('scripts')
        <script>
            // AJAX Filter Manager (Alpine.js Component)
            // Context7: %100, Yalıhan Bekçi: ✅
            function ilanFilter() {
                return {
                    showFilters: false,
                    ilceler: [],
                    filters: {
                        search: '{{ request('search') }}',
                        yayin_durumu: '{{ request('yayin_durumu') }}',
                        kategori_id: '{{ request('kategori_id') }}',
                        kiralama_turu: '{{ request('kiralama_turu') }}',
                        sort: '{{ request('sort', 'created_desc') }}',
                        tab: '{{ request('tab', 'active') }}',
                        il_id: '{{ request('il_id') }}',
                        ilce_id: '{{ request('ilce_id') }}',
                        min_fiyat: '{{ request('min_fiyat') }}',
                        max_fiyat: '{{ request('max_fiyat') }}',
                        min_m2: '{{ request('min_m2') }}',
                        max_m2: '{{ request('max_m2') }}'
                    },
                    loading: false,
                    totalCount: {{ $ilanlar->total() }},

                    init() {
                        // URL'den query parametrelerini oku
                        const urlParams = new URLSearchParams(window.location.search);
                        Object.keys(this.filters).forEach(key => {
                            if (urlParams.has(key)) {
                                this.filters[key] = urlParams.get(key);
                            }
                        });

                        if (this.filters.il_id) {
                            this.fetchIlceler();
                        }
                    },

                    async fetchIlceler() {
                        if (!this.filters.il_id) {
                            this.ilceler = [];
                            return;
                        }
                        try {
                            const response = await fetch(`/api/v1/admin/address/ilceler?il_id=${this.filters.il_id}`);
                            const data = await response.json();
                            this.ilceler = data.data || [];
                        } catch (e) {
                            console.error('Ilce fetch error:', e);
                        }
                    },

                    setArea(min, max) {
                        this.filters.min_m2 = min;
                        this.filters.max_m2 = max;
                    },

                    clearFilters() {
                        Object.keys(this.filters).forEach(key => {
                            this.filters[key] = '';
                        });
                        this.filters.sort = 'created_desc';
                        this.filters.tab = 'active';
                        this.applyFilters();
                    },

                    async applyFilters() {
                        this.loading = true;

                        try {
                            // Query parametrelerini oluştur
                            const params = new URLSearchParams();
                            Object.keys(this.filters).forEach(key => {
                                if (this.filters[key]) {
                                    params.append(key, this.filters[key]);
                                }
                            });

                            // URL'i güncelle (back/forward desteği için)
                            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                            window.history.pushState({}, '', newUrl);

                            // AJAX isteği
                            const response = await fetch('{{ route('admin.ilanlar.filter') }}?' + params.toString(), {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                }
                            });

                            if (!response.ok) {
                                throw new Error('Filtreleme başarısız');
                            }

                            const data = await response.json();

                            if (data.success) {
                                // Desktop Table View - Tablo içeriğini güncelle
                                const tbody = document.getElementById('ilanlar-tbody');
                                if (tbody && data.html) {
                                    // HTML'i parse et ve sadece tbody içeriğini al
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(data.html, 'text/html');
                                    const newTbody = doc.querySelector('tbody');

                                    if (newTbody) {
                                        tbody.innerHTML = newTbody.innerHTML;
                                    }
                                }

                                // Mobile Card View - Card içeriğini güncelle
                                const cardsContainer = document.getElementById('ilanlar-cards-container');
                                if (cardsContainer && data.cards_html) {
                                    // HTML'i parse et ve card container'ı al
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(data.cards_html, 'text/html');
                                    const newCardsContainer = doc.querySelector('#ilanlar-cards-container');

                                    if (newCardsContainer) {
                                        cardsContainer.innerHTML = newCardsContainer.innerHTML;
                                    }
                                }

                                // Pagination'ı güncelle
                                const pagination = document.getElementById('ilanlar-pagination');
                                if (pagination && data.pagination) {
                                    pagination.innerHTML = data.pagination;
                                }

                                // Total count'u güncelle
                                if (data.total !== undefined) {
                                    this.totalCount = data.total;
                                }

                                // Toast notification
                                if (window.toast) {
                                    window.toast.success(`${this.totalCount} ilan bulundu`);
                                }
                            }
                        } catch (error) {
                            console.error('Filter error:', error);
                            if (window.toast) {
                                window.toast.error('Filtreleme sırasında bir hata oluştu');
                            }
                        } finally {
                            this.loading = false;
                        }
                    },

                };
            }

            // AI Quick Actions Manager (Alpine.js Component)
            // Context7: %100, Yalıhan Bekçi: ✅
            function aiQuickActions() {
                return {
                    processing: false,
                    results: null,
                    showResults: false,

                    get selectedIds() {
                        // Bulk actions manager'dan selectedIds'i al
                        const bulkManager = Alpine.$data(document.querySelector('[x-data*="bulkActionsManager"]'));
                        return bulkManager?.selectedIds || [];
                    },

                    async analyzeListings(type = 'comprehensive') {
                        const ids = this.selectedIds;
                        if (ids.length === 0) {
                            if (window.toast) {
                                window.toast.error('Lütfen en az bir ilan seçin');
                            }
                            return;
                        }

                        this.processing = true;
                        this.showResults = false;

                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            const response = await fetch('{{ route('admin.ai.bulk-analyze') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    ilan_ids: ids,
                                    type: type
                                })
                            });

                            if (!response.ok) {
                                throw new Error('Analiz başarısız');
                            }

                            const data = await response.json();
                            this.results = data.results;
                            this.showResults = true;

                            // Sonuçları modal'da göster
                            this.showResultsModal(data);

                            if (window.toast) {
                                window.toast.success(`${data.count} ilan analiz edildi`);
                            }
                        } catch (error) {
                            console.error('AI Analysis error:', error);
                            if (window.toast) {
                                window.toast.error('AI analiz sırasında bir hata oluştu');
                            }
                        } finally {
                            this.processing = false;
                        }
                    },

                    showResultsModal(data) {
                        // Basit alert ile sonuçları göster (ileride modal'a çevrilebilir)
                        let message = `${data.count} ilan analiz edildi:\n\n`;
                        data.results.forEach((result, index) => {
                            message += `${index + 1}. ${result.baslik}\n`;
                            if (result.analysis.recommendations) {
                                message += `   Öneriler: ${result.analysis.recommendations.join(', ')}\n`;
                            }
                            message += '\n';
                        });

                        // Modal açmak yerine console'a yazdır (ileride modal component eklenebilir)
                        console.log('AI Analysis Results:', data);
                    },

                    async suggestPrices() {
                        await this.analyzeListings('price');
                    },

                    async optimizeTitles() {
                        await this.analyzeListings('title');
                    }
                };
            }

            // Bulk Actions Manager (Alpine.js Component)
            // Context7: %100, Yalıhan Bekçi: ✅
            function bulkActionsManager() {
                return {
                    selectedIds: [],
                    selectAll: false,
                    processing: false,

                    toggleSelectAll() {
                        const checkboxes = document.querySelectorAll('.row-checkbox');

                        if (this.selectAll) {
                            this.selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
                        } else {
                            this.selectedIds = [];
                        }

                        checkboxes.forEach(cb => cb.checked = this.selectAll);
                    },

                    updateSelectAll() {
                        const checkboxes = document.querySelectorAll('.row-checkbox');
                        const checkedCount = this.selectedIds.length;

                        this.selectAll = checkedCount === checkboxes.length && checkboxes.length > 0;
                    },

                    clearSelection() {
                        this.selectedIds = [];
                        this.selectAll = false;
                        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
                    },

                    confirmBulkDelete() {
                        if (this.selectedIds.length === 0) {
                            window.toast.error('Lütfen en az bir ilan seçin');
                            return;
                        }

                        if (confirm(
                                `${this.selectedIds.length} ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`)) {
                            this.bulkAction('delete');
                        }
                    },

                    async bulkAction(action) {
                        if (this.selectedIds.length === 0) {
                            window.toast.error('Lütfen en az bir ilan seçin');
                            return;
                        }

                        this.processing = true;

                        try {
                            const response = await fetch('{{ route('admin.ilanlar.bulk.action') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    ids: this.selectedIds,
                                    action: action,
                                }),
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.toast.success(data.message || 'İşlem başarılı');

                                // Reload page after 1 second
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                throw new Error(data.message || 'İşlem başarısız');
                            }

                        } catch (error) {
                            console.error('Bulk action error:', error);
                            window.toast.error(error.message || 'Toplu işlem başarısız oldu');
                        } finally {
                            this.processing = false;
                        }
                    }
                }
            }

            // Inline Yayın Durumu Toggle Component
            // Context7: %100, Yalıhan Bekçi: ✅
            function yayinDurumuToggle(ilanId, initialYayinDurumu) {
                return {
                    open: false,
                    currentYayinDurumu: initialYayinDurumu,
                    updating: false,
                    durumSecenekleri: [
                        { value: 'yayinda', label: 'Yayında' },
                        { value: 'beklemede', label: 'Beklemede' },
                        { value: 'taslak', label: 'Taslak' },
                        { value: 'pasif', label: 'Pasif' },
                        { value: 'arsiv', label: 'Arşiv' },
                    ],

                    async changeYayinDurumu(newDurum) {
                        if (newDurum === this.currentYayinDurumu) {
                            this.open = false;
                            return;
                        }

                        this.updating = true;

                        try {
                            const response = await fetch(`/admin/ilanlar/${ilanId}/durum`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    yayin_durumu: newDurum
                                }),
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.currentYayinDurumu = newDurum;
                                window.toast.success(`Yayın durumu "${newDurum}" olarak güncellendi`);
                            } else {
                                throw new Error(data.message || 'Güncelleme başarısız');
                            }

                        } catch (error) {
                            console.error('Yayın durumu update error:', error);
                            window.toast.error(error.message || 'Yayın durumu güncellenemedi');
                        } finally {
                            this.updating = false;
                            this.open = false;
                        }
                    },

                    getYayinDurumuClasses() {
                        const classes = {
                            yayinda: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800/50 focus:ring-blue-500 dark:focus:ring-blue-400',
                            beklemede: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-800/50 focus:ring-yellow-500',
                            taslak: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 focus:ring-blue-500',
                            pasif: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800/50 focus:ring-blue-500 dark:focus:ring-blue-400',
                            arsiv: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-800/50 focus:ring-indigo-500',
                        };
                        return classes[this.currentYayinDurumu] || classes.taslak;
                    },

                    getYayinDurumuLabel(value) {
                        const option = this.durumSecenekleri.find((item) => item.value === value);
                        return option ? option.label : 'Taslak';
                    }
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const paginate = document.querySelector('.mt-6')
                const tbody = document.querySelector('table tbody')
                if (!window.ApiAdapter || !paginate || !tbody) return
                const durumEl = document.getElementById('meta-durum')
                const totalEl = document.getElementById('meta-total')
                const pageEl = document.getElementById('meta-page')
                const section = document.querySelector('[data-meta="true"]')
                const perSelect = section?.querySelector('select[data-per-page-select]')
                let currentPer = 20
                const urlInit = new URL(window.location.href)
                const qPer = parseInt(urlInit.searchParams.get('per_page') || '')
                const storageKey = 'yalihan_admin_per_page'
                const sPer = parseInt(localStorage.getItem(storageKey) || '')
                if (qPer) {
                    currentPer = qPer;
                    if (perSelect) perSelect.value = String(qPer)
                } else if (sPer) {
                    currentPer = sPer;
                    if (perSelect) perSelect.value = String(sPer)
                }
                if (perSelect) {
                    perSelect.addEventListener('change', function() {
                        currentPer = parseInt(perSelect.value || '20');
                        const u = new URL(window.location.href);
                        u.searchParams.set('per_page', String(currentPer));
                        window.history.replaceState({}, '', u.toString());
                        loadPage(1)
                    })
                }

                function setLoading(f) {
                    if (!durumEl) return
                    durumEl.setAttribute('aria-busy', f ? 'true' : 'false');
                    durumEl.textContent = f ? 'Yükleniyor…' : ''
                }

                function renderRows(items) {
                    if (!items || items.length === 0) {
                        tbody.innerHTML =
                            '<tr><td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Kayıt bulunamadı</td></tr>';
                        return
                    }
                    const rows = items.map(function(it) {
                        const title = it.title || ('İlan #' + (it.id || ''))
                        const price = (it.fiyat != null ? it.fiyat : '') + ' ' + (it.para_birimi || '')
                        return (
                            '<tr>' +
                            '<td class="px-6 py-4"><input type="checkbox"></td>' +
                            '<td class="px-6 py-4"><div class="text-sm font-medium">' + title +
                            '</div><div class="text-sm text-gray-500">#' + (it.id || '') + '</div></td>' +
                            '<td class="px-6 py-4">' + '' + '</td>' +
                            '<td class="px-6 py-4">' + price + '</td>' +
                            '<td class="px-6 py-4">' + '' + '</td>' +
                            '<td class="px-6 py-4">' + '' + '</td>' +
                            '<td class="px-6 py-4">' + '' + '</td>' +
                            '<td class="px-6 py-4"><a href="/admin/ilanlar/' + (it.id || '') +
                            '" class="text-blue-600">Detay</a></td>' +
                            '</tr>'
                        )
                    }).join('')
                    tbody.innerHTML = rows
                }

                function updateMeta(meta) {
                    if (!meta) return
                    totalEl.textContent = 'Toplam: ' + (meta.total != null ? meta.total : '-')
                    pageEl.innerHTML = '📄 Sayfa: ' + (meta.current_page || 1) + ' / ' + (meta.last_page || 1)
                    if (meta.per_page) {
                        currentPer = parseInt(meta.per_page);
                        perSelect.value = String(meta.per_page);
                        localStorage.setItem(storageKey, String(meta.per_page))
                    }
                    const links = paginate.querySelectorAll('a[href*="page="]')
                    links.forEach(function(a) {
                        const u = new URL(a.href, window.location.origin);
                        const p = parseInt(u.searchParams.get('page') || '1');
                        a.setAttribute('aria-label', 'Sayfa ' + p);
                        if (p === meta.current_page) {
                            a.setAttribute('aria-disabled', 'true')
                        } else {
                            a.removeAttribute('aria-disabled')
                        }
                    })
                }

                function loadPage(page) {
                    setLoading(true)
                    window.ApiAdapter.get('/ilanlar', {
                            page: Number(page || 1),
                            per_page: currentPer
                        })
                        .then(function(res) {
                            renderRows(res.data || []);
                            updateMeta(res.meta || null);
                            setLoading(false)
                        })
                        .catch(function(err) {
                            setLoading(false);
                            const a = document.createElement('div');
                            a.setAttribute('role', 'alert');
                            a.className = 'px-6 py-2 text-sm text-red-600';
                            a.textContent = 'Hata: ' + ((err.response && err.response.message) || err.message ||
                                'Bilinmeyen hata');
                            paginate.parentNode.insertBefore(a, paginate);
                            setTimeout(function() {
                                a.remove()
                            }, 4000)
                        })
                }
                // Auto-init çalışıyor; ek init gerekmez
            })

            // Match Modal Handler
            function openMatchModal(ilanId) {
                const modal = document.getElementById('match-modal');
                if (!modal) return;

                window.ApiAdapter.get(`/api/v1/match/cortex-learnings?ilan_id=${ilanId}`)
                    .then(function(res) {
                        const matches = res.data?.matches || [];
                        let html = `<div class="max-h-96 overflow-y-auto">`;

                        if (matches.length === 0) {
                            html += `<p class="text-gray-500 py-4">Bu ilan için henüz eşleşme yok.</p>`;
                        } else {
                            matches.forEach(match => {
                                html += `
                                    <div class="border-b border-gray-200 dark:border-slate-800 py-3 last:border-b-0 dark:border-slate-700">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">${match.talep_sahibi_adi || 'Müşteri'}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">${match.talep_detay || 'Detay bilgisi'}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-emerald-600">${Math.round(match.match_score)}%</p>
                                                <p class="text-xs text-gray-500">Uyum Skoru</p>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        html += `</div>`;

                        const content = modal.querySelector('.modal-content');
                        if (content) {
                            content.innerHTML = html;
                        }
                        modal.style.display = 'flex';
                    })
                    .catch(function(err) {
                        console.error('Match modal error:', err);
                    });
            }

            // Modal close handler
            const closeModal = () => {
                const modal = document.getElementById('match-modal');
                if (modal) modal.style.display = 'none';
            };

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeModal();
            });
        </script>

        {{-- Match Modal --}}
        <div id="match-modal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 items-center justify-center z-50 p-4"
            style="display: none;">
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg max-w-2xl w-full">
                <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 flex justify-between items-center dark:border-slate-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">🏆 Müşteri Uyum Detayları</h3>
                    <button onclick="document.getElementById('match-modal').style.display='none'"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 modal-content">
                    <p class="text-gray-500">Yükleniyor...</p>
                </div>
            </div>
        </div>
    @endpush

@endsection
