@extends('admin.layouts.admin')

@section('title', 'Config Seçenekleri Yönetimi')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span
                            class="px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200">
                            ✨ Yeni Sistem
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Config Seçenekleri Yönetimi</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Kategori ve Yayın Tipi bazlı config seçeneklerini yönetin. Tüm 22 config seçeneği yönetilebilir.
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.ozellikler.index') }}"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200">
                        Özellik Yönetimi
                    </a>
                    <a href="{{ route('admin.config-options.create') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        + Yeni Config Ekle
                    </a>
                </div>
            </div>
        </div>

        {{-- Filtreler --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <form method="GET" action="{{ route('admin.config-options.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Kategori Filtresi --}}
                <div>
                    <label for="kategori_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Kategori
                    </label>
                    <select name="kategori_id" id="kategori_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white">
                        <option value="">Tümü</option>
                        @foreach ($kategoriler as $kategori)
                            <option value="{{ $kategori->id }}"
                                {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Yayın Tipi Filtresi --}}
                <div>
                    <label for="yayin_tipi_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Yayın Tipi
                    </label>
                    <select name="junction_id" id="junction_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white">
                        <option value="">Tümü</option>
                        @foreach ($yayinTipleri as $yayinTipi)
                            <option value="{{ $yayinTipi->id }}"
                                {{ request('yayin_tipi_id') == $yayinTipi->id ? 'selected' : '' }}>
                                {{ $yayinTipi->kategori->name ?? '' }} - {{ $yayinTipi->yayin_tipi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Option Key Filtresi --}}
                <div>
                    <label for="option_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Option Key
                    </label>
                    <select name="option_key" id="option_key"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white">
                        <option value="">Tümü</option>
                        @foreach ($optionKeys as $key)
                            <option value="{{ $key }}" {{ request('option_key') == $key ? 'selected' : '' }}>
                                {{ $key }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Aktiflik Filtresi --}}
                <div>
                    <label for="aktiflik_durumu" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Aktiflik
                    </label>
                    <select name="aktiflik_durumu" id="aktiflik_durumu"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white">
                        <option value="active" {{ request('aktiflik_durumu') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="passive" {{ request('aktiflik_durumu') == 'passive' ? 'selected' : '' }}>Pasif</option>
                        <option value="all" {{ request('aktiflik_durumu') == 'all' ? 'selected' : '' }}>Tümü</option>
                    </select>
                </div>

                {{-- Filtre Butonları --}}
                <div class="md:col-span-4 flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Filtrele
                    </button>
                    <a href="{{ route('admin.config-options.index') }}"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200">
                        Temizle
                    </a>
                </div>
            </form>
        </div>

        {{-- Liste --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg overflow-hidden dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 dark:bg-slate-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Option Key
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Kategori
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Yayın Tipi
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Seçenek Sayısı
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                Durum
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($configOptions as $config)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if ($config->icon)
                                            <span class="text-lg">{{ $config->icon }}</span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $config->option_key }}
                                            </div>
                                            @if ($config->label)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $config->label }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $config->kategori->name ?? 'Genel' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $config->yayinTipi->yayin_tipi ?? 'Tümü' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $config->option_type === 'simple' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' : '' }}
                                        {{ $config->option_type === 'associative' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : '' }}
                                        {{ $config->option_type === 'object_array' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' : '' }}
                                        {{ $config->option_type === 'nested' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-200' : '' }}">
                                        {{ $config->option_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                        @if (is_array($config->option_value))
                                            {{ count($config->option_value) }}
                                        @else
                                            0
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full {{ $config->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200' }}">
                                        {{ $config->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.config-options.show', $config->id) }}"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Görüntüle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.config-options.edit', $config->id) }}"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="Düzenle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.config-options.duplicate', $config->id) }}"
                                            method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                title="Kopyala">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </form>
                                        <button type="button"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 delete-config-option"
                                            data-id="{{ $config->id }}"
                                            data-name="{{ $config->label ?? $config->option_key }}" title="Sil">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Config seçeneği
                                            bulunamadı</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Yeni bir config seçeneği ekleyerek başlayın.
                                        </p>
                                        <div class="mt-6">
                                            <a href="{{ route('admin.config-options.create') }}"
                                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:shadow-none">
                                                + Yeni Config Ekle
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($configOptions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    {{ $configOptions->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            // ✅ Delete işlemi için API Helper kullan
            document.querySelectorAll('.delete-config-option').forEach(button => {
                button.addEventListener('click', async function() {
                    const configId = this.dataset.id;
                    const configName = this.dataset.name || 'Config seçeneği';

                    // Onay al
                    if (!confirm(`"${configName}" config seçeneğini silmek istediğinize emin misiniz?`)) {
                        return;
                    }

                    // ✅ Loading Manager kullan
                    if (window.LoadingManager) {
                        window.LoadingManager.set(`config-option-delete-${configId}`, true, this);
                    } else {
                        this.disabled = true;
                    }

                    try {
                        // ✅ API Helper kullan (merkezi yönetim)
                        const endpoint = window.APIConfig?.admin?.configOptions?.destroy(configId) ||
                            `/admin/config-options/${configId}`;

                        await window.APIHelper?.request(endpoint, {
                            method: 'DELETE',
                        }, {
                            showLoading: false, // Loading Manager kullanıyoruz
                            loadingKey: `config-option-delete-${configId}`,
                        }) || await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    ?.content || '',
                                'X-HTTP-Method-Override': 'DELETE',
                            },
                        });

                        // ✅ NotificationHelper kullan
                        if (window.NotificationHelper) {
                            window.NotificationHelper.success('Config seçeneği başarıyla silindi ✅');
                        } else {
                            alert('Config seçeneği başarıyla silindi');
                        }

                        // Satırı kaldır
                        const row = this.closest('tr');
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        } else {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Config option delete error:', error);
                        // ✅ NotificationHelper kullan
                        if (window.NotificationHelper) {
                            window.NotificationHelper.error(error.message ||
                                'Silme işlemi sırasında hata oluştu ❌');
                        } else {
                            alert('Hata: ' + (error.message || 'Silme işlemi sırasında hata oluştu'));
                        }
                    } finally {
                        // ✅ Loading Manager kullan
                        if (window.LoadingManager) {
                            window.LoadingManager.set(`config-option-delete-${configId}`, false, this);
                        } else {
                            this.disabled = false;
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
