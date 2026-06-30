/**
 * Context7 Alpine.js Global Stores
 * Merkezi state management için Alpine Store pattern'leri
 *
 * @version 1.0.0
 * @context7-compliant true
 * @alpine-js 3.x
 */

document.addEventListener('alpine:init', () => {
    /**
     * Toast Store
     * Merkezi toast yönetimi
     */
    Alpine.store('toast', {
        toasts: [],
        maxToasts: 5,
        defaultDuration: 5000,

        add(message, type = 'info', options = {}) {
            const id = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

            const toast = {
                id,
                message,
                type,
                duration: options.duration || this.defaultDuration,
                dismissible: options.dismissible !== false,
                icon: options.icon || this.getDefaultIcon(type),
                action: options.action || null,
                visible: true,
            };

            // Max toast kontrolü
            if (this.toasts.length >= this.maxToasts) {
                this.remove(this.toasts[0].id);
            }

            this.toasts.push(toast);

            // Otomatik kaldırma
            if (toast.duration > 0) {
                setTimeout(() => {
                    this.remove(id);
                }, toast.duration);
            }

            return id;
        },

        remove(id) {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index > -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter((t) => t.id !== id);
                }, 300);
            }
        },

        clearAll() {
            this.toasts.forEach((toast) => {
                toast.visible = false;
            });
            setTimeout(() => {
                this.toasts = [];
            }, 300);
        },

        success(message, options = {}) {
            return this.add(message, 'success', options);
        },

        error(message, options = {}) {
            return this.add(message, 'error', options);
        },

        warning(message, options = {}) {
            return this.add(message, 'warning', options);
        },

        info(message, options = {}) {
            return this.add(message, 'info', options);
        },

        getDefaultIcon(type) {
            const icons = {
                success: 'check-circle',
                error: 'alert-circle',
                warning: 'alert-triangle',
                info: 'info',
            };
            return icons[type] || icons.info;
        },
    });

    /**
     * Categories Store
     * İlan kategorileri için global state
     */
    Alpine.store('categories', {
        editMode: {},
        selectedItems: [],
        filters: {
            search: '',
            seviye: '',
            status: '',
        },

        setEditMode(id, value) {
            this.editMode[id] = value;
        },

        isEditing(id) {
            return this.editMode[id] || false;
        },

        toggleEditMode(id) {
            this.editMode[id] = !this.editMode[id];
        },

        cancelEdit(id) {
            this.editMode[id] = false;
        },

        selectItem(id) {
            if (!this.selectedItems.includes(id)) {
                this.selectedItems.push(id);
            }
        },

        deselectItem(id) {
            this.selectedItems = this.selectedItems.filter((item) => item !== id);
        },

        toggleItem(id) {
            if (this.selectedItems.includes(id)) {
                this.deselectItem(id);
            } else {
                this.selectItem(id);
            }
        },

        selectAll(ids) {
            this.selectedItems = [...ids];
        },

        deselectAll() {
            this.selectedItems = [];
        },

        isSelected(id) {
            return this.selectedItems.includes(id);
        },

        get hasSelection() {
            return this.selectedItems.length > 0;
        },

        get selectedCount() {
            return this.selectedItems.length;
        },

        setFilter(key, value) {
            this.filters[key] = value;
        },

        clearFilters() {
            this.filters = {
                search: '',
                seviye: '',
                status: '',
            };
        },
    });

    /**
     * Tasks Store
     * Görevler için global state
     */
    Alpine.store('tasks', {
        selectedTasks: [],
        filters: {
            search: '',
            status: '',
            oncelik: '',
            danisman_id: '',
        },
        stats: {
            bekleyen: 0,
            devam_eden: 0,
            tamamlanan: 0,
            toplam: 0,
        },

        selectTask(id) {
            if (!this.selectedTasks.includes(id)) {
                this.selectedTasks.push(id);
            }
        },

        deselectTask(id) {
            this.selectedTasks = this.selectedTasks.filter((task) => task !== id);
        },

        toggleTask(id) {
            if (this.selectedTasks.includes(id)) {
                this.deselectTask(id);
            } else {
                this.selectTask(id);
            }
        },

        selectAll(ids) {
            this.selectedTasks = [...ids];
        },

        deselectAll() {
            this.selectedTasks = [];
        },

        isSelected(id) {
            return this.selectedTasks.includes(id);
        },

        get hasSelection() {
            return this.selectedTasks.length > 0;
        },

        get selectedCount() {
            return this.selectedTasks.length;
        },

        updateStats(stats) {
            this.stats = { ...this.stats, ...stats };
        },
    });

    /**
     * Address Store
     * Adres yönetimi için global state
     */
    Alpine.store('address', {
        currentType: 'ulkeler',
        selectedUlke: null,
        selectedIl: null,
        selectedIlce: null,
        searchQuery: '',
        sortOrder: 'asc',

        setType(type) {
            this.currentType = type;
            this.searchQuery = '';
        },

        setSelectedUlke(ulke) {
            this.selectedUlke = ulke;
            this.selectedIl = null;
            this.selectedIlce = null;
        },

        setSelectedIl(il) {
            this.selectedIl = il;
            this.selectedIlce = null;
        },

        setSelectedIlce(ilce) {
            this.selectedIlce = ilce;
        },

        clearSelection() {
            this.selectedUlke = null;
            this.selectedIl = null;
            this.selectedIlce = null;
        },

        setSearch(query) {
            this.searchQuery = query;
        },

        toggleSort() {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        },
    });

    /**
     * Loading Store
     * Global loading state yönetimi
     */
    Alpine.store('loading', {
        states: {},

        start(key = 'default') {
            this.states[key] = true;
        },

        stop(key = 'default') {
            this.states[key] = false;
        },

        isLoading(key = 'default') {
            return this.states[key] || false;
        },

        stopAll() {
            Object.keys(this.states).forEach((key) => {
                this.states[key] = false;
            });
        },
    });

    /**
     * Modal Store
     * Global modal yönetimi
     */
    Alpine.store('modal', {
        openModals: [],

        open(modalId, data = null) {
            if (!this.openModals.includes(modalId)) {
                this.openModals.push(modalId);

                // Body scroll'u kilitle
                if (this.openModals.length === 1) {
                    document.body.style.overflow = 'hidden';
                }

                // Event dispatch
                window.dispatchEvent(
                    new CustomEvent('modal-opened', {
                        detail: { modalId, data },
                    })
                );
            }
        },

        close(modalId) {
            this.openModals = this.openModals.filter((id) => id !== modalId);

            // Body scroll'u aç
            if (this.openModals.length === 0) {
                document.body.style.overflow = '';
            }

            // Event dispatch
            window.dispatchEvent(
                new CustomEvent('modal-closed', {
                    detail: { modalId },
                })
            );
        },

        closeAll() {
            this.openModals = [];
            document.body.style.overflow = '';
            window.dispatchEvent(new CustomEvent('all-modals-closed'));
        },

        isOpen(modalId) {
            return this.openModals.includes(modalId);
        },

        get hasOpenModal() {
            return this.openModals.length > 0;
        },
    });

    /**
     * Form Store
     * Form state ve validation yönetimi
     */
    Alpine.store('form', {
        dirty: {},
        errors: {},
        submitting: {},

        markDirty(formId) {
            this.dirty[formId] = true;
        },

        markClean(formId) {
            this.dirty[formId] = false;
        },

        isDirty(formId) {
            return this.dirty[formId] || false;
        },

        setErrors(formId, errors) {
            this.errors[formId] = errors;
        },

        clearErrors(formId) {
            this.errors[formId] = {};
        },

        getErrors(formId) {
            return this.errors[formId] || {};
        },

        hasErrors(formId) {
            const errors = this.errors[formId] || {};
            return Object.keys(errors).length > 0;
        },

        startSubmitting(formId) {
            this.submitting[formId] = true;
        },

        stopSubmitting(formId) {
            this.submitting[formId] = false;
        },

        isSubmitting(formId) {
            return this.submitting[formId] || false;
        },
    });
});

/**
 * Global Toast Helper Functions
 * Alpine dışından kullanım için
 */
window.showToast = function (message, type = 'info', options = {}) {
    if (window.toast) {
        return window.toast.show(message, type, options);
    } else if (window.Alpine) {
        return Alpine.store('toast').add(message, type, options);
    }
};

window.toastSuccess = function (message, options = {}) {
    return window.showToast(message, 'success', options);
};

window.toastError = function (message, options = {}) {
    return window.showToast(message, 'error', options);
};

window.toastWarning = function (message, options = {}) {
    return window.showToast(message, 'warning', options);
};

window.toastInfo = function (message, options = {}) {
    return window.showToast(message, 'info', options);
};
