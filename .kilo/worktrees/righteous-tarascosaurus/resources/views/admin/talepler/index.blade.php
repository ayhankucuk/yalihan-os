@extends('admin.layouts.admin')

@section('title', 'Talep Radar Ekranı')

@section('content')
    {{-- 🎯 TALEP RADAR EKRANI - Demand Engine --}}
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-purple-950 dark:to-pink-950 p-6 transition-all duration-300">

        {{-- 📡 RADAR HEADER --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-4xl font-black bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 dark:from-purple-400 dark:via-pink-400 dark:to-rose-400 bg-clip-text text-transparent mb-2 transition-all">
                        🎯 Talep Radar Ekranı
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        Müşteri taleplerini gerçek zamanlı izleyin ve eşleştirin
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <a href="{{ route('admin.talepler.create') }}"
                        class="group relative px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-500 dark:to-pink-500 text-white dark:text-slate-50 rounded-xl hover:shadow-2xl dark:hover:shadow-purple-500/20 hover:scale-105 dark:hover:scale-105 active:scale-95 dark:active:scale-95 transition-all duration-300 font-bold overflow-hidden">
                        <span
                            class="absolute inset-0 bg-white/20 dark:bg-gray-50/5 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                        <span class="relative flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                            Yeni Talep Ekle
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 📊 TELEMETRI KARTLARI (Toplam, Aktif, Beklemede, Eşleşti) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Toplam Talep --}}
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-purple-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 dark:bg-purple-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2">
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $istatistikler['toplam'] ?? 0 }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Toplam Talep</p>
                </div>
            </div>

            {{-- Aktif Talepler (Yeşil Işık) --}}
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-green-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2">
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div
                                class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 dark:bg-green-400 rounded-full animate-pulse shadow-lg dark:shadow-green-500/50">
                            </div>
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $istatistikler['aktif'] ?? 0 }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Aktif Talepler</p>
                </div>
            </div>

            {{-- Bekleyen Talepler (Sarı Işık) --}}
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-yellow-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/10 dark:bg-yellow-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2">
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div
                                class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-500 dark:bg-yellow-400 rounded-full animate-pulse shadow-lg dark:shadow-yellow-500/50">
                            </div>
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                        {{ $istatistikler['beklemede'] ?? 0 }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Bekleyen</p>
                </div>
            </div>

            {{-- Eşleşen Talepler (Mavi Işık) --}}
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-2xl dark:hover:shadow-blue-900/20 transition-all duration-300 hover:scale-105 dark:hover:scale-105 overflow-hidden dark:border-slate-700">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-3xl dark:blur-3xl -translate-y-1/2 translate-x-1/2">
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl relative">
                            {{-- Telemetri Işığı --}}
                            <div
                                class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 dark:bg-blue-400 rounded-full animate-pulse shadow-lg dark:shadow-blue-500/50">
                            </div>
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1 dark:text-slate-100">{{ $istatistikler['eslesen'] ?? 0 }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Eşleşti</p>
                </div>
            </div>
        </div>

        {{-- 🔍 ARAMA VE FİLTRELEME --}}
        <form action="{{ route('admin.talepler.index') }}" method="GET"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 mb-8 shadow-lg dark:shadow-none dark:border-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Arama --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Ara</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Başlık, kişi adı..."
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 transition-all duration-200 dark:text-slate-100">
                </div>

                {{-- Durum Filtresi --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Durum</label>
                    <select name="talep_durumu" onchange="this.form.submit()"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="Aktif" {{ request('talep_durumu') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="Beklemede" {{ request('talep_durumu') == 'Beklemede' ? 'selected' : '' }}>Beklemede
                        </option>
                        <option value="Tamamlandı" {{ request('talep_durumu') == 'Tamamlandı' ? 'selected' : '' }}>Tamamlandı
                        </option>
                    </select>
                </div>

                {{-- Talep Tipi --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Talep Tipi</label>
                    <select name="tip" onchange="this.form.submit()"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="Satılık" {{ request('tip') == 'Satılık' ? 'selected' : '' }}>Satılık</option>
                        <option value="Kiralık" {{ request('tip') == 'Kiralık' ? 'selected' : '' }}>Kiralık</option>
                        <option value="Günlük Kiralık" {{ request('tip') == 'Günlük Kiralık' ? 'selected' : '' }}>Günlük
                        </option>
                    </select>
                </div>
            </div>
        </form>

        {{-- 📋 TALEP LİSTESİ (Modern Tablo) --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-lg dark:shadow-none overflow-hidden dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                Talep</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                Kişi</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                Durum</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                Bütçe</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                Danışman</th>
                            <th
                                class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($talepler as $talep)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $talep->baslik ?? 'Başlıksız Talep' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $talep->id }} •
                                        {{ $talep->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $talep->kisi->tam_ad ?? '-' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $talep->kisi->telefon ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $durumDegeri = $talep->talep_durumu?->value ?? 'Beklemede';
                                    @endphp
                                    @if ($durumDegeri === 'Aktif')
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            <span
                                                class="w-2 h-2 bg-green-500 dark:bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @elseif($durumDegeri === 'Beklemede')
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                                            <span
                                                class="w-2 h-2 bg-yellow-500 dark:bg-yellow-400 rounded-full mr-2 animate-pulse"></span>
                                            Beklemede
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400 dark:bg-slate-900 dark:text-slate-200">
                                            {{ $durumDegeri }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    @if ($talep->min_fiyat && $talep->max_fiyat)
                                        {{ number_format($talep->min_fiyat) }} - {{ number_format($talep->max_fiyat) }} ₺
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $talep->danisman->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.talepler.show', $talep) }}"
                                        class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 mr-3 transition-colors">
                                        Görüntüle
                                    </a>
                                    <a href="{{ route('admin.talepler.edit', $talep) }}"
                                        class="text-pink-600 dark:text-pink-400 hover:text-pink-900 dark:hover:text-pink-300 transition-colors">
                                        Düzenle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                            </path>
                                        </svg>
                                        <p class="mt-4 text-lg font-medium">Henüz talep kaydı bulunmuyor</p>
                                        <p class="mt-2">Yeni talep ekleyerek başlayın</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($talepler->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    {{ $talepler->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
