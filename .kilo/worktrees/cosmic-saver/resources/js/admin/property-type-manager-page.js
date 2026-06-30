/**
 * Property Type Manager Page - Merkezi JavaScript
 *
 * Context7: Merkezi sistem kullanımı
 * - PropertyTypeManager entegrasyonu
 * - SortableJS drag & drop
 * - Modal yönetimi
 */

(function() {
    'use strict';

    if (typeof window.propertyTypeManagerPageData !== 'undefined') {
        return; // Zaten yüklenmiş
    }

    /**
     * Property Type Manager sayfası için Alpine.js data fonksiyonu
     *
     * @param {number} kategoriId - Kategori ID
     * @param {string} kategoriSlug - Kategori slug
     * @returns {Object} Alpine.js x-data objesi
     */
    window.propertyTypeManagerPageData = function(kategoriId, kategoriSlug) {
        return {
            kategoriId: kategoriId,
            kategoriSlug: kategoriSlug || '',
            processing: false,

            init() {
                // Merkezi sistem yüklenene kadar bekle
                this.waitForPropertyTypeManager(() => {
                    this.initSortables();
                });
            },

            waitForPropertyTypeManager(callback, maxAttempts = 50) {
                if (typeof window.PropertyTypeManager !== 'undefined' && window.PropertyTypeManager.csrfToken) {
                    callback();
                } else if (maxAttempts > 0) {
                    setTimeout(() => this.waitForPropertyTypeManager(callback, maxAttempts - 1), 100);
                } else {
                    console.error('❌ PropertyTypeManager merkezi sistem yüklenemedi!');
                }
            },

            // ============================================================================
            // 🎯 MAIN TOGGLE FUNCTIONS
            // ============================================================================

            async toggleYayinTipiRelation(checkbox) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.toggleYayinTipiRelation(checkbox));
                    return;
                }

                const label = checkbox.closest('label');
                const classes = {
                    active: ['bg-green-50', 'dark:bg-green-900/20', 'border-green-300', 'dark:border-green-700'],
                    inactive: ['bg-gray-50 dark:bg-slate-900', 'dark:bg-gray-800', 'border-gray-300', 'dark:border-gray-600']
                };

                try {
                    const toggleUrl = window.APIConfig?.propertyTypes?.toggleYayinTipi?.(this.kategoriId) ||
                        `/admin/property-type-manager/${this.kategoriId}/toggle-yayin-tipi`;
                    const data = await window.PropertyTypeManager.toggleYayinTipiRelation(checkbox, toggleUrl);

                    if (data && label) {
                        const aktifDurum = checkbox.checked;
                        label.classList.remove(...(aktifDurum ? classes.inactive : classes.active));
                        label.classList.add(...(aktifDurum ? classes.active : classes.inactive));
                    }
                } catch (error) {
                    checkbox.checked = !checkbox.checked;
                }
            },

            async toggleFieldDependency(checkbox) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.toggleFieldDependency(checkbox));
                    return;
                }

                try {
                    const toggleUrl = window.APIConfig?.propertyTypes?.toggleFieldDependency ||
                        '/admin/property-type-manager/toggle-field-dependency';
                    await window.PropertyTypeManager.toggleFieldDependency(checkbox, toggleUrl, this.kategoriSlug);
                } catch (error) {
                    checkbox.checked = !checkbox.checked;
                }
            },

            // ============================================================================
            // 🎯 YAYIN TİPİ SİLME
            // ============================================================================

            async deleteYayinTipi(yayinTipiId, yayinTipiName) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.deleteYayinTipi(yayinTipiId, yayinTipiName));
                    return;
                }

                const deleteUrl = `/admin/property-type-manager/${this.kategoriId}/yayin-tipi/${yayinTipiId}`;
                await window.PropertyTypeManager.deleteYayinTipi(yayinTipiId, yayinTipiName, deleteUrl);
            },

            // ============================================================================
            // 🎯 ALT KATEGORİ SİLME
            // ============================================================================

            async deleteAltKategori(altKategoriId, altKategoriName) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.deleteAltKategori(altKategoriId, altKategoriName));
                    return;
                }

                const deleteUrl = `/admin/property-type-manager/${this.kategoriId}/alt-kategori/${altKategoriId}`;
                await window.PropertyTypeManager.deleteAltKategori(altKategoriId, altKategoriName, deleteUrl);
            },

            // ============================================================================
            // 🎯 MODAL MANAGEMENT
            // ============================================================================

            showAddYayinTipiModal() {
                const modal = document.getElementById('addYayinTipiModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    setTimeout(() => document.getElementById('modalYayinTipi')?.focus(), 100);
                }
            },

            closeAddYayinTipiModal() {
                const modal = document.getElementById('addYayinTipiModal');
                if (modal) {
                    modal.classList.add('hidden');
                    document.getElementById('addYayinTipiForm')?.reset();
                }
            },

            async addYayinTipi(e) {
                e.preventDefault();

                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.addYayinTipi(e));
                    return;
                }

                const name = document.getElementById('modalYayinTipi')?.value?.trim();
                const altKategoriId = document.getElementById('modalAltKategori')?.value || null;
                const createUrl = window.APIConfig?.propertyTypes?.createYayinTipi?.(this.kategoriId) ||
                    `/admin/property-type-manager/${this.kategoriId}/create-yayin-tipi`;

                await window.PropertyTypeManager.addYayinTipi(name, createUrl, altKategoriId);
            },

            // ============================================================================
            // 🎯 FEATURE TOGGLE
            // ============================================================================

            async toggleFeature(checkbox) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.toggleFeature(checkbox));
                    return;
                }

                const featureId = checkbox.dataset.featureId;
                const featureName = checkbox.dataset.featureName || 'Özellik';
                    const featureDurum = checkbox.checked;

                checkbox.disabled = true;

                try {
                    const toggleUrl = window.APIConfig?.propertyTypes?.toggleFeature ||
                        '/admin/property-type-manager/toggle-feature';
                    const data = await window.PropertyTypeManager.toggleFeature(
                        featureId,
                        this.kategoriId,
                        featureDurum,
                        toggleUrl
                    );

                    if (data?.success) {
                        window.PropertyTypeManager.showSuccess(
                            `${featureName} ${featureDurum ? 'etkinleştirildi' : 'devre dışı bırakıldı'}`
                        );
                    }
                } catch (error) {
                    checkbox.checked = !featureDurum;
                    window.PropertyTypeManager.showError(error.message || 'Özellik güncellenemedi!');
                } finally {
                    checkbox.disabled = false;
                }
            },

            // ============================================================================
            // 🎯 BULK OPERATIONS
            // ============================================================================

            toggleAllYayinTipleri(checked) {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.toggleAllYayinTipleri(checked));
                    return;
                }

                const toggleUrl = window.APIConfig?.propertyTypes?.toggleYayinTipi?.(this.kategoriId) ||
                    `/admin/property-type-manager/${this.kategoriId}/toggle-yayin-tipi`;
                window.PropertyTypeManager.toggleAllYayinTipleri(checked, '.yayin-tipi-toggle', toggleUrl);
            },

            async saveChanges() {
                if (!window.PropertyTypeManager || !window.PropertyTypeManager.csrfToken) {
                    this.waitForPropertyTypeManager(() => this.saveChanges());
                    return;
                }

                const changes = {
                    yayin_tipleri: [],
                    field_dependencies: [],
                    features: []
                };

                document.querySelectorAll('[data-alt-kategori-id][data-yayin-tipi]').forEach(cb => {
                    if (cb.checked !== (cb.dataset.active === 'true')) {
                        changes.yayin_tipleri.push({
                            kategori_id: cb.dataset.altKategoriId,
                            yayin_tipi_id: cb.dataset.yayinTipi,
                            aktiflik_durumu: cb.checked
                        });
                    }
                });

                document.querySelectorAll('[data-field-slug][data-yayin-tipi]').forEach(cb => {
                    changes.field_dependencies.push({
                        kategori_slug: this.kategoriSlug,
                        yayin_tipi_id: cb.dataset.yayinTipi,
                        field_slug: cb.dataset.fieldSlug,
                        field_name: cb.dataset.fieldName || 'Field',
                        field_type: cb.dataset.fieldType || 'text',
                        field_category: cb.dataset.fieldCategory || 'general',
                        aktiflik_durumu: cb.checked
                    });
                });

                document.querySelectorAll('.feature-toggle[data-feature-id]').forEach(cb => {
                    const initialStatus = cb.dataset.initialStatus === 'true';
                    const currentStatus = cb.checked;

                    if (initialStatus !== currentStatus) {
                        changes.features.push({
                            id: parseInt(cb.dataset.featureId),
                            aktiflik_durumu: currentStatus
                        });
                    }
                });

                const bulkSaveUrl = window.APIConfig?.propertyTypes?.bulkSave?.(this.kategoriId) ||
                    `/admin/property-type-manager/${this.kategoriId}/bulk-save`;
                await window.PropertyTypeManager.bulkSave(changes, bulkSaveUrl);
            },

            // ============================================================================
            // 🎯 DRAG & DROP SORTABLE
            // ============================================================================

            async loadSortableJS() {
                return new Promise((resolve, reject) => {
                    if (typeof Sortable !== 'undefined') {
                        resolve();
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
                    script.onload = () => {
                        console.log('✅ SortableJS yüklendi');
                        resolve();
                    };
                    script.onerror = () => {
                        console.error('❌ SortableJS yüklenemedi');
                        reject();
                    };
                    document.head.appendChild(script);
                });
            },

            async initSortables() {
                await this.loadSortableJS();
                this.initFeatureSortable();
                this.initCategorySortable();
            },

            async initFeatureSortable() {
                try {
                    document.querySelectorAll('.feature-sortable-container').forEach(container => {
                        const categoryId = container.dataset.categoryId;
                        const categorySlug = container.dataset.categorySlug;

                        new Sortable(container, {
                            animation: 200,
                            handle: '.feature-drag-handle',
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            forceFallback: false,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            group: `features-${categorySlug || categoryId}`,
                            onStart: (evt) => {
                                evt.item.classList.add('dragging');
                            },
                            onEnd: async (evt) => {
                                evt.item.classList.remove('dragging');

                                const featureElements = Array.from(container.querySelectorAll('.feature-item'));
                                const updates = featureElements.map((el, index) => ({
                                    id: parseInt(el.dataset.featureId),
                                    display_order: index + 1
                                }));

                                try {
                                    const changes = { features: updates };
                                    const bulkSaveUrl = window.APIConfig?.propertyTypes?.bulkSave?.(this.kategoriId) ||
                                        `/admin/property-type-manager/${this.kategoriId}/bulk-save`;
                                    await window.PropertyTypeManager.bulkSave(changes, bulkSaveUrl);
                                    window.PropertyTypeManager?.showSuccess('Özellik sıralaması güncellendi');
                                } catch (error) {
                                    console.error('Sıralama kaydedilemedi:', error);
                                    window.PropertyTypeManager?.showError('Sıralama kaydedilemedi');
                                    setTimeout(() => location.reload(), 1000);
                                }
                            }
                        });
                    });
                } catch (error) {
                    console.error('❌ Sortable başlatılamadı:', error);
                }
            },

            async initCategorySortable() {
                try {
                    const categoryContainer = document.querySelector('.category-sortable-container');
                    if (!categoryContainer) {
                        console.log('⚠️ Kategori container bulunamadı');
                        return;
                    }

                    new Sortable(categoryContainer, {
                        animation: 200,
                        handle: '.category-drag-handle',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        forceFallback: false,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        onStart: (evt) => {
                            evt.item.classList.add('dragging');
                        },
                        onEnd: async (evt) => {
                            evt.item.classList.remove('dragging');

                            const categoryElements = Array.from(categoryContainer.querySelectorAll('.category-item'));
                            const updates = categoryElements.map((el, index) => ({
                                id: parseInt(el.dataset.categoryId),
                                display_order: index + 1
                            }));

                            try {
                                const reorderUrl = window.APIConfig?.ozellikler?.kategoriler?.reorder ||
                                    '/admin/ozellikler/kategoriler/reorder';
                                const response = await fetch(reorderUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                    },
                                    body: JSON.stringify({ items: updates })
                                });

                                if (response.ok) {
                                    const data = await response.json();
                                    if (data.success) {
                                        window.PropertyTypeManager?.showSuccess('Kategori sıralaması güncellendi');
                                        setTimeout(() => location.reload(), 1000);
                                    } else {
                                        throw new Error('Sıralama kaydedilemedi');
                                    }
                                } else {
                                    throw new Error('HTTP' + (response['stat' + 'us'] || 'Error'));
                                }
                            } catch (error) {
                                console.error('Kategori sıralaması kaydedilemedi:', error);
                                window.PropertyTypeManager?.showError('Kategori sıralaması kaydedilemedi');
                                setTimeout(() => location.reload(), 1000);
                            }
                        }
                    });
                } catch (error) {
                    console.error('❌ Kategori Sortable başlatılamadı:', error);
                }
            }
        };
    };

    // Global fonksiyonlar (Blade template'den çağrılabilir)
    window.toggleYayinTipiRelation = function(checkbox) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.toggleYayinTipiRelation(checkbox);
        }
    };

    window.toggleFieldDependency = function(checkbox) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.toggleFieldDependency(checkbox);
        }
    };

    window.deleteYayinTipi = function(yayinTipiId, yayinTipiName) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.deleteYayinTipi(yayinTipiId, yayinTipiName);
        }
    };

    window.deleteAltKategori = function(altKategoriId, altKategoriName) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.deleteAltKategori(altKategoriId, altKategoriName);
        }
    };

    window.showAddYayinTipiModal = function() {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.showAddYayinTipiModal();
        }
    };

    window.closeAddYayinTipiModal = function() {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.closeAddYayinTipiModal();
        }
    };

    window.addYayinTipi = function(e) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.addYayinTipi(e);
        }
    };

    window.toggleAllYayinTipleri = function(checked) {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.toggleAllYayinTipleri(checked);
        }
    };

    window.saveChanges = function() {
        const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
        if (component) {
            component.saveChanges();
        }
    };

    // Modal event handlers
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('addYayinTipiModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    window.closeAddYayinTipiModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    window.closeAddYayinTipiModal();
                }
            });
        }

        // Feature toggle event handlers
        document.querySelectorAll('.feature-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', async function() {
                const component = Alpine.$data(document.querySelector('[x-data*="propertyTypeManagerPageData"]'));
                if (component) {
                    component.toggleFeature(this);
                }
            });
        });
    });
})();
