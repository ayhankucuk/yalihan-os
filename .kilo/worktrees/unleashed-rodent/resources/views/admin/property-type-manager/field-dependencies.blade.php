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
                    // ✅ SAB: Alpine.js yüklenmeden önce içeriklerin görünmesini önle
                    if (!this.activeTab && this.propertyTypes.length > 0) {
                        this.activeTab = this.propertyTypes[0].id;
                        this.selectedPropertyTypeId = this.propertyTypes[0].id;
                        this.selectedPropertyTypeName = this.propertyTypes[0].name;
                    }
                    this.ensureSelection();
                },

                ensureSelection() {
                    if (!this.activeTab && this.propertyTypes.length) {
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
                        }
                    } catch (error) {
                        window.NotificationHelper?.error('Silme işlemi başarısız ❌');
                    }
                },
            };
        };
    </script>

    {{-- Breadcrumb Navigation --}}
    <div class="container mx-auto px-4 py-4">
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

    <div class="container mx-auto px-4 py-8" x-data="fieldDependenciesManager()">
        <div class="max-w-7xl mx-auto">
            <!-- Selection Summary -->
            <div class="mb-8">
                <div
                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl shadow-sm p-6 dark:shadow-none">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-blue-800 dark:text-blue-200">
                                Kategori & Yayın Tipi Seçimi
                            </h2>
                            <p class="mt-2 text-sm text-blue-700 dark:text-blue-300" x-show="selectedPropertyTypeId">
                                Seçili yayın tipi:
                                <span class="font-semibold" x-text="selectedPropertyTypeName"></span>.
                                Bu kombinasyona ait zorunlu ve isteğe bağlı tüm özellikler aşağıdaki listede yer alır.
                            </p>
                            <p class="mt-2 text-sm text-blue-700 dark:text-blue-300" x-show="!selectedPropertyTypeId">
                                Başlamak için aşağıdaki yayın tiplerinden birini seçin; seçim yaptıktan sonra
                                ilgili özellikler otomatik olarak gösterilecektir.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div
                                class="bg-white dark:bg-slate-900 rounded-lg border border-blue-100 dark:border-blue-800 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-blue-500 dark:text-blue-300">Ana Kategori</p>
                                <p class="mt-1 font-semibold text-blue-900 dark:text-blue-100">{{ $kategori->name }}</p>
                            </div>
<div class="bg-white dark:bg-slate-900 rounded-lg border border-blue-100 dark:border-blue-800 px-4 py-3"
                                x-show="propertyTypes.length > 0">
                                <p class="text-xs uppercase tracking-wide text-blue-500 dark:text-blue-300">Alt Kategori</p>
                                <div class="mt-1 font-semibold text-blue-900 dark:text-blue-100">
                                @if($kategori->children->count() > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($kategori->children as $altKat)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                                                    <i class="fas fa-layer-group mr-1.5 opacity-70"></i>
                                                    {{ $altKat->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-[10px] text-gray-500 dark:text-gray-400 font-normal">İlan oluşturma sırasında seçilecektir</p>
                                    @else
                                        <span>Alt kategori bulunmuyor</span>
                                    @endif
                                </div>
                            </div>
                            <div class="bg-white dark:bg-slate-900 rounded-lg border border-blue-100 dark:border-blue-800 px-4 py-3"
                                x-show="propertyTypes.length > 0">
                                <p class="text-xs uppercase tracking-wide text-blue-500 dark:text-blue-300">Yayın Tipi</p>
                                <p class="mt-1 font-semibold text-blue-900 dark:text-blue-100"
                                    x-text="selectedPropertyTypeName || 'Henüz seçilmedi'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('admin.property_types.index') }}"
                                    class="text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 dark:text-slate-300">
                                    Yayın Tipi Yöneticisi
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                                        class="ml-1 text-gray-700 dark:text-slate-200 hover:text-blue-600 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 dark:text-slate-300">{{ $kategori->name }}</a>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="ml-1 text-gray-500 dark:text-gray-400">Özellik Yönetimi</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Özellik Yönetimi
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        <span class="font-semibold">{{ $kategori->name }}</span> kategorisi için özellik atamalarını yönetin
                    </p>
                    <div class="mt-3 flex items-center gap-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">💡 İpucu:</span>
                        <a href="{{ route('admin.ups.features.index') }}"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline font-medium">
                            Özellikleri Oluştur/Düzenle
                        </a>
                        <span class="text-gray-400">•</span>
                        <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline font-medium">
                            Özellik Kategorilerini Yönet
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.ups.features.create') }}"
                        class="px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105 active:scale-95 dark:shadow-none">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Yeni Özellik Oluştur
                    </a>
                    <a href="{{ route('admin.ups.features.index') }}"
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Tüm Özellikleri Yönet
                    </a>
                    <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                        class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 dark:text-slate-100">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>

            <!-- Property Type Tabs -->
            @if ($yayinTipleri->count() > 0)
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg mb-6">
                    <div class="border-b border-gray-200 dark:border-slate-800 px-6 overflow-x-auto dark:border-slate-700">
                        <nav class="-mb-px flex space-x-8 min-w-max" aria-label="Tabs">
                            @foreach ($yayinTipleri as $index => $yayinTipi)
                                @php
                                    // Slug usage removed, using ID for tabs
                                @endphp
                                <button @click="setPropertyType({{ $yayinTipi->id }})"
                                    :class="activeTab == {{ $yayinTipi->id }} ? 'active' : ''"
                                    class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ $yayinTipi->yayin_tipi }}
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full"
                                        :class="activeTab == {{ $yayinTipi->id }} ?
                                            'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' :
                                            'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                                        {{ $assignmentCounts[(string)$yayinTipi->id] ?? $assignmentCounts[$yayinTipi->id] ?? 0 }}
                                    </span>
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Contents -->
                    @foreach ($yayinTipleri as $index => $yayinTipi)
                        @php
                            // Slug usage removed
                        @endphp
                        <div x-show="activeTab == {{ $yayinTipi->id }}" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100" class="p-6">

                            <!-- Sub-Tabs: Assignments vs Smart Logic -->
                            <div class="mb-8" x-data="{ subTab: 'assignments' }">
                                <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 mb-6 dark:border-slate-700">
                                    <div class="flex gap-8">
                                        <button @click="subTab = 'assignments'"
                                            :class="subTab === 'assignments' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            class="py-4 border-b-2 font-black text-sm transition-all focus:outline-none flex items-center gap-2">
                                            <i class="fas fa-list-ul"></i>
                                            Özellik Atamaları & Sıralama
                                        </button>
                                        <button @click="subTab = 'logic'"
                                            :class="subTab === 'logic' ? 'border-purple-600 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            class="py-4 border-b-2 font-black text-sm transition-all focus:outline-none flex items-center gap-2">
                                            <i class="fas fa-brain text-purple-500"></i>
                                            Akıllı Koşullar (Smart Logic)
                                            <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase transition-all">Beta</span>
                                        </button>
                                    </div>

                                    <div x-show="subTab === 'assignments'">
                                        <button @click="setPropertyType({{ $yayinTipi->id }}); showAddFeatureModal = true"
                                            class="group relative px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black rounded-xl shadow-lg shadow-blue-500/20 active:scale-95 transition-all flex items-center gap-2 text-sm">
                                            <i class="fas fa-plus group-hover:rotate-90 transition-transform"></i>
                                            Havuza Git & Özellik Seç
                                        </button>
                                    </div>

                                    <div x-show="subTab === 'logic'">
                                        <button @click="showAddLogicModal = true; selectedPropertyTypeId = {{ $yayinTipi->id }}"
                                            class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-black rounded-xl shadow-lg shadow-purple-500/20 active:scale-95 transition-all flex items-center gap-2 text-sm">
                                            <i class="fas fa-magic"></i>
                                            Yeni Koşul Oluştur
                                        </button>
                                    </div>
                                </div>

                                <!-- Sub-Tab Body: Assignments -->
                                <div x-show="subTab === 'assignments'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="mb-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                        Bu bölümden özelliklerin bu yayın tipinde aktif/pasif ve zorunlu olup olmadığını yönetebilirsiniz.
                                    </div>

                            <!-- Assigned Features List -->
                            @php
                                $assignments = $assignmentsByType[(string)$yayinTipi->id] ?? $assignmentsByType[$yayinTipi->id] ?? collect([]);
                            @endphp

                            @if ($assignments && $assignments->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($assignments as $assignment)
                                        @php
                                            $feature = $assignment->feature;
                                        @endphp
                                        {{-- ✅ FIX: Feature null kontrolü --}}
                                        @if (!$feature)
                                            @continue
                                        @endif
                                        <div
                                            data-feature-slug="{{ $feature->slug }}"
                                            data-feature-name="{{ $feature->name }}"
                                            class="feature-card bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-5 shadow-sm dark:shadow-none dark:border-slate-700">
                                            <!-- Header -->
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex-1">
                                                    <h4
                                                        class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                                                        @if ($feature && $feature->field_icon)
                                                            <span class="text-lg">{{ $feature->field_icon }}</span>
                                                        @endif
                                                        {{ $feature->name ?? 'Bilinmeyen Özellik' }}
                                                    </h4>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        <span class="font-mono">{{ $feature->slug ?? 'N/A' }}</span>
                                                        <span class="mx-1">•</span>
                                                        <span class="capitalize">
                                                            {{ $feature->type ?? 'text' }}
                                                        </span>
                                                    </p>
                                                </div>

                                                <!-- Delete Button -->
                                                @if ($feature && $feature->id)
                                                    <button
                                                        @click="unassignFeature({{ $yayinTipi->id }}, {{ $feature->id }})"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>

                                            <!-- Category -->
                                            @if ($feature && $feature->category)
                                                <div class="mb-3">
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                                        {{ $feature->category->name }}
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Toggle Switches -->
                                            <div class="space-y-3 border-t border-gray-200 dark:border-slate-800 pt-4 dark:border-slate-700">
                                                <!-- Visible Toggle -->
                                                <label class="flex items-center justify-between cursor-pointer group">
                                                    <span
                                                        class="text-sm font-medium text-gray-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-300">
                                                        Görünür
                                                    </span>
                                                    <div class="relative">
                                                        <input type="checkbox" class="sr-only peer"
                                                            {{ $assignment->is_visible ? 'checked' : '' }}
                                                            @change="toggleAssignment({{ $assignment->id }}, 'is_visible', $event.target.checked)">
                                                        <div
                                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:after:bg-gray-100 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500">
                                                        </div>
                                                    </div>
                                                </label>

                                                <!-- Required Toggle -->
                                                <label class="flex items-center justify-between cursor-pointer group">
                                                    <span
                                                        class="text-sm font-medium text-gray-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors flex items-center gap-2 dark:text-slate-300">
                                                        Zorunlu
                                                        @if ($assignment->is_required)
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </span>
                                                    <div class="relative">
                                                        <input type="checkbox" class="sr-only peer"
                                                            {{ $assignment->is_required ? 'checked' : '' }}
                                                            @change="toggleAssignment({{ $assignment->id }}, 'is_required', $event.target.checked)">
                                                        <div
                                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:after:bg-gray-100 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600 dark:peer-checked:bg-red-500">
                                                        </div>
                                                    </div>
                                                </label>

                                                <!-- Group Name (if set) -->
                                                @if ($assignment->group_name)
                                                    <div
                                                        class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                        </svg>
                                                        {{ $assignment->group_name }}
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- AI Features (if any) -->
                                            @if ($feature)
                                                @php
                                                    // ✅ SAB: hasAiCapabilities() method kontrolü (stdClass için)
                                                    $hasAiCapabilities = method_exists($feature, 'hasAiCapabilities')
                                                        ? $feature->hasAiCapabilities()
                                                        : ($feature->ai_auto_fill ?? false) ||
                                                            ($feature->ai_suggestion ?? false) ||
                                                            ($feature->ai_calculation ?? false);
                                                @endphp
                                                @if ($hasAiCapabilities)
                                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                                        <div class="flex flex-wrap gap-1">
                                                            @if ($feature->ai_auto_fill ?? false)
                                                                <span
                                                                    class="px-2 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                                                    🤖 Otomatik
                                                                </span>
                                                            @endif
                                                            @if ($feature->ai_suggestion ?? false)
                                                                <span
                                                                    class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                                                    💡 Öneri
                                                                </span>
                                                            @endif
                                                            @if ($feature->ai_calculation ?? false)
                                                                <span
                                                                    class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
                                                                    ⚡ Hesaplama
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <!-- Empty State -->
                                <div
                                    class="text-center py-12 bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-800">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz özellik
                                        atanmamış</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Bu yayın tipine özellik ekleyerek başlayın
                                    </p>
                                    <div class="mt-6">
                                        <button
                                            @click="setPropertyType({{ $yayinTipi->id }}); showAddFeatureModal = true"
                                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            İlk Özelliği Ekle
                                        </button>
                                    </div>
                                </div>
                                    @endif
                                </div>

                                <!-- Sub-Tab Body: Smart Logic -->
                                <div x-show="subTab === 'logic'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="mb-6 bg-purple-50 dark:bg-purple-900/10 border-l-4 border-purple-500 p-4 rounded-r-lg">
                                        <div class="flex">
                                            <div class="flex-shrink-0 text-purple-600">
                                                <i class="fas fa-brain text-xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-purple-700 dark:text-purple-300">
                                                    <strong>Akıllı Koşullar (Smart Logic):</strong> Bir alanın görünürlüğünü, başka bir alanın değerine bağlayarak akıllı formlar oluşturabilirsiniz.
                                                    Örn: "Daire Tipi" seçilmeden "Kat Sayısı" alanını gizle.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        // Bu yayın tipine ait bağımlılıkları filtrele
                                        $currentDeps = $fieldDependencies->filter(function($dep) use ($yayinTipi) {
                                            return $dep->yayin_tipi_id == $yayinTipi->id || $dep->yayin_tipi == $yayinTipi->yayin_tipi;
                                        });
                                    @endphp

                                    @if($currentDeps->count() > 0)
                                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900">
                                                    <tr>
                                                        <th class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                                                        <th class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">Koşullu Alan</th>
                                                        <th class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bağımlı Olduğu Alan</th>
                                                        <th class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kural</th>
                                                        <th class="px-6 py-4 text-right text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($currentDeps as $dep)
                                                        @php
                                                            $options = is_string($dep->field_options) ? json_decode($dep->field_options, true) : ($dep->field_options ?? []);
                                                            $dependsOnSlug = $options['depends_on'] ?? 'N/A';
                                                        @endphp
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $dep->aktiflik_durumu ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' }} dark:text-slate-300">
                                                                    {{ $dep->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    @if($dep->field_icon) <span class="mr-2 text-lg">{{ $dep->field_icon }}</span> @endif
                                                                    <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $dep->field_name }}</div>
                                                                </div>
                                                                <div class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">{{ $dep->field_slug }}</div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                                                    <i class="fas fa-link mr-1 opacity-50 text-purple-500"></i>
                                                                    {{ $dependsOnSlug }}
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="text-xs px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded border border-blue-100 dark:border-blue-800 font-bold">
                                                                    Doluysa Göster
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                <div class="flex justify-end gap-2">
                                                                    <button @click="editLogic({{ $dep->id }})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button @click="deleteLogic({{ $dep->id }})" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="px-6 py-16 text-center border-2 border-dashed border-purple-100 dark:border-purple-900/30 rounded-2xl bg-purple-50/10">
                                            <div class="w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/20 dark:to-purple-800/20 rounded-3xl mx-auto mb-6 flex items-center justify-center border border-purple-200 dark:border-purple-700 shadow-inner">
                                                <i class="fas fa-brain text-4xl text-purple-600/50"></i>
                                            </div>
                                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2 dark:text-slate-100">Henüz Akıllı Kural Yok</h3>
                                            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto text-sm">
                                                Bu yayın tipindeki alanlar arası henüz bir mantıksal bağ kurulmamış.
                                            </p>
                                            <button @click="showAddLogicModal = true; selectedPropertyTypeId = {{ $yayinTipi->id }}"
                                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-xl shadow-xl shadow-purple-500/20 hover:shadow-purple-500/40 hover:-translate-y-1 transition-all">
                                                <i class="fas fa-magic mr-3"></i>
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
                <div
                    class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Henüz yayın tipi tanımlanmamış
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <p>Özellik yönetimi için önce yayın tipleri (örn: "Satılık", "Kiralık") eklemelisiniz.</p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                                    class="text-sm font-medium text-yellow-800 dark:text-yellow-200 hover:text-yellow-900 dark:hover:text-yellow-100">
                                    Yayın Tipi Yöneticisi'ne Git →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Add Feature Modal -->
        <div x-show="showAddFeatureModal" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="display: none;">

            <div @click.away="showAddFeatureModal = false" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        Özellik Ekle
                    </h3>
                    <button @click="showAddFeatureModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    @foreach ($availableFeatures as $categoryName => $features)
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ $categoryName }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($features as $feature)
                                    {{-- ✅ FIX: Feature null kontrolü --}}
                                    @if (!$feature)
                                        @continue
                                    @endif
                                    <label
                                        class="flex items-center p-3 bg-gray-50 dark:bg-slate-900 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors border border-transparent hover:border-blue-300 dark:hover:border-blue-700">
                                        <input type="checkbox"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                            :checked="selectedFeatures.includes({{ $feature->id }})"
                                            @change="toggleFeatureSelection({{ $feature->id }})">
                                        <span class="ml-3 flex-1">
                                            <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                @if ($feature->field_icon)
                                                    {{ $feature->field_icon }}
                                                @endif
                                                {{ $feature->name }}
                                            </span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                                {{ $feature->slug }} •
                                                {{ $feature->field_type ?? ($feature->type ?? 'text') }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="selectedFeatures.length"></span> özellik seçildi
                    </div>
                    <div class="flex gap-3">
                        <button @click="showAddFeatureModal = false"
                            class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 dark:text-slate-100">
                            İptal
                        </button>
                        <button @click="assignSelectedFeatures()" :disabled="selectedFeatures.length === 0"
                            :class="selectedFeatures.length === 0 ? 'opacity-50 cursor-not-allowed' :
                                'hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 active:scale-95'"
                            class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Özellikleri Ekle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Logic (Conditional Visibility) Modal -->
    <div x-show="showAddLogicModal" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4">

        <div @click.away="showAddLogicModal = false" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100 dark:border-slate-800">

            <!-- Modal Header -->
            <div class="p-8 border-b border-gray-100 dark:border-slate-800 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                            <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                                <i class="fas fa-magic"></i>
                            </div>
                            Mantıksal Koşul Oluştur
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                            Alanlar arası akıllı görünürlük kuralları tanımlayın.
                        </p>
                    </div>
                    <button @click="showAddLogicModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8 space-y-8">
                <div class="grid grid-cols-1 gap-8">
                    {{-- Target Field --}}
                    <div>
                        <label class="block text-sm font-black text-gray-700 dark:text-slate-200 mb-3 uppercase tracking-wider dark:text-slate-300">
                            Hangi Alan Koşula Bağlı? (Hedef)
                        </label>
                        <select x-model="logicForm.field_slug" class="w-full bg-gray-50 dark:bg-slate-900 border-2 border-gray-100 dark:border-slate-800 rounded-2xl px-5 py-4 focus:border-purple-500 transition-all font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            <option value="">Lütfen alan seçin...</option>
                            @foreach($availableFeatures as $categoryName => $features)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($features as $feature)
                                        <option value="{{ $feature->slug }}">{{ $feature->name }} ({{ $feature->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-400">Gizlenecek veya gösterilecek olan alan bu olacaktır.</p>
                    </div>

                    <div class="flex items-center justify-center py-2">
                        <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                        <div class="mx-4 bg-purple-100 dark:bg-purple-900/30 px-4 py-1 rounded-full text-[10px] font-black text-purple-600 uppercase">Koşul Bağlantısı</div>
                        <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                    </div>

                    {{-- Source Field --}}
                    <div>
                        <label class="block text-sm font-black text-gray-700 dark:text-slate-200 mb-3 uppercase tracking-wider dark:text-slate-300">
                            Hangi Alana Bağımlı? (Kaynak)
                        </label>
                        <select x-model="logicForm.depends_on" class="w-full bg-gray-50 dark:bg-slate-900 border-2 border-gray-100 dark:border-slate-800 rounded-2xl px-5 py-4 focus:border-purple-500 transition-all font-bold text-gray-900 dark:text-white dark:text-slate-100">
                            <option value="">Lütfen alan seçin...</option>
                            @foreach($availableFeatures as $categoryName => $features)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($features as $feature)
                                        <option value="{{ $feature->slug }}">{{ $feature->name }} ({{ $feature->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-400">Bu alanın değeri değiştiğinde hedef alan tepki verecektir.</p>
                    </div>

                    {{-- Condition Type --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-3xl border border-gray-100 dark:border-slate-800 dark:bg-slate-900">
                        <label class="block text-sm font-black text-gray-700 dark:text-slate-200 mb-4 uppercase tracking-wider dark:text-slate-300">Kural Tipi</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex-1 min-w-[140px] cursor-pointer group">
                                <input type="radio" x-model="logicForm.condition" value="filled" class="hidden peer">
                                <div class="p-4 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-2xl text-center peer-checked:border-purple-600 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 group-hover:bg-gray-50 transition-all dark:border-slate-700">
                                    <i class="fas fa-check-circle mb-2 text-gray-300 peer-checked:text-purple-600"></i>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Doluysa Göster</div>
                                </div>
                            </label>

                            <label class="flex-1 min-w-[140px] cursor-pointer group">
                                <input type="radio" x-model="logicForm.condition" value="equals" class="hidden peer">
                                <div class="p-4 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-2xl text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 group-hover:bg-gray-50 transition-all dark:border-slate-700">
                                    <i class="fas fa-equals mb-2 text-gray-300 peer-checked:text-blue-600"></i>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Eşitse Göster</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-8 bg-gray-50 dark:bg-gray-900/80 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between dark:bg-slate-900">
                <button @click="showAddLogicModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 font-bold text-sm">İptal</button>
                <button @click="saveLogic()" :disabled="!logicForm.field_slug || !logicForm.depends_on"
                    class="px-10 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-2xl shadow-xl shadow-purple-500/30 hover:shadow-purple-500/50 hover:-translate-y-1 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    Kaydet & Yayına Al
                </button>
            </div>
        </div>
    </div>
@endsection
