@extends('admin.layouts.admin')

@section('title', 'Özellik Yönetimi - Property Hub')

@section('content')
    <div x-data="featuresManager()" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>Özellikler</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Özellik Yönetimi</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.property-hub.features.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yeni Özellik
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Özellik ara..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                </div>
                <div class="w-full sm:w-48">
                    <select name="category_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Kategoriler</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <select name="aktiflik"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Durumlar</option>
                        <option value="active" {{ request('aktiflik') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('aktiflik') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-all duration-200">
                    Filtrele
                </button>
                @if (request()->hasAny(['search', 'category_id', 'aktiflik']))
                    <a href="{{ route('admin.property-hub.features.index') }}"
                        class="px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        {{-- Stats Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $features->total() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Özellik</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $features->where('aktiflik_durumu', App\Enums\AktiflikDurumu::AKTIF)->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Aktif</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                    {{ $features->where('aktiflik_durumu', App\Enums\AktiflikDurumu::PASIF)->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pasif</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $categories->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Kategori</div>
            </div>
        </div>

        {{-- Features Table --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Özellik
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tip
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Kategori
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Kullanım
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($features as $feature)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                            @if ($feature->icon)
                                                <i class="{{ $feature->icon }} text-blue-600 dark:text-blue-400"></i>
                                            @else
                                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-slate-100">
                                                {{ $feature->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $feature->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($feature->type)
                                        @case('boolean') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 @break
                                        @case('number') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                                        @case('select') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 @break
                                        @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endswitch
                                ">
                                        {{ ucfirst($feature->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-slate-300">
                                    {{ $feature->category?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $feature->assignments_count ?? 0 }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">atama</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="toggleFeature({{ $feature->id }})"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                                        {{ $feature->aktiflik_durumu ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                        <span class="sr-only">Durumu değiştir</span>
                                        <span
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                        {{ $feature->aktiflik_durumu ? 'translate-x-5' : 'translate-x-0' }}">
                                        </span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.property-hub.features.edit', $feature) }}"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button @click="archiveFeature({{ $feature->id }}, '{{ $feature->name }}')"
                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">Özellik bulunamadı</p>
                                        <a href="{{ route('admin.property-hub.features.create') }}"
                                            class="mt-4 text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-all duration-200">
                                            İlk özelliği oluştur →
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($features->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
                    {{ $features->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function featuresManager() {
            return {
                async toggleFeature(featureId) {
                    try {
                        const response = await fetch(`/admin/property-hub/features/${featureId}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Toggle error:', error);
                    }
                },

                async archiveFeature(featureId, featureName) {
                    if (!confirm(`"${featureName}" özelliğini arşivlemek istediğinize emin misiniz?`)) {
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/property-hub/features/${featureId}/archive`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Archive error:', error);
                    }
                }
            };
        }
    </script>
@endsection
