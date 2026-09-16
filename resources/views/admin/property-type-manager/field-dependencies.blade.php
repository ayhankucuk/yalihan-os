@extends('admin.layouts.admin')

@php
    // ✅ FIX: Null kontrolü eklendi
    $yayinTipleri = $yayinTipleri ?? collect();
    $assignmentCounts = $assignmentCounts ?? [];

    $propertyTypesSummary = $yayinTipleri
        ->map(function ($yayinTipi) use ($assignmentCounts) {
            $slug = $yayinTipi->slug ?? $yayinTipi->yayin_tipi;
            $yayinTipiId = $yayinTipi->id;

            $count = $assignmentCounts[(string)$yayinTipiId] ?? $assignmentCounts[$yayinTipiId] ?? 0;

            return [
                'id' => $yayinTipiId,
                'slug' => $slug,
                'name' => $yayinTipi->yayin_tipi,
                'count' => $count,
            ];
        })
        ->values();

    $defaultPropertyType = $propertyTypesSummary->first();
@endphp

@section('title', 'Özellik Yönetimi - ' . $kategori->name)

@section('styles')
    <style>
        /* ✅ SAB: Alpine.js yüklenmeden önce tab içeriklerini gizle */
        [x-cloak] {
            display: none !important;
        }

        /* Tab içerikleri için özel x-cloak kuralı */
        [x-show][x-cloak] {
            display: none !important;
        }

        .tab-button {
            @apply border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600;
        }

        .tab-button.active {
            @apply border-blue-500 text-blue-600 dark:text-blue-400;
        }

        .feature-card {
            @apply transition-all duration-300 hover:shadow-lg;
        }

        .feature-card:hover {
            @apply -translate-y-1;
        }
    </style>
@endsection

