/**
 * Config Options Form Builder
 *
 * Option type'a göre dinamik form alanları oluşturur
 * Context7: C7-CONFIG-OPTIONS-FORM-BUILDER-2025-12-15
 */

class ConfigOptionsFormBuilder {
    constructor(containerId, hiddenInputId) {
        this.container = document.getElementById(containerId);
        this.hiddenInput = document.getElementById(hiddenInputId);
        this.currentType = null;
        this.currentData = null;
    }

    /**
     * Form builder'ı başlat
     */
    init(optionType, optionValue = null) {
        this.currentType = optionType;
        this.currentData = optionValue || this.getDefaultData(optionType);
        this.renderForm(optionType);
    }

    /**
     * Option type'a göre varsayılan veri oluştur
     */
    getDefaultData(type) {
        switch (type) {
            case 'simple':
                return [];
            case 'associative':
                return {};
            case 'object_array':
                return [];
            case 'nested':
                return {};
            default:
                return [];
        }
    }

    /**
     * Formu render et
     */
    renderForm(optionType) {
        this.currentType = optionType;

        if (!this.currentData) {
            this.currentData = this.getDefaultData(optionType);
        }

        switch (optionType) {
            case 'simple':
                this.renderSimpleForm();
                break;
            case 'associative':
                this.renderAssociativeForm();
                break;
            case 'object_array':
                this.renderObjectArrayForm();
                break;
            case 'nested':
                this.renderNestedForm();
                break;
            default:
                this.container.innerHTML = '<p class="text-red-600 dark:text-red-400">Geçersiz option type!</p>';
        }

        this.updateHiddenInput();
    }

