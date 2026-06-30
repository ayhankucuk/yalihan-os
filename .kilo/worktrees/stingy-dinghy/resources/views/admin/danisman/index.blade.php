@extends('admin.layouts.admin')

@section('title', 'Danışman Yönetimi')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    Danışman Yönetimi
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Emlak danışmanlarınızı yönetin ve performanslarını takip
                    edin</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.danisman.create') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Yeni Danışman
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div id="success-message"
                class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg shadow-lg p-4 mb-6 animate-slide-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-green-800 dark:text-green-200">
                            {{ session('success') }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()"
                            class="text-green-500 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div
                class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 rounded-lg shadow-lg p-4 mb-6 animate-slide-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                            {{ session('error') }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()"
                            class="text-red-500 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-600">{{ $istatistikler['toplam_danisman'] ?? 0 }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Danışman</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-600">{{ $istatistikler['aktif_danisman'] ?? 0 }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Aktif Danışman</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-600">{{ $istatistikler['online_danisman'] ?? 0 }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Online Danışman</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-600">
                            %{{ number_format($istatistikler['ortalama_performans'] ?? 0, 1) }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Ortalama Performans</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <form method="GET" action="{{ route('admin.danisman.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Arama</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Ad, email, telefon..."
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                        <select style="color-scheme: light dark;" name="aktiflik_durumu"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                            <option value="">Tümü</option>
                            <option value="1" {{ ($filters['aktiflik_durumu'] ?? '') == '1' ? 'selected' : '' }}>Aktif
                            </option>
                            <option value="0" {{ ($filters['aktiflik_durumu'] ?? '') == '0' ? 'selected' : '' }}>
                                Pasif</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Online
                            Durum</label>
                        <select style="color-scheme: light dark;" name="online"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                            <option value="">Tümü</option>
                            <option value="Online" {{ ($filters['online'] ?? '') === 'Online' ? 'selected' : '' }}>Online
                            </option>
                            <option value="Offline" {{ ($filters['online'] ?? '') === 'Offline' ? 'selected' : '' }}>
                                Offline</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Sıralama</label>
                        <select style="color-scheme: light dark;" name="sort"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                            <option value="created_desc"
                                {{ ($filters['sort'] ?? '') === 'created_desc' ? 'selected' : '' }}>En Yeni</option>
                            <option value="created_asc"
                                {{ ($filters['sort'] ?? '') === 'created_asc' ? 'selected' : '' }}>En Eski</option>
                            <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Ad
                                (A-Z)</option>
                            <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Ad
                                (Z-A)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <a href="{{ route('admin.danisman.index') }}"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">Temizle</a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Filtrele
                    </button>
                </div>
            </form>
        </div>

        <!-- Danışman Listesi -->
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
            <div
                class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Danışman Listesi</h3>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $danismanlar->total() }} danışman</span>
                    <button type="button" onclick="exportDanismanCsv()"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">CSV
                        İndir</button>
                </div>
            </div>

            <div class="p-6">
                <x-admin.meta-info title="Danışmanlar" :meta="[
                    'total' => $danismanlar->total(),
                    'current_page' => $danismanlar->currentPage(),
                    'last_page' => $danismanlar->lastPage(),
                    'per_page' => $danismanlar->perPage(),
                ]" :show-per-page="true" :per-page-options="[20, 50, 100]"
                    listId="danismanlar" listEndpoint="/api/admin/api/v1/danismanlar" />
                @if ($danismanlar->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="admin-table-th">Danışman</th>
                                    <th class="admin-table-th">İletişim</th>
                                    <th class="admin-table-th">Ünvan</th>
                                    <th class="admin-table-th">Performans</th>
                                    <th class="admin-table-th">Durum / Sosyal Medya</th>
                                    <th class="admin-table-th">Kayıt Tarihi</th>
                                    <th class="admin-table-th" width="150">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($danismanlar as $danisman)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    @if ($danisman->profile_photo && file_exists(storage_path('app/public/' . $danisman->profile_photo)))
                                                        <img class="h-10 w-10 rounded-full object-cover"
                                                            src="{{ asset('storage/' . $danisman->profile_photo) }}"
                                                            alt="{{ $danisman->name }}">
                                                    @else
                                                        <div
                                                            class="h-10 w-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center text-white font-semibold">
                                                            {{ strtoupper(substr($danisman->name, 0, 2)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-4">
                                                    <div
                                                        class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                        {{ $danisman->name }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $danisman->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $danisman->phone_number ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $danisman->title ?? 'Danışman' }}</div>
                                            @if ($danisman->position)
                                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ config('danisman.positions.' . $danisman->position, $danisman->position) }}
                                                </div>
                                            @endif
                                            @if ($danisman->department)
                                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ config('danisman.departments.' . $danisman->department, $danisman->department) }}
                                                </div>
                                            @endif
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                @if ($danisman->uzmanlik_alanlari && count($danisman->uzmanlik_alanlari) > 0)
                                                    {{ implode(', ', $danisman->uzmanlik_alanlari) }}
                                                @elseif($danisman->uzmanlik_alani)
                                                    {{ $danisman->uzmanlik_alani }}
                                                @else
                                                    Genel
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-1 bg-gray-200 dark:bg-slate-900 rounded-full h-2 mr-2">
                                                    <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full"
                                                        style="width: {{ $danisman->is_verified ? 100 : 50 }}%"></div>
                                                </div>
                                                <span
                                                    class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $danisman->is_verified ? '100' : '50' }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    {{-- ✅ SAB: Sadece bu danışmanın statusunu göster (tek badge) --}}
                                                    @php
                                                        // Context7: yayin_statusu field kullanımı (status YASAK)
                                                        $yayinDurumuValue = null;
                                                        $yayinDurumuLabel = 'Aktif';

                                                        // yayin_statusu_text varsa öncelikli olarak kullan
                                                        if (!empty($danisman->yayin_statusu_text)) {
                                                            $yayinDurumuText = strtolower(
                                                                trim($danisman->yayin_statusu_text),
                                                            );
                                                            $yayinDurumuValue = $yayinDurumuText;

                                                            // Label mapping
                                                            $labelMap = [
                                                                'aktif' => 'Aktif',
                                                                'pasif' => 'Pasif',
                                                                'taslak' => 'Taslak',
                                                                'onay_bekliyor' => 'Onay Bekliyor',
                                                                'yayinda' => 'Yayında',
                                                                'satildi' => 'Satıldı',
                                                                'kiralandi' => 'Kiralandı',
                                                                'arsivlendi' => 'Arşivlendi',
                                                            ];
                                                            $yayinDurumuLabel =
                                                                $labelMap[$yayinDurumuText] ??
                                                                ucfirst(str_replace('_', ' ', $yayinDurumuText));
                                                        } else {
                                                            // Boolean yayin_statusu kontrolü
                                                            if (is_bool($danisman->yayin_statusu)) {
                                                                $yayinDurumuValue = $danisman->yayin_statusu
                                                                    ? 'aktif'
                                                                    : 'pasif';
                                                                $yayinDurumuLabel = $danisman->yayin_statusu
                                                                    ? 'Aktif'
                                                                    : 'Pasif';
                                                            } elseif (
                                                                $danisman->yayin_statusu === 1 ||
                                                                $danisman->yayin_statusu === '1'
                                                            ) {
                                                                $yayinDurumuValue = 'aktif';
                                                                $yayinDurumuLabel = 'Aktif';
                                                            } elseif (
                                                                $danisman->yayin_statusu === 0 ||
                                                                $danisman->yayin_statusu === '0'
                                                            ) {
                                                                $yayinDurumuValue = 'pasif';
                                                                $yayinDurumuLabel = 'Pasif';
                                                            } else {
                                                                $yayinDurumuValue = 'aktif';
                                                                $yayinDurumuLabel = 'Aktif';
                                                            }
                                                        }

                                                        // Renk mapping
                                                        $colorMap = [
                                                            'aktif' =>
                                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                            'pasif' =>
                                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                            'taslak' =>
                                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                                            'onay_bekliyor' =>
                                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                            'yayinda' =>
                                                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                            'satildi' =>
                                                                'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                                            'kiralandi' =>
                                                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                                            'arsivlendi' =>
                                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                                        ];
                                                        $colorClass =
                                                            $colorMap[$yayinDurumuValue] ??
                                                            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
                                                    @endphp

                                                    {{-- Tek status badge'i (Tailwind ile) --}}
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }} transition-all duration-200">
                                                        {{ $yayinDurumuLabel }}
                                                    </span>

                                                    {{-- Online status badge'i (ayrı, sadece online ise) --}}
                                                    @if ($danisman->last_activity_at && $danisman->last_activity_at->isAfter(now()->subMinutes(5)))
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 transition-all duration-200">
                                                            Online
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Sosyal medya linkleri --}}
                                                @php
                                                    $hasSocialMedia =
                                                        !empty($danisman->instagram_profile) ||
                                                        !empty($danisman->linkedin_profile) ||
                                                        !empty($danisman->facebook_profile) ||
                                                        !empty($danisman->twitter_profile) ||
                                                        !empty($danisman->youtube_channel) ||
                                                        !empty($danisman->tiktok_profile) ||
                                                        !empty($danisman->whatsapp_number) ||
                                                        !empty($danisman->telegram_username) ||
                                                        !empty($danisman->website);
                                                @endphp
                                                @if ($hasSocialMedia)
                                                    <x-admin.danisman-social-links :danisman="$danisman" size="xs" />
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $danisman->created_at?->format('d.m.Y') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('admin.danisman.show', $danisman) }}"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                                    title="Görüntüle">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.danisman.edit', $danisman) }}"
                                                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400"
                                                    title="Düzenle">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                                <form method="POST"
                                                    action="{{ route('admin.danisman.destroy', $danisman) }}"
                                                    class="inline-block"
                                                    onsubmit="return confirm('⚠️ {{ $danisman->name }} danışmanını silmek istediğinizden emin misiniz?')">
                                                    @csrf @method('DELETE')<button type="submit"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400"
                                                        title="Sil"><svg class="w-5 h-5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $danismanlar->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz
                            danışman bulunmuyor</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">İlk danışmanınızı ekleyerek başlayın.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.danisman.create') }}"
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                İlk Danışmanı Ekle
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('success-message');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        successMessage.remove();
                    }, 500);
                }, 5000);
            }
        });

        function exportDanismanCsv() {
            const rows = Array.from(document.querySelectorAll('table tbody tr'));
            let csv = 'Ad,E-posta,Telefon,Ünvan,Performans,Durum,Kayıt Tarihi\n';

            rows.forEach(r => {
                const cells = r.querySelectorAll('td');
                if (cells.length) {
                    const name = (cells[0]?.innerText || '').trim().replace(/\n/g, ' ').replace(/\s+/g, ' ');
                    const contact = (cells[1]?.innerText || '').trim();
                    const title = (cells[2]?.innerText || '').trim().replace(/\n/g, ' ');
                    const perf = (cells[3]?.innerText || '').trim();
                    const durum = (cells[4]?.innerText || '').trim();
                    const date = (cells[5]?.innerText || '').trim();
                    csv += `"${name}","${contact}","${title}","${perf}","${durum}","${date}"\n`;
                }
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'danismanlar_' + (new Date().toISOString().split('T')[0]) + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paginate = document.querySelector('.mt-6') || document.querySelector('.px-6.py-4 + .mt-6')
            const tableBody = document.querySelector('table tbody')
            if (!window.ApiAdapter || !paginate || !tableBody) return
            const loadingEl = document.getElementById('meta-status')
            const totalEl = document.getElementById('meta-total')
            const pageEl = document.getElementById('meta-page')
            const container = document.querySelector('[data-meta="true"]')
            const perSelect = document.getElementById('per_page_select')
            let currentPer = 20
            const urlInit = new URL(window.location.href)
            const qPer = parseInt(urlInit.searchParams.get('per_page') || '')
            const storageKey = 'yalihan_admin_per_page'
            const sPer = parseInt(localStorage.getItem(storageKey) || '')

            if (perSelect) {
                if (qPer) {
                    currentPer = qPer;
                    perSelect.value = String(qPer)
                } else if (sPer) {
                    currentPer = sPer;
                    perSelect.value = String(sPer)
                }
                perSelect.addEventListener('change', function() {
                    currentPer = parseInt(perSelect.value || '20');
                    const u = new URL(window.location.href);
                    u.searchParams.set('per_page', String(currentPer));
                    window.history.replaceState({}, '', u.toString());
                    loadPage(1)
                })
            }

            function esc(s) {
                return s == null ? '' : String(s).replace(/[<>&"']/g, function(c) {
                    return {
                        '<': '&lt;',
                        '>': '&gt;',
                        '&': '&amp;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [c]
                })
            }

            function getActiveFilters() {
                var params = {}
                var form = document.querySelector('form[action*="danisman"]')
                if (!form) return params
                var fd = new FormData(form)
                fd.forEach(function(v, k) {
                    if (v) params[k] = v
                })
                return params
            }

            function setLoading(f) {
                loadingEl.setAttribute('aria-busy', f ? 'true' : 'false');
                loadingEl.textContent = f ? 'Yükleniyor…' : ''
            }

            function renderRows(items) {
                if (!items || items.length === 0) {
                    tableBody.innerHTML =
                        '<tr><td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Kayıt bulunamadı</td></tr>';
                    return
                }
                var rows = items.map(function(it) {
                    var name = esc(it.name || '')
                    var email = esc(it.email || '')
                    return (
                        '<tr>' +
                        '<td class="px-6 py-4">' + name + '<div class="text-sm text-gray-500">' +
                        email + '</div></td>' +
                        '<td class="px-6 py-4">' + esc(it.phone_number || '-') + '</td>' +
                        '<td class="px-6 py-4">' + '' + '</td>' +
                        '<td class="px-6 py-4">' + '' + '</td>' +
                        '<td class="px-6 py-4">' + '' + '</td>' +
                        '<td class="px-6 py-4">' + '' + '</td>' +
                        '<td class="px-6 py-4"><a href="/admin/danisman/' + esc(it.id || '') +
                        '" class="text-blue-600">Detay</a></td>' +
                        '</tr>'
                    )
                }).join('')
                tableBody.innerHTML = rows
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
                var params = Object.assign({
                    page: Number(page || 1),
                    per_page: currentPer
                }, getActiveFilters())
                window.ApiAdapter.get('/danismanlar', params)
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
    </script>
@endpush
