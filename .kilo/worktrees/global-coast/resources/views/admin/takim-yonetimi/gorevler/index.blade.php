@php
    use Illuminate\Support\Facades\Storage;
@endphp

@extends('admin.layouts.admin')

@section('title', 'Görev Yönetimi')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Modern Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center text-4xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        <div
                            class="mr-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-r from-purple-500 to-pink-600 shadow-lg">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                </path>
                            </svg>
                        </div>
                        📋 Görev Yönetimi
                    </h1>
                    <p class="mt-3 text-xl text-gray-600 dark:text-gray-400">
                        Takım üyelerine görev atayın, takip edin ve performanslarını analiz edin
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <x-context7.button variant="secondary" href="{{ route('admin.takim.raporlar') }}">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Raporlar
                    </x-context7.button>
                    <x-context7.button variant="primary" href="{{ route('admin.takim.gorevler.create') }}">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Yeni Görev
                    </x-context7.button>
                </div>
            </div>
        </div>

        <!-- 📊 Görev İstatistikleri -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-blue-800">Bekleyen Görevler</h4>
                        <p class="text-2xl font-bold text-blue-900">{{ $istatistikler['bekleyen'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-yellow-200 bg-gradient-to-r from-yellow-50 to-amber-50 p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-r from-yellow-500 to-amber-600">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-yellow-800">Devam Eden</h4>
                        <p class="text-2xl font-bold text-yellow-900">{{ $istatistikler['devam_eden'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-green-800">Tamamlanan</h4>
                        <p class="text-2xl font-bold text-green-900">{{ $istatistikler['tamamlanan'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-violet-50 p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-violet-600">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-purple-800">Toplam Görev</h4>
                        <p class="text-2xl font-bold text-purple-900">{{ $gorevler->total() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔍 Filtreler -->
        <div
            class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-6 shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <h2 class="mb-4 flex items-center text-lg font-semibold text-gray-800 dark:text-slate-200">
                <svg class="mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                🔍 Görev Filtreleri
            </h2>

            <form method="GET" action="{{ route('admin.takim.gorevler.index') }}"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div>
                    <input type="text"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:focus:ring-blue-400"
                        name="search" placeholder="Görev ara..." value="{{ request('search') }}">
                </div>
                <div>
                    <select style="color-scheme: light dark;"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white"
                        name="aktiflik_durumu">
                        <option value="">Tüm Durumlar</option>
                        @foreach (['bekliyor', 'devam_ediyor', 'tamamlandi', 'iptal', 'beklemede'] as $statusOption)
                            <option value="{{ $statusOption }}"
                                {{ request('aktiflik_durumu') == $statusOption ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select style="color-scheme: light dark;"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white"
                        name="oncelik">
                        <option value="">Tüm Öncelikler</option>
                        @foreach (['acil', 'yuksek', 'normal', 'dusuk'] as $oncelik)
                            <option value="{{ $oncelik }}" {{ request('oncelik') == $oncelik ? 'selected' : '' }}>
                                {{ ucfirst($oncelik) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select style="color-scheme: light dark;"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white"
                        name="tip">
                        <option value="">Tüm Tipler</option>
                        @foreach (['musteri_takibi', 'ilan_hazirlama', 'musteri_ziyareti', 'dokuman_hazirlama', 'diger'] as $tip)
                            <option value="{{ $tip }}" {{ request('tip') == $tip ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $tip)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select style="color-scheme: light dark;"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white"
                        name="danisman_id">
                        <option value="">Tüm Danışmanlar</option>
                        @foreach ($danismanlar ?? [] as $danisman)
                            <option value="{{ $danisman->id }}"
                                {{ request('danisman_id') == $danisman->id ? 'selected' : '' }}>
                                {{ $danisman->name ?? ($danisman->ad ?? 'Danışman') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="touch-target-optimized inline-flex w-full items-center rounded-lg bg-orange-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-orange-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95 dark:shadow-none dark:focus:ring-blue-400">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Ara
                    </button>
                </div>
            </form>
        </div>

        <!-- Görev Listesi -->
        <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 dark:shadow-none">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200">Görevler ({{ $gorevler->total() }})
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button type="button"
                            class="touch-target-optimized inline-flex items-center rounded-lg bg-gray-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-gray-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95 dark:shadow-none"
                            onclick="topluGorevAta()">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Toplu Ata
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto" x-data="{ contentLoaded: true }" x-init="setTimeout(() => contentLoaded = true, 100)">
                <!-- Skeleton Loading State -->
                <div x-show="!contentLoaded" x-transition>
                    <div class="animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700">
                        <div class="space-y-3 p-4">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-4 w-3/4 rounded bg-gray-300 dark:bg-gray-600"></div>
                                        <div class="h-3 w-1/2 rounded bg-gray-300 dark:bg-gray-600"></div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- Actual Content -->
                <div x-show="contentLoaded" x-transition class="opacity-100 transition-opacity duration-300">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <input type="checkbox" id="selectAllCheckbox"
                                    class="w-4 h-4 text-orange-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
                                    onchange="toggleSelectAll()">
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Görev
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Danışman
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Durum
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Öncelik
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Tarih
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-gray-50 dark:divide-gray-700 dark:bg-slate-900">
                            @forelse ($gorevler as $gorev)
                                <tr class="transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <input type="checkbox"
                                            class="gorev-checkbox h-4 w-4 rounded border-gray-300 bg-gray-50 text-orange-600 transition-colors duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:focus:ring-blue-400"
                                            value="{{ $gorev->id }}">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div
                                                    class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                                    {{ $gorev->baslik }}</div>
                                                <div class="text-sm text-gray-500">{{ Str::limit($gorev->aciklama, 50) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 flex-shrink-0">
                                                @php
                                                    $pf = $gorev->danisman->profil_fotografi ?? null;
                                                    $pfUrl = $pf && Storage::exists($pf) ? Storage::url($pf) : null;
                                                @endphp
                                                @if ($gorev->danisman && $pfUrl)
                                                    <img class="h-8 w-8 rounded-full object-cover"
                                                        src="{{ $pfUrl }}"
                                                        alt="{{ $gorev->danisman->name ?? 'Danışman' }}"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div
                                                        class="hidden h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600">
                                                        <svg class="h-4 w-4 text-white" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <div
                                                    class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                                    {{ $gorev->danisman->name ?? 'Atanmamış' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @php
                                            $islemDurumuEtiketi = ucfirst(str_replace('_', ' ', (string) $gorev->islem_statusu));
                                            $islemDurumuSinifi = match ($gorev->islem_statusu) {
                                                'tamamlandi' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'devam_ediyor' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                'iptal' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $islemDurumuSinifi }}">
                                            {{ $islemDurumuEtiketi }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @php
                                            $oncelikEtiketi = ucfirst((string) $gorev->oncelik);
                                            $oncelikSinifi = match ($gorev->oncelik) {
                                                'kritik' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                'yuksek' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                'orta' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                                default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $oncelikSinifi }}">
                                            {{ $oncelikEtiketi }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                        {{ $gorev->deadline ? $gorev->deadline->format('d.m.Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('admin.takim.gorevler.show', $gorev) }}"
                                                class="text-blue-600 hover:text-blue-900">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.takim.gorevler.edit', $gorev) }}"
                                                class="text-indigo-600 hover:text-indigo-900">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <x-neo.empty-state title="Henüz görev bulunmuyor"
                                            description="İlk görevi oluşturarak başlayın" :actionHref="route('admin.takim.gorevler.create')"
                                            actionText="İlk Görevi Oluştur" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($gorevler->hasPages())
                <div class="border-t border-gray-200 px-6 py-4 dark:border-slate-700">
                    {{ $gorevler->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    @endsection

    @push('scripts')
        <script>
            // Toplu seçim
            document.getElementById('select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.gorev-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Toplu görev atama
            function topluGorevAta() {
                const selectedGorevler = Array.from(document.querySelectorAll('.gorev-checkbox:checked')).map(cb => cb.value);

                if (selectedGorevler.length === 0) {
                    if (window.showToast) {
                        window.showToast('Lütfen en az bir görev seçin', 'warning');
                    } else {
                        alert('Lütfen en az bir görev seçin');
                    }
                    return;
                }

                const danismanId = prompt('Danışman ID girin:');
                if (!danismanId) return;

                fetch('{{ route('admin.takim.gorevler.toplu-ata') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            gorev_ids: selectedGorevler,
                            danisman_id: danismanId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (window.showToast) {
                                window.showToast(data.message || 'Görevler başarıyla atandı!', 'success');
                            } else {
                                alert('Görevler başarıyla atandı!');
                            }
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if (window.showToast) {
                                window.showToast(data.message || 'Hata oluştu', 'error');
                            } else {
                                alert('Hata: ' + data.message);
                            }
                        }
                    })
                    .catch(error => {
                        if (window.showToast) {
                            window.showToast('Bir hata oluştu', 'error');
                        } else {
                            alert('Bir hata oluştu');
                        }
                    });
            }
        </script>
    @endpush
