@extends('admin.layouts.admin')

@section('title', 'İlan Özellikleri Yönetimi')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-8" x-data="{
        activeTab: '{{ $activeTab ?? 'ozellikler' }}',
        selectedIds: [],
        selectedPackId: null,
        setTab(tab) {
            this.activeTab = tab;
            window.location.hash = tab;
        }
    }" x-init="activeTab = window.location.hash ? window.location.hash.substring(1) : activeTab">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            🏷️ İlan Özellikleri Yönetimi
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            İlan formlarında kullanılacak özellikleri ve kategorilerini tek sayfada yönetin
                        </p>
                    </div>
                    <div class="flex gap-2">
                        @if (\Illuminate\Support\Facades\Route::has('admin.property_types.index'))
                            <a href="{{ route('admin.property_types.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 transform hover:scale-105 active:scale-95 text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                                Özellik Yönetimi
                            </a>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('admin.features-management.config-options'))
                            <a href="{{ route('admin.features-management.config-options') }}"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Config Seçenekleri
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Toplam Özellik</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['toplam'] }}</div>
                </div>
                {{-- ✅ SAB: "Aktif" kelimesi yasak, "Yayında" kullanılmalı --}}
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Yayında</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['aktif'] }}</div>
                </div>
                {{-- ✅ SAB: "Pasif" kelimesi yasak, "Taslak" kullanılmalı --}}
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Taslak</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['pasif'] }}</div>
                </div>
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Kategorisiz</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['kategorisiz'] }}</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Kategori</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['kategori_sayisi'] }}</div>
                </div>
            </div>

            {{-- Tab Navigation (PHASE 2.2: Tab-based UI!) --}}
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">

                {{-- Tab Headers --}}
                <div class="border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
                    <nav class="flex -mb-px">
                        <button @click="setTab('ozellikler')"
                            :class="activeTab === 'ozellikler' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="group inline-flex items-center gap-2 px-6 py-4 border-b-2 font-semibold text-sm transition-colors">
                            <span class="text-lg">📋</span>
                            Tüm Özellikler
                            <span
                                class="ml-2 px-2 py-0.5 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                {{ $istatistikler['toplam'] }}
                            </span>
                        </button>

                        <button @click="setTab('kategoriler')"
                            :class="activeTab === 'kategoriler' ? 'border-purple-500 text-purple-600 dark:text-purple-400' :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="group inline-flex items-center gap-2 px-6 py-4 border-b-2 font-semibold text-sm transition-colors">
                            <span class="text-lg">🏷️</span>
                            Kategoriler
                            <span
                                class="ml-2 px-2 py-0.5 rounded-full text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                {{ $istatistikler['kategori_sayisi'] }}
                            </span>
                        </button>

                        <button @click="setTab('kategorisiz')"
                            :class="activeTab === 'kategorisiz' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="group inline-flex items-center gap-2 px-6 py-4 border-b-2 font-semibold text-sm transition-colors">
                            <span class="text-lg">⚠️</span>
                            Kategorisiz
                            <span
                                class="ml-2 px-2 py-0.5 rounded-full text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                {{ $istatistikler['kategorisiz'] }}
                            </span>
                        </button>
                    </nav>
                </div>

                {{-- Tab Content --}}
                <div class="p-6">

                    {{-- TAB 1: Tüm Özellikler --}}
                    <div x-show="activeTab === 'ozellikler'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">

                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                📋 Tüm Özellikler
                            </h2>
                            <div class="flex items-center gap-3">
                                <form action="{{ route('admin.ozellikler.index') }}" method="GET"
                                    class="hidden md:block">
                                    <input type="hidden" name="tab" value="ozellikler">
                                    <div class="relative">
                                        <input type="search" name="q" value="{{ request('q') }}"
                                            placeholder="Özellik ara..."
                                            class="w-64 px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-9 dark:text-slate-100" />
                                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                    </div>
                                </form>
                                <a href="{{ route('admin.ozellikler.create') }}"
                                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:scale-105 transition-all shadow-lg">
                                    + Yeni Özellik
                                </a>
                            </div>
                        </div>

                        @if ($ozellikler->isEmpty())
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">📭</div>
                                <p>Henüz özellik bulunmuyor</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-slate-900">
                                        <tr>
                                            <th class="px-6 py-3 text-left">
                                                <input type="checkbox" @click="selectedIds = $event.target.checked ? {{ $ozellikler->pluck('id') }} : []" :checked="selectedIds.length === {{ $ozellikler->count() }} && {{ $ozellikler->count() }} > 0" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-bold text-gray-900 dark:text-white uppercase dark:text-slate-100">
                                                Özellik</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-bold text-gray-900 dark:text-white uppercase dark:text-slate-100">
                                                Kategori</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-bold text-gray-900 dark:text-white uppercase dark:text-slate-100">
                                                Tip</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-bold text-gray-900 dark:text-white uppercase dark:text-slate-100">
                                                Durum</th>
                                            <th
                                                class="px-6 py-3 text-right text-xs font-bold text-gray-900 dark:text-white uppercase dark:text-slate-100">
                                                İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($ozellikler as $ozellik)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-6 py-4">
                                                    <input type="checkbox" :value="{{ $ozellik->id }}" x-model="selectedIds" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                </td>
                                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                                    {{ $ozellik->name }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-200">
                                                    {{ $ozellik->category->name ?? 'Kategorisiz' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    @php
                                                        $colors = [
                                                            'text' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                            'number' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                            'boolean' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                                            'toggle' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                                            'select' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                            'checkbox' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                            'radio' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                            'textarea' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                        ];
                                                        $type = $ozellik->type ?? 'text';
                                                        $color = $colors[$type] ?? $colors['text'];
                                                    @endphp
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md {{ $color }}">
                                                        {{ strtoupper($type) }}
                                                    </span>
                                                </td>
                                                {{-- ✅ SAB: "Aktif" kelimesi yasak, "Yayında" kullanılmalı --}}
                                                <td class="px-6 py-4">
                                                    <span
                                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $ozellik->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                        {{ $ozellik->aktiflik_durumu ? 'Yayında' : 'Taslak' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm">
                                                    <a href="{{ route('admin.ozellikler.edit', $ozellik) }}"
                                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                        Düzenle
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $ozellikler->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- TAB 2: Kategoriler --}}
                    <div x-show="activeTab === 'kategoriler'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">

                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                🏷️ Özellik Kategorileri
                            </h2>
                            <a href="{{ route('admin.ozellikler.kategoriler.create') }}"
                                class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:scale-105 transition-all shadow-lg">
                                + Yeni Kategori
                            </a>
                        </div>

                        @if ($kategoriListesi->isEmpty())
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">📭</div>
                                <p>Henüz kategori bulunmuyor</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($kategoriListesi as $kategori)
                                    <div
                                        class="group bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden dark:shadow-none dark:border-slate-700">
                                        {{-- Icon Header --}}
                                        <div
                                            class="relative h-32 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 flex items-center justify-center">
                                            @php
                                                $iconClass = $kategori->icon ?? 'fas fa-list';
                                                $isFontAwesome =
                                                    str_starts_with($iconClass, 'fas ') ||
                                                    str_starts_with($iconClass, 'far ') ||
                                                    str_starts_with($iconClass, 'fab ') ||
                                                    str_starts_with($iconClass, 'fal ');
                                            @endphp
                                            @if ($isFontAwesome)
                                                <i
                                                    class="{{ $iconClass }} text-5xl text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300"></i>
                                            @else
                                                <span
                                                    class="text-5xl group-hover:scale-110 transition-transform duration-300">{{ $kategori->icon ?? '📦' }}</span>
                                            @endif
                                            {{-- Gradient Overlay --}}
                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-white/50 dark:from-gray-800/50 to-transparent">
                                            </div>
                                        </div>

                                        {{-- Content --}}
                                        <div class="p-6">
                                            <div class="mb-3">
                                                <h3
                                                    class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                                                    {{ $kategori->name }}
                                                </h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                    {{ $kategori->description ?? 'Açıklama yok' }}
                                                </p>
                                            </div>

                                            {{-- Stats & Action --}}
                                            <div
                                                class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-slate-800">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                    {{ $kategori->features_count ?? 0 }} özellik
                                                </span>
                                                <a href="{{ route('admin.ozellikler.kategoriler.show', $kategori) }}"
                                                    class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 group-hover:gap-2 gap-1 transition-all duration-200">
                                                    Detay
                                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">
                                {{ $kategoriListesi->appends(['tab' => 'kategoriler'])->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- TAB 3: Kategorisiz Özellikler --}}
                    <div x-show="activeTab === 'kategorisiz'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">

                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                    ⚠️ Kategorisiz Özellikler
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Bu özellikler henüz bir kategoriye atanmamış
                                </p>
                            </div>
                        </div>

                        @if ($kategorisizOzellikler->isEmpty())
                            <div class="text-center py-12">
                                <div class="text-6xl mb-4">✅</div>
                                <h3 class="text-xl font-bold text-green-600 dark:text-green-400 mb-2">
                                    Harika! Tüm özellikler kategorize edilmiş
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Kategorisiz özellik bulunmuyor
                                </p>
                            </div>
                        @else
                            <div
                                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                                <div class="flex items-center gap-2">
                                    <span class="text-yellow-600 dark:text-yellow-400">⚠️</span>
                                    <span class="text-sm text-yellow-700 dark:text-yellow-300 font-semibold">
                                        {{ $kategorisizOzellikler->total() }} özellik kategoriye atanmayı bekliyor
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach ($kategorisizOzellikler as $ozellik)
                                    <div
                                        class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors dark:border-slate-700">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                                    {{ $ozellik->name }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Tip: {{ $ozellik->type ?? ($ozellik->field_type ?? 'text') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.ozellikler.edit', $ozellik) }}"
                                                    class="px-3 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                                    Kategoriye Ata
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4">
                                {{ $kategorisizOzellikler->appends(['tab' => 'kategorisiz'])->links() }}
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>

        {{-- Floating Bulk Actions Bar --}}
        <div x-show="selectedIds.length > 0" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
            class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 shadow-[0_-8px_30px_rgb(0,0,0,0.12)] rounded-2xl px-8 py-4 flex items-center gap-8 min-w-[500px] dark:border-slate-700">
            <div class="flex items-center gap-3 border-r border-gray-200 dark:border-slate-800 pr-8 dark:border-slate-700">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold" x-text="selectedIds.length"></span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Özellik Seçildi</span>
            </div>

            <div class="flex items-center gap-4">
                <button @click="$dispatch('open-modal', 'assign-pack-modal')"
                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-bold rounded-lg hover:shadow-lg hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Pakete Ata
                </button>

                <button @click="bulkAction('delete')"
                    class="px-4 py-2 border border-red-200 dark:border-red-900/30 text-red-600 dark:text-red-400 text-sm font-bold rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sil
                </button>

                <button @click="selectedIds = []" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium">Seçimi Kaldır</button>
            </div>
        </div>

        {{-- Modal: Assign to Pack --}}
        <div x-data="{ open: false }" x-show="open" @open-modal.window="if($event.detail === 'assign-pack-modal') open = true"
            class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div @click="open = false" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-lg w-full p-8 border border-gray-100 dark:border-slate-800">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">📦 Özellik Paketine Ata</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Seçtiğiniz <span class="font-bold text-blue-600" x-text="selectedIds.length"></span> özelliği hangi pakete eklemek istersiniz?</p>
                    </div>

                    <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar mb-8">
                        @foreach($featurePacks as $pack)
                            <label class="relative flex items-center p-4 cursor-pointer rounded-2xl border-2 transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                :class="selectedPackId == {{ $pack->id }} ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700'">
                                <input type="radio" name="pack_id" value="{{ $pack->id }}" class="hidden" x-model="selectedPackId">
                                <span class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-xl mr-4">📦</span>
                                <div class="flex-1">
                                    <div class="font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $pack->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $pack->items_count ?? 0 }} özellik içeriyor</div>
                                </div>
                                <div x-show="selectedPackId == {{ $pack->id }}" class="text-blue-600">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex gap-4">
                        <button @click="open = false" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 font-bold rounded-xl transition-all hover:bg-gray-200 dark:hover:bg-gray-600 dark:bg-slate-900 dark:text-slate-300">İptal</button>
                        <button @click="assignToPack()" class="flex-2 px-8 py-3 bg-blue-600 text-white font-bold rounded-xl transition-all hover:bg-blue-700 hover:shadow-lg disabled:opacity-50" :disabled="!selectedPackId">Onayla ve Ata</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function bulkAction(action) {
            if (!confirm('Seçili özellikleri silmek istediğinizden emin misiniz?')) return;

            fetch('{{ route("admin.ozellikler.bulk-action") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    action: action,
                    ids: window.Alpine.find(document.querySelector('[x-data]')).selectedIds
                })
            }).then(rep => rep.json()).then(res => {
                location.reload();
            });
        }

        function assignToPack() {
            const alpine = window.Alpine.find(document.querySelector('[x-data]'));
            if (!alpine.selectedPackId) return;

            fetch('{{ route("admin.ozellikler.bulk-assign-to-pack") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    pack_id: alpine.selectedPackId,
                    ids: alpine.selectedIds
                })
            }).then(rep => rep.json()).then(res => {
                alert(res.message);
                location.reload();
            });
        }
    </script>
@endsection
