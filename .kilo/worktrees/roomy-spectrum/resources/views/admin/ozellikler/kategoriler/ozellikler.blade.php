@extends('admin.layouts.admin')

@section('title', 'Kategori Özellikleri: ' . $kategori->name)

@section('content')
    <div class="rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all duration-200 dark:shadow-none dark:border-slate-700"
        x-data="{
            selectedIds: [],
            selectAll: false,
            toggleAll() {
                this.selectAll = !this.selectAll;
                const checkboxes = document.querySelectorAll('.feature-checkbox');
                this.selectedIds = this.selectAll ? Array.from(checkboxes).map(cb => cb.value) : [];
            },
            toggleSelect(id) {
                if (this.selectedIds.includes(id)) {
                    this.selectedIds = this.selectedIds.filter(i => i !== id);
                } else {
                    this.selectedIds.push(id);
                }
                this.selectAll = this.selectedIds.length === {{ $kategori->features->count() }};
            },
            async submitBulkAction(action) {
                    if (this.selectedIds.length === 0) {
                        window.toast?.error('Lütfen en az bir özellik seçin!');
                        return;
                    }
                    if (action === 'delete' && !confirm(this.selectedIds.length + ' özelliği silmek istediğinizden emin misiniz?')) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('_token', document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || '');
                        this.selectedIds.forEach(id => formData.append('ids[]', id));

        // ✅ SAB: Merkezi endpoint sistemi kullan (route helper)
        const url = '{{ route('admin.ozellikler.bulk-action') }}';
        const response = await fetch(url, {
        method: 'POST',
        headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
        });

        if (response.ok) {
        window.toast?.success('İşlem başarıyla tamamlandı');
        setTimeout(() => location.reload(), 1000);
        } else {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || 'İşlem başarısız');
        }
        } catch (error) {
        console.error('Bulk action error:', error);
        window.toast?.error('İşlem sırasında hata oluştu: ' + error.message);
        }
        }
        }">
        <div class="p-6">
            <!-- Success Message -->
            @if (session('success'))
                <div
                    class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-800 dark:text-green-300 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">
                    <i class="fas fa-tag mr-2"></i> {{ $kategori->name }} Kategorisindeki Özellikler
                </h1>
                <div class="flex gap-3">
                    <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:text-slate-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kategorilere Dön
                    </a>
                    <a href="{{ route('admin.ozellikler.create', ['category' => $kategori->id]) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 dark:bg-blue-500 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yeni Özellik Ekle
                    </a>
                </div>
            </div>
            <!-- Kategori Bilgileri -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Kategori Bilgileri</h2>
                        <p class="text-blue-600 dark:text-blue-300 mt-1">{{ $kategori->name }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="px-3 py-1 text-sm font-medium rounded-full {{ $kategori->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                            {{ $kategori->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Oluşturulma: {{ $kategori->created_at->format('d.m.Y') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Toolbar -->
            @if ($kategori->features->count() > 0)
                <div class="mb-4 p-4 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700"
                    x-show="selectedIds.length > 0" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 bg-blue-600 text-white rounded-full font-semibold">
                                <span x-text="selectedIds.length"></span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                    <span x-text="selectedIds.length"></span> özellik seçildi
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Toplu işlem yapmak için bir aksiyon
                                    seçin</p>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <button type="button" @click="submitBulkAction('activate')"
                                class="px-4 py-2 bg-green-600 dark:bg-green-500 text-white rounded-lg hover:bg-green-700 dark:hover:bg-green-600 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-green-500 transition-all duration-200 flex items-center gap-2 font-medium text-sm shadow-md dark:shadow-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Aktif Et
                            </button>
                            <button type="button" @click="submitBulkAction('deactivate')"
                                class="px-4 py-2 bg-yellow-600 dark:bg-yellow-500 text-white rounded-lg hover:bg-yellow-700 dark:hover:bg-yellow-600 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-yellow-500 transition-all duration-200 flex items-center gap-2 font-medium text-sm shadow-md dark:shadow-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pasif Et
                            </button>
                            <button type="button" @click="submitBulkAction('delete')"
                                class="px-4 py-2 bg-red-600 dark:bg-red-500 text-white rounded-lg hover:bg-red-700 dark:hover:bg-red-600 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-red-500 transition-all duration-200 flex items-center gap-2 font-medium text-sm shadow-md dark:shadow-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Sil
                            </button>
                        </div>
                    </div>
                </div>
            @endif


            <!-- Özellikler Tablosu -->
            @if ($kategori->features->count() > 0)
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 text-left w-12">
                                    <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    ID</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Özellik Adı</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tür</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Durum</th>
                                <th scope="col"
                                    class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($kategori->features as $ozellik)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                    :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedIds.includes('{{ $ozellik->id }}') }">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <input type="checkbox" value="{{ $ozellik->id }}"
                                            class="feature-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            :checked="selectedIds.includes('{{ $ozellik->id }}')"
                                            @change="toggleSelect('{{ $ozellik->id }}')">
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ozellik->id }}</td>
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $ozellik->name ?? 'İsimsiz Özellik' }}
                                        @if ($ozellik->description)
                                            <span
                                                class="block text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($ozellik->description, 50) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            Metin
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $ozellik->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                            {{ $ozellik->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                        <a href="{{ route('admin.ozellikler.edit', $ozellik->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2"
                                            title="Düzenle">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-neo.empty-state title="Bu kategoride henüz özellik bulunmuyor"
                    description="Bir özellik ekleyerek başlayabilirsiniz" :actionHref="route('admin.ozellikler.create', ['category' => $kategori->id])"
                    actionText="Yeni Özellik Ekle" />
            @endif
        </div>
    </div>
@endsection
