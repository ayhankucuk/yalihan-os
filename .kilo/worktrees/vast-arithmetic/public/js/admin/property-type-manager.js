/**
 * Context7 Property Type Manager - Merkezi Sistem
 *
 * Merkezi Property Type Manager sistemi
 * Tüm property type yönetimi işlemleri için tek bir sistem
 *
 * @version 1.0.0
 * @since 2025-12-09
 */

window.PropertyTypeManager = {
    csrfToken: null,
    debounceTimers: {},
    debugMode: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',

    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!this.csrfToken) {
            this.log('error', 'CSRF token NOT FOUND!');
            this.showError('CSRF token eksik! Lütfen sayfayı yenileyin (F5).');
        } else {
            this.log('info', 'CSRF token cached:', this.csrfToken.substring(0, 15) + '...');
        }
        return this;
    },

    log(level, ...args) {
        if (!this.debugMode) return;
        
        const levels = {
            info: console.log,
            error: console.error,
            warn: console.warn,
            debug: console.debug
        };
        
        const logger = levels[level] || console.log;
        logger(`[PropertyTypeManager]`, ...args);
    },

    async request(url, data = {}, method = 'POST') {
        if (!this.csrfToken) {
            throw new Error('CSRF token not initialized');
        }

        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        };

        if (method !== 'GET' && method !== 'DELETE') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);

            const contentType = response.headers.get('content-type');
            if (!contentType?.includes('application/json')) {
                const text = await response.text();
                this.log('error', 'Non-JSON response:', text.substring(0, 500));
                throw new Error('Server returned HTML instead of JSON');
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            return response.json();
        } catch (error) {
            this.log('error', 'Request failed:', error);
            throw error;
        }
    },

    debounce(key, callback, delay = 300) {
        clearTimeout(this.debounceTimers[key]);
        this.debounceTimers[key] = setTimeout(callback, delay);
    },

    showSuccess(message) {
        if (window.toast?.success) {
            window.toast.success(message);
        } else {
            const toast = document.getElementById('successToast');
            if (toast) {
                const span = toast.querySelector('span');
                if (span) {
                    span.textContent = message;
                }
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 3000);
            }
        }
    },

    showError(message) {
        if (window.toast?.error) {
            window.toast.error(message);
        } else {
            const toast = document.getElementById('errorToast');
            if (toast) {
                const span = toast.querySelector('span');
                if (span) {
                    span.textContent = message;
                }
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 3000);
            }
        }
    },

    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('hidden', !show);
        }
    },

    async toggleYayinTipiRelation(checkbox, routeUrl) {
        const {
            altKategoriId,
            yayinTipiId,
            yayinTipiName
        } = checkbox.dataset;
        const status = checkbox.checked;
        const label = checkbox.closest('label');

        checkbox.disabled = true;
        label?.classList.add('opacity-50', 'cursor-wait');

        try {
            const data = await this.request(routeUrl, {
                alt_kategori_id: altKategoriId,
                yayin_tipi_id: yayinTipiId,
                status: status
            });

            if (data.success) {
                const classes = {
                    active: ['bg-green-50', 'dark:bg-green-900/20', 'border-green-300', 'dark:border-green-700'],
                    inactive: ['bg-gray-50 dark:bg-slate-900', 'dark:bg-gray-800', 'border-gray-300', 'dark:border-gray-600']
                };

                if (label) {
                    label.classList.remove(...(status ? classes.inactive : classes.active));
                    label.classList.add(...(status ? classes.active : classes.inactive));
                }

                this.showSuccess(`${yayinTipiName} ${status ? 'etkinleştirildi' : 'devre dışı bırakıldı'}`);
                this.log('info', 'Yayın tipi ilişkisi güncellendi:', data);
                return data;
            }
        } catch (error) {
            this.log('error', 'Toggle hatası:', error);
            checkbox.checked = !status;
            this.showError(error.message || 'Güncelleme başarısız!');
            throw error;
        } finally {
            checkbox.disabled = false;
            label?.classList.remove('opacity-50', 'cursor-wait');
        }
    },

    async toggleFieldDependency(checkbox, routeUrl, kategoriSlug) {
        const {
            fieldId,
            fieldSlug,
            fieldName,
            fieldType,
            fieldCategory,
            yayinTipiId,
            yayinTipiSlug
        } = checkbox.dataset;
        const status = checkbox.checked;
        const upsertMode = !fieldId;

        checkbox.disabled = true;

        try {
            const payload = upsertMode ? {
                kategori_slug: kategoriSlug,
                field_slug: fieldSlug,
                field_name: fieldName || 'Field',
                field_type: fieldType || 'text',
                field_category: fieldCategory || 'general',
                yayin_tipi_id: yayinTipiId,
                yayin_tipi: yayinTipiSlug,
                status: status
            } : {
                field_id: parseInt(fieldId),
                status: status
            };

            const data = await this.request(routeUrl, payload);

            if (data.success) {
                if (upsertMode && data.data?.field_id) {
                    checkbox.setAttribute('data-field-id', data.data.field_id);
                }

                this.showSuccess('Alan ilişkisi güncellendi');
                this.log('info', 'Field dependency güncellendi:', data);
                return data;
            }
        } catch (error) {
            this.log('error', 'Toggle hatası:', error);
            checkbox.checked = !status;
            this.showError(error.message || 'Alan ilişkisi güncellenemedi!');
            throw error;
        } finally {
            checkbox.disabled = false;
        }
    },

    async deleteYayinTipi(yayinTipiId, yayinTipiName, routeUrl) {
        if (!confirm(`"${yayinTipiName}" yayın tipini silmek istediğinize emin misiniz?\n\n⚠️ Bu yayın tipine ait ilanlar varsa silme işlemi başarısız olacaktır.`)) {
            return;
        }

        this.showLoading(true);

        try {
            const data = await this.request(routeUrl, {}, 'DELETE');

            if (data.success) {
                this.showSuccess(`"${yayinTipiName}" yayın tipi başarıyla silindi! Sayfa yenileniyor...`);
                setTimeout(() => location.reload(), 1500);
                return data;
            }
        } catch (error) {
            this.showLoading(false);
            this.showError(error.message || 'Yayın tipi silinirken bir hata oluştu!');
            this.log('error', 'Delete error:', error);
            throw error;
        }
    },

    async deleteAltKategori(altKategoriId, altKategoriName, routeUrl) {
        if (!confirm(`"${altKategoriName}" alt kategorisini silmek istediğinize emin misiniz?\n\n⚠️ Bu alt kategoriye ait ilanlar veya alt kategoriler varsa silme işlemi başarısız olacaktır.`)) {
            return;
        }

        this.showLoading(true);

        try {
            const data = await this.request(routeUrl, {}, 'DELETE');

            if (data.success) {
                this.showSuccess(`"${altKategoriName}" alt kategorisi başarıyla silindi! Sayfa yenileniyor...`);
                setTimeout(() => location.reload(), 1500);
                return data;
            }
        } catch (error) {
            this.showLoading(false);
            this.showError(error.message || 'Alt kategori silinirken bir hata oluştu!');
            this.log('error', 'Delete error:', error);
            throw error;
        }
    },

    async addYayinTipi(name, routeUrl, altKategoriId = null) {
        if (!name?.trim()) {
            this.showError('Yayın tipi adı gerekli');
            return;
        }

        this.showLoading(true);

        try {
            const data = await this.request(routeUrl, { name, alt_kategori_id: altKategoriId });

            if (data.success) {
                this.showSuccess('Yayın tipi eklendi! Sayfa yenileniyor...');
                setTimeout(() => location.reload(), 1000);
                return data;
            }
        } catch (error) {
            this.showLoading(false);
            this.showError(error.message || 'Ekleme başarısız!');
            this.log('error', 'Add error:', error);
            throw error;
        }
    },

    async toggleFeature(featureId, kategoriId, status, routeUrl) {
        try {
            const data = await this.request(routeUrl, {
                feature_id: featureId,
                kategori_id: kategoriId,
                status: status
            });

            if (data.success) {
                return data;
            }
        } catch (error) {
            this.log('error', 'Feature toggle hatası:', error);
            throw error;
        }
    },

    toggleAllYayinTipleri(checked, checkboxSelector, routeUrl) {
        this.debounce('bulkToggle', () => {
            const checkboxes = document.querySelectorAll(checkboxSelector);
            const count = Array.from(checkboxes).filter(cb => cb.checked !== checked).length;

            if (count === 0) {
                this.showSuccess('Tüm değerler zaten bu durumda');
                return;
            }

            this.showLoading(true);

            let completed = 0;
            checkboxes.forEach(cb => {
                if (cb.checked !== checked) {
                    cb.checked = checked;
                    this.toggleYayinTipiRelation(cb, routeUrl).finally(() => {
                        completed++;
                        if (completed === count) {
                            this.showLoading(false);
                            this.showSuccess(`${count} değişiklik tamamlandı`);
                        }
                    });
                }
            });
        }, 100);
    },

    async bulkSave(changes, routeUrl) {
        this.showLoading(true);

        try {
            const totalChanges = changes.yayin_tipleri?.length +
                changes.field_dependencies?.length +
                changes.features?.length || 0;

            if (totalChanges === 0) {
                this.showLoading(false);
                this.showSuccess('Değişiklik yok');
                return;
            }

            const data = await this.request(routeUrl, changes);

            if (data.success) {
                this.showSuccess(`${totalChanges} değişiklik kaydedildi! Sayfa yenileniyor...`);
                setTimeout(() => location.reload(), 2000);
                return data;
            }
        } catch (error) {
            this.showLoading(false);
            this.showError(error.message || 'Kaydetme başarısız!');
            this.log('error', 'Bulk save error:', error);
            throw error;
        }
    }
};

if (typeof window !== 'undefined') {
    window.PropertyTypeManager = window.PropertyTypeManager || PropertyTypeManager;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PropertyTypeManager.init());
    } else {
        PropertyTypeManager.init();
    }
}
