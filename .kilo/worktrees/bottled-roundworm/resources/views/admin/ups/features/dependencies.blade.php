@extends('admin.layouts.admin')

@section('title', 'Feature Dependencies')

@section('content')
<div class="px-4 py-6" x-data="featureDependenciesManager()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm mb-6 dark:shadow-none">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">Feature Dependencies Matrix</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Özelliklerin koşullu görünürlüğünü yönetin (örn: "Havuz Tipi" sadece "Havuz Var" seçiliyse görünsün)
                    </p>
                </div>
                <button @click="openCreateModal()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg 
                               transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Yeni Bağımlılık
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Dependencies List -->
        <div x-show="!loading" class="bg-white dark:bg-slate-900 rounded-lg shadow-sm dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Bağımlı Özellik (Child)
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Koşul
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Ana Özellik (Parent)
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="dep in dependencies" :key="dep.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="dep.feature?.name"></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="dep.feature?.slug"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-md bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200"
                                              x-text="formatCondition(dep)"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="dep.parent_feature?.name"></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="dep.parent_feature?.slug"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="toggleStatus(dep.id)"
                                            :class="dep.aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' dark:text-slate-200"
                                            class="px-2.5 py-1 text-xs font-medium rounded-full transition-all duration-200">
                                        <span x-text="dep.aktiflik_durumu ? 'Aktif' : 'Pasif'"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="editDependency(dep)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 transition-colors duration-150">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteDependency(dep.id)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="dependencies.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle text-3xl mb-3"></i>
                                <p>Henüz bağımlılık tanımlanmamış</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="showModal"
             x-cloak
             @click.self="showModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-2xl w-full mx-4 transform transition-all duration-200"
                 @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="editingId ? 'Bağımlılığı Düzenle' : 'Yeni Bağımlılık Ekle'"></h3>
                </div>

                <form @submit.prevent="saveDependency()" class="px-6 py-4 space-y-4">
                    <!-- Child Feature -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Bağımlı Özellik (Görünürlüğü kontrol edilecek)
                        </label>
                        <select x-model="form.feature_id" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       transition-all duration-200">
                            <option value="">Seçiniz...</option>
                            <template x-for="feature in allFeatures" :key="feature.id">
                                <option :value="feature.id" x-text="`${feature.name} (${feature.slug})`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Parent Feature -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Ana Özellik (Bu seçilince yukarıdaki özellik görünsün)
                        </label>
                        <select x-model="form.parent_feature_id" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       transition-all duration-200">
                            <option value="">Seçiniz...</option>
                            <template x-for="feature in allFeatures" :key="feature.id">
                                <option :value="feature.id" x-text="`${feature.name} (${feature.slug})`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Operator -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Operatör
                            </label>
                            <select x-model="form.operator"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                           bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                           focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                           transition-all duration-200">
                                <option value="=">=</option>
                                <option value="!=">!=</option>
                                <option value=">">></option>
                                <option value="<"><</option>
                                <option value=">=">>=</option>
                                <option value="<="><=</option>
                                <option value="in">in (virgülle ayır)</option>
                                <option value="not_in">not in</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Parent Değeri (null = true için)
                            </label>
                            <input type="text" x-model="form.parent_value"
                                   placeholder="Örn: merkezi, 1, true"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                          bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                          focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          transition-all duration-200">
                        </div>
                    </div>

                    <!-- Condition Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Koşul Tipi
                        </label>
                        <select x-model="form.condition_type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       transition-all duration-200">
                            <option value="show">Göster (koşul sağlanırsa göster)</option>
                            <option value="hide">Gizle (koşul sağlanırsa gizle)</option>
                        </select>
                    </div>

                    <!-- Display Order -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Görüntülenme Sırası
                        </label>
                        <input type="number" x-model.number="form.display_order" min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      transition-all duration-200">
                    </div>

                    <!-- Aktiflik Durumu -->
                    <div class="flex items-center">
                        <input type="checkbox" x-model="form.aktiflik_durumu" id="aktiflik_durumu"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="aktiflik_durumu" class="ml-2 block text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            Aktif
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600
                                       text-gray-800 dark:text-gray-200 rounded-lg transition-all duration-200">
                            İptal
                        </button>
                        <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                       transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!saving">Kaydet</span>
                            <span x-show="saving">Kaydediliyor...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function featureDependenciesManager() {
    return {
        loading: true,
        saving: false,
        showModal: false,
        editingId: null,
        dependencies: [],
        allFeatures: [],
        form: {
            feature_id: '',
            parent_feature_id: '',
            parent_value: '',
            operator: '=',
            condition_type: 'show',
            aktiflik_durumu: true,
            display_order: 0
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const [depsResponse, featuresResponse] = await Promise.all([
                    fetch('/api/v1/admin/feature-dependencies'),
                    fetch('/api/v1/admin/features?per_page=500')
                ]);

                const depsData = await depsResponse.json();
                const featuresData = await featuresResponse.json();

                this.dependencies = depsData.data || [];
                this.allFeatures = featuresData.data || [];
            } catch (error) {
                console.error('Veri yükleme hatası:', error);
                alert('Veriler yüklenirken bir hata oluştu');
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.editingId = null;
            this.form = {
                feature_id: '',
                parent_feature_id: '',
                parent_value: '',
                operator: '=',
                condition_type: 'show',
                aktiflik_durumu: true,
                display_order: 0
            };
            this.showModal = true;
        },

        editDependency(dep) {
            this.editingId = dep.id;
            this.form = {
                feature_id: dep.feature_id,
                parent_feature_id: dep.parent_feature_id,
                parent_value: dep.parent_value || '',
                operator: dep.operator,
                condition_type: dep.condition_type,
                aktiflik_durumu: dep.aktiflik_durumu,
                display_order: dep.display_order
            };
            this.showModal = true;
        },

        async saveDependency() {
            this.saving = true;
            try {
                const url = this.editingId
                    ? `/api/v1/admin/feature-dependencies/${this.editingId}`
                    : '/api/v1/admin/feature-dependencies';

                const method = this.editingId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                if (!response.ok) throw new Error('Kayıt başarısız');

                await this.loadData();
                this.showModal = false;
                alert(this.editingId ? 'Bağımlılık güncellendi' : 'Bağımlılık eklendi');
            } catch (error) {
                console.error('Kayıt hatası:', error);
                alert('Kayıt sırasında bir hata oluştu');
            } finally {
                this.saving = false;
            }
        },

        async toggleStatus(id) {
            try {
                const response = await fetch(`/api/v1/admin/feature-dependencies/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Durum değiştirilemedi');

                await this.loadData();
            } catch (error) {
                console.error('Durum değiştirme hatası:', error);
                alert('Durum değiştirirken bir hata oluştu');
            }
        },

        async deleteDependency(id) {
            if (!confirm('Bu bağımlılığı silmek istediğinizden emin misiniz?')) return;

            try {
                const response = await fetch(`/api/v1/admin/feature-dependencies/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Silme başarısız');

                await this.loadData();
                alert('Bağımlılık silindi');
            } catch (error) {
                console.error('Silme hatası:', error);
                alert('Silme sırasında bir hata oluştu');
            }
        },

        formatCondition(dep) {
            const value = dep.parent_value || 'true';
            const operator = dep.operator;
            const type = dep.condition_type === 'show' ? '→' : '✗';
            return `${type} ${operator} ${value}`;
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
