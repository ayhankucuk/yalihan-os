@extends('admin.layouts.admin')

@section('title', 'Toplu İşlemler - Özellikler')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ✅ SAB: Header with navigation --}}
            <div class="mb-8">
                <a href="{{ route('admin.ozellikler.index') }}"
                    class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Geri Dön
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    ⚡ Toplu İşlemler
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Birden fazla özellikte aynı işlemi yapın
                </p>
            </div>

            {{-- ✅ SAB: Main form with Alpine.js state management --}}
            <form method="POST" action="{{ route('admin.ozellikler.bulk-action') }}"
                class="bg-white dark:bg-slate-900 rounded-xl shadow-md p-8 dark:shadow-none" x-data="bulkActionForm()" @change="updateCount()">
                @csrf

                {{-- ✅ SAB: Filter Section --}}
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg dark:bg-slate-900">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🔍 Filtreleme</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Category Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Kategori
                            </label>
                            <select id="categoryFilter"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100"
                                @change="filterOzellikler()">
                                <option value="">Tümü</option>
                                @foreach ($kategoriler as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->name }}</option>
                                @endforeach
                                <option value="null">Kategorisiz</option>
                            </select>
                        </div>

                        {{-- Aktiflik Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Durum
                            </label>
                            <select id="aktiflikFilter"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100"
                                @change="filterOzellikler()">
                                <option value="">Tümü</option>
                                <option value="active">Yayında</option>
                                <option value="inactive">Taslak</option>
                            </select>
                        </div>
                    </div>

                    {{-- ✅ SAB: Filter counter --}}
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Görüntülenen: <span id="visibleCount">{{ count($ozellikler) }}</span> /
                        Seçilen: <span class="font-semibold" x-text="selectedCount">0</span>
                    </div>
                </div>

                {{-- ✅ SAB: Select All Section --}}
                <div
                    class="mb-8 flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" @click="toggleSelectAll()"
                            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            Tümünü Seç
                        </span>
                    </label>

                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="selectedCount"></span> özellik seçildi
                    </span>
                </div>

                {{-- ✅ SAB: Items List --}}
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📝 Özellikler</h2>

                    <div
                        class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 dark:border-slate-800 rounded-lg p-4 bg-white dark:bg-slate-900 dark:border-slate-700">
                        @forelse($ozellikler as $ozellik)
                            <div class="ozellik-item flex items-start p-4 border border-gray-200 dark:border-slate-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors dark:border-slate-700"
                                data-category="{{ $ozellik->feature_category_id ?? '' }}"
                                data-aktiflik="{{ $ozellik->aktiflik_durumu ? 'active' : 'inactive' }}">
                                <input type="checkbox" name="ids[]" value="{{ $ozellik->id }}"
                                    class="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">

                                <div class="ml-4 flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                            {{ $ozellik->name }}
                                        </h3>
                                        {{-- ✅ SAB: Aktiflik badge (aktiflik_durumu field) --}}
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $ozellik->aktiflik_durumu ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
                                            {{ $ozellik->aktiflik_durumu ? '✓ Yayında' : '○ Taslak' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <span class="font-medium">Tür:</span> {{ $ozellik->field_type }} |
                                        <span class="font-medium">Kategori:</span>
                                        {{ $ozellik->category->name ?? '(Kategorisiz)' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                📭 Henüz özellik bulunmamaktadır
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ✅ SAB: Action Options --}}
                <div
                    class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">⚙️ İşlem Seçin</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Activate --}}
                        <label
                            class="flex items-center p-4 border-2 border-gray-200 dark:border-slate-800 rounded-lg cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/20 transition-all dark:border-slate-700"
                            @click="$refs.activateRadio.checked = true">
                            <input type="radio" name="action" value="activate" x-ref="activateRadio"
                                class="w-4 h-4 text-green-600 focus:ring-green-500" required>
                            <span class="ml-3">
                                <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">✅ Yayına Al</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Seçilen özellikler aktif yapılır</div>
                            </span>
                        </label>

                        {{-- Deactivate --}}
                        <label
                            class="flex items-center p-4 border-2 border-gray-200 dark:border-slate-800 rounded-lg cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-all dark:border-slate-700"
                            @click="$refs.deactivateRadio.checked = true">
                            <input type="radio" name="action" value="deactivate" x-ref="deactivateRadio"
                                class="w-4 h-4 text-red-600 focus:ring-red-500" required>
                            <span class="ml-3">
                                <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">⏸️ Taslak Yap</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Seçilen özellikler pasif yapılır</div>
                            </span>
                        </label>

                        {{-- Delete --}}
                        <label
                            class="flex items-center p-4 border-2 border-red-200 dark:border-red-800 rounded-lg cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                            @click="$refs.deleteRadio.checked = true">
                            <input type="radio" name="action" value="delete" x-ref="deleteRadio"
                                class="w-4 h-4 text-red-600 focus:ring-red-500" required>
                            <span class="ml-3">
                                <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">🗑️ Sil</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Seçilen özellikler silinir</div>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- ✅ SAB: Action Buttons --}}
                <div class="flex gap-4 mb-8">
                    <button type="submit" :disabled="selectedCount === 0"
                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <span x-show="selectedCount === 0">Seçim Yapınız</span>
                        <span x-show="selectedCount > 0">✓ İşlemi Uygula (<span x-text="selectedCount"></span>)</span>
                    </button>

                    <a href="{{ route('admin.ozellikler.index') }}"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg transition-all duration-200 dark:text-slate-100">
                        İptal
                    </a>
                </div>

                {{-- ✅ SAB: Warning message --}}
                <div
                    class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                        <strong>⚠️ Dikkat:</strong> Silme işlemi geri alınamaz. Lütfen kontrol ediniz.
                    </p>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * ✅ SAB: Bulk Action Form State Management
             * Alpine.js component for handling checkbox selection and filtering
             */
            function bulkActionForm() {
                return {
                    selectedCount: 0,
                    selectAll: false,

                    /**
                     * Toggle select all checkboxes
                     */
                    toggleSelectAll() {
                        this.selectAll = !this.selectAll;
                        document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
                            cb.checked = this.selectAll;
                        });
                        this.updateCount();
                    },

                    /**
                     * Update selected count
                     */
                    updateCount() {
                        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
                        this.selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                    }
                };
            }

            /**
             * ✅ SAB: Filter ozellikler by category and aktiflik durumu
             */
            function filterOzellikler() {
                const categoryFilter = document.getElementById('categoryFilter').value;
                const aktiflikFilter = document.getElementById('aktiflikFilter').value;
                let visibleCount = 0;

                document.querySelectorAll('.ozellik-item').forEach(item => {
                    const itemCategory = item.dataset.category || '';
                    const itemAktiflik = item.dataset.aktiflik;

                    // Check category match
                    const categoryMatch = !categoryFilter || categoryFilter === '' ||
                        categoryFilter === itemCategory ||
                        (categoryFilter === 'null' && !itemCategory);

                    // Check aktiflik match
                    const aktiflikMatch = !aktiflikFilter || aktiflikFilter === '' || aktiflikFilter === itemAktiflik;

                    // Show/hide item
                    const show = categoryMatch && aktiflikMatch;
                    item.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                document.getElementById('visibleCount').textContent = visibleCount;
            }
        </script>
    @endpush
@endsection
