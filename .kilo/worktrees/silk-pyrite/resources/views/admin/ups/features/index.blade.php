@extends('admin.layouts.admin')

@section('title', 'UPS Özellik Yöneticisi')

@section('content')
    <div class="container-fluid px-4 py-6" x-data="featureManager()">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600 dark:text-transparent dark:bg-clip-text dark:from-white dark:to-gray-300">
                    Özellik Yöneticisi
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">
                    Tüm sistem özelliklerini tek merkezden yönetin (UPS)
                </p>
            </div>
            <button @click="openModal()"
                class="group relative inline-flex items-center justify-center px-6 py-3 font-semibold text-white transition-all duration-200 ease-in-out bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg hover:shadow-xl active:scale-95">
                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:rotate-90" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yeni Özellik Ekle
            </button>
        </div>

        {{-- Filters --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-5 mb-8 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center gap-2 mb-4 text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtrele
            </div>

            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-12 gap-6 items-end">
                <div class="lg:col-span-4">
                    <label
                        class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                        🔍 Arama
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="İsim veya slug ile ara..."
                            class="pl-10 w-full h-11 rounded-xl border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:shadow-none dark:text-white dark:border-slate-700 dark:bg-slate-900">
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label
                        class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                        📂 Kategori
                    </label>
                    <select name="category"
                        class="w-full h-11 rounded-xl border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm dark:shadow-none dark:text-white dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Tüm Kategoriler</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label
                        class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                        ⌨️ Veri Tipi
                    </label>
                    <select name="type"
                        class="w-full h-11 rounded-xl border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm dark:shadow-none dark:text-white dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Tümü</option>
                        <option value="text" {{ request('type') === 'text' ? 'selected' : '' }}>Metin (Text)</option>
                        <option value="number" {{ request('type') === 'number' ? 'selected' : '' }}>Sayı (Number)</option>
                        <option value="boolean" {{ request('type') === 'boolean' ? 'selected' : '' }}>Evet/Hayır (Boolean)</option>
                        <option value="date" {{ request('type') === 'date' ? 'selected' : '' }}>Tarih (Date)</option>
                        <option value="select" {{ request('type') === 'select' ? 'selected' : '' }}>Seçim (Select)</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label
                        class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                        🚦 Durum
                    </label>
                    <select name="aktiflik_durumu"
                        class="w-full h-11 rounded-xl border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm dark:shadow-none dark:text-white dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Tümü</option>
                        <option value="1" {{ request('aktiflik_durumu') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('aktiflik_durumu') === '0' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                <div class="lg:col-span-1 flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 h-11 flex items-center justify-center bg-gray-900 dark:bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-gray-800 dark:hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 transition-all shadow-lg active:scale-95">
                        Filtrele
                    </button>
                    @if (request()->hasAny(['search', 'category', 'type', 'aktiflik_durumu']))
                        <a href="{{ route('admin.ups.features.index') }}"
                            class="w-11 h-11 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-slate-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all group dark:bg-slate-900"
                            title="Filtreleri Temizle">
                            <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Features Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow overflow-hidden dark:shadow-none">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Özellik
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Kategori
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tip
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Durum
                        </th>
                        <th
                            class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($features as $feature)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $feature->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $feature->slug }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                {{ $feature->category->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    {{ $feature->type }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($feature->aktiflik_durumu)
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editFeature({{ json_encode([
                                            'id' => $feature->id,
                                            'name' => $feature->name,
                                            'slug' => $feature->slug,
                                            'type' => $feature->type,
                                            'feature_category_id' => $feature->feature_category_id,
                                            'description' => $feature->description,
                                            'aktiflik_durumu' => $feature->aktiflik_durumu,
                                        ]) }})"
                                        class="p-2 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors group"
                                        title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="toggleDurum({{ $feature->id }}, {{ $feature->aktiflik_durumu ? 'false' : 'true' }})"
                                        class="p-2 rounded-lg transition-colors group {{ $feature->aktiflik_durumu ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50' : 'bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50' }}"
                                        title="{{ $feature->aktiflik_durumu ? 'Pasifleştir' : 'Aktifleştir' }}">
                                        @if ($feature->aktiflik_durumu)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Feature bulunamadı
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($features->hasPages())
            <div class="mt-6">
                {{ $features->links() }}
            </div>
        @endif

        {{-- Create/Edit Modal (Alpine) --}}
        <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">

            <div @click.away="showModal = false" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">

                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="modalTitle"></h3>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="formData.slug" required
                                class="w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:shadow-none dark:text-white dark:bg-slate-900"
                                placeholder="Örn: bahce">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-medium ml-1">lowercase, a-z 0-9 _</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                İsim <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="formData.name" required
                                class="w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:shadow-none dark:text-white dark:bg-slate-900"
                                placeholder="Örn: Bahçe">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                Tip <span class="text-red-500">*</span>
                            </label>
                            <select x-model="formData.type" required
                                class="w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm cursor-pointer dark:shadow-none dark:text-white dark:bg-slate-900">
                                <option value="">Seçin</option>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                                <option value="multiselect">Multiselect</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                Kategori
                            </label>
                            <select x-model="formData.feature_category_id"
                                class="w-full h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm cursor-pointer dark:shadow-none dark:text-white dark:bg-slate-900">
                                <option value="">Seçin</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                            Açıklama
                        </label>
                        <textarea x-model="formData.description" rows="3"
                            class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm placeholder-gray-400 dark:shadow-none dark:text-white dark:bg-slate-900"
                            placeholder="Özellik hakkında kısa bilgi..."></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="modal_aktiflik_durumu" x-model="formData.aktiflik_durumu"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="modal_aktiflik_durumu" class="ml-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            Aktif
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-300">
                            İptal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-50 dark:shadow-none"
                            :disabled="loading">
                            <span x-show="!loading">Kaydet</span>
                            <span x-show="loading">Kaydediliyor...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function featureManager() {
                return {
                    showModal: false,
                    loading: false,
                    modalTitle: 'Yeni Özellik',
                    formData: {
                        id: null,
                        slug: '',
                        name: '',
                        type: '',
                        feature_category_id: '',
                        description: '',
                        aktiflik_durumu: true
                    },

                    openModal() {
                        this.modalTitle = 'Yeni Özellik';
                        this.formData = {
                            id: null,
                            slug: '',
                            name: '',
                            type: '',
                            feature_category_id: '',
                            description: '',
                            aktiflik_durumu: true
                        };
                        this.showModal = true;
                    },

                    editFeature(feature) {
                        this.modalTitle = 'Özellik Düzenle';
                        this.formData = {
                            id: feature.id,
                            slug: feature.slug,
                            name: feature.name,
                            type: feature.type,
                            feature_category_id: feature.feature_category_id || '',
                            description: feature.description || '',
                            aktiflik_durumu: !!feature.aktiflik_durumu
                        };
                        this.showModal = true;
                    },

                    async submitForm() {
                        this.loading = true;
                        const url = this.formData.id ?
                            `/admin/ups/features/${this.formData.id}` :
                            '/admin/ups/features';
                        
                        // Laravel method spoofing for PUT
                        const isUpdate = !!this.formData.id;
                        const method = 'POST'; // Always POST for fetch compatibility
                        
                        const payload = { ...this.formData };
                        if (isUpdate) {
                            payload._method = 'PUT';
                        }

                        try {
                            const response = await fetch(url, {
                                method: method, // Always POST
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            });

                            const result = await response.json();

                            if (response.ok) {
                                window.toast?.success(this.formData.id ? 'Özellik güncellendi' :
                                    'Yeni özellik oluşturuldu');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                window.toast?.error(result.message || 'Bir hata oluştu');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            window.toast?.error('İşlem sırasında bir hata oluştu');
                        } finally {
                            this.loading = false;
                        }
                    },

                    async toggleDurum(id, newDurum) {
                        if (!confirm(`Özelliğin durumunu ${newDurum ? 'aktif' : 'pasif'} yapmak istediğinize emin misiniz?`)) {
                            return;
                        }

                        try {
                            const response = await fetch(`/admin/ups/features/${id}/durum`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    aktiflik_durumu: newDurum
                                })
                            });

                            if (response.ok) {
                                window.toast?.success('Durum güncellendi');
                                setTimeout(() => window.location.reload(), 500);
                            } else {
                                const data = await response.json();
                                window.toast?.error(data.message || 'Durum güncellenemedi');
                            }
                        } catch (error) {
                            console.error('Toggle error:', error);
                            window.toast?.error('İşlem sırasında hata oluştu');
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection
