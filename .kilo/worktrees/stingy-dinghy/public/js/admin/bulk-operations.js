/**
 * Context7 Bulk Operations Manager
 *
 * @description Multi-select and batch operations for tables
 * @author Yalıhan Emlak - Context7 Team
 * @date 2025-11-04
 * @version 1.0.0
 *
 * Yalıhan Bekçi Standards:
 * - Pure vanilla JS
 * - Alpine.js compatible
 * - AJAX-based
 * - Toast notifications
 */

const BulkOperations = {
    selectedItems: new Set(),

    /**
     * Initialize bulk operations for a table
     *
     * @param {string} tableSelector - Table selector
     */
    init(tableSelector = 'table') {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        // Add select all checkbox to header
        const headerCheckbox = table.querySelector('thead input[type="checkbox"]');
        if (headerCheckbox) {
            headerCheckbox.addEventListener('change', (e) => {
                this.selectAll(e.target.checked, tableSelector);
            });
        }

        // Add individual checkboxes change event
        const checkboxes = table.querySelectorAll('tbody input[type="checkbox"].bulk-select');
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                this.updateSelection();
            });
        });

        this.updateUI();
    },

    /**
     * Select/deselect all items
     */
    selectAll(checked, tableSelector = 'table') {
        const checkboxes = document.querySelectorAll(
            `${tableSelector} tbody input[type="checkbox"].bulk-select`
        );
        checkboxes.forEach((checkbox) => {
            checkbox.checked = checked;
            const id = checkbox.value;
            if (checked) {
                this.selectedItems.add(id);
            } else {
                this.selectedItems.delete(id);
            }
        });
        this.updateUI();
    },

    /**
     * Update selection based on checkboxes
     */
    updateSelection() {
        this.selectedItems.clear();
        const checkboxes = document.querySelectorAll(
            'tbody input[type="checkbox"].bulk-select:checked'
        );
        checkboxes.forEach((checkbox) => {
            this.selectedItems.add(checkbox.value);
        });
        this.updateUI();
    },

    /**
     * Update UI (selection count, action buttons)
     */
    updateUI() {
        const count = this.selectedItems.size;

        // Update count display
        const countEl = document.getElementById('bulk-selected-count');
        if (countEl) {
            countEl.textContent = count;
        }

        // Show/hide bulk action bar
        const bulkBar = document.getElementById('bulk-action-bar');
        if (bulkBar) {
            if (count > 0) {
                bulkBar.classList.remove('hidden');
                bulkBar.classList.add('flex');
            } else {
                bulkBar.classList.add('hidden');
                bulkBar.classList.remove('flex');
            }
        }

        // Update select all checkbox state
        const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
        const totalCheckboxes = document.querySelectorAll(
            'tbody input[type="checkbox"].bulk-select'
        ).length;
        if (headerCheckbox) {
            headerCheckbox.checked = count === totalCheckboxes && count > 0;
            headerCheckbox.indeterminate = count > 0 && count < totalCheckboxes;
        }
    },

    /**
     * Bulk assign category
     */
    async assignCategory(categoryId, endpoint) {
        if (this.selectedItems.size === 0) {
            window.toast?.warning('Lütfen en az bir öğe seçin');
            return;
        }

        const confirmed = await window.confirmDialog(
            `${this.selectedItems.size} öğeye kategori atanacak. Devam edilsin mi?`,
            { title: 'Toplu Kategori Atama' }
        );

        if (!confirmed) return;

        const hideLoading = window.showLoading?.(document.querySelector('.neo-container'));

        try {
            const result = await window.AjaxHelper.post(endpoint, {
                items: Array.from(this.selectedItems),
                category_id: categoryId,
            });

            if (result.success) {
                window.toast?.success(`${this.selectedItems.size} öğe güncellendi`);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.toast?.error(result.message);
            }
        } catch (error) {
            window.toast?.error('Toplu güncelleme başarısız');
        } finally {
            hideLoading?.();
        }
    },

    /**
     * Bulk enable/disable
     */
    async toggleStatus(status, endpoint) {
        if (this.selectedItems.size === 0) {
            window.toast?.warning('Lütfen en az bir öğe seçin');
            return;
        }

        const action = status ? 'aktif' : 'pasif';
        const confirmed = await window.confirmDialog(
            `${this.selectedItems.size} öğe ${action} yapılacak. Devam edilsin mi?`,
            { title: `Toplu ${action.toUpperCase()} Yapma` }
        );

        if (!confirmed) return;

        const hideLoading = window.showLoading?.(document.querySelector('.neo-container'));

        try {
            const result = await window.AjaxHelper.post(endpoint, {
                items: Array.from(this.selectedItems),
                status: status,
            });

            if (result.success) {
                window.toast?.success(`${this.selectedItems.size} öğe ${action} yapıldı`);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.toast?.error(result.message);
            }
        } catch (error) {
            window.toast?.error('Toplu güncelleme başarısız');
        } finally {
            hideLoading?.();
        }
    },

    /**
     * Bulk delete
     */
    async delete(endpoint) {
        if (this.selectedItems.size === 0) {
            window.toast?.warning('Lütfen en az bir öğe seçin');
            return;
        }

        const confirmed = await window.confirmDialog(
            `${this.selectedItems.size} öğe SİLİNECEK! Bu işlem geri alınamaz. Devam edilsin mi?`,
            {
                title: 'TOPLU SİLME (Dikkat!)',
                confirmText: 'Evet, Sil',
                cancelText: 'İptal',
            }
        );

        if (!confirmed) return;

        const hideLoading = window.showLoading?.(document.querySelector('.neo-container'));

        try {
            const result = await window.AjaxHelper.post(endpoint, {
                items: Array.from(this.selectedItems),
            });

            if (result.success) {
                window.toast?.success(`${this.selectedItems.size} öğe silindi`);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.toast?.error(result.message);
            }
        } catch (error) {
            window.toast?.error('Toplu silme başarısız');
        } finally {
            hideLoading?.();
        }
    },

    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedItems.clear();
        document.querySelectorAll('input[type="checkbox"].bulk-select').forEach((cb) => {
            cb.checked = false;
        });
        this.updateUI();
    },
};

// Global availability
window.BulkOperations = BulkOperations;
