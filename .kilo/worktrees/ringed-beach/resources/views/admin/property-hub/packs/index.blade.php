@extends('admin.layouts.admin')

@section('title', 'Özellik Paketleri - Property Hub')

@section('content')
    <div x-data="packManager()" class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>Özellik Paketleri</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Özellik Paketleri</h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Birden fazla özelliği gruplar halinde yönetin</p>
            </div>
            <button @click="showCreateModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yeni Paket
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $packs->total() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Paket</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $packs->where('aktiflik_durumu', true)->count() }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aktif Paket</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $packs->sum('features_count') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Özellik</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Packs Grid --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none">
            @if ($packs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                    @foreach ($packs as $pack)
                        <div
                            class="group border border-gray-200 dark:border-slate-800 rounded-lg p-4 hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-200">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-slate-100">{{ $pack->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pack->slug }}</p>
                                </div>
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $pack->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }} dark:text-slate-200">
                                    {{ $pack->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                </span>
                            </div>

                            @if ($pack->description)
                                <p class="text-sm text-gray-600 dark:text-slate-200 mb-3 line-clamp-2">
                                    {{ $pack->description }}
                                </p>
                            @endif

                            <div class="flex items-center gap-2 mb-4">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded text-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ $pack->features_count }} Özellik
                                </span>
                            </div>

                            {{-- Feature List Preview --}}
                            @if ($pack->features->count() > 0)
                                <div class="mb-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($pack->features->take(4) as $feature)
                                            <span
                                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 text-xs rounded dark:bg-slate-900 dark:text-slate-300">
                                                {{ $feature->name }}
                                            </span>
                                        @endforeach
                                        @if ($pack->features->count() > 4)
                                            <span
                                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded dark:bg-slate-900">
                                                +{{ $pack->features->count() - 4 }} daha
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div
                                class="flex items-center gap-2 pt-3 border-t border-gray-200 dark:border-slate-800 opacity-0 group-hover:opacity-100 transition-all duration-200">
                                <button @click="applyPack({{ $pack->id }})"
                                    class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-all duration-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Uygula
                                </button>
                                <button @click="editPack({{ $pack->id }}, {{ $pack->toJson() }})"
                                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deletePack({{ $pack->id }}, '{{ $pack->name }}')"
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 pb-6">
                    {{ $packs->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4 dark:bg-slate-900">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-1">Henüz paket yok</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">İlk özellik paketinizi oluşturun</p>
                    <button @click="resetForm(); showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yeni Paket Oluştur
                    </button>
                </div>
            @endif
        </div>

        {{-- Create/Edit Modal (Premium Redesign) --}}
        <template x-teleport="body">
            <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm" @click="showCreateModal = false"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        {{-- Modal Header --}}
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-slate-800 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-slate-100" x-text="isEditing ? 'Paketi Düzenle' : 'Yeni Paket Oluştur'"></h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Özellikleri gruplayarak şablonlara hızlıca atayın</p>
                                </div>
                                <button @click="showCreateModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow transition-all duration-200 dark:shadow-none dark:bg-slate-900">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal Body --}}
                        <div class="flex-1 overflow-y-auto">
                            <form @submit.prevent="savePack" class="h-full flex flex-col md:flex-row">
                                {{-- Left Panel: Pack Details --}}
                                <div class="w-full md:w-1/3 p-8 border-r border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-gray-800/50 space-y-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 dark:text-slate-100 mb-2">
                                            Paket Adı <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="newPack.name"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 py-3"
                                            placeholder="Örn: Site Özellikleri Paketi" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 dark:text-slate-100 mb-2">
                                            Açıklama
                                        </label>
                                        <textarea x-model="newPack.description" rows="4"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            placeholder="Bu paketin içeriği ve kullanım amacı hakkında bilgi..."></textarea>
                                    </div>

                                    <div x-show="isEditing" class="flex items-center gap-3">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="newPack.aktiflik_durumu" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 dark:text-white">Aktiflik Durumu</span>
                                        </label>
                                    </div>

                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                                        <div class="flex items-start gap-3">
                                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                                Paketler, benzer özellikleri (örn: Isıtma sistemleri, Manzara seçenekleri) tek seferde şablonlara eklemenizi sağlar.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right Panel: Feature Selection --}}
                                <div class="w-full md:w-2/3 p-8 flex flex-col h-[600px] md:h-auto">
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-4">
                                            <label class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                                                Özellik Seçimi
                                            </label>
                                            <span class="text-xs px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-slate-200 rounded-full dark:bg-slate-900">
                                                <span x-text="newPack.feature_ids.length"></span> seçildi
                                            </span>
                                        </div>

                                        {{-- Search --}}
                                        <div class="relative">
                                            <input type="text" x-model="featureSearch" placeholder="Özellik ara..."
                                                class="w-full rounded-xl border-gray-200 dark:border-slate-800 dark:bg-slate-900 pl-10 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                                            <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="flex-1 overflow-y-auto pr-2 space-y-6 custom-scrollbar">
                                        @foreach ($features->groupBy(fn($f) => $f->category?->name ?? 'Genel') as $categoryName => $categoryFeatures)
                                            <div x-show="matchesSearch('{{ $categoryName }}', [{{ $categoryFeatures->pluck('name')->map(fn($n) => "'$n'")->implode(',') }}])">
                                                <h3 class="sticky top-0 z-10 bg-white dark:bg-slate-900 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                                                    {{ $categoryName }}
                                                    <span class="h-px flex-1 bg-gray-100 dark:bg-gray-700 dark:bg-slate-900"></span>
                                                </h3>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    @foreach ($categoryFeatures as $feature)
                                                        <label x-show="matchesFeature('{{ $feature->name }}')"
                                                            class="group cursor-pointer relative flex items-center p-3 rounded-xl border transition-all duration-200"
                                                            :class="newPack.feature_ids.includes({{ $feature->id }})
                                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10 ring-1 ring-blue-500'
                                                                : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-sm'">

                                                            <div class="flex items-center h-5">
                                                                <input type="checkbox"
                                                                    :checked="newPack.feature_ids.includes({{ $feature->id }})"
                                                                    @change="toggleFeature({{ $feature->id }})"
                                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition-colors duration-200">
                                                            </div>
                                                            <div class="ml-3 flex-1">
                                                                <span class="block text-sm font-medium transition-colors duration-200"
                                                                    :class="newPack.feature_ids.includes({{ $feature->id }}) ? 'text-blue-900 dark:text-blue-100' : 'text-gray-700 dark:text-slate-200 group-hover:text-gray-900 dark:group-hover:text-white'">
                                                                    {{ $feature->name }}
                                                                </span>
                                                                <span class="block text-xs text-gray-500 dark:text-gray-500">{{ $feature->slug }}</span>
                                                            </div>

                                                            {{-- Type Badge --}}
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded border ml-2 text-gray-400 border-gray-100 dark:border-slate-800">
                                                                {{ substr($feature->type, 0, 3) }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-8 py-5 border-t border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-3 dark:bg-slate-900">
                            <button type="button" @click="showCreateModal = false"
                                class="px-6 py-2.5 text-gray-700 dark:text-slate-200 font-medium hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all duration-200 dark:text-slate-300">
                                İptal
                            </button>
                            <button @click="savePack"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/20 transition-all duration-200 transform hover:scale-[1.02]">
                                <svg x-show="!isEditing" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <svg x-show="isEditing" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="isEditing ? 'Güncelle' : 'Paketi Oluştur'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Apply Modal --}}
        <template x-teleport="body">
            <div x-show="showApplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="showApplyModal = false"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-lg w-full">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Paketi Uygula</h2>
                            <p class="text-gray-600 dark:text-slate-200 mb-4">
                                Bu paketin özelliklerini hangi şablonlara uygulamak istiyorsunuz?
                            </p>

                            <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg dark:border-slate-700">
                                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 dark:bg-slate-900">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Master Şablonlar</span>
                                </div>
                                @foreach ($templates as $yayinTipi)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-all duration-200">
                                        <input type="checkbox"
                                            :checked="selectedTemplateIds.includes({{ $yayinTipi->id }})"
                                            @change="toggleTemplate({{ $yayinTipi->id }})"
                                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $yayinTipi->name ?? 'Yayın Tipi' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span x-text="selectedTemplateIds.length"></span> şablon seçildi
                            </p>

                            <div class="flex items-center justify-end gap-3 pt-4">
                                <button type="button" @click="showApplyModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-slate-200 hover:text-gray-900 dark:hover:text-white transition-all duration-200 dark:text-slate-300">
                                    İptal
                                </button>
                                <button @click="confirmApply" :disabled="selectedTemplateIds.length === 0"
                                    :class="selectedTemplateIds.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                                    Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            function packManager() {
                return {
                    showCreateModal: false,
                    showApplyModal: false,
                    isEditing: false,
                    currentPackId: null,
                    selectedPackId: null,
                    selectedTemplateIds: [],
                    featureSearch: '',
                    newPack: {
                        name: '',
                        description: '',
                        aktiflik_durumu: true,
                        feature_ids: []
                    },

                    resetForm() {
                        this.isEditing = false;
                        this.currentPackId = null;
                        this.newPack = {
                            name: '',
                            description: '',
                            aktiflik_durumu: true,
                            feature_ids: []
                        };
                        this.featureSearch = '';
                    },

                    matchesFeature(name) {
                        if (!this.featureSearch) return true;
                        return name.toLowerCase().includes(this.featureSearch.toLowerCase());
                    },

                    matchesSearch(categoryName, features) {
                        if (!this.featureSearch) return true;
                        return categoryName.toLowerCase().includes(this.featureSearch.toLowerCase()) ||
                               features.some(f => f.toLowerCase().includes(this.featureSearch.toLowerCase()));
                    },

                    toggleFeature(featureId) {
                        const index = this.newPack.feature_ids.indexOf(featureId);
                        if (index === -1) {
                            this.newPack.feature_ids.push(featureId);
                        } else {
                            this.newPack.feature_ids.splice(index, 1);
                        }
                    },

                    toggleTemplate(templateId) {
                        const index = this.selectedTemplateIds.indexOf(templateId);
                        if (index === -1) {
                            this.selectedTemplateIds.push(templateId);
                        } else {
                            this.selectedTemplateIds.splice(index, 1);
                        }
                    },

                    editPack(packId, packData) {
                        this.resetForm();
                        this.isEditing = true;
                        this.currentPackId = packId;
                        this.newPack.name = packData.name;
                        this.newPack.description = packData.description;
                        this.newPack.aktiflik_durumu = !!packData.aktiflik_durumu;
                        this.newPack.feature_ids = packData.features.map(f => f.id);
                        this.showCreateModal = true;
                    },

                    async savePack() {
                        if (this.newPack.feature_ids.length === 0) {
                            alert('En az bir özellik seçmelisiniz');
                            return;
                        }

                        const url = this.isEditing
                            ? `{{ url('admin/property-hub/packs') }}/${this.currentPackId}`
                            : '{{ route('admin.property-hub.packs.store') }}';

                        const method = this.isEditing ? 'PUT' : 'POST';

                        try {
                            const response = await fetch(url, {
                                method: method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(this.newPack)
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
                    },

                    async deletePack(packId, packName) {
                        if(!confirm(`"${packName}" paketini silmek istediğinize emin misiniz?`)) return;

                        try {
                            const response = await fetch(`{{ url('admin/property-hub/packs') }}/${packId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
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
                    },

                    applyPack(packId) {
                        this.selectedPackId = packId;
                        this.selectedTemplateIds = [];
                        this.showApplyModal = true;
                    },

                    async confirmApply() {
                        if (this.selectedTemplateIds.length === 0) {
                            alert('En az bir şablon seçmelisiniz');
                            return;
                        }

                        try {
                            const response = await fetch(
                                `{{ url('admin/property-hub/packs') }}/${this.selectedPackId}/apply`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        yayin_tipi_ids: this.selectedTemplateIds
                                    })
                                });

                            const data = await response.json();

                            if (data.success) {
                                this.showApplyModal = false;
                                alert(data.message || 'Paket başarıyla uygulandı');
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Bir hata oluştu');
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection
