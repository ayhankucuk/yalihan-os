// ilan-create-categories.js - Category management functionality
// ⚠️ DEPRECATION NOTE: Bu dosya legacy ilan-create sistemi içindir.
// 📍 SSOT: Wizard için merkezi kaynak: resources/js/wizard/step1-cascade.js
// 🔄 REFACTOR PLAN: Bu dosya gelecekte step1-cascade.js'e birleştirilecek

// Context7 uyumlu - Helper functions (core.js'den import edilecek)
function showLoading(message) {
    window.toast?.info(message, 2000);
}

function hideLoading() {
    // Toast otomatik kapanır
}

function showNotification(message, type = 'info') {
    if (window.toast) {
        switch (type) {
            case 'success':
                window.toast.success(message);
                break;
            case 'error':
                window.toast.error(message);
                break;
            case 'warning':
                window.toast.warning(message);
                break;
            default:
                window.toast.info(message);
        }
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

function setIndicatorState(indicatorId, isActive) {
    const el = document.getElementById(indicatorId);
    if (!el) {
        return;
    }

    el.classList.toggle('bg-green-500', Boolean(isActive));
    el.classList.toggle('bg-gray-300', !isActive);
}

function loadAltKategoriler(anaKategoriId) {
    if (!anaKategoriId) {
        clearAltKategoriler();
        return Promise.resolve();
    }

    showLoading('Alt kategoriler yükleniyor...');

    const subcategoriesUrl = window.APIConfig?.categories?.subcategories
        ? window.APIConfig.categories.subcategories(anaKategoriId)
        : `/api/v1/categories/sub/${anaKategoriId}`;

    return fetch(subcategoriesUrl, {
        cache: 'no-cache',
        headers: { 'Cache-Control': 'no-cache' },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! Response not OK`);
            }
            return response.json();
        })
        .then((data) => {
            hideLoading();
            // ✅ SAB: ResponseService format kontrolü
            let subcategories = [];

            if (data.success && data.data) {
                // ResponseService format: {success: true, data: {subcategories: [...], count: ...}}
                subcategories =
                    data.data.subcategories || data.data.alt_kategoriler || data.data.data || [];
            } else if (data.subcategories) {
                // Direkt subcategories format
                subcategories = data.subcategories;
            } else if (data.data && Array.isArray(data.data)) {
                // Array format
                subcategories = data.data;
            } else if (Array.isArray(data)) {
                // Direkt array format
                subcategories = data;
            }

            if (Array.isArray(subcategories) && subcategories.length > 0) {
                populateAltKategoriler(subcategories);
                return Promise.resolve();
            } else {
                showNotification('Alt kategoriler bulunamadı', 'warning');
                populateAltKategoriler([]);
                return Promise.resolve();
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Alt kategori yükleme hatası:', error);
            showNotification('Alt kategoriler yüklenemedi', 'error');
            return Promise.reject(error);
        });
}

function clearAltKategoriler() {
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    const yayinTipiSelect = document.getElementById('junction_id');

    altKategoriSelect.innerHTML = '<option value="">Önce ana kategori seçin...</option>';
    yayinTipiSelect.innerHTML = '<option value="">Önce alt kategori seçin...</option>';

    // Clear type-based fields
    clearTypeBasedFields();
}

function populateAltKategoriler(categories) {
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    const yayinTipiSelect = document.getElementById('junction_id');

    altKategoriSelect.innerHTML = '<option value="">Alt kategori seçin...</option>';
    yayinTipiSelect.innerHTML = '<option value="">Önce alt kategori seçin...</option>';

    categories.forEach((category) => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        altKategoriSelect.appendChild(option);
    });

    // Auto-select default or single option
    try {
        // ✅ FIX: Edit mode için window.ilanData'dan değer al
        let defaultValue = altKategoriSelect.dataset?.default;
        if (!defaultValue && window.editMode && window.ilanData?.alt_kategori_id) {
            defaultValue = String(window.ilanData.alt_kategori_id);
        }

        let targetValue = null;

        if (
            defaultValue &&
            categories.some((category) => String(category.id) === String(defaultValue))
        ) {
            targetValue = defaultValue;
        } else if (!defaultValue && categories.length === 1) {
            targetValue = String(categories[0].id);
        }

        if (targetValue && altKategoriSelect.value !== targetValue) {
            altKategoriSelect.value = targetValue;
            // Prevent re-triggering with the same default on subsequent loads
            altKategoriSelect.dataset.default = '';
            altKategoriSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } catch (error) {
        console.warn('Alt kategori otomatik seçim uygulanamadı:', error);
    }
}

function loadYayinTipleri(altKategoriId) {
    if (!altKategoriId) {
        clearYayinTipleri();
        return Promise.resolve();
    }

    showLoading('Yayın tipleri yükleniyor...');

    // ✅ FIX: Alt kategori ID'sini kullan (pivot tablo filtresi için!)
    const anaKategoriSelect = document.getElementById('ana_kategori_id');
    const anaKategoriSlug =
        anaKategoriSelect?.options[anaKategoriSelect.selectedIndex]?.dataset?.slug;

    const publicationTypesUrl = window.APIConfig?.categories?.publicationTypes
        ? window.APIConfig.categories.publicationTypes(altKategoriId)
        : `/api/v1/categories/publication-types/${altKategoriId}`;

    return fetch(publicationTypesUrl, {
        cache: 'no-cache',
        headers: { 'Cache-Control': 'no-cache' },
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            // ✅ SAB: ResponseService format kontrolü
            if (data.success) {
                // ResponseService format: data.data.types veya data.data
                const responseData = data.data || data;
                const types =
                    responseData.types ||
                    responseData.publication_types ||
                    responseData.yayinTipleri ||
                    [];
                console.log('✅ Yayın tipleri yüklendi:', types.length, 'adet', types);
                populateYayinTipleri(types);

                // ⚠️ Event'i henüz dispatch etme - Kullanıcı yayın tipi seçince dispatch edilecek
                console.log('⏳ Yayın tipi yüklendi, kullanıcı seçimi bekleniyor...');
                return Promise.resolve();
            } else {
                showNotification('Yayın tipleri yüklenemedi', 'error');
                return Promise.reject(new Error('Yayın tipleri yüklenemedi'));
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Yayın tipi yükleme hatası:', error);
            showNotification('Yayın tipleri yüklenemedi', 'error');
            return Promise.reject(error);
        });
}

function clearYayinTipleri() {
    const yayinTipiSelect = document.getElementById('junction_id');
    yayinTipiSelect.innerHTML = '<option value="">Önce alt kategori seçin...</option>';

    clearTypeBasedFields();
}

function populateYayinTipleri(types) {
    console.log('📊 populateYayinTipleri called with:', types);
    const yayinTipiSelect = document.getElementById('junction_id');

    if (!yayinTipiSelect) {
        console.error('❌ junction_id element not found!');
        return;
    }

    yayinTipiSelect.innerHTML = '<option value="">Yayın tipi seçin...</option>';

    if (!types || types.length === 0) {
        console.warn('⚠️ No types provided to populateYayinTipleri');
        return;
    }

    types.forEach((type) => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.name;
        yayinTipiSelect.appendChild(option);
        console.log(`✅ Added option: ${type.name} (ID: ${type.id})`);
    });

    console.log(`✅ Total options added: ${types.length}`);

    // Auto-select default or single option
    try {
        // ✅ FIX: Edit mode için window.ilanData'dan değer al
        let defaultValue = yayinTipiSelect.dataset?.default;
        if (!defaultValue && window.editMode && window.ilanData?.junction_id) {
            defaultValue = String(window.ilanData.junction_id);
        }

        let targetValue = null;

        if (defaultValue && types.some((type) => String(type.id) === String(defaultValue))) {
            targetValue = defaultValue;
        } else if (!defaultValue && types.length === 1) {
            targetValue = String(types[0].id);
        }

        if (targetValue && yayinTipiSelect.value !== targetValue) {
            yayinTipiSelect.value = targetValue;
            yayinTipiSelect.dataset.default = '';
            yayinTipiSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } catch (error) {
        console.warn('Yayın tipi otomatik seçim uygulanamadı:', error);
    }
}

// Queue for retries
let typeBasedFieldsRetryCount = 0;
const MAX_RETRIES = 5;

function loadTypeBasedFields() {
    const anaKategoriId = document.getElementById('ana_kategori_id').value;
    const altKategoriId = document.getElementById('alt_kategori_id').value;
    const yayinTipiId = document.getElementById('junction_id').value;

    if (!anaKategoriId || !altKategoriId || !yayinTipiId) {
        clearTypeBasedFields();
        return;
    }

    // ✅ DOM Guard: Wait for Step 2 container
    const container = document.getElementById('type-based-fields-container');
    if (!container) {
        if (typeBasedFieldsRetryCount < MAX_RETRIES) {
            typeBasedFieldsRetryCount++;
            console.log(
                `⏳ UPS Features: Container not found, retrying (${typeBasedFieldsRetryCount}/${MAX_RETRIES})...`
            );
            setTimeout(loadTypeBasedFields, 1000);
            return;
        } else {
            console.warn('⚠️ UPS Features: Container not found after timeout. Skipping load.');
            typeBasedFieldsRetryCount = 0; // Reset
            return;
        }
    }
    // Reset counter on success
    typeBasedFieldsRetryCount = 0;

    showLoading('Özel alanlar yükleniyor...');

    const fieldsUrl = window.APIConfig?.categories?.fields
        ? window.APIConfig.categories.fields(anaKategoriId, yayinTipiId)
        : `/api/v1/categories/fields/${anaKategoriId}/${yayinTipiId}`;

    fetch(fieldsUrl)
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                renderTypeBasedFields(data.fields);
            } else {
                showNotification('Özel alanlar yüklenemedi', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Özel alan yükleme hatası:', error);
            showNotification('Özel alanlar yüklenemedi', 'error');
        });
}

function clearTypeBasedFields() {
    const container = document.getElementById('type-based-fields-container');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2 dark:text-slate-600"></i>
                <p class="text-gray-500 dark:text-slate-500">Kategori seçimine göre alanlar yükleniyor...</p>
            </div>
        `;
    }
}

function renderTypeBasedFields(fields) {
    const container = document.getElementById('type-based-fields-container');

    // ✅ NULL CHECK - Element yoksa skip et (Property Type Manager sayfasında bu element yok, normal)
    if (!container) {
        return;
    }

    if (!fields || fields.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-info-circle text-2xl text-blue-400 mb-2"></i>
                <p class="text-gray-500 dark:text-slate-500">Bu kategori için özel alan bulunmuyor.</p>
            </div>
        `;
        return;
    }

    let html = '';

    fields.forEach((field) => {
        html += generateFieldHTML(field);
    });

    container.innerHTML = html;

    // Initialize field interactions
    initializeFieldInteractions();
}

function generateFieldHTML(field) {
    const fieldName = `type_fields[${field.id}]`;
    const fieldId = `field_${field.id}`;

    let html = '<div class="mb-4">';

    // Label
    html += `<label for="${fieldId}" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">${field.label}`;
    if (field.required) {
        html += '<span class="text-red-500">*</span>';
    }
    html += '</label>';

    // Field input based on type
    switch (field.type) {
        case 'text':
            html += `<input type="text" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.placeholder) html += `placeholder="${field.placeholder}"`;
            if (field.required) html += 'required';
            html += '>';
            break;

        case 'number':
            html += `<input type="number" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.placeholder) html += `placeholder="${field.placeholder}"`;
            if (field.min !== undefined) html += `min="${field.min}"`;
            if (field.max !== undefined) html += `max="${field.max}"`;
            if (field.required) html += 'required';
            html += '>';
            break;

        case 'select':
            html += `<select id="${fieldId}" name="${fieldName}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.required) html += 'required';
            html += '>';
            html += '<option value="">Seçin...</option>';
            if (field.options) {
                field.options.forEach((option) => {
                    html += `<option value="${option.value}">${option.label}</option>`;
                });
            }
            html += '</select>';
            break;

        case 'checkbox':
            html += '<div class="space-y-2">';
            if (field.options) {
                field.options.forEach((option, index) => {
                    const checkboxName = `${fieldName}[]`;
                    const checkboxId = `${fieldId}_${index}`;
                    html += `
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="${checkboxId}" name="${checkboxName}" value="${option.value}" class="mr-3 rounded focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-slate-300">${option.label}</span>
                        </label>
                    `;
                });
            }
            html += '</div>';
            break;

        case 'radio':
            html += '<div class="space-y-2">';
            if (field.options) {
                field.options.forEach((option, index) => {
                    const radioId = `${fieldId}_${index}`;
                    html += `
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" id="${radioId}" name="${fieldName}" value="${option.value}" class="mr-3 focus:ring-purple-500"`;
                    if (field.required) html += 'required';
                    html += `>
                            <span class="text-sm text-gray-700 dark:text-slate-300">${option.label}</span>
                        </label>
                    `;
                });
            }
            html += '</div>';
            break;

        case 'textarea':
            html += `<textarea id="${fieldId}" name="${fieldName}" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.placeholder) html += `placeholder="${field.placeholder}"`;
            if (field.required) html += 'required';
            html += '></textarea>';
            break;

        case 'date':
            html += `<input type="date" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.required) html += 'required';
            html += '>';
            break;

        case 'datetime':
            html += `<input type="datetime-local" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:border-slate-600"`;
            if (field.required) html += 'required';
            html += '>';
            break;
    }

    // Help text
    if (field.help_text) {
        html += `<p class="mt-1 text-sm text-gray-500 dark:text-slate-500">${field.help_text}</p>`;
    }

    html += '</div>';

    return html;
}

function initializeFieldInteractions() {
    // Add any special interactions for dynamic fields
    const typeFields = document.querySelectorAll(
        '#type-based-fields-container input, #type-based-fields-container select, #type-based-fields-container textarea'
    );

    typeFields.forEach((field) => {
        // Add validation
        field.addEventListener('blur', function () {
            validateField(this);
        });

        // Add conditional logic if needed
        if (field.hasAttribute('data-conditional')) {
            initializeConditionalField(field);
        }
    });
}

function initializeConditionalField(field) {
    const condition = JSON.parse(field.getAttribute('data-conditional'));
    const targetField = document.querySelector(`[name="${condition.field}"]`);

    if (targetField) {
        targetField.addEventListener('change', () => {
            checkConditionalVisibility(field, condition);
        });

        // Initial check
        checkConditionalVisibility(field, condition);
    }
}

function checkConditionalVisibility(field, condition) {
    const targetField = document.querySelector(`[name="${condition.field}"]`);
    const targetValue = targetField ? targetField.value : '';
    const fieldContainer = field.closest('.mb-4');

    let shouldShow = false;

    switch (condition.operator) {
        case 'equals':
            shouldShow = targetValue === condition.value;
            break;
        case 'not_equals':
            shouldShow = targetValue !== condition.value;
            break;
        case 'contains':
            shouldShow = targetValue.includes(condition.value);
            break;
        case 'in':
            shouldShow = condition.value.includes(targetValue);
            break;
    }

    if (shouldShow) {
        fieldContainer.style.display = 'block';
        field.required = field.hasAttribute('data-original-required') || field.required;
    } else {
        fieldContainer.style.display = 'none';
        field.required = false;
    }
}

function validateCategories() {
    const anaKategori = document.getElementById('ana_kategori_id').value;
    const altKategori = document.getElementById('alt_kategori_id').value;
    const yayinTipi = document.getElementById('junction_id').value;

    if (!anaKategori) {
        showFieldError(document.getElementById('ana_kategori'), 'Ana kategori seçimi zorunludur.');
        return false;
    }

    if (!altKategori) {
        showFieldError(document.getElementById('alt_kategori'), 'Alt kategori seçimi zorunludur.');
        return false;
    }

    if (!yayinTipi) {
        showFieldError(document.getElementById('junction_id'), 'Yayın tipi seçimi zorunludur.');
        return false;
    }

    return true;
}

// Initialize category event listeners
let categoryListenersInitialized = false;

document.addEventListener('DOMContentLoaded', () => {
    // Prevent duplicate initialization
    if (categoryListenersInitialized) {
        console.log('⚠️ Category listeners already initialized, skipping...');
        return;
    }

    console.log('✅ Initializing category event listeners...');

    const anaKategoriSelect = document.getElementById('ana_kategori_id');
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    const yayinTipiSelect = document.getElementById('junction_id');

    // ✅ Helper: Robust Slug Resolver (SSOT)
    function resolveSlug(selectElement) {
        if (!selectElement) return null;

        // 1. Try dataset from selected option
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        let slug = selectedOption?.dataset?.slug || selectedOption?.getAttribute('data-slug');

        // 2. Fallback: Search by value if selectedIndex is out of sync
        if (!slug && selectElement.value) {
            const byValue = selectElement.querySelector(`option[value="${selectElement.value}"]`);
            slug = byValue?.dataset?.slug || byValue?.getAttribute('data-slug');
        }

        // 3. Fallback: Cache
        if (!slug && window.WizardState?.lastKnownSlug) {
            console.warn(
                '⚠️ Slug not found in DOM, using cache:',
                window.WizardState.lastKnownSlug
            );
            slug = window.WizardState.lastKnownSlug;
        }

        // Update Cache if found
        if (slug) {
            window.WizardState = window.WizardState || {};
            window.WizardState.lastKnownSlug = slug;
        }

        return slug || '';
    }

    if (anaKategoriSelect) {
        anaKategoriSelect.addEventListener('change', function () {
            console.log('🔵 Ana kategori change:', this.value);
            loadAltKategoriler(this.value);
            setIndicatorState('ana-kategori-indicator', Boolean(this.value));
            setIndicatorState('alt-kategori-indicator', false);
            setIndicatorState('yayin-tipi-indicator', false);

            if (this.value) {
                const anaKategoriSlug = resolveSlug(this);

                // ✅ Standard Payload
                const event = new CustomEvent('category-changed', {
                    detail: {
                        category: {
                            id: this.value,
                            slug: anaKategoriSlug,
                            parent_slug: anaKategoriSlug,
                        },
                        // Flattened standard properties
                        category_id: this.value,
                        category_slug: anaKategoriSlug,
                        junction_id: null,
                        yayin_tipi_slug: null,

                        yayinTipi: null,
                        yayinTipiId: null,
                    },
                });
                window.dispatchEvent(event);
            }
        });
        console.log('✅ Ana kategori listener added');
    }

    if (altKategoriSelect) {
        altKategoriSelect.addEventListener('change', function () {
            console.log('🔵 Alt kategori change:', this.value);
            loadYayinTipleri(this.value);
            setIndicatorState('alt-kategori-indicator', Boolean(this.value));
            setIndicatorState('yayin-tipi-indicator', false);

            if (this.value && anaKategoriSelect?.value) {
                const anaKategoriSlug = resolveSlug(anaKategoriSelect);

                const event = new CustomEvent('category-changed', {
                    detail: {
                        category: {
                            id: anaKategoriSelect.value,
                            slug: anaKategoriSlug,
                            parent_slug: anaKategoriSlug,
                        },
                        category_id: anaKategoriSelect.value,
                        category_slug: anaKategoriSlug,
                        alt_kategori_id: this.value,
                        junction_id: null,
                        yayin_tipi_slug: null,

                        altKategoriId: this.value,
                        yayinTipi: null,
                        yayinTipiId: null,
                    },
                });
                window.dispatchEvent(event);
            }
        });
        console.log('✅ Alt kategori listener added');
    }

    if (yayinTipiSelect) {
        yayinTipiSelect.addEventListener('change', function () {
            console.log('🔵 Yayın tipi change:', this.value);
            setIndicatorState('yayin-tipi-indicator', Boolean(this.value));

            const anaKategoriSelect = document.getElementById('ana_kategori_id');
            const anaKategoriId = anaKategoriSelect?.value;
            const anaKategoriSlug = resolveSlug(anaKategoriSelect);

            const yayinTipiText = this.options[this.selectedIndex]?.text;
            const yayinTipiId = this.value;
            // Best effort yayin tipi slug (usually lowercase name if no data-slug)
            const yayinTipiSlug =
                this.options[this.selectedIndex]?.dataset?.slug ||
                this.options[this.selectedIndex]?.getAttribute('data-slug') ||
                '';

            if (anaKategoriId && yayinTipiId) {
                console.log('🎯 Dispatching category-changed event:', {
                    kategoriId: anaKategoriId,
                    kategoriSlug: anaKategoriSlug,
                    yayinTipi: yayinTipiText,
                    yayinTipiId: yayinTipiId,
                });

                const event = new CustomEvent('category-changed', {
                    detail: {
                        category: {
                            id: anaKategoriId,
                            slug: anaKategoriSlug,
                            parent_slug: anaKategoriSlug,
                        },
                        // ✅ Standard Payload properties
                        category_id: anaKategoriId,
                        category_slug: anaKategoriSlug,
                        junction_id: yayinTipiId,
                        yayin_tipi_slug: yayinTipiSlug,

                        yayinTipi: yayinTipiText,
                        yayinTipiId: yayinTipiId,
                    },
                });
                window.dispatchEvent(event);
            }

            loadTypeBasedFields();
        });
        console.log('✅ Yayın tipi listener added');
    }

    categoryListenersInitialized = true;
    console.log('✅ Category listeners initialization complete');

    // ✅ Edit Mode: Load existing category values
    if (window.editMode && window.ilanData) {
        console.log('📝 Edit mode detected, loading existing category values...', window.ilanData);

        const anaKategoriId = window.ilanData.ana_kategori_id;
        const altKategoriId = window.ilanData.alt_kategori_id;
        const yayinTipiId = window.ilanData.junction_id;

        // Set ana kategori
        if (anaKategoriId && anaKategoriSelect) {
            anaKategoriSelect.value = anaKategoriId;
            setIndicatorState('ana-kategori-indicator', true);

            // Load alt kategoriler and wait for response
            loadAltKategoriler(anaKategoriId).then(() => {
                // Set alt kategori after categories are loaded
                if (altKategoriId && altKategoriSelect) {
                    // Wait a bit more to ensure options are populated
                    setTimeout(() => {
                        if (altKategoriSelect.querySelector(`option[value="${altKategoriId}"]`)) {
                            altKategoriSelect.value = altKategoriId;
                            setIndicatorState('alt-kategori-indicator', true);

                            // Load yayın tipleri
                            loadYayinTipleri(altKategoriId).then(() => {
                                // Set yayın tipi after types are loaded
                                if (yayinTipiId && yayinTipiSelect) {
                                    setTimeout(() => {
                                        if (
                                            yayinTipiSelect.querySelector(
                                                `option[value="${yayinTipiId}"]`
                                            )
                                        ) {
                                            yayinTipiSelect.value = yayinTipiId;
                                            setIndicatorState('yayin-tipi-indicator', true);
                                            console.log(
                                                '✅ All category values loaded in edit mode'
                                            );
                                        }
                                    }, 300);
                                }
                            });
                        }
                    }, 300);
                }
            });
        }
    }

    // 🆕 Auto-dispatch on preselected values (page restored, back/forward cache vb.)
    try {
        const hasAll =
            anaKategoriSelect?.value && altKategoriSelect?.value && yayinTipiSelect?.value;
        if (hasAll) {
            const anaKategoriSlugAuto =
                anaKategoriSelect.options[anaKategoriSelect.selectedIndex]?.dataset?.slug;
            const yayinTipiTextAuto = yayinTipiSelect.options[yayinTipiSelect.selectedIndex]?.text;
            const eventAuto = new CustomEvent('category-changed', {
                detail: {
                    category: {
                        id: anaKategoriSelect.value,
                        slug: anaKategoriSlugAuto,
                        parent_slug: anaKategoriSlugAuto,
                    },
                    yayinTipi: yayinTipiTextAuto,
                    yayinTipiId: yayinTipiSelect.value,
                },
            });
            console.log('⚡ Auto-dispatching category-changed (preselected values)');
            window.dispatchEvent(eventAuto);
        }
    } catch (e) {
        console.warn('Auto-dispatch skipped:', e);
    }
});

