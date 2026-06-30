@extends('admin.layouts.admin')

@section('title', 'AI Alan Önerileri - Property Hub')

@section('content')
    <div x-data="suggestionsManager()" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>AI Alan Önerileri</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">AI Alan Önerileri</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    AI motorunun önerdiği alanları inceleyin, onaylayın veya reddedin
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showGenerateModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Yeni Öneriler Üret
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Bekleyen</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['approved'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Onaylanan</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['applied'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Uygulanan</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Reddedilen</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $stats['rolled_back'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Geri Alınan</div>
            </div>
        </div>

        {{-- Filters --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Slug veya etiket ara..."
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                </div>
                <div class="w-full sm:w-40">
                    <select name="oneri_durumu"
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending" {{ request('oneri_durumu') === 'pending' ? 'selected' : '' }}>Bekleyen
                        </option>
                        <option value="approved" {{ request('oneri_durumu') === 'approved' ? 'selected' : '' }}>Onaylı
                        </option>
                        <option value="applied" {{ request('oneri_durumu') === 'applied' ? 'selected' : '' }}>Uygulanmış
                        </option>
                        <option value="rejected" {{ request('oneri_durumu') === 'rejected' ? 'selected' : '' }}>Reddedilmiş
                        </option>
                        <option value="rolled_back" {{ request('oneri_durumu') === 'rolled_back' ? 'selected' : '' }}>Geri
                            Alınmış</option>
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <select name="priority"
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Öncelikler</option>
                        <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Kritik</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Yüksek</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Orta</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Düşük</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select name="listing_type_id"
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Yayın Tipleri</option>
                        @foreach ($listingTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ request('listing_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->ad }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
                    Filtrele
                </button>
                @if (request()->hasAny(['search', 'oneri_durumu', 'priority', 'listing_type_id']))
                    <a href="{{ route('admin.property-hub.field-suggestions.index') }}"
                        class="px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        {{-- Suggestions Table --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Öneri
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tip / Grup
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Skor
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Öncelik
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
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                        @forelse($suggestions as $suggestion)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-all duration-200">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.property-hub.field-suggestions.show', $suggestion) }}"
                                        class="block hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        <div class="font-medium text-gray-900 dark:text-slate-100">
                                            {{ $suggestion->label }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $suggestion->slug }}
                                            @if ($suggestion->feature)
                                                · {{ $suggestion->feature->name }}
                                            @endif
                                        </div>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700 dark:text-slate-300">
                                        {{ $suggestion->field_type ?? '—' }}
                                    </div>
                                    @if ($suggestion->group_name)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $suggestion->group_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                        @if ($suggestion->total_score >= 70) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @elseif ($suggestion->total_score >= 40) bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $suggestion->total_score }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @switch($suggestion->priority)
                                            @case('critical') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @break
                                            @case('high') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 @break
                                            @case('medium') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endswitch">
                                        @switch($suggestion->priority)
                                            @case('critical')
                                                Kritik
                                            @break

                                            @case('high')
                                                Yüksek
                                            @break

                                            @case('medium')
                                                Orta
                                            @break

                                            @default
                                                Düşük
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @switch($suggestion->oneri_durumu)
                                            @case('pending') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 @break
                                            @case('approved') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                                            @case('applied') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 @break
                                            @case('rejected') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @break
                                            @case('rolled_back') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @break
                                        @endswitch">
                                        @switch($suggestion->oneri_durumu)
                                            @case('pending')
                                                Bekleyen
                                            @break

                                            @case('approved')
                                                Onaylı
                                            @break

                                            @case('applied')
                                                Uygulanmış
                                            @break

                                            @case('rejected')
                                                Reddedilmiş
                                            @break

                                            @case('rolled_back')
                                                Geri Alınmış
                                            @break
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.property-hub.field-suggestions.show', $suggestion) }}"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200"
                                            title="Detay">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @if ($suggestion->oneri_durumu === 'pending')
                                            <button @click="quickAction({{ $suggestion->id }}, 'approve')"
                                                class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-all duration-200"
                                                title="Onayla">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button @click="quickAction({{ $suggestion->id }}, 'reject')"
                                                class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200"
                                                title="Reddet">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif
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
                                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400">Henüz öneri bulunmuyor</p>
                                            <button @click="showGenerateModal = true"
                                                class="mt-4 text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-all duration-200">
                                                AI ile öneriler üret →
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($suggestions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
                        {{ $suggestions->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            {{-- Generate Modal --}}
            <div x-show="showGenerateModal" x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="showGenerateModal = false">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-700 p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">AI Önerileri Üret</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Kategori</label>
                            <select x-model="generateForm.main_category_id"
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Seçiniz</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Yayın Tipi</label>
                            <select x-model="generateForm.listing_type_id"
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Seçiniz</option>
                                @foreach ($listingTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->ad }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="showGenerateModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200">
                            İptal
                        </button>
                        <button @click="generateSuggestions()"
                            :disabled="generating || !generateForm.main_category_id || !generateForm.listing_type_id"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            <template x-if="generating">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <span x-text="generating ? 'Üretiliyor...' : 'Üret'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Notification --}}
            <div x-show="notification" x-transition
                class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white text-sm z-50"
                :class="notificationType === 'success' ? 'bg-green-600' : 'bg-red-600'" x-text="notification"
                @click="notification = null">
            </div>
        </div>

        <script>
            function suggestionsManager() {
                return {
                    showGenerateModal: false,
                    generating: false,
                    notification: null,
                    notificationType: 'success',
                    generateForm: {
                        main_category_id: '',
                        listing_type_id: '',
                    },

                    showNotification(message, type = 'success') {
                        this.notification = message;
                        this.notificationType = type;
                        setTimeout(() => this.notification = null, 4000);
                    },

                    async generateSuggestions() {
                        this.generating = true;
                        try {
                            const response = await fetch('{{ route('admin.property-hub.field-suggestions.generate') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(this.generateForm),
                            });

                            const data = await response.json();
                            if (data.success) {
                                this.showGenerateModal = false;
                                this.showNotification(data.message || 'Öneriler üretildi');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                this.showNotification(data.message || 'Hata oluştu', 'error');
                            }
                        } catch (e) {
                            this.showNotification('Ağ hatası', 'error');
                        } finally {
                            this.generating = false;
                        }
                    },

                    async quickAction(suggestionId, action) {
                        if (!confirm(action === 'approve' ? 'Bu öneriyi onaylamak istiyor musunuz?' :
                                'Bu öneriyi reddetmek istiyor musunuz?')) return;

                        try {
                            const response = await fetch(
                            `/admin/property-hub/field-suggestions/${suggestionId}/${action}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({}),
                            });

                            const data = await response.json();
                            if (data.success) {
                                this.showNotification(data.message || 'İşlem başarılı');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                this.showNotification(data.message || 'Hata oluştu', 'error');
                            }
                        } catch (e) {
                            this.showNotification('Ağ hatası', 'error');
                        }
                    }
                };
            }
        </script>
    @endsection
