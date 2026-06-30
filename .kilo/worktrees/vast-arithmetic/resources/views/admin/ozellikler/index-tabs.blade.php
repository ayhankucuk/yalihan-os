@extends('admin.layouts.admin')

@section('title', 'İlan Özellikleri Yönetimi')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-8" x-data="{
        activeTab: '{{ $activeTab ?? 'ozellikler' }}',
        setTab(tab) {
            this.activeTab = tab;
            window.location.hash = tab;
        }" x-init="activeTab = window.location.hash ? window.location.hash.substring(1) : activeTab">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    🏷️ İlan Özellikleri Yönetimi
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    İlan formlarında kullanılacak özellikleri ve kategorilerini tek sayfada yönetin
                </p>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Toplam Özellik</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['toplam'] }}</div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Aktif</div>
                    <div class="text-3xl font-bold">{{ $istatistikler['aktif'] }}</div>
                </div>
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Pasif</div>
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
                            <a href="{{ route('admin.ozellikler.create') }}"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:scale-105 transition-all shadow-lg">
                                + Yeni Özellik
                            </a>
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
                                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                                    {{ $ozellik->name }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-200">
                                                    {{ $ozellik->category->name ?? 'Kategorisiz' }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-200">
                                                    {{ $ozellik->type ?? 'text' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span
                                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $ozellik->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                        {{ $ozellik->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
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
                                {{ $ozellikler->appends(['tab' => 'ozellikler'])->links() }}
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
                                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6 hover:shadow-lg transition-all hover:-translate-y-1 dark:border-slate-700">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $kategori->name }}
                                            </h3>
                                            <span class="text-2xl">{{ $kategori->icon ?? '📦' }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            {{ $kategori->description ?? 'Açıklama yok' }}
                                        </p>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">
                                                {{ $kategori->features_count }} özellik
                                            </span>
                                            <a href="{{ route('admin.ozellikler.kategoriler.show', $kategori) }}"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-semibold">
                                                Detay →
                                            </a>
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
                                                    Tip: {{ $ozellik->field_type }}
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
    </div>
@endsection
