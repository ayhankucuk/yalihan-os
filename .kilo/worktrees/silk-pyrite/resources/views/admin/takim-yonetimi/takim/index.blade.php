@extends('admin.layouts.admin')

@section('title', 'Takım Yönetimi')

@section('content')
    <div class="p-6">
        <!-- Page Header -->
        <div class=" mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">Takım Yönetimi</h1>
                    <p class="text-gray-600 mt-2">Takım üyelerini yönetin, performansları takip edin ve görev dağılımını
                        optimize edin</p>
                </div>
                <div class="flex space-x-3">
                    <x-context7.button variant="secondary" onclick="uyeEkleModal()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Üye Ekle
                    </x-context7.button>
                    <x-context7.button variant="secondary" href="{{ route('admin.takim.performans') }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Performans
                    </x-context7.button>
                    <x-context7.button variant="primary" href="{{ route('admin.takim.istatistikler') }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        Takım Performansı
                    </x-context7.button>
                </div>
            </div>
        </div>

        <!-- 📊 Takım İstatistikleri -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Üye</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                            {{ $istatistikler['toplam_uye'] ?? 0 }}</p>
                    </div>
                </div>
            </x-context7.card>

            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Üye</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                            {{ $istatistikler['status_uye'] ?? 0 }}</p>
                    </div>
                </div>
            </x-context7.card>

            <x-context7.card variant="gradient">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-yellow-800">Toplam Görev</h4>
                        <p class="text-2xl font-bold text-yellow-900">{{ $istatistikler['toplam_gorev'] ?? 0 }}</p>
                    </div>
                </div>
            </x-context7.card>

        </div>

        <!-- Fourth Stats Card -->
        <div class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl border border-purple-200 shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-purple-800">Ortalama Performans</h4>
                    <p class="text-2xl font-bold text-purple-900">{{ $istatistikler['ortalama_performans'] ?? 0 }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔍 Filtreler -->
    <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-xl border border-gray-200 shadow-sm p-6 mb-8 dark:border-slate-800 dark:shadow-none">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center dark:text-slate-200">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            🔍 Takım Üyesi Filtreleri
        </h2>

        <form method="GET" action="{{ route('admin.takim.takimlar.index') }}"
            class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="space-y-2 relative">
                <input type="text"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    name="search" placeholder="Üye ara..." value="{{ request('search') }}">
            </div>
            <div class="space-y-2 relative">
                <select style="color-scheme: light dark;"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    name="rol">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-gray-400">Tüm Roller
                    </option>
                    @foreach (['admin', 'danisman', 'alt_kullanici', 'musteri_temsilcisi'] as $rol)
                        <option value="{{ $rol }}"
                            class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            {{ request('rol') == $rol ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $rol)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2 relative">
                <select style="color-scheme: light dark;"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    name="aktiflik_durumu">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-gray-400">Tüm
                        Durumlar</option>
                    @foreach (['active', 'pasif', 'izinli', 'tatilde'] as $statusOption)
                        <option value="{{ $statusOption }}"
                            class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            {{ request('aktiflik_durumu') == $statusOption ? 'selected' : '' }}>
                            {{ ucfirst($statusOption) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2 relative">
                <select style="color-scheme: light dark;"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    name="lokasyon">
                    <option value="" class="bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-gray-400">Tüm
                        Lokasyonlar</option>
                    @foreach ($lokasyonlar ?? [] as $lokasyon)
                        <option value="{{ $lokasyon }}"
                            class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            {{ request('lokasyon') == $lokasyon ? 'selected' : '' }}>
                            {{ $lokasyon }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2 relative">
                <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 w-full touch-target-optimized dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Ara
                </button>
            </div>
        </form>
    </div>

    <!-- 📋 Takım Üyeleri Listesi -->
    <div
                <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200">Takım Üyeleri ({{ $takimUyeleri->count() }})</h2>
                <div class="flex items-center space-x-3">
                    <button type="button"
                        class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none"
                        onclick="selectAll()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Tümünü Seç
                    </button>
                    <button type="button"
                        class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none"
                        onclick="clearSelection()">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Seçimi Temizle
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <input type="checkbox" id="selectAllCheckbox"
                                    class="w-4 h-4 text-orange-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200"
                                    onchange="toggleSelectAll()">
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Üye</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rol</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Lokasyon</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Performans</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Son Aktivite</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($takimUyeleri as $uye)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox"
                                        class="item-checkbox w-4 h-4 text-orange-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200"
                                        value="{{ $uye->id }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                                            @if ($uye->user && $uye->user->profile_photo_url)
                                                <img class="w-10 h-10 rounded-full object-cover"
                                                    src="{{ $uye->user->profile_photo_url }}"
                                                    alt="{{ $uye->user->name }}">
                                            @else
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {{ $uye->user->name ?? 'Bilinmeyen Kullanıcı' }}</div>
                                            <div class="text-sm text-gray-500">{{ $uye->user->email ?? 'Email yok' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if ($uye->rol == 'admin') bg-red-100 text-red-800 border border-red-200
                                        @elseif($uye->rol == 'danisman') bg-blue-100 text-blue-800 border border-blue-200
                                        @elseif($uye->rol == 'musteri_temsilcisi') bg-green-100 text-green-800 border border-green-200
                                        @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                                        {{ ucfirst(str_replace('_', ' ', $uye->rol)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @php($uyeStatus = $uye->aktiflik_durumu ?? 'active')
                                        @if ($uyeStatus == 'active') bg-green-100 text-green-800 border border-green-200
                                        @elseif($uyeStatus == 'pasif') bg-red-100 text-red-800 border border-red-200
                                        @elseif($uyeStatus == 'izinli') bg-yellow-100 text-yellow-800 border border-yellow-200
                                        @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                                        {{ ucfirst($uyeStatus) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                                    {{ $uye->lokasyon ?? 'Belirtilmemiş' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full transition-all duration-1000"
                                                style="width: {{ $uye->performans ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ $uye->performans ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $uye->son_aktivite ? $uye->son_aktivite->diffForHumans() : 'Hiç aktivite yok' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.takim-yonetimi.takim.show', $uye->id) }}"
                                            class="text-blue-600 hover:text-blue-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.takim-yonetimi.takim.edit', $uye->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button type="button" class="text-red-600 hover:text-red-900"
                                            onclick="uyeCikar({{ $uye->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($takimUyeleri->count() == 0)
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">Henüz takım üyesi bulunmuyor</h3>
                    <p class="mt-1 text-sm text-gray-500">İlk takım üyesini ekleyerek başlayın</p>
                    <div class="mt-6">
                        <button type="button"
                            class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none"
                            onclick="uyeEkleModal()">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            İlk Üyeyi Ekle
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Üye Ekleme Modal -->
    <div id="uyeEkleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white dark:bg-slate-900">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">Yeni Takım Üyesi Ekle</h3>
                <form id="uyeEkleForm" method="POST" action="{{ route('admin.takim.takimlar.uye-ekle') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Kullanıcı</label>
                            <select style="color-scheme: light dark;" name="user_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                                required>
                                <option value="">Kullanıcı Seçin</option>
                                @foreach ($kullanicilar ?? [] as $kullanici)
                                    <option value="{{ $kullanici->id }}">{{ $kullanici->name }}
                                        ({{ $kullanici->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Rol</label>
                            <select style="color-scheme: light dark;" name="rol"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                required>
                                <option value="">Rol Seçin</option>
                                <option value="admin">Admin</option>
                                <option value="danisman">Danışman</option>
                                <option value="alt_kullanici">Alt Kullanıcı</option>
                                <option value="musteri_temsilcisi">Müşteri Temsilcisi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Lokasyon</label>
                            <input type="text" name="lokasyon"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                placeholder="Lokasyon">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                            <select style="color-scheme: light dark;" name="aktiflik_durumu"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                required>
                                <option value="active">Aktif</option>
                                <option value="pasif">Pasif</option>
                                <option value="izinli">İzinli</option>
                                <option value="tatilde">Tatilde</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                            class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none"
                            onclick="closeUyeEkleModal()">İptal</button>
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">Üye
                            Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Tümünü seç
        function selectAll() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }

        // Seçimi temizle
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }

        // Tümünü seç/kaldır toggle
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.item-checkbox');

            checkboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
        }

        // Üye ekleme modal
        function uyeEkleModal() {
            document.getElementById('uyeEkleModal').classList.remove('hidden');
        }

        function closeUyeEkleModal() {
            document.getElementById('uyeEkleModal').classList.add('hidden');
        }

        // Üye çıkarma
        function uyeCikar(uyeId) {
            if (window.showToast && window.showConfirm) {
                window.showConfirm('Bu üyeyi takımdan çıkarmak istediğinizden emin misiniz?', () => {
                    performUyeCikar(uyeId);
                });
            } else {
                if (confirm('Bu üyeyi takımdan çıkarmak istediğinizden emin misiniz?')) {
                    performUyeCikar(uyeId);
                }
            }
        }

        function performUyeCikar(uyeId) {
            fetch('{{ route('admin.takim.takimlar.uye-cikar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        takim_uye_id: uyeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.showToast) {
                            window.showToast(data.message || 'Üye başarıyla çıkarıldı!', 'success');
                        } else {
                            alert('Üye başarıyla çıkarıldı!');
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

        // Form submit
        document.getElementById('uyeEkleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.showToast) {
                            window.showToast(data.message || 'Üye başarıyla eklendi!', 'success');
                        } else {
                            alert('Üye başarıyla eklendi!');
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
        });
    </script>
@endpush
