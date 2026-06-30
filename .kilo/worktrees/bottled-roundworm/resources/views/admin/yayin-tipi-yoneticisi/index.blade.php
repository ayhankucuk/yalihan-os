@extends('admin.layouts.admin')

@section('title', 'Yayın Tipi Yöneticisi')
@section('meta_description', 'Tek sayfada kategori, yayın tipi ve ilişki yönetimi')
@section('meta_keywords', 'yayın tipi, kategori yönetimi, ilan yönetimi')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="yayinTipiYoneticisi()">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Yayın Tipi
                        Yöneticisi</h1>
                    <p class="text-gray-600 dark:text-gray-400">Tek sayfada kategori, yayın tipi ve ilişki yönetimi</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.ilan-kategorileri.index') }}"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 transition-all duration-200 dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kategorilere Dön
                    </a>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div
                class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-800/30 rounded-xl p-4">
                <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">Toplam Kategori</div>
                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $istatistikler['toplam_kategori'] }}
                </div>
            </div>
            <div
                class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800/30 rounded-xl p-4">
                <div class="text-sm text-green-600 dark:text-green-400 mb-1">Toplam Yayın Tipi</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $istatistikler['toplam_yayin_tipi'] }}
                </div>
            </div>
            <div
                class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-800/30 rounded-xl p-4">
                <div class="text-sm text-purple-600 dark:text-purple-400 mb-1">Aktif Yayın Tipi</div>
                <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                    {{ $istatistikler['aktif_yayin_tipi'] }}</div>
            </div>
        </div>

        <!-- Bilgi Kartı -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/30 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">💡 Nasıl Çalışır?</h3>
                    <p class="text-sm text-blue-800 dark:text-blue-400">Tek sayfada kategori, yayın tipleri ve ilişkileri
                        yönetin. Ayrı sayfalara gerek yok, her şey bir arada!</p>
                </div>
            </div>
        </div>

        <!-- Kategoriler ve Yayın Tipleri -->
        <div class="space-y-6">
            @foreach ($kategoriler as $item)
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
                    <!-- Kategori Header -->
                    <div
                        class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    @if ($item['kategori']->icon)
                                        <span class="text-2xl">{{ $item['kategori']->icon }}</span>
                                    @else
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $item['kategori']->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item['yayin_tipi_count'] }}
                                        yayın tipi</p>
                                </div>
                            </div>
                            <button @click="openAddModal({{ $item['kategori']->id }})"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Yayın Tipi Ekle
                            </button>
                        </div>
                    </div>

                    <!-- Yayın Tipleri Listesi -->
                    <div class="p-6">
                        @if ($item['yayin_tipleri']->count() > 0)
                            <div class="space-y-2">
                                @foreach ($item['yayin_tipleri'] as $yayinTipi)
                                    <div class="group flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors dark:bg-slate-900"
                                        x-data="{ editing: false, yayinTipi: '{{ addslashes($yayinTipi->yayin_tipi) }}', display_order: {{ $yayinTipi->display_order }} }">
                                        <div class="flex items-center gap-3 flex-1">
                                            <!-- Sıralama -->
                                            <div
                                                class="flex-shrink-0 cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 6h16M4 12h16M4 18h16" />
                                                </svg>
                                            </div>

                                            <!-- Yayın Tipi Adı -->
                                            <div class="flex-1">
                                                <span x-show="!editing"
                                                    class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $yayinTipi->yayin_tipi }}</span>
                                                <input x-show="editing" x-model="yayinTipi" type="text"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-slate-100"
                                                    @keyup.enter="updateYayinTipi({{ $yayinTipi->id }}, yayinTipi, order)"
                                                    @keyup.escape="editing = false; yayinTipi = '{{ addslashes($yayinTipi->yayin_tipi) }}'">
                                            </div>

                                            <!-- Status Badge -->
                                            <div class="flex-shrink-0">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $yayinTipi->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                                    {{ $yayinTipi->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- İşlem Butonları -->
                                        <div
                                            class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editing = !editing"
                                                class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="toggleStatus({{ $yayinTipi->id }})"
                                                class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                            <button @click="deleteYayinTipi({{ $yayinTipi->id }})"
                                                class="p-1.5 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Henüz yayın tipi eklenmemiş</p>
                                <button @click="openAddModal({{ $item['kategori']->id }})"
                                    class="mt-3 inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    İlk Yayın Tipini Ekle
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Yayın Tipi Ekleme Modal -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="showAddModal = false">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showAddModal = false">
                </div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yayın Tipi
                        Ekle</h3>
                    <form @submit.prevent="addYayinTipi()">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Yayın
                                    Tipi Adı</label>
                                <input type="text" x-model="newYayinTipi.yayin_tipi" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:text-slate-100">
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button type="button" @click="showAddModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors dark:bg-slate-900 dark:text-slate-300">
                                İptal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function yayinTipiYoneticisi() {
                return {
                    showAddModal: false,
                    newYayinTipi: {
                        kategori_id: null,
                        yayin_tipi: '',
                    },

                    openAddModal(kategoriId) {
                        this.newYayinTipi.kategori_id = kategoriId;
                        this.newYayinTipi.yayin_tipi = '';
                        this.showAddModal = true;
                    },

                    async addYayinTipi() {
                        try {
                            const response = await fetch('{{ route('admin.yayin-tipi-yoneticisi.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify(this.newYayinTipi),
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.showAddModal = false;
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu!');
                            }
                        } catch (error) {
                            alert('Bir hata oluştu!');
                            console.error(error);
                        }
                    },

                    async updateYayinTipi(id, yayinTipi, order) {
                        try {
                            const response = await fetch(`{{ url('admin/yayin-tipi-yoneticisi') }}/${id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    yayin_tipi: yayinTipi,
                                    order: order,
                                }),
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu!');
                            }
                        } catch (error) {
                            alert('Bir hata oluştu!');
                            console.error(error);
                        }
                    },

                    async toggleStatus(id) {
                        try {
                            const response = await fetch(`{{ url('admin/yayin-tipi-yoneticisi') }}/${id}/toggle-status`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu!');
                            }
                        } catch (error) {
                            alert('Bir hata oluştu!');
                            console.error(error);
                        }
                    },

                    async deleteYayinTipi(id) {
                        if (!confirm('Bu yayın tipini silmek istediğinize emin misiniz?')) {
                            return;
                        }

                        try {
                            const response = await fetch(`{{ url('admin/yayin-tipi-yoneticisi') }}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu!');
                            }
                        } catch (error) {
                            alert('Bir hata oluştu!');
                            console.error(error);
                        }
                    },
                };
            }
        </script>
    @endpush
@endsection