/**
 * Kategori Dinamik Alanlar (Alpine için)
 */
window.kategoriDinamikAlanlar = function () {
    return {
        selectedKategori: null,
        selectedAltKategori: null,
        selectedYayinTipi: null,

        hasRequiredFields: false,
        hasRecommendedFields: false,
        requiredFieldsHtml: '',
        recommendedFieldsHtml: '',
        fieldInfo: null,

        init() {
            console.log('Kategori dinamik alanlar initialized');
        },
    };
};

// Export functions for use in other modules
window.IlanCreateCategories = {
    loadAltKategoriler,
    loadYayinTipleri,
    loadTypeBasedFields,
    validateCategories,
    kategoriDinamikAlanlar: window.kategoriDinamikAlanlar,
    initializeCategories: function () {
        console.log('✅ IlanCreateCategories.initializeCategories() called');
        // Event listeners already set up in DOMContentLoaded
        // This method is just for consistency
    },
    // 🆕 Simple dispatcher for inline fallback
    dispatchCategoryChanged: function () {
        try {
            const ana = document.getElementById('ana_kategori_id');
            const yayin = document.getElementById('junction_id');
            if (!ana || !yayin || !ana.value || !yayin.value) return;
            const anaSlug = ana.options[ana.selectedIndex]?.dataset?.slug;
            const yayinText = yayin.options[yayin.selectedIndex]?.text;
            const ev = new CustomEvent('category-changed', {
                detail: {
                    category: { id: ana.value, slug: anaSlug, parent_slug: anaSlug },
                    yayinTipi: yayinText,
                    yayinTipiId: yayin.value,
                },
            });
            window.dispatchEvent(ev);
        } catch (e) {
            console.warn('dispatchCategoryChanged failed:', e);
        }
    },
};

// Context7: Global scope export for inline onclick handlers
window.loadAltKategoriler = loadAltKategoriler;
window.loadYayinTipleri = loadYayinTipleri;
window.dispatchCategoryChanged = window.IlanCreateCategories.dispatchCategoryChanged;
