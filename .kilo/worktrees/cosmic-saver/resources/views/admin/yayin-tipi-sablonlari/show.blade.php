@extends('admin.layouts.app')

@section('title', $sablon->ad . ' Şablonu')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Breadcrumb --}}
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('admin.property-hub.yayin-tipi-sablonlari.index') }}"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        Yayın Tipi Şablonları
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 dark:text-slate-200 font-medium dark:text-slate-300">{{ $sablon->ad }}</li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                    {{ $sablon->ad }}
                    <code
                        class="ml-3 px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded dark:bg-slate-900">
                        {{ $sablon->slug }}
                    </code>
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Bu şablona atanan özellikler, "{{ $sablon->ad }}" yayın tipindeki tüm ilanlarda görünecek.
                </p>
            </div>
            <a href="{{ route('admin.property-hub.yayin-tipi-sablonlari.index') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-slate-300">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Geri
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Atanmış Özellikler --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                            Atanmış Özellikler
                            <span
                                class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                {{ $sablon->featureAssignments->count() }}
                            </span>
                        </h2>
                    </div>

                    <div class="p-4">
                        @forelse($groupedAssignments as $categoryName => $items)
                            <div class="mb-6 last:mb-0">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-slate-200 mb-3 flex items-center dark:text-slate-300">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    {{ $categoryName }}
                                    <span class="ml-2 text-xs text-gray-500">({{ count($items) }})</span>
                                </h3>
                                <div class="space-y-2">
                                    @foreach ($items as $item)
                                        @php
                                            $feature = $item['feature'];
                                            $assignment = $item['assignment'];
                                        @endphp
                                        <div
                                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg group hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-slate-900">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                    {{ $feature->name }}
                                                </span>
                                                @if ($assignment->is_required)
                                                    <span
                                                        class="ml-2 px-1.5 py-0.5 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded">
                                                        Zorunlu
                                                    </span>
                                                @endif
                                                <code class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $feature->slug }}
                                                </code>
                                            </div>
                                            <button type="button" onclick="removeFeature({{ $feature->id }})"
                                                class="opacity-0 group-hover:opacity-100 p-1.5 text-red-500 hover:text-red-700 hover:bg-red-100 dark:hover:bg-red-900/50 rounded transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Özellik yok</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Sağ taraftan özellik ekleyerek başlayın.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Eklenebilir Özellikler --}}
            <div>
                <div
                    class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 sticky top-4 dark:shadow-none dark:border-slate-700">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                            Özellik Ekle
                        </h2>
                        <input type="text" id="feature-search" placeholder="Özellik ara..."
                            class="mt-3 w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="p-4 max-h-[60vh] overflow-y-auto" id="available-features">
                        @forelse($availableFeatures as $categoryName => $categoryFeatures)
                            <div class="mb-4 last:mb-0 feature-category">
                                <h3
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    {{ $categoryName }}
                                </h3>
                                <div class="space-y-1">
                                    @foreach ($categoryFeatures as $feature)
                                        <button type="button"
                                            onclick="assignFeature({{ $feature->id }}, '{{ addslashes($feature->name) }}')"
                                            class="feature-item w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors dark:text-slate-300"
                                            data-name="{{ strtolower($feature->name) }}" data-slug="{{ $feature->slug }}">
                                            <span class="flex items-center justify-between">
                                                <span>{{ $feature->name }}</span>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                                Tüm özellikler atanmış! 🎉
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const sablonId = {{ $sablon->id }};

            // Özellik arama
            document.getElementById('feature-search').addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                document.querySelectorAll('.feature-item').forEach(item => {
                    const name = item.dataset.name;
                    const slug = item.dataset.slug;
                    const match = name.includes(search) || slug.includes(search);
                    item.style.display = match ? '' : 'none';
                });

                document.querySelectorAll('.feature-category').forEach(cat => {
                    const hasVisibleItems = cat.querySelector('.feature-item:not([style*="display: none"])');
                    cat.style.display = hasVisibleItems ? '' : 'none';
                });
            });

            // Özellik ata
            async function assignFeature(featureId, featureName) {
                try {
                    const response = await fetch(`/admin/property-hub/yayin-tipi-sablonlari/${sablonId}/assign`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            feature_id: featureId
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Sayfayı yenile
                        window.location.reload();
                    } else {
                        alert(data.message || 'Bir hata oluştu');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                }
            }

            // Özellik kaldır
            async function removeFeature(featureId) {
                if (!confirm('Bu özelliği kaldırmak istediğinize emin misiniz?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/property-hub/yayin-tipi-sablonlari/${sablonId}/remove`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            feature_id: featureId
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Bir hata oluştu');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                }
            }
        </script>
    @endpush
@endsection
