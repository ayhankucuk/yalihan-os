@extends('admin.layouts.admin')

@section('title', 'Nexus Studio: ' . $kategori->name)
@section('meta_description', 'Kategori özellik mirasını, yerel atamaları ve override akışını yönetin.')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="nexusStudio()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                    Nexus Studio
                    <span class="text-blue-600 dark:text-blue-400">{{ $kategori->name }}</span>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Kategori için miras alınan ve yerel özellikleri görselleştir, gerekirse override et.
                </p>
            </div>
            <a href="{{ route('admin.ilan-kategorileri.index') }}"
               class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-200 hover:text-gray-900 dark:hover:text-gray-100 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                ← Kategori Listesine Dön
            </a>
        </div>

        @php
            $featureGroups = collect($blueprint['feature_categories'] ?? []);
            $totalFeatures = $featureGroups->sum(function ($group) {
                return isset($group['features']) && is_array($group['features']) ? count($group['features']) : 0;
            });
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Sol Panel: Global Feature Pool --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm flex flex-col dark:shadow-none dark:border-slate-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Tüm Özellikler</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sistemde tanımlı global feature havuzu</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                        {{ $allFeatures->count() }} kayıt
                    </span>
                </div>
                <div class="flex-1 overflow-y-auto max-h-[480px] divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($allFeatures as $feature)
                        <div class="px-4 py-2.5 flex items-start justify-between hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                    {{ $feature->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Tip: <span class="font-medium">{{ $feature->type ?? 'boolean' }}</span>
                                    @if ($feature->category)
                                        • Grup: <span class="font-medium">{{ $feature->category->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <button @click="attachFeature({{ $feature->id }})"
                                    :disabled="directAssignments.includes({{ $feature->id }})"
                                    class="ml-2 inline-flex items-center px-2 py-1 text-[10px] font-medium rounded-md transition-colors duration-200
                                        dark:focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400
                                        disabled:opacity-40 disabled:cursor-not-allowed"
                                    :class="directAssignments.includes({{ $feature->id }})
                                        ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600 cursor-not-allowed'
                                        : 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800/40 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2'">
                                <template x-if="!directAssignments.includes({{ $feature->id }})">
                                    <span>+ Ekle</span>
                                </template>
                                <template x-if="directAssignments.includes({{ $feature->id }})">
                                    <span>✓ Eklendi</span>
                                </template>
                            </button>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Tanımlı özellik bulunamadı.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Orta Panel: Active Blueprint --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm flex flex-col lg:col-span-1 dark:shadow-none dark:border-slate-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Aktif Blueprint</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Miras motorunun bu kategori için ürettiği efektif alan seti
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-200">
                        {{ $totalFeatures }} alan
                    </span>
                </div>
                <div class="flex-1 overflow-y-auto max-h-[480px] divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($featureGroups as $group)
                        @php
                            $features = collect($group['features'] ?? []);
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-200 text-xs font-semibold">
                                        {{ strtoupper(substr($group['name'] ?? 'Grup', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                            {{ $group['name'] ?? 'Genel Özellikler' }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ $features->count() }} alan
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if ($features->isNotEmpty())
                                <div class="space-y-1.5">
                                    @foreach ($features as $feature)
                                        @php
                                            $isLocal = in_array($feature['id'], $directAssignments, true);
                                        @endphp
                                        <div class="flex items-start justify-between px-2 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                                    {{ $feature['name'] }}
                                                </p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                    Tip: <span class="font-medium">{{ $feature['type'] ?? 'boolean' }}</span>
                                                    @if (!empty($feature['required']))
                                                        • <span class="text-red-600 dark:text-red-300 font-semibold">Zorunlu</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="ml-2 flex flex-col items-end gap-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                    :class="directAssignments.includes({{ $feature['id'] }})
                                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200'
                                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'">
                                                    <span x-text="directAssignments.includes({{ $feature['id'] }}) ? 'Local (Bu kategori)' : 'Miras (Parent)'"></span>
                                                </span>
                                                <button @click="overrideFeature({{ $feature['id'] }})"
                                                    x-show="!directAssignments.includes({{ $feature['id'] }})"
                                                    type="button"
                                                    class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800/40 dark:hover:bg-blue-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200">
                                                    Override
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bu grupta alan yok.</p>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Bu kategori için henüz blueprint üretilmemiş görünüyor.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Sağ Panel: Kategori Bilgisi / İnheritance Flag --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm flex flex-col dark:shadow-none dark:border-slate-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Kategori Bilgisi</h2>
                </div>
                <div class="p-4 space-y-3 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ad</p>
                        <p class="font-medium">{{ $kategori->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Seviye</p>
                        <p class="font-medium">
                            @if ($kategori->seviye === 0)
                                Ana Kategori
                            @elseif ($kategori->seviye === 1)
                                Alt Kategori
                            @else
                                Yayın Tipi
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Üst Kategori</p>
                        @if ($kategori->parent)
                            <a href="{{ route('admin.ilan-kategorileri.nexus-studio', $kategori->parent_id) }}"
                               class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $kategori->parent->name }}
                            </a>
                        @else
                            <p class="font-medium">Yok (Root)</p>
                        @endif
                    </div>

                    @php
                        $inheritFlag = $kategori->getAttribute('inherit_from_parent');
                        $inheritLabel = $inheritFlag === null || $inheritFlag === true
                            ? 'Üst kategoriden miras alır'
                            : 'Miras zincirini burada keser';
                        $inheritIsOn = $inheritFlag === null || $inheritFlag === true;
                    @endphp

                    <div class="pt-2 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Miras Davranışı</p>
                            <button @click="toggleInheritance()"
                                    type="button"
                                    class="inline-flex items-center px-2 py-1 text-[10px] font-medium text-white rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-2"
                                    :class="inheritFlag
                                        ? 'bg-rose-600 hover:bg-rose-700 dark:bg-rose-700 dark:hover:bg-rose-800 focus:ring-rose-500 dark:focus:ring-rose-400'
                                        : 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 focus:ring-emerald-500 dark:focus:ring-emerald-400'">
                                <span x-text="inheritFlag ? 'Mirasi Kes' : 'Mirasi A\u00e7'"></span>
                            </button>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold"
                            :class="inheritFlag
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                                : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200'">
                            <span x-text="inheritFlag ? 'Üst kategoriden miras alır' : 'Miras zincirini burada keser'"></span>
                        </span>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Anahtarla miras motoru davranışını değiştir. Kapalıysa bu kategori tamamen bağımsız (Standalone) olur.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function nexusStudio() {
            return {
                // State
                directAssignments: @json($directAssignments),
                inheritFlag: {{ $inheritFlag === null || $inheritFlag === true ? 'true' : 'false' }},
                kategoriId: {{ $kategori->id }},
                csrfToken: '{{ csrf_token() }}',

                // DNA Kopyalayıcı: Override Feature
                async overrideFeature(featureId) {
                    if (!confirm('Bu özelliği bu kategoriye yerel olarak kopyalamak istediğinizden emin misiniz?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/ilan-kategorileri/${this.kategoriId}/override-feature`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ feature_id: featureId })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Add to local assignments
                            this.directAssignments.push(featureId);
                            alert(data.message || 'Özellik başarıyla kopyalandı.');
                        } else {
                            alert(data.message || 'Özellik kopyalanamadı.');
                        }
                    } catch (error) {
                        console.error('Override error:', error);
                        alert('Bir hata oluştu: ' + error.message);
                    }
                },

                // Miras Kesici: Toggle Inheritance
                async toggleInheritance() {
                    try {
                        const response = await fetch(`/admin/ilan-kategorileri/${this.kategoriId}/toggle-inheritance`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Update flag
                            this.inheritFlag = data.inherit_from_parent;
                            alert(data.message || 'Miras ayarı değiştirildi.');
                        } else {
                            alert(data.message || 'Miras ayarı değiştirilemedi.');
                        }
                    } catch (error) {
                        console.error('Toggle inheritance error:', error);
                        alert('Bir hata oluştu: ' + error.message);
                    }
                },

                // Global Havuzdan Ekleme: Attach Feature
                async attachFeature(featureId) {
                    if (this.directAssignments.includes(featureId)) {
                        alert('Bu özellik zaten bu kategoriye atanmış.');
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/ilan-kategorileri/${this.kategoriId}/attach-feature`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ feature_id: featureId })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Add to local assignments
                            this.directAssignments.push(featureId);
                            alert(data.message || 'Özellik başarıyla eklendi.');
                        } else {
                            alert(data.message || 'Özellik eklenemedi.');
                        }
                    } catch (error) {
                        console.error('Attach feature error:', error);
                        alert('Bir hata oluştu: ' + error.message);
                    }
                }
            };
        }
    </script>
    @endpush
@endsection