@section('content')
    <script>
        // ✅ SAB: Alpine.js data tanımını global fonksiyon olarak hazırla
        window.fieldDependenciesManager = function fieldDependenciesManagerFactory() {
            return {
                propertyTypes: {!! json_encode($propertyTypesSummary) !!},
                activeTab: {!! json_encode($defaultYayinTipiId ?? ($defaultPropertyType ? ($defaultPropertyType['id'] ?? null) : null)) !!},
                selectedPropertyTypeId: {!! json_encode($defaultYayinTipiId ?? ($defaultPropertyType ? ($defaultPropertyType['id'] ?? null) : null)) !!},
                selectedPropertyTypeName: '',
                showAddFeatureModal: false,
                showAddLogicModal: false,
                selectedFeatures: [],
                logicForm: {
                    id: null,
                    field_slug: '',
                    depends_on: '',
                    condition: 'filled',
                    value: '',
                    is_active: true
                },

                init() {
                    if (this.activeTab) {
                        this.setPropertyType(this.activeTab);
                    } else if (this.propertyTypes && this.propertyTypes.length > 0) {
                        this.setPropertyType(this.propertyTypes[0].id);
                    }
                },

                ensureSelection() {
                    if (!this.activeTab && this.propertyTypes && this.propertyTypes.length) {
                        this.setPropertyType(this.propertyTypes[0].id);
                    }
                },

                setPropertyType(id) {
                    const match = this.propertyTypes.find((type) => type.id === parseInt(id));
                    if (match) {
                        this.activeTab = match.id;
                        this.selectedPropertyTypeId = match.id;
                        this.selectedPropertyTypeName = match.name;
                    }
                },

                toggleFeatureSelection(featureId) {
                    const index = this.selectedFeatures.indexOf(featureId);
                    if (index > -1) {
                        this.selectedFeatures.splice(index, 1);
                    } else {
                        this.selectedFeatures.push(featureId);
                    }
                },

                async assignSelectedFeatures() {
                    if (!this.selectedPropertyTypeId || this.selectedFeatures.length === 0) {
                        if (window.NotificationHelper) {
                            window.NotificationHelper.warning('Önce yayın tipini ve en az bir özelliği seçin ⛔');
                        } else if (window.showToast) {
                            window.showToast('Önce yayın tipini ve en az bir özelliği seçin ⛔', 'warning');
                        } else {
                            alert('Önce yayın tipini ve en az bir özelliği seçin ⛔');
                        }
                        return;
                    }

                    try {
                        // ✅ API Helper kullan (merkezi yönetim)
                        const endpoint = window.APIConfig?.admin?.propertyTypeManager?.syncFeatures(this
                            .selectedPropertyTypeId);
                        const result = await window.APIHelper.request(endpoint ||
                            `/admin/property-type-manager/property-type/${this.selectedPropertyTypeId}/sync-features`, {
                                method: 'POST',
                                body: JSON.stringify({
                                    feature_ids: this.selectedFeatures,
                                }),
                            });

                        if (result.success) {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.success('Özellikler başarıyla atandı ✅');
                            } else if (window.showToast) {
                                window.showToast('Özellikler başarıyla atandı ✅', 'success');
                            } else {
                                alert('Özellikler başarıyla atandı ✅');
                            }
                            window.location.reload();
                        } else {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.error(result.message || 'Özellik atama başarısız ❌');
                            } else if (window.showToast) {
                                window.showToast(result.message || 'Özellik atama başarısız ❌', 'error');
                            } else {
                                alert(result.message || 'Özellik atama başarısız ❌');
                            }
                        }
                    } catch (error) {
                        if (window.NotificationHelper) {
                            window.NotificationHelper.error('Özellikler atanırken hata oluştu ❌');
                        } else if (window.showToast) {
                            window.showToast('Özellikler atanırken hata oluştu ❌', 'error');
                        } else {
                            alert('Özellikler atanırken hata oluştu ❌');
                        }
                    }
                },

                async toggleAssignment(assignmentId, field, value) {
                    try {
                        // ✅ API Helper kullan (merkezi yönetim)
                        const endpoint = window.APIConfig?.admin?.propertyTypeManager?.toggleFeatureAssignment;
                        const result = await window.APIHelper.request(endpoint ||
                            '/admin/property-type-manager/toggle-feature-assignment', {
                                method: 'POST',
                                body: JSON.stringify({
                                    assignment_id: assignmentId,
                                    field,
                                    value,
                                }),
                            });

                        if (result.success) {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.success('Özellik güncellendi ✅');
                            } else if (window.showToast) {
                                window.showToast('Özellik güncellendi ✅', 'success');
                            } else {
                                alert('Özellik güncellendi ✅');
                            }
                        } else {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.error(result.message || 'Güncelleme başarısız ❌');
                            } else if (window.showToast) {
                                window.showToast(result.message || 'Güncelleme başarısız ❌', 'error');
                            } else {
                                alert(result.message || 'Güncelleme başarısız ❌');
                            }
                        }
                    } catch (error) {
                        if (window.NotificationHelper) {
                            window.NotificationHelper.error('Özellik güncellenirken hata oluştu ❌');
                        } else if (window.showToast) {
                            window.showToast('Özellik güncellenirken hata oluştu ❌', 'error');
                        } else {
                            alert('Özellik güncellenirken hata oluştu ❌');
                        }
                    }
                },

                async unassignFeature(propertyTypeId, featureId) {
                    let confirmed = false;
                    if (window.showConfirm) {
                        try {
                            confirmed = await window.showConfirm(
                                'Bu özelliği kaldırmak istediğinizden emin misiniz?',
                                'Özellik Kaldır',
                                'warning'
                            );
                        } catch (e) {
                            confirmed = false;
                        }
                    } else {
                        confirmed = window.confirm('Bu özelliği kaldırmak istediğinizden emin misiniz?');
                    }

                    if (!confirmed) {
                        return;
                    }

                    try {
                        // ✅ API Helper kullan (merkezi yönetim)
                        const endpoint = window.APIConfig?.admin?.propertyTypeManager?.unassignFeature(
                            propertyTypeId);
                        const result = await window.APIHelper.request(endpoint ||
                            `/admin/property-type-manager/property-type/${propertyTypeId}/unassign-feature`, {
                                method: 'DELETE',
                                body: JSON.stringify({
                                    feature_id: featureId,
                                }),
                            });

                        if (result.success) {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.success('Özellik başarıyla kaldırıldı ✅');
                            } else if (window.showToast) {
                                window.showToast('Özellik başarıyla kaldırıldı ✅', 'success');
                            } else {
                                alert('Özellik başarıyla kaldırıldı ✅');
                            }
                            window.location.reload();
                        } else {
                            if (window.NotificationHelper) {
                                window.NotificationHelper.error(result.message || 'Özellik kaldırma başarısız ❌');
                            } else if (window.showToast) {
                                window.showToast(result.message || 'Özellik kaldırma başarısız ❌', 'error');
                            } else {
                                alert(result.message || 'Özellik kaldırma başarısız ❌');
                            }
                        }
                    } catch (error) {
                        if (window.NotificationHelper) {
                            window.NotificationHelper.error('Özellik kaldırılırken hata oluştu ❌');
                        } else if (window.showToast) {
                            window.showToast('Özellik kaldırılırken hata oluştu ❌', 'error');
                        } else {
                            alert('Özellik kaldırılırken hata oluştu ❌');
                        }
                    }
                },

                async saveLogic() {
                    const payload = {
                        yayin_tipi_id: this.selectedPropertyTypeId,
                        field_slug: this.logicForm.field_slug,
                        depends_on_field_slug: this.logicForm.depends_on,
                        aktiflik_durumu: this.logicForm.is_active,
                        // Context7: Diğer gerekli alanlar controller tarafından kategori slug üzerinden çözülüyor
                        field_name: document.querySelector(`[data-feature-slug="${this.logicForm.field_slug}"]`)?.getAttribute('data-feature-name') || this.logicForm.field_slug,
                        field_type: 'select', // Default
                        field_category: 'Smart Logic'
                    };

                    try {
                        // Correct URLs matching routes/admin.php prefix 'property-type-manager'
                        const url = this.logicForm.id
                            ? `/admin/property-type-manager/{{ $kategori->id }}/field-dependencies/${this.logicForm.id}`
                            : `/admin/property-type-manager/{{ $kategori->id }}/field-dependencies`;

                        const response = await fetch(url, {
                            method: this.logicForm.id ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (result.success) {
                            window.NotificationHelper?.success(result.message || 'Mantıksal kural kaydedildi ✅');
                            window.location.reload();
                        } else {
                            // Show descriptive error (e.g. from DFS Check)
                            window.NotificationHelper?.error(result.message || 'Kural kaydedilemedi ❌');
                        }
                    } catch (error) {
                        console.error('Save error:', error);
                        window.NotificationHelper?.error('Bir hata oluştu ❌');
                    }
                },

                editLogic(id) {
                     // Find the logic item from data (needs to be available in JS or fetched)
                     // For now, simpler to just delete and recreate or reload
                     // Ideally we would fetch the item details and populate logicForm
                    console.log('Edit logic:', id);
                    alert('Düzenleme özelliği yapım aşamasında. Lütfen silip tekrar oluşturun.');
                },

                async deleteLogic(id) {
                    if (!confirm('Bu koşulu silmek istediğinizden emin misiniz?')) return;

                    try {
                        const response = await fetch(`/admin/property-type-manager/{{ $kategori->id }}/field-dependencies/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const result = await response.json();
                        if (result.success) {
                            window.NotificationHelper?.success('Koşul silindi ✅');
                            window.location.reload();
                        } else {
                            window.NotificationHelper?.error(result.message || 'Silinemedi ❌');
                        }
                    } catch (error) {
                        console.error('Delete error:', error);
                    }
                }
            };
        };
    </script>

    {{-- 🏛️ Breadcrumb Navigation --}}
    <div class="container mx-auto px-4 pt-6 pb-2">
        @include('components.neo.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard.index')],
                ['label' => 'Mülk Yönetimi', 'url' => route('admin.property_types.index')],
                ['label' => $kategori->name, 'url' => route('admin.property_types.show', $kategori->id)],
                [
                    'label' => 'Alan İlişkileri',
                    'url' => route('admin.property_types.field_dependencies', $kategori->id),
                    'current' => true,
                ],
            ],
        ])
    </div>

    <div class="container mx-auto px-4 pb-12" x-data="fieldDependenciesManager()">
        <div class="max-w-7xl mx-auto">

            <!-- 🏛️ Header Section -->
            <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl shadow-sm">
                            @if ($kategori->icon && preg_match('/^[a-z0-9\-]+$/i', $kategori->icon))
                                <x-icon :name="$kategori->icon" class="w-6 h-6" />
                            @else
                                <span>{{ $kategori->icon ?? '🏠' }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    Özellik & Alan İlişkileri
                                </h1>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                    {{ $kategori->name }}
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                Seçilen yayın tipine göre form alanlarının zorunluluğunu ve akıllı görünürlük kurallarını belirleyin.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                    <a href="{{ route('admin.ups.features.create') }}"
                        class="inline-flex items-center px-4 py-2.5 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl shadow-sm hover:shadow transition-all duration-200 active:scale-95 text-sm">
                        <x-icon name="ekle" class="w-4 h-4 mr-1.5 text-slate-950" />
                        Yeni Özellik Oluştur
                    </a>
                    <a href="{{ route('admin.ups.features.index') }}"
                        class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-400 font-semibold rounded-xl border border-slate-700/80 shadow-sm transition-all duration-200 active:scale-95 text-sm dark:bg-slate-800 dark:hover:bg-slate-700">
                        <x-icon name="liste" class="w-4 h-4 mr-1.5 text-amber-400" />
                        Tüm Özellikler
                    </a>
                    <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                        class="inline-flex items-center px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 font-medium rounded-xl border border-slate-200 shadow-sm transition-all duration-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:border-slate-700 text-sm">
                        <x-icon name="sol-ok" class="w-4 h-4 mr-1.5 text-slate-500 dark:text-slate-400" />
                        Geri Dön
                    </a>
                </div>
            </div>

            <!-- Selection Summary Card -->
            <div class="mb-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon name="katman" class="w-4 h-4 text-amber-500" />
                            <span>Kategori & Yayın Tipi Özeti</span>
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="selectedPropertyTypeId">
                            Aktif yapılandırılan yayın tipi:
                            <span class="font-bold text-amber-600 dark:text-amber-400" x-text="selectedPropertyTypeName"></span>.
                            Bu tipe ait zorunlu ve isteğe bağlı tüm özellikler aşağıda listelenmiştir.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/80 px-4 py-2.5">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500">Ana Kategori</p>
                            <p class="mt-0.5 font-bold text-slate-900 dark:text-white text-sm">{{ $kategori->name }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/80 px-4 py-2.5" x-show="propertyTypes.length > 0">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500">Alt Kategoriler</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @if($kategori->children->count() > 0)
                                    @foreach($kategori->children as $altKat)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                            {{ $altKat->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-xs text-slate-400">Bulunmuyor</span>
                                @endif
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/80 px-4 py-2.5" x-show="propertyTypes.length > 0">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500">Seçili Yayın Tipi</p>
                            <p class="mt-0.5 font-bold text-amber-600 dark:text-amber-400 text-sm" x-text="selectedPropertyTypeName || 'Seçilmedi'"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Type Tabs -->
            @if ($yayinTipleri->count() > 0)
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm mb-6">
                    <div class="p-2 border-b border-slate-100 dark:border-slate-800">
                        <nav class="flex items-center space-x-1.5 overflow-x-auto p-1" aria-label="Tabs">
                            @foreach ($yayinTipleri as $index => $yayinTipi)
                                <button @click="setPropertyType({{ $yayinTipi->id }})"
                                    :class="activeTab == {{ $yayinTipi->id }} ?
                                        'bg-slate-900 text-amber-400 dark:bg-amber-400/10 dark:text-amber-400 shadow-sm font-semibold' :
                                        'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium'"
                                    class="whitespace-nowrap py-2.5 px-4 rounded-xl text-sm transition-all duration-200 flex items-center gap-2">
                                    <x-icon name="etiket" class="w-4 h-4" />
                                    <span>{{ $yayinTipi->yayin_tipi }}</span>
                                    <span :class="activeTab == {{ $yayinTipi->id }} ? 'bg-amber-400/20 text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                        class="ml-1 py-0.5 px-2 rounded-full text-xs font-semibold transition-colors">
                                        {{ $assignmentCounts[(string)$yayinTipi->id] ?? $assignmentCounts[$yayinTipi->id] ?? 0 }}
                                    </span>
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Contents -->
                    @foreach ($yayinTipleri as $index => $yayinTipi)
                        <div x-show="activeTab == {{ $yayinTipi->id }}" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6">

                            <!-- Sub-Tabs: Assignments vs Smart Logic -->
                            <div class="mb-8" x-data="{ subTab: 'assignments' }">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 mb-6 pb-2">
                                    <div class="flex items-center gap-2">
                                        <button @click="subTab = 'assignments'"
                                            :class="subTab === 'assignments' ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium'"
                                            class="px-4 py-2 rounded-xl text-sm transition-all flex items-center gap-2">
                                            <x-icon name="liste" class="w-4 h-4 text-amber-500" />
                                            <span>Özellik Atamaları</span>
                                        </button>
                                        <button @click="subTab = 'logic'"
                                            :class="subTab === 'logic' ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium'"
                                            class="px-4 py-2 rounded-xl text-sm transition-all flex items-center gap-2">
                                            <x-icon name="ai" class="w-4 h-4 text-purple-500" />
                                            <span>Akıllı Koşullar</span>
                                            <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">Beta</span>
                                        </button>
                                    </div>

                                    <div x-show="subTab === 'assignments'">
                                        <button @click="setPropertyType({{ $yayinTipi->id }}); showAddFeatureModal = true"
                                            class="inline-flex items-center px-4 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl shadow-sm transition-all text-xs active:scale-95">
                                            <x-icon name="ekle" class="w-3.5 h-3.5 mr-1.5 text-slate-950" />
                                            Havuza Git & Özellik Seç
                                        </button>
                                    </div>

                                    <div x-show="subTab === 'logic'">
                                        <button @click="showAddLogicModal = true; selectedPropertyTypeId = {{ $yayinTipi->id }}"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold rounded-xl shadow-sm transition-all text-xs active:scale-95">
                                            <x-icon name="ai" class="w-3.5 h-3.5 mr-1.5" />
                                            Yeni Koşul Oluştur
                                        </button>
                                    </div>
                                </div>

                                <!-- Sub-Tab Body: Assignments -->
                                <div x-show="subTab === 'assignments'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="mb-4 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <x-icon name="bilgi" class="w-4 h-4 text-amber-500 shrink-0" />
                                        <span>Bu bölümden özelliklerin bu yayın tipinde aktif/pasif ve zorunlu olup olmadığını yönetebilirsiniz.</span>
                                    </div>

                                    @php
                                        $assignments = $assignmentsByType[(string)$yayinTipi->id] ?? $assignmentsByType[$yayinTipi->id] ?? collect([]);
                                    @endphp

                                    @if ($assignments && $assignments->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            @foreach ($assignments as $assignment)
                                                @php
                                                    $feature = $assignment->feature;
                                                @endphp
                                                @if (!$feature)
                                                    @continue
                                                @endif
                                                <div
                                                    data-feature-slug="{{ $feature->slug }}"
                                                    data-feature-name="{{ $feature->name }}"
                                                    class="feature-card bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 hover:border-amber-400/50 hover:bg-white dark:hover:bg-slate-800 transition-all duration-200">
                                                    <!-- Header -->
                                                    <div class="flex items-start justify-between mb-3">
                                                        <div class="flex-1 min-w-0 pr-2">
                                                            <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 truncate">
                                                                @if ($feature && $feature->field_icon)
                                                                    <span class="text-base">{{ $feature->field_icon }}</span>
                                                                @endif
                                                                <span class="truncate">{{ $feature->name ?? 'Bilinmeyen Özellik' }}</span>
                                                            </h4>
                                                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                                                <span class="font-mono text-[11px]">{{ $feature->slug ?? 'N/A' }}</span>
                                                                <span class="mx-1">•</span>
                                                                <span class="capitalize">{{ $feature->type ?? 'text' }}</span>
                                                            </p>
                                                        </div>

                                                        <!-- Delete Button -->
                                                        @if ($feature && $feature->id)
                                                            <button
                                                                @click="unassignFeature({{ $yayinTipi->id }}, {{ $feature->id }})"
                                                                class="text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                                                title="Özelliği Kaldır">
                                                                <x-icon name="sil" class="w-4 h-4" />
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <!-- Category Badge -->
                                                    @if ($feature && $feature->category)
                                                        <div class="mb-3">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                                {{ $feature->category->name }}
                                                            </span>
                                                        </div>
                                                    @endif

                                                    <!-- Toggle Switches -->
                                                    <div class="space-y-2.5 border-t border-slate-200/60 dark:border-slate-700/60 pt-3">
                                                        <!-- Visible Toggle -->
                                                        <label class="flex items-center justify-between cursor-pointer group">
                                                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-amber-600 transition-colors">
                                                                Görünür
                                                            </span>
                                                            <div class="relative">
                                                                <input type="checkbox" class="sr-only peer"
                                                                    {{ $assignment->is_visible ? 'checked' : '' }}
                                                                    @change="toggleAssignment({{ $assignment->id }}, 'is_visible', $event.target.checked)">
                                                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500">
                                                                </div>
                                                            </div>
                                                        </label>

                                                        <!-- Required Toggle -->
                                                        <label class="flex items-center justify-between cursor-pointer group">
                                                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-rose-600 transition-colors flex items-center gap-1">
                                                                Zorunlu
                                                                @if ($assignment->is_required)
                                                                    <span class="text-rose-500">*</span>
                                                                @endif
                                                            </span>
                                                            <div class="relative">
                                                                <input type="checkbox" class="sr-only peer"
                                                                    {{ $assignment->is_required ? 'checked' : '' }}
                                                                    @change="toggleAssignment({{ $assignment->id }}, 'is_required', $event.target.checked)">
                                                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500">
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <!-- Empty State -->
                                        <div class="text-center py-12 bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                                            <div class="w-12 h-12 bg-amber-500/10 rounded-2xl mx-auto mb-3 flex items-center justify-center text-amber-600">
                                                <x-icon name="liste" class="w-6 h-6" />
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Henüz özellik atanmamış</h3>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Bu yayın tipine havuzdan özellik ekleyerek başlayabilirsiniz.
                                            </p>
                                            <div class="mt-5">
                                                <button
                                                    @click="setPropertyType({{ $yayinTipi->id }}); showAddFeatureModal = true"
                                                    class="inline-flex items-center px-4 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl text-xs shadow-sm transition-all">
                                                    <x-icon name="ekle" class="w-3.5 h-3.5 mr-1 text-slate-950" />
                                                    İlk Özelliği Ekle
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Sub-Tab Body: Smart Logic -->
                                <div x-show="subTab === 'logic'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="mb-6 bg-purple-50 dark:bg-purple-950/20 border-l-4 border-purple-500 p-4 rounded-r-2xl">
                                        <div class="flex items-start gap-3">
                                            <x-icon name="ai" class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
                                            <div>
                                                <p class="text-xs text-purple-900 dark:text-purple-200">
                                                    <strong>Akıllı Koşullar (Smart Logic):</strong> Bir alanın görünürlüğünü başka bir alanın değerine bağlayarak dinamik form akışları oluşturun.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        $currentDeps = $fieldDependencies->filter(function($dep) use ($yayinTipi) {
                                            return $dep->yayin_tipi_id == $yayinTipi->id || $dep->yayin_tipi == $yayinTipi->yayin_tipi;
                                        });
                                    @endphp

                                    @if($currentDeps->count() > 0)
                                        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                                <thead class="bg-slate-50 dark:bg-slate-800/60">
                                                    <tr>
                                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Durum</th>
                                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Koşullu Alan</th>
                                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Bağımlı Olduğu Alan</th>
                                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Kural</th>
                                                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200/70 dark:divide-slate-800">
                                                    @foreach($currentDeps as $dep)
                                                        @php
                                                            $options = is_string($dep->field_options) ? json_decode($dep->field_options, true) : ($dep->field_options ?? []);
                                                            $dependsOnSlug = $options['depends_on'] ?? 'N/A';
                                                        @endphp
                                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $dep->aktiflik_durumu ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60' : 'bg-slate-200/70 text-slate-600 dark:bg-slate-700 dark:text-slate-400' }}">
                                                                    {{ $dep->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $dep->field_name }}</div>
                                                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $dep->field_slug }}</div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                                                    <x-icon name="link" class="w-3.5 h-3.5 text-purple-500" />
                                                                    <span>{{ $dependsOnSlug }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="text-xs px-2.5 py-1 bg-purple-50 dark:bg-purple-950/30 text-purple-700 dark:text-purple-300 rounded-lg border border-purple-200/60 dark:border-purple-800 font-semibold">
                                                                    Doluysa Göster
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                                <div class="flex justify-end gap-1.5">
                                                                    <button @click="editLogic({{ $dep->id }})" class="p-1.5 text-slate-600 hover:text-amber-600 dark:text-slate-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                                        <x-icon name="duzenle" class="w-4 h-4" />
                                                                    </button>
                                                                    <button @click="deleteLogic({{ $dep->id }})" class="p-1.5 text-rose-600 hover:text-rose-700 dark:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                                                        <x-icon name="sil" class="w-4 h-4" />
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="px-6 py-12 text-center border-2 border-dashed border-purple-200/80 dark:border-purple-900/30 rounded-2xl bg-purple-50/20">
                                            <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-2xl mx-auto mb-4 flex items-center justify-center text-purple-600">
                                                <x-icon name="ai" class="w-7 h-7" />
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Henüz Akıllı Kural Yok</h3>
                                            <p class="text-slate-500 dark:text-slate-400 mb-5 max-w-xs mx-auto text-xs">
                                                Bu yayın tipindeki alanlar arasında henüz mantıksal bir kural tanımlanmamış.
                                            </p>
                                            <button @click="showAddLogicModal = true; selectedPropertyTypeId = {{ $yayinTipi->id }}"
                                                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-bold rounded-xl shadow-sm hover:shadow transition-all text-xs">
                                                <x-icon name="ai" class="w-4 h-4 mr-1.5" />
                                                İlk Mantıksal Koşulu Ekle
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- No Property Types -->
                <div class="bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                    <div class="flex items-start gap-3">
                        <x-icon name="uyari" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                Henüz yayın tipi tanımlanmamış
                            </h3>
                            <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                                Özellik yönetimi için önce yayın tipleri (örn: "Satılık", "Kiralık") eklemelisiniz.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                                    class="inline-flex items-center text-xs font-bold text-amber-900 dark:text-amber-200 hover:underline">
                                    Yayın Tipi Yöneticisi'ne Git →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Add Feature Modal -->
        <div x-show="showAddFeatureModal" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">

            <div @click.away="showAddFeatureModal = false"
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[85vh] overflow-hidden border border-slate-200 dark:border-slate-800 flex flex-col">

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-5 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon name="ekle" class="w-5 h-5 text-amber-500" />
                        <span>Havuzdan Özellik Ekle</span>
                    </h3>
                    <button @click="showAddFeatureModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1">
                        <x-icon name="kapat" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto max-h-[60vh] space-y-5">
                    @forelse ($availableFeatures as $categoryName => $features)
                        <div>
                            <h4 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                <x-icon name="katman" class="w-4 h-4 text-amber-500" />
                                <span>{{ $categoryName }}</span>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                @foreach ($features as $feature)
                                    @if (!$feature)
                                        @continue
                                    @endif
                                    <label
                                        class="flex items-center p-3 bg-slate-50/70 dark:bg-slate-800/40 rounded-xl hover:bg-amber-50/60 dark:hover:bg-slate-800 cursor-pointer transition-colors border border-slate-200/60 dark:border-slate-700/60">
                                        <input type="checkbox"
                                            class="w-4 h-4 text-amber-600 bg-white border-slate-300 rounded focus:ring-amber-500 dark:bg-slate-800 dark:border-slate-600"
                                            :checked="selectedFeatures.includes({{ $feature->id }})"
                                            @change="toggleFeatureSelection({{ $feature->id }})">
                                        <span class="ml-3 flex-1 min-w-0">
                                            <span class="block text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                @if ($feature->field_icon)
                                                    {{ $feature->field_icon }}
                                                @endif
                                                {{ $feature->name }}
                                            </span>
                                            <span class="block text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                                {{ $feature->slug }} • {{ $feature->field_type ?? ($feature->type ?? 'text') }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="py-12 px-4 text-center">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center mb-3">
                                <x-icon name="katman" class="w-7 h-7" />
                            </div>
                            <h4 class="text-base font-bold text-slate-900 dark:text-white">Tanımlı Özellik Bulunamadı</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">
                                Bu kategoriye atanabilecek özellik havuzu henüz oluşturulmamış. Yeni özellikler tanımlayarak form alanlarını dinamik olarak yapılandırabilirsiniz.
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('admin.ups.features.create') }}"
                                    class="inline-flex items-center px-4 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl text-xs shadow-sm transition-all">
                                    <x-icon name="ekle" class="w-3.5 h-3.5 mr-1.5 text-slate-950" />
                                    Yeni Özellik Oluştur
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400">
                        <span x-text="selectedFeatures.length" class="font-bold text-amber-600 dark:text-amber-400"></span> özellik seçildi
                    </div>
                    <div class="flex gap-2">
                        <button @click="showAddFeatureModal = false"
                            class="px-4 py-2 bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-xl border border-slate-200 dark:border-slate-700 text-xs transition-colors">
                            İptal
                        </button>
                        <button @click="assignSelectedFeatures()" :disabled="selectedFeatures.length === 0"
                            class="px-5 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl shadow-sm transition-all text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                            Özellikleri Ekle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Smart Logic Modal -->
        <div x-show="showAddLogicModal" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4">

            <div @click.away="showAddLogicModal = false"
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-200 dark:border-slate-800">

                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-purple-50/40 dark:bg-purple-950/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                                <x-icon name="ai" class="w-5 h-5 text-purple-600" />
                                <span>Mantıksal Koşul Oluştur</span>
                            </h3>
                            <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs">
                                Alanlar arası akıllı görünürlük kuralları tanımlayın.
                            </p>
                        </div>
                        <button @click="showAddLogicModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1">
                            <x-icon name="kapat" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">
                    {{-- Target Field --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Hangi Alan Koşula Bağlı? (Hedef)
                        </label>
                        <select x-model="logicForm.field_slug" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 font-medium text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">Lütfen alan seçin...</option>
                            @foreach($availableFeatures as $categoryName => $features)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($features as $feature)
                                        <option value="{{ $feature->slug }}">{{ $feature->name }} ({{ $feature->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Gizlenecek veya gösterilecek olan alan bu olacaktır.</p>
                    </div>

                    {{-- Source Field --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Hangi Alana Bağımlı? (Kaynak)
                        </label>
                        <select x-model="logicForm.depends_on" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 font-medium text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">Lütfen alan seçin...</option>
                            @foreach($availableFeatures as $categoryName => $features)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($features as $feature)
                                        <option value="{{ $feature->slug }}">{{ $feature->name }} ({{ $feature->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Bu alanın değeri değiştiğinde hedef alan tepki verecektir.</p>
                    </div>

                    {{-- Condition Type --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Kural Tipi</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" x-model="logicForm.condition" value="filled" class="hidden peer">
                                <div class="p-3.5 bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-center peer-checked:border-purple-600 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-950/30 transition-all">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Doluysa Göster</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" x-model="logicForm.condition" value="equals" class="hidden peer">
                                <div class="p-3.5 bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-center peer-checked:border-purple-600 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-950/30 transition-all">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Eşitse Göster</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button @click="showAddLogicModal = false" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 text-xs font-bold">İptal</button>
                    <button @click="saveLogic()" :disabled="!logicForm.field_slug || !logicForm.depends_on"
                        class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-bold rounded-xl shadow-sm hover:shadow transition-all text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                        Kaydet & Yayına Al
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
