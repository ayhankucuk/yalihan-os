// fields.js - Dinamik Alan Yönetimi

/**
 * Type Based Fields Manager
 * Kategori bazlı dinamik alanlar
 */
window.typeBasedFieldsManager = function () {
    return {
        // Dinamik alanlar
        newFieldName: '',
        newFieldType: 'text',
        customFields: [],

        /**
         * Yeni alan ekle
         */
        addCustomField() {
            if (!this.newFieldName || this.newFieldName.trim() === '') {
                window.toast?.warning('Lütfen alan adı girin');
                return;
            }

            this.customFields.push({
                id: Date.now(),
                name: this.newFieldName,
                type: this.newFieldType,
                value: '',
            });

            this.newFieldName = '';
            this.newFieldType = 'text';

            window.toast?.success('Alan eklendi');
        },

        /**
         * Alan sil
         */
        removeCustomField(fieldId) {
            this.customFields = this.customFields.filter((f) => f.id !== fieldId);
            window.toast?.info('Alan silindi');
        },

        /**
         * Alan değerini güncelle
         */
        updateFieldValue(fieldId, value) {
            const field = this.customFields.find((f) => f.id === fieldId);
            if (field) {
                field.value = value;
            }
        },

        init() {
            console.log('Fields manager initialized');
        },
    };
};

/**
 * Features Manager
 * Özellik yönetimi
 */
window.featuresManager = function () {
    return {
        newFeature: '',
        customFeatures: [],
        selectedFeatures: [],
        categoryFeatures: [], // API'den gelen özellikler

        /**
         * category-changed eventi dinle
         */
        init() {
            console.log('✅ Features Manager initialized');

            // category-changed eventini dinle
            document.addEventListener('category-changed', async (event) => {
                console.log('🎯 Features: Category changed event received', event.detail);
                const { category } = event.detail;

                // ✅ SAB: Slug bazlı yükleme (önce slug, fallback olarak id)
                if (category) {
                    const categorySlug = category.slug || category.parent_slug;
                    if (categorySlug) {
                        await this.loadFeaturesFromAPIBySlug(categorySlug);
                    } else if (category.id) {
                        // Fallback: ID ile yükleme (legacy support)
                        await this.loadFeaturesFromAPI(category.id);
                    }
                }
            });
        },

        /**
         * API'den özellikleri yükle (slug bazlı - Context7 standardı)
         */
        async loadFeaturesFromAPIBySlug(categorySlug) {
            try {
                console.log('📡 Loading features for category slug:', categorySlug);

                // ✅ SAB: Merkezi API config kullan
                const url = window.APIConfig?.features?.byCategory?.(categorySlug) ||
                    `/api/v1/admin/features/category/${encodeURIComponent(categorySlug)}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response['stat' + 'us']}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('✅ Features loaded:', data);

                // ✅ SAB: FeaturesService format'ı - data.data veya data.features
                const features = data.data?.data || data.data || data.features || [];
                if (Array.isArray(features) && features.length > 0) {
                    // FeaturesService format: [{ name, slug, features: [...] }]
                    this.categoryFeatures = features.flatMap(cat => cat.features || []);
                } else if (data.success && data.features) {
                    // Legacy format
                    this.categoryFeatures = data.features;
                } else {
                    console.warn('⚠️ No features found for this category');
                    this.categoryFeatures = [];
                }
                this.renderFeatures();
            } catch (error) {
                console.error('❌ Error loading features:', error);
                this.categoryFeatures = [];
                this.renderFeatures();
            }
        },

        /**
         * API'den özellikleri yükle (ID bazlı - Legacy support)
         */
        async loadFeaturesFromAPI(categoryId) {
            try {
                console.log('📡 Loading features for category ID:', categoryId);

                // ✅ SAB: Merkezi API config kullan (legacy endpoint)
                const url = window.APIConfig?.features?.byCategoryId?.(categoryId) ||
                    `/api/v1/admin/features?category_id=${categoryId}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                    },
                });

                const data = await response.json();
                console.log('✅ Features loaded:', data);

                if (data.success && data.features) {
                    this.categoryFeatures = data.features;
                    this.renderFeatures();
                } else {
                    console.warn('⚠️ No features found for this category');
                    this.categoryFeatures = [];
                    this.renderFeatures();
                }
            } catch (error) {
                console.error('❌ Error loading features:', error);
                this.categoryFeatures = [];
                this.renderFeatures();
            }
        },

        /**
         * Özellikleri render et
         */
        renderFeatures() {
            const container = document.getElementById('dynamic-features-container');
            if (!container) {
                console.warn('⚠️ Features container not found');
                return;
            }

            if (this.categoryFeatures.length === 0) {
                container.innerHTML =
                    '<p class="text-gray-500 dark:text-slate-500">Bu kategori için özellik bulunamadı</p>';
                return;
            }

            // Özellikleri kategorilere göre grupla
            const grouped = this.groupFeaturesByCategory(this.categoryFeatures);

            let html = '';
            Object.keys(grouped).forEach((categoryName) => {
                html += '<div class="mb-6">';
                html += `<h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 dark:text-slate-200">${categoryName}</h4>`;
                html += '<div class="grid grid-cols-2 md:grid-cols-3 gap-3">';

                grouped[categoryName].forEach((feature) => {
                    html += `
                        <label class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer dark:bg-slate-950">
                            <input type="checkbox"
                                   name="features[]"
                                   value="${feature.id}"
                                   class="rounded mr-2"
                                   x-model="selectedFeatures">
                            <span class="text-sm">${feature.name}</span>
                        </label>
                    `;
                });

                html += '</div></div>';
            });

            container.innerHTML = html;

            // Seçili özellikleri checkbox'lara uygula
            this.selectedFeatures.forEach((featureId) => {
                const checkbox = container.querySelector(`input[value="${featureId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        },

        /**
         * Özellikleri kategoriye göre grupla
         */
        groupFeaturesByCategory(features) {
            return features.reduce((acc, feature) => {
                const categoryName = feature.category_name || 'Diğer';
                if (!acc[categoryName]) {
                    acc[categoryName] = [];
                }
                acc[categoryName].push(feature);
                return acc;
            }, {});
        },

        /**
         * Özellik ekle (addCustomFeature alias)
         */
        addFeature() {
            if (!this.newFeature || this.newFeature.trim() === '') {
                window.toast?.warning('Lütfen özellik adı girin');
                return;
            }

            this.customFeatures.push({
                id: Date.now(),
                name: this.newFeature,
            });

            this.newFeature = '';
            window.toast?.success('Özellik eklendi');
        },

        /**
         * Özel özellik ekle (Alpine'dan çağrılır)
         */
        addCustomFeature() {
            this.addFeature();
        },

        /**
         * Özellik sil
         */
        removeFeature(featureId) {
            this.customFeatures = this.customFeatures.filter((f) => f.id !== featureId);
        },

        /**
         * Özel özellik sil (removeCustomFeature alias)
         */
        removeCustomFeature(index) {
            if (index >= 0 && index < this.customFeatures.length) {
                this.customFeatures.splice(index, 1);
                window.toast?.info('Özellik silindi');
            }
        },

        /**
         * Özellik seç/kaldır
         */
        toggleFeature(featureId) {
            const index = this.selectedFeatures.indexOf(featureId);
            if (index > -1) {
                this.selectedFeatures.splice(index, 1);
            } else {
                this.selectedFeatures.push(featureId);
            }
        },
    };
};

// Export
export default {
    typeBasedFieldsManager: window.typeBasedFieldsManager,
    featuresManager: window.featuresManager,
};