    /**
     * Simple Array Form (Basit Liste)
     */
    renderSimpleForm() {
        const values = Array.isArray(this.currentData) ? this.currentData : [];

        let html = `
            <div class="space-y-3">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Her satıra bir değer girin (örn: "1", "2", "3+")
                    </p>
                    <button type="button" onclick="window.formBuilder.addSimpleItem()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 text-sm">
                        + Ekle
                    </button>
                </div>
                <div id="simple-items-container" class="space-y-2">
        `;

        values.forEach((value, index) => {
            html += this.getSimpleItemHtml(index, value);
        });

        if (values.length === 0) {
            html += this.getSimpleItemHtml(0, '');
        }

        html += `
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    getSimpleItemHtml(index, value) {
        return `
            <div class="flex items-center gap-2 simple-item" data-index="${index}">
                <input type="text"
                    value="${this.escapeHtml(value)}"
                    onchange="window.formBuilder.updateSimpleItem(${index}, this.value)"
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white">
                <button type="button" onclick="window.formBuilder.removeSimpleItem(${index})"
                    class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200">
                    Sil
                </button>
            </div>
        `;
    }

    addSimpleItem() {
        const container = document.getElementById('simple-items-container');
        const index = container.children.length;
        const newItem = document.createElement('div');
        newItem.className = 'flex items-center gap-2 simple-item';
        newItem.setAttribute('data-index', index);
        newItem.innerHTML = this.getSimpleItemHtml(index, '');
        container.appendChild(newItem);
        this.updateHiddenInput();
    }

    removeSimpleItem(index) {
        const item = document.querySelector(`.simple-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            // Index'leri yeniden düzenle
            const items = document.querySelectorAll('.simple-item');
            items.forEach((item, newIndex) => {
                item.setAttribute('data-index', newIndex);
                const input = item.querySelector('input');
                input.setAttribute('onchange', `window.formBuilder.updateSimpleItem(${newIndex}, this.value)`);
                const button = item.querySelector('button');
                button.setAttribute('onclick', `window.formBuilder.removeSimpleItem(${newIndex})`);
            });
            this.updateHiddenInput();
        }
    }

    updateSimpleItem(index, value) {
        if (!Array.isArray(this.currentData)) {
            this.currentData = [];
        }
        this.currentData[index] = value;
        this.currentData = this.currentData.filter(v => v !== undefined && v !== '');
        this.updateHiddenInput();
    }

    /**
     * Associative Array Form (Key-Value Çiftleri)
     */
    renderAssociativeForm() {
        const data = typeof this.currentData === 'object' && !Array.isArray(this.currentData)
            ? this.currentData
            : {};

        const entries = Object.entries(data);

        let html = `
            <div class="space-y-3">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Key-Value çiftleri girin (örn: "Hayır" => "Hayır", "Evet" => "Evet")
                    </p>
                    <button type="button" onclick="window.formBuilder.addAssociativeItem()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 text-sm">
                        + Ekle
                    </button>
                </div>
                <div id="associative-items-container" class="space-y-2">
        `;

        entries.forEach(([key, value], index) => {
            html += this.getAssociativeItemHtml(index, key, value);
        });

        if (entries.length === 0) {
            html += this.getAssociativeItemHtml(0, '', '');
        }

        html += `
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    getAssociativeItemHtml(index, key, value) {
        return `
            <div class="grid grid-cols-2 gap-2 associative-item" data-index="${index}" data-old-key="${this.escapeHtml(key)}">
                <input type="text"
                    placeholder="Key"
                    value="${this.escapeHtml(key)}"
                    onchange="window.formBuilder.updateAssociativeKey(${index}, this.value)"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white">
                <div class="flex gap-2">
                    <input type="text"
                        placeholder="Value"
                        value="${this.escapeHtml(value)}"
                        onchange="window.formBuilder.updateAssociativeValue(${index}, this.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white">
                    <button type="button" onclick="window.formBuilder.removeAssociativeItem(${index})"
                        class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200">
                        Sil
                    </button>
                </div>
            </div>
        `;
    }

    addAssociativeItem() {
        const container = document.getElementById('associative-items-container');
        const index = container.children.length;
        const newItem = document.createElement('div');
        newItem.className = 'grid grid-cols-2 gap-2 associative-item';
        newItem.setAttribute('data-index', index);
        newItem.innerHTML = this.getAssociativeItemHtml(index, '', '');
        container.appendChild(newItem);
        this.updateHiddenInput();
    }

    removeAssociativeItem(index) {
        const item = document.querySelector(`.associative-item[data-index="${index}"]`);
        if (item) {
            // Silinen item'in key'ini currentData'dan kaldır
            const keyInput = item.querySelectorAll('input')[0];
            const oldKey = item.getAttribute('data-old-key') || keyInput.value;
            if (oldKey && this.currentData && this.currentData[oldKey] !== undefined) {
                delete this.currentData[oldKey];
            }

            item.remove();
            const items = document.querySelectorAll('.associative-item');
            items.forEach((item, newIndex) => {
                item.setAttribute('data-index', newIndex);
                // Event handler'ları güncelle
                const inputs = item.querySelectorAll('input');
                inputs[0].setAttribute('onchange', `window.formBuilder.updateAssociativeKey(${newIndex}, this.value)`);
                inputs[1].setAttribute('onchange', `window.formBuilder.updateAssociativeValue(${newIndex}, this.value)`);
                const button = item.querySelector('button');
                button.setAttribute('onclick', `window.formBuilder.removeAssociativeItem(${newIndex})`);
            });
            this.updateHiddenInput();
        }
    }

    updateAssociativeKey(index, newKey) {
        const item = document.querySelector(`.associative-item[data-index="${index}"]`);
        if (item) {
            const valueInput = item.querySelectorAll('input')[1];
            const value = valueInput.value;
            const oldKey = item.getAttribute('data-old-key') || '';

            if (!this.currentData || typeof this.currentData !== 'object' || Array.isArray(this.currentData)) {
                this.currentData = {};
            }

            // Eski key'i sil
            if (oldKey && this.currentData[oldKey] !== undefined) {
                delete this.currentData[oldKey];
            }

            // Yeni key'i ekle
            if (newKey) {
                item.setAttribute('data-old-key', newKey);
                this.currentData[newKey] = value || '';
            }

            this.updateHiddenInput();
        }
    }

    updateAssociativeValue(index, value) {
        const item = document.querySelector(`.associative-item[data-index="${index}"]`);
        if (item) {
            const keyInput = item.querySelectorAll('input')[0];
            const key = keyInput.value;

            if (!this.currentData || typeof this.currentData !== 'object' || Array.isArray(this.currentData)) {
                this.currentData = {};
            }

            if (key) {
                this.currentData[key] = value || '';
            }

            this.updateHiddenInput();
        }
    }

    /**
     * Object Array Form (Obje Dizisi)
     */
    renderObjectArrayForm() {
        const items = Array.isArray(this.currentData) ? this.currentData : [];

        let html = `
            <div class="space-y-4">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Her obje için birden fazla alan girin
                    </p>
                    <button type="button" onclick="window.formBuilder.addObjectArrayItem()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 text-sm">
                        + Obje Ekle
                    </button>
                </div>
                <div id="object-array-items-container" class="space-y-4">
        `;

        if (items.length === 0) {
            html += this.getObjectArrayItemHtml(0, {});
        } else {
            items.forEach((item, index) => {
                html += this.getObjectArrayItemHtml(index, item);
            });
        }

        html += `
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    getObjectArrayItemHtml(index, item) {
        const fields = Object.keys(item);
        const hasFields = fields.length > 0;

        let html = `
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 object-array-item" data-index="${index}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Obje #${index + 1}</h3>
                    <button type="button" onclick="window.formBuilder.removeObjectArrayItem(${index})"
                        class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 text-sm">
                        Sil
                    </button>
                </div>
                <div class="space-y-2" id="object-fields-${index}">
        `;

        if (hasFields) {
            fields.forEach(field => {
                html += this.getObjectFieldHtml(index, field, item[field]);
            });
        } else {
            html += this.getObjectFieldHtml(index, '', '');
        }

        html += `
                </div>
                <button type="button" onclick="window.formBuilder.addObjectField(${index})"
                    class="mt-2 px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 text-sm">
                    + Alan Ekle
                </button>
            </div>
        `;

        return html;
    }

    getObjectFieldHtml(objIndex, field, value) {
        const fieldId = `field_${objIndex}_${field || 'new'}_${Date.now()}`;
        return `
            <div class="grid grid-cols-2 gap-2 object-field" data-field-id="${fieldId}" data-field-name="${this.escapeHtml(field)}">
                <input type="text"
                    placeholder="Alan Adı"
                    value="${this.escapeHtml(field)}"
                    onchange="window.formBuilder.updateObjectFieldName('${fieldId}', this.value)"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white">
                <div class="flex gap-2">
                    <input type="text"
                        placeholder="Değer"
                        value="${this.escapeHtml(value)}"
                        onchange="window.formBuilder.updateObjectFieldValue('${fieldId}', this.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white">
                    <button type="button" onclick="window.formBuilder.removeObjectField('${fieldId}')"
                        class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200">
                        Sil
                    </button>
                </div>
            </div>
        `;
    }

    addObjectArrayItem() {
        const container = document.getElementById('object-array-items-container');
        const index = container.children.length;
        const newItem = document.createElement('div');
        newItem.className = 'border border-gray-200 dark:border-gray-700 rounded-lg p-4 object-array-item';
        newItem.setAttribute('data-index', index);
        newItem.innerHTML = this.getObjectArrayItemHtml(index, {});
        container.appendChild(newItem);
        this.updateHiddenInput();
    }

    removeObjectArrayItem(index) {
        const item = document.querySelector(`.object-array-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const items = document.querySelectorAll('.object-array-item');
            items.forEach((item, newIndex) => {
                item.setAttribute('data-index', newIndex);
                const title = item.querySelector('h3');
                if (title) title.textContent = `Obje #${newIndex + 1}`;
            });
            this.updateHiddenInput();
        }
    }

    addObjectField(objIndex) {
        const fieldsContainer = document.getElementById(`object-fields-${objIndex}`);
        if (fieldsContainer) {
            const newField = document.createElement('div');
            newField.innerHTML = this.getObjectFieldHtml(objIndex, '', '');
            fieldsContainer.appendChild(newField);
            this.updateHiddenInput();
        }
    }

    removeObjectField(fieldId) {
        const field = document.querySelector(`.object-field[data-field-id="${fieldId}"]`);
        if (field) {
            const objItem = field.closest('.object-array-item');
            if (objItem) {
                const objIndex = parseInt(objItem.getAttribute('data-index'));
                const fieldName = field.getAttribute('data-field-name');

                if (this.currentData[objIndex] && this.currentData[objIndex][fieldName] !== undefined) {
                    delete this.currentData[objIndex][fieldName];
                }
            }
            field.remove();
            this.updateHiddenInput();
        }
    }

    updateObjectFieldName(fieldId, newFieldName) {
        const field = document.querySelector(`.object-field[data-field-id="${fieldId}"]`);
        if (field) {
            const objItem = field.closest('.object-array-item');
            if (objItem) {
                const objIndex = parseInt(objItem.getAttribute('data-index'));
                const oldFieldName = field.getAttribute('data-field-name');

                if (!Array.isArray(this.currentData)) {
                    this.currentData = [];
                }
                if (!this.currentData[objIndex]) {
                    this.currentData[objIndex] = {};
                }

                // Eski field'ı yeni field'a taşı
                if (oldFieldName && this.currentData[objIndex][oldFieldName] !== undefined) {
                    const value = this.currentData[objIndex][oldFieldName];
                    delete this.currentData[objIndex][oldFieldName];
                    this.currentData[objIndex][newFieldName] = value;
                } else {
                    // Yeni field ekle
                    const valueInput = field.querySelectorAll('input')[1];
                    this.currentData[objIndex][newFieldName] = valueInput.value || '';
                }

                field.setAttribute('data-field-name', newFieldName);
                this.updateHiddenInput();
            }
        }
    }

    updateObjectFieldValue(fieldId, value) {
        const field = document.querySelector(`.object-field[data-field-id="${fieldId}"]`);
        if (field) {
            const objItem = field.closest('.object-array-item');
            if (objItem) {
                const objIndex = parseInt(objItem.getAttribute('data-index'));
                const fieldName = field.getAttribute('data-field-name');

                if (!Array.isArray(this.currentData)) {
                    this.currentData = [];
                }
                if (!this.currentData[objIndex]) {
                    this.currentData[objIndex] = {};
                }

                if (fieldName) {
                    this.currentData[objIndex][fieldName] = value;
                }

                this.updateHiddenInput();
            }
        }
    }

    /**
     * Nested Form (İç İçe Yapı) - JSON Editor
     */
    renderNestedForm() {
        const data = typeof this.currentData === 'object' && !Array.isArray(this.currentData)
            ? this.currentData
            : {};

        let html = `
            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    İç içe yapılar için JSON formatında veri girin. JSON geçerli olmalıdır.
                </p>
                <textarea id="nested-json-editor"
                    rows="15"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-black dark:text-white font-mono text-sm"
                    onchange="window.formBuilder.updateNestedData(this.value)"
                    placeholder='{"section1": {"key1": "value1", "key2": "value2"}, "section2": {"key3": "value3"}}'>${JSON.stringify(data, null, 2)}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    💡 JSON formatını kontrol edin. Geçersiz JSON kaydedilemez.
                </p>
            </div>
        `;

        this.container.innerHTML = html;
    }

    updateNestedData(jsonString) {
        try {
            this.currentData = JSON.parse(jsonString);
            this.updateHiddenInput();
        } catch (e) {
            console.error('JSON parse hatası:', e);
            // Hata durumunda eski değeri koru
        }
    }

    /**
     * Hidden input'u güncelle
     */
    updateHiddenInput() {
        if (this.hiddenInput) {
            try {
                // Simple array için boş değerleri temizle
                if (this.currentType === 'simple' && Array.isArray(this.currentData)) {
                    this.currentData = this.currentData.filter(v => v !== '' && v !== undefined && v !== null);
                }

                // Associative için boş key'leri temizle
                if (this.currentType === 'associative' && typeof this.currentData === 'object' && !Array.isArray(this.currentData)) {
                    const cleaned = {};
                    Object.entries(this.currentData).forEach(([key, value]) => {
                        if (key && value) {
                            cleaned[key] = value;
                        }
                    });
                    this.currentData = cleaned;
                }

                this.hiddenInput.value = JSON.stringify(this.currentData);
            } catch (e) {
                console.error('JSON stringify hatası:', e);
            }
        }
    }

    /**
     * HTML escape
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance (form sayfalarında kullanılacak)
window.formBuilder = null;
