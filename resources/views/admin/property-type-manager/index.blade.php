@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="{ search: '' }">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2 flex items-center gap-3">
                        <span class="text-3xl text-[#C9A84C]">🎯</span>
                        <span>Yayın Tipi Yöneticisi</span>
                    </h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                        Tek Sayfada Kategori, Yayın Tipi ve İlişki Yönetimi
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button"
                        onclick="showAddKategoriModal()"
                        class="inline-flex items-center px-4 py-2.5 bg-[#0A1628] hover:bg-[#15243B] text-[#C9A84C] font-semibold text-sm rounded-xl border border-[#C9A84C]/40 shadow-sm transition-all hover:scale-[1.02] active:scale-95">
                        <x-icon name="ekle" class="w-4 h-4 mr-2 text-[#C9A84C]" />
                        <span>Kategori Ekle</span>
                    </button>
                    <a href="{{ route('admin.ilan-kategorileri.index') }}"
                        class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                        <x-icon name="liste" class="w-4 h-4 mr-2 text-slate-400" />
                        <span>Tüm Kategoriler</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <input type="text"
                    x-model="search"
                    placeholder="Kategori ara..."
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2.5 pl-10 text-sm text-slate-800 dark:text-slate-100 shadow-sm focus:border-[#C9A84C] focus:outline-none focus:ring-1 focus:ring-[#C9A84C]" />
                <x-icon name="arama" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            </div>
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                Toplam <span class="font-bold text-slate-900 dark:text-white">{{ $kategoriler->count() }}</span> Ana Kategori
            </div>
        </div>

        <!-- Kategori Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($kategoriler as $kategori)
                <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                    x-show="!search || '{{ strtolower(addslashes($kategori->name)) }}'.includes(search.toLowerCase())"
                    class="group bg-white dark:bg-slate-900 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 p-6 border-l-4 border-blue-600 dark:border-blue-500 border border-slate-200/80 dark:border-slate-800 transform hover:-translate-y-0.5">

                    <!-- Kategori İkonu ve İsim -->
                    <div class="flex items-center mb-4">
                        <div class="text-4xl mr-4 group-hover:scale-110 transition-transform duration-300">
                            {{ $kategori->icon ?? '🏠' }}
                        </div>
                        <div class="flex-1">
                            <h3
                                class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                                {{ $kategori->name }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if ($kategori->yayinTipleri && $kategori->yayinTipleri->count() > 0)
                                    {{ $kategori->yayinTipleri->count() }} Yayın Tipi
                                @else
                                    Yayın tipi tanımlanmamış
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Yayın Tipleri Preview -->
                    @if ($kategori->yayinTipleri && $kategori->yayinTipleri->count() > 0)
                        <div class="mb-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">
                                Yayın Tipleri
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($kategori->yayinTipleri->take(12) as $yayinTipi)
                                    <span
                                        class="text-xs px-2.5 py-1 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-200 dark:border-green-800 rounded-full text-green-700 dark:text-green-300 font-medium flex items-center gap-1">
                                        @if ($yayinTipi->icon)
                                            <span>{{ $yayinTipi->icon }}</span>
                                        @endif
                                        <span>{{ $yayinTipi->yayin_tipi }}</span>
                                    </span>
                                @endforeach
                                @if ($kategori->yayinTipleri->count() > 12)
                                    <span
                                        class="text-xs px-2.5 py-1 bg-gray-100 dark:bg-slate-900 rounded-full text-gray-600 dark:text-gray-400 font-medium">
                                        +{{ $kategori->yayinTipleri->count() - 12 }} daha
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Alt Kategoriler Preview -->
                    <div class="flex flex-wrap gap-2 mb-4 min-h-[32px]">
                        @foreach ($kategori->children->take(12) as $altKategori)
                            <span
                                class="text-xs px-3 py-1 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/30 dark:to-purple-900/30 border border-blue-200 dark:border-blue-800 rounded-full text-blue-700 dark:text-blue-300 font-medium">
                                {{ $altKategori->name }}
                            </span>
                        @endforeach
                        @if ($kategori->children->count() > 12)
                            <span
                                class="text-xs px-3 py-1 bg-gray-100 dark:bg-slate-900 rounded-full text-gray-600 dark:text-gray-400 font-medium">
                                +{{ $kategori->children->count() - 12 }} daha
                            </span>
                        @endif
                        @if ($kategori->children->count() === 0)
                            <span
                                class="text-xs px-3 py-1 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-full text-yellow-700 dark:text-yellow-300">
                                Alt kategori yok
                            </span>
                        @endif
                    </div>

                    <!-- Stats & Action -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5" title="Alt Kategori Sayısı">
                                <x-icon name="katman" class="w-3.5 h-3.5 text-blue-500" />
                                <span>{{ $kategori->children->count() }} alt kategori</span>
                            </span>
                            @if ($kategori->yayinTipleri && $kategori->yayinTipleri->count() > 0)
                                <span class="inline-flex items-center gap-1.5" title="Yayın Tipi Sayısı">
                                    <x-icon name="etiket" class="w-3.5 h-3.5 text-emerald-500" />
                                    <span>{{ $kategori->yayinTipleri->count() }} yayın tipi</span>
                                </span>
                            @endif
                        </div>
                        <span
                            class="text-xs font-bold text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 flex items-center gap-1">
                            Yönet
                            <x-icon name="sag-ok" class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Footer Info -->
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start">
                <div class="text-2xl mr-3">💡</div>
                <div>
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                        Nasıl Çalışır?
                    </h4>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Tek sayfada kategori, yayın tipleri ve ilişkileri yönetin.
                        Ayrı sayfalara gerek yok, her şey bir arada!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Yeni Kategori Ekle -->
    <div id="addKategoriModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-xl p-8 max-w-md w-full">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">
                ➕ Yeni Kategori Ekle
            </h3>

            <form id="addKategoriForm" onsubmit="addKategori(event)">
                <!-- Kategori Adı -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Kategori Adı
                    </label>
                    <input type="text" id="modalKategoriName" required
                        placeholder="Örn: Yazlık, Villa, Apartman"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 dark:shadow-none dark:bg-slate-900">
                </div>

                <!-- Kategori Slug -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Slug (URL)
                    </label>
                    <input type="text" id="modalKategoriSlug" required
                        placeholder="Örn: yazlik, villa, apartman"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 dark:shadow-none dark:bg-slate-900">
                </div>

                <!-- Kategori İkonu -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        İkon (Emoji)
                    </label>
                    <input type="text" id="modalKategoriIcon" maxlength="2"
                        placeholder="🏠"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 text-2xl dark:shadow-none dark:bg-slate-900">
                </div>

                <!-- Butonlar -->
                <div class="flex gap-3">
                    <button type="button" onclick="closeAddKategoriModal()"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg shadow-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex-1 dark:bg-gray-700 dark:hover:bg-gray-600">
                        İptal
                    </button>
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg shadow-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 active:scale-95 flex-1">
                        <x-icon name="ekle" class="w-4 h-4 mr-2" />
                        Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/property-type-manager.js') }}"></script>
@endsection