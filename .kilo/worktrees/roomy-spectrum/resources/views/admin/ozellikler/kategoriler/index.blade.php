@extends('admin.layouts.admin')

@section('title', 'Özellik Grupları Yönetimi')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Özellik Grupları Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Özellik kategorilerini (Genel, Konut, Arsa, vb.) yönetin ve düzenleyin</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ups.features.index') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Özellik Listesi
                </a>
                <a href="{{ route('admin.ozellikler.kategoriler.create') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md dark:shadow-blue-900/20 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Yeni Kategori
                </a>
            </div>
        </div>

        <!-- UPS Integration Info Banner -->
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        🤖 UPS Sistemi Entegrasyonu
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p class="mb-2">
                            <strong>Özellik Kategorileri</strong> artık UPS (Unified Property Schema) sistemi ile entegre
                            edilmiştir.
                            Bu sayfa <strong>AI semantic labeling</strong> ve <strong>özellik gruplandırma</strong> için
                            kullanılmaya devam edecektir.
                        </p>
                        <p class="mb-2">
                            <strong>Kategori bazlı özellik atamaları</strong> ve <strong>alan bağımlılıkları</strong> için lütfen yeni yönetim panelini kullanın:
                        </p>
                        <div class="mt-3 flex items-center gap-3">
                            <a href="{{ route('admin.ilan-kategorileri.index') }}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Kategori Yönetimi'ne Git
                            </a>
                            <a href="{{ route('admin.property-hub.index') }}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-300">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Property Hub
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="text-red-700 dark:text-red-300">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Filter & Search Bar -->
        <div class="mb-6 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-4 shadow-sm dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.ozellikler.kategoriler.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Kategori adı, slug veya açıklama ara..."
                        class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="w-full md:w-48">
                    <select name="aktiflik_durumu" onchange="this.form.submit()"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200">
                        <option value="">Tüm Durumlar</option>
                        <option value="1" {{ request('aktiflik_durumu') === '1' ? 'selected' : '' }}>Yayında</option>
                        <option value="0" {{ request('aktiflik_durumu') === '0' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-sm dark:shadow-none">
                    Filtrele
                </button>
                @if(request()->anyFilled(['q', 'aktiflik_durumu']))
                    <a href="{{ route('admin.ozellikler.kategoriler.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition-all duration-200">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        <!-- Kategori Tablosu -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none mb-8 dark:border-slate-700">
            @if ($kategoriler->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Kategori
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Özellik Sayısı
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Uygulama Alanı
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Durum
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($kategoriler as $kategori)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                    <!-- Kategori Adı -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div
                                                    class="h-10 w-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                                    <span class="text-orange-600 dark:text-orange-400 font-medium text-sm">
                                                        {{ substr($kategori->name, 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                    {{ $kategori->name }}
                                                </div>
                                                @if ($kategori->description)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ Str::limit($kategori->description, 50) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Özellik Sayısı -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            {{ $kategori->features_count ?? 0 }} özellik
                                        </span>
                                    </td>


                                    <!-- Uygulama Alanı (applies_to) -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @php
                                                $appliedKategoriler = $kategori->applied_kategoriler;
                                                $visibleItems = $appliedKategoriler->take(3);
                                                $remainingCount = $appliedKategoriler->count() - 3;
                                            @endphp

                                            @if ($appliedKategoriler->count() > 0)
                                                @foreach ($visibleItems as $ilanKategori)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                        {{ $ilanKategori->name }}
                                                    </span>
                                                @endforeach

                                                @if ($remainingCount > 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                                        +{{ $remainingCount }} Diğer
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                                    Tümü
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Durum -->
                                    {{-- ✅ SAB: "Aktif" kelimesi yasak, "Yayında" kullanılmalı --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $kategori->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                            {{ $kategori->aktiflik_durumu ? 'Yayında' : 'Pasif' }}
                                        </span>
                                    </td>

                                    <!-- İşlemler -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('admin.ozellikler.kategoriler.edit', $kategori->id) }}"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Düzenle
                                            </a>

                                            <form
                                                action="{{ route('admin.ozellikler.kategoriler.destroy', $kategori->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-red-600 to-pink-600 rounded-lg hover:from-red-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-sm dark:shadow-none disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Sil
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-slate-800 flex justify-end dark:bg-slate-900">
                    {{ $kategoriler->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Kategori bulunamadı</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Yeni bir kategori oluşturarak başlayın.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('admin.ozellikler.kategoriler.create') }}"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Yeni Kategori Oluştur
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
