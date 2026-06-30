@extends('admin.layouts.admin')

@section('title', 'Config Seçeneği Detayları')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.config-options.index') }}"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                            @if ($configOption->icon)
                                <span>{{ $configOption->icon }}</span>
                            @endif
                            {{ $configOption->label ?? $configOption->option_key }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $configOption->description ?? 'Config seçeneği detayları' }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.config-options.edit', $configOption->id) }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Düzenle
                    </a>
                    <button type="button" id="duplicate-config-option"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all duration-200">
                        Kopyala
                    </button>
                    <script>
                        // ✅ Duplicate işlemi için API Helper kullan
                        document.getElementById('duplicate-config-option')?.addEventListener('click', async function() {
                            const button = this;

                            // ✅ Loading Manager kullan
                            if (window.LoadingManager) {
                                window.LoadingManager.set('config-option-duplicate', true, button);
                            } else {
                                button.disabled = true;
                                button.textContent = 'Kopyalanıyor...';
                            }

                            try {
                                // ✅ API Helper kullan (merkezi yönetim)
                                const endpoint = window.APIConfig?.admin?.configOptions?.duplicate({{ $configOption->id }}) ||
                                    '{{ route('admin.config-options.duplicate', $configOption->id) }}';

                                const result = await window.APIHelper?.request(endpoint, {
                                    method: 'POST',
                                }, {
                                    showLoading: false, // Loading Manager kullanıyoruz
                                    loadingKey: 'config-option-duplicate',
                                }) || await fetch(endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                            '',
                                    },
                                });

                                // Fallback için response handling
                                if (!window.APIHelper && result instanceof Response) {
                                    if (!result.ok) {
                                        const errorData = await result.json().catch(() => ({}));
                                        throw new Error(errorData.message || `HTTP ${result.status}`);
                                    }
                                    const data = await result.json();
                                    if (data.success !== false) {
                                        // ✅ NotificationHelper kullan
                                        if (window.NotificationHelper) {
                                            window.NotificationHelper.success('Config seçeneği başarıyla kopyalandı ✅');
                                        } else {
                                            alert('Config seçeneği başarıyla kopyalandı');
                                        }
                                        if (data.data?.id) {
                                            window.location.href = `/admin/config-options/${data.data.id}/edit`;
                                        } else {
                                            window.location.reload();
                                        }
                                    } else {
                                        throw new Error(data.message || 'Kopyalama başarısız');
                                    }
                                } else {
                                    // API Helper kullanıldı
                                    if (result.success !== false) {
                                        // ✅ NotificationHelper kullan
                                        if (window.NotificationHelper) {
                                            window.NotificationHelper.success('Config seçeneği başarıyla kopyalandı ✅');
                                        } else {
                                            alert('Config seçeneği başarıyla kopyalandı');
                                        }
                                        if (result.data?.id) {
                                            window.location.href = `/admin/config-options/${result.data.id}/edit`;
                                        } else {
                                            window.location.reload();
                                        }
                                    } else {
                                        throw new Error(result.message || 'Kopyalama başarısız');
                                    }
                                }
                            } catch (error) {
                                console.error('Config option duplicate error:', error);
                                // ✅ NotificationHelper kullan
                                if (window.NotificationHelper) {
                                    window.NotificationHelper.error(error.message || 'Kopyalama sırasında hata oluştu ❌');
                                } else {
                                    alert('Hata: ' + (error.message || 'Kopyalama sırasında hata oluştu'));
                                }
                            } finally {
                                // ✅ Loading Manager kullan
                                if (window.LoadingManager) {
                                    window.LoadingManager.set('config-option-duplicate', false, button);
                                } else {
                                    button.disabled = false;
                                    button.textContent = 'Kopyala';
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>

        {{-- Genel Bilgiler --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Sol Kolon: Temel Bilgiler --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Temel Bilgiler</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Option Key</dt>
                        <dd
                            class="mt-1 text-sm text-gray-900 dark:text-white font-mono bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded dark:bg-slate-900 dark:text-slate-100">
                            {{ $configOption->option_key }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Option Type</dt>
                        <dd class="mt-1">
                            <span
                                class="px-3 py-1 text-sm font-semibold rounded-full
                                {{ $configOption->option_type === 'simple' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' : '' }}
                                {{ $configOption->option_type === 'associative' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : '' }}
                                {{ $configOption->option_type === 'object_array' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' : '' }}
                                {{ $configOption->option_type === 'nested' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-200' : '' }}">
                                {{ $configOption->option_type }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $configOption->kategori->name ?? 'Genel (Tüm Kategoriler)' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Yayın Tipi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $configOption->yayinTipi ? ($configOption->yayinTipi->kategori->name ?? '') . ' - ' . $configOption->yayinTipi->yayin_tipi : 'Tüm Yayın Tipleri' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Durum</dt>
                        <dd class="mt-1">
                            <span
                                class="px-3 py-1 text-sm font-semibold rounded-full {{ $configOption->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200' }}">
                                {{ $configOption->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sıralama</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $configOption->display_order }}
                        </dd>
                    </div>
                    @if ($configOption->description)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Açıklama</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $configOption->description }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Sağ Kolon: İstatistikler --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">İstatistikler</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Seçenek Sayısı</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            @if (is_array($configOption->option_value))
                                {{ count($configOption->option_value) }}
                            @else
                                0
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Oluşturulma Tarihi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $configOption->created_at ? $configOption->created_at->format('d.m.Y H:i') : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Son Güncelleme</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $configOption->updated_at ? $configOption->updated_at->format('d.m.Y H:i') : '-' }}
                        </dd>
                    </div>
                    @if ($configOption->deleted_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Silinme Tarihi</dt>
                            <dd class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ $configOption->deleted_at->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Option Value Detayları --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Seçenek Değerleri</h2>
                <div class="flex gap-2">
                    <button onclick="copyToClipboard()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200">
                        JSON Kopyala
                    </button>
                    <button onclick="toggleView()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        <span id="view-toggle-text">Tablo Görünümü</span>
                    </button>
                </div>
            </div>

            {{-- JSON Görünümü --}}
            <div id="json-view" class="mb-6">
                <pre
                    class="bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-4 overflow-x-auto text-sm dark:border-slate-700"><code id="json-content">{{ json_encode($configOption->option_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>

            {{-- Tablo Görünümü (Simple & Associative) --}}
            <div id="table-view" class="hidden">
                @if ($configOption->option_type === 'simple')
                    <div class="space-y-2">
                        @foreach ($configOption->option_value as $index => $value)
                            <div
                                class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:border-slate-700">
                                <span
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400 w-12">{{ $index + 1 }}.</span>
                                <span class="text-sm text-gray-900 dark:text-white flex-1 dark:text-slate-100">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif ($configOption->option_type === 'associative')
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase">
                                        Key
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase">
                                        Value
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($configOption->option_value as $key => $value)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $key }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $value }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($configOption->option_type === 'object_array')
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase">
                                        #
                                    </th>
                                    @if (isset($configOption->option_value[0]))
                                        @foreach (array_keys($configOption->option_value[0]) as $field)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase">
                                                {{ ucfirst(str_replace('_', ' ', $field)) }}
                                            </th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($configOption->option_value as $index => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $index + 1 }}
                                        </td>
                                        @foreach ($item as $key => $value)
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                @if ($key === 'icon' && $value)
                                                    <span class="text-lg">{{ $value }}</span>
                                                @elseif ($key === 'color' && $value)
                                                    <span
                                                        class="px-2 py-1 text-xs rounded {{ $value }}">{{ $value }}</span>
                                                @else
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($configOption->option_type === 'nested')
                    <div class="space-y-4">
                        @foreach ($configOption->option_value as $sectionKey => $sectionValue)
                            <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 dark:border-slate-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">
                                    {{ ucfirst(str_replace('_', ' ', $sectionKey)) }}
                                </h3>
                                @if (is_array($sectionValue))
                                    <div class="ml-4 space-y-2">
                                        @foreach ($sectionValue as $key => $value)
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-32">
                                                    {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                </span>
                                                <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                                    @if (is_array($value))
                                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-900 dark:text-white ml-4 dark:text-slate-100">{{ $sectionValue }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let isTableView = false;

            function toggleView() {
                isTableView = !isTableView;
                const jsonView = document.getElementById('json-view');
                const tableView = document.getElementById('table-view');
                const toggleText = document.getElementById('view-toggle-text');

                if (isTableView) {
                    jsonView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                    toggleText.textContent = 'JSON Görünümü';
                } else {
                    jsonView.classList.remove('hidden');
                    tableView.classList.add('hidden');
                    toggleText.textContent = 'Tablo Görünümü';
                }
            }

            function copyToClipboard() {
                const jsonContent = document.getElementById('json-content').textContent;
                navigator.clipboard.writeText(jsonContent).then(() => {
                    // ✅ NotificationHelper kullan
                    if (window.NotificationHelper) {
                        window.NotificationHelper.success('JSON kopyalandı ✅');
                    } else {
                        alert('JSON kopyalandı!');
                    }
                }).catch(err => {
                    console.error('Kopyalama hatası:', err);
                    // ✅ NotificationHelper kullan
                    if (window.NotificationHelper) {
                        window.NotificationHelper.error('Kopyalama başarısız ❌');
                    } else {
                        alert('Kopyalama başarısız!');
                    }
                });
            }
        </script>
    @endpush
@endsection
