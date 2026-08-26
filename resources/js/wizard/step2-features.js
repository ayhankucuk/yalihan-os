/**
 * Step 2 Unified Wizard Engine — CREATE + EDIT in one flow.
 *
 * CREATE: /api/v1/wizard/features → empty form → user input → submit
 * EDIT:   /api/v1/wizard/features-with-values → hydrated form → update → submit
 *
 * SSOT: FeatureTemplateResolver (backend) → this JS (frontend)
 * No hardcoded fields. No slug matching. No if/else category logic.
 */

/**
 * Alpine.js component for schema-driven Step 2 features.
 *
 * @param {Object} config - Configuration: { ilanId: number|null }
 */
export function wizardStep2FeaturesComponent(config = {}) {
  return {
    loading: true,
    fields: [],
    groups: [],
    meta: null,
    form: {},
    error: null,
    loaded: false,
    ilanId: config.ilanId || null,

    init() {
      window.__step2ActiveInstance = this;

      // Support both direct config and window-level ilanId
      if (!this.ilanId && window.ilanId) {
        this.ilanId = window.ilanId;
      }

      // Listen for category/yayin tipi changes from Step 1
      window.addEventListener("category-changed", () => this.load());
      document.addEventListener("wizard-step-changed", (e) => {
        if (e.detail?.step === 2 && !this.loaded) {
          this.load();
        }
      });

      // Try loading immediately if Step 1 data already exists
      this.$nextTick(() => this.load());
    },

    /**
     * Read Step 1 selection state from DOM or window.wizard.
     */
    getStep1Params() {
      // 1. Read directly from DOM selects (SSOT)
      const anaSelect = document.getElementById("ana_kategori_id");
      const altSelect = document.getElementById("alt_kategori_id");
      const yayinSelect = document.getElementById("junction_id") || document.getElementById("yayin_tipi_id");

      let ana_kategori_id = anaSelect?.value || null;
      let alt_kategori_id = altSelect?.value || null;
      let yayin_tipi_id = yayinSelect?.value || null;

      // 2. Fallback to window.wizard state if DOM is not yet populated
      if (!ana_kategori_id && window.wizard?.ana_kategori_id) {
        ana_kategori_id = window.wizard.ana_kategori_id;
      }
      if (!alt_kategori_id && window.wizard?.alt_kategori_id) {
        alt_kategori_id = window.wizard.alt_kategori_id;
      }
      if (!yayin_tipi_id && window.wizard?.yayin_tipi_id) {
        yayin_tipi_id = window.wizard.yayin_tipi_id;
      }

      return {
        ana_kategori_id: ana_kategori_id || null,
        alt_kategori_id: alt_kategori_id || null,
        yayin_tipi_id: yayin_tipi_id || null,
      };
    },

    /**
     * Unified load — dispatches to CREATE or EDIT mode.
     */
    async load() {
      const params = this.getStep1Params();

      if (!params.ana_kategori_id || !params.yayin_tipi_id) {
        this.fields = [];
        this.groups = [];
        this.meta = null;
        this.loaded = false;
        this.loading = false;
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        if (this.ilanId) {
          await this.loadEditMode(params);
        } else {
          await this.loadCreateMode(params);
        }
        this.loaded = true;
      } catch (e) {
        console.warn("[Step2Features] Load failed (graceful):", e);
        this.error =
          "Özellikler yüklenemedi. Lütfen kategori seçimini kontrol edin.";
        this.fields = [];
        this.groups = [];
      } finally {
        this.loading = false;
      }
    },

    /**
     * CREATE mode — fetch fields, build empty form.
     */
    async loadCreateMode(params) {
      const url = this.buildUrl("/api/v1/wizard/features", params);
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`Features API ${response.status}`);
      }

      const json = await response.json();
      const data = json.data;

      this.fields = data.fields || [];
      this.groups = data.groups || [];
      this.meta = data.meta || {};

      this.buildEmptyForm();
    },

    /**
     * EDIT mode — fetch fields + existing values, build hydrated form.
     */
    async loadEditMode(params) {
      const url = this.buildUrl("/api/v1/wizard/features-with-values", {
        ...params,
        ilan_id: this.ilanId,
      });
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`Features+Values API ${response.status}`);
      }

      const json = await response.json();
      const data = json.data;

      this.fields = data.fields || [];
      this.groups = data.groups || [];
      this.meta = data.meta || {};

      this.buildHydratedForm(data.values || {});
    },

    /**
     * Build URL with query params.
     */
    buildUrl(base, params) {
      const qs = new URLSearchParams();
      Object.entries(params).forEach(([key, val]) => {
        if (val !== null && val !== undefined && val !== "") {
          qs.set(key, val);
        }
      });
      return `${base}?${qs.toString()}`;
    },

    /**
     * CREATE: set every field to its type-appropriate default.
     */
    buildEmptyForm() {
      this.form = {};
      this.fields.forEach((field) => {
        this.form[field.slug] = this.defaultValue(field);
      });
    },

    /**
     * EDIT: merge DB values into form, fallback to defaults for missing fields.
     */
    buildHydratedForm(values) {
      this.form = {};
      this.fields.forEach((field) => {
        if (values[field.slug] !== undefined && values[field.slug] !== null) {
          this.form[field.slug] = values[field.slug];
        } else {
          this.form[field.slug] = this.defaultValue(field);
        }
      });
    },

    /**
     * Type-appropriate default value for a field.
     */
    defaultValue(field) {
      switch (field.type) {
        case "boolean":
          return false;
        case "multiselect":
          return [];
        case "number":
          return null;
        default:
          return "";
      }
    },

    /**
     * Check if a specific field is required.
     */
    isFieldRequired(slug) {
      const field = this.fields.find((f) => f.slug === slug);
      return field?.required || false;
    },

    /**
     * Get input type attribute for a field type.
     */
    getInputType(fieldType) {
      const typeMap = {
        number: "number",
        text: "text",
        boolean: "checkbox",
      };
      return typeMap[fieldType] || "text";
    },

    // ── Dependency Evaluator ──

    /**
     * Evaluate a single dependency rule against current form state.
     * @param {Object|null} rule - {field, operator, value?}
     * @returns {boolean} True if condition is met (or no rule)
     */
    evaluateRule(rule) {
      if (!rule || !rule.field || !rule.operator) {
        return true; // No rule = always true
      }

      const fieldSlug = rule.field;
      // If the source field doesn't exist in schema, treat rule as inactive
      const sourceField = this.fields.find((f) => f.slug === fieldSlug);
      if (!sourceField) {
        return true;
      }

      const currentValue = this.form[fieldSlug];

      switch (rule.operator) {
        case "=":
          return String(currentValue ?? "") === String(rule.value ?? "");
        case "!=":
          return String(currentValue ?? "") !== String(rule.value ?? "");
        case "in":
          if (!Array.isArray(rule.value)) return true;
          return rule.value.map(String).includes(String(currentValue ?? ""));
        case "not_in":
          if (!Array.isArray(rule.value)) return true;
          return !rule.value.map(String).includes(String(currentValue ?? ""));
        case "truthy":
          return (
            !!currentValue && currentValue !== "0" && currentValue !== "false"
          );
        case "falsy":
          return (
            !currentValue ||
            currentValue === "0" ||
            currentValue === "false" ||
            currentValue === ""
          );
        default:
          return true; // Unknown operator = inactive
      }
    },

    /**
     * Check if a field should be visible based on visible_if rule.
     */
    isVisible(field) {
      return this.evaluateRule(field.visible_if);
    },

    /**
     * Check if a field is effectively required (base required OR required_if active).
     */
    isRequired(field) {
      if (field.required) return true;
      if (!field.required_if) return false;
      return this.evaluateRule(field.required_if);
    },

    /**
     * Check if a field should be enabled based on enabled_if rule.
     */
    isEnabled(field) {
      return this.evaluateRule(field.enabled_if);
    },

    /**
     * Validate all required feature fields (dependency-aware).
     * @returns {string[]} Array of invalid field slugs
     */
    validateFeatures() {
      const invalid = [];
      this.fields.forEach((field) => {
        // Skip invisible fields — they are not user-actionable
        if (!this.isVisible(field)) return;

        if (this.isRequired(field)) {
          const value = this.form[field.slug];
          if (value === null || value === undefined || value === "") {
            invalid.push(field.slug);
          }
        }
      });
      return invalid;
    },

    /**
     * Get sanitized form data for submission.
     * - Invisible fields → excluded from payload
     * - Disabled fields → excluded from payload
     * - Boolean false → deterministic '0'
     * - Empty multiselect → []
     * @returns {Object} Sanitized feature slug-value pairs
     */
    getFormData() {
      const data = {};
      this.fields.forEach((field) => {
        // Skip invisible fields
        if (!this.isVisible(field)) return;
        // Skip disabled fields
        if (!this.isEnabled(field)) return;

        let value = this.form[field.slug];

        // Boolean normalization
        if (field.type === "boolean") {
          data[field.slug] = value ? "1" : "0";
          return;
        }

        // Multiselect normalization
        if (field.type === "multiselect") {
          data[field.slug] = Array.isArray(value) ? value : [];
          return;
        }

        data[field.slug] = value;
      });
      return data;
    },

    /**
     * Render group fields directly as HTML to avoid nested Alpine template x-for issues.
     */
    renderGroupHtml(groupItem) {
      if (!groupItem || !groupItem.fields) return "";

      return groupItem.fields
        .map((field) => {
          const visible = this.isVisible(field);
          const hiddenStyle = visible ? "" : 'style="display: none;"';
          const required = this.isRequired(field);
          const enabled = this.isEnabled(field);
          const requiredStar = required
            ? '<span class="text-red-500 ml-0.5">*</span>'
            : "";
          const unitText = field.unit
            ? `<span class="text-xs text-gray-400 dark:text-slate-500 ml-1">(${field.unit})</span>`
            : "";
          const requiredAttr = required ? "required" : "";
          const disabledAttr = !enabled ? "disabled" : "";
          const rawVal = this.form[field.slug];
          const val = rawVal !== undefined && rawVal !== null ? rawVal : "";

          let inputHtml = "";

          if (field.type === "number") {
            inputHtml = `
              <input type="number" id="feature-${field.slug}" name="features[${field.slug}]"
                ${requiredAttr} ${disabledAttr} step="any" value="${val}"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors disabled:opacity-50"
                oninput="window.__step2FormSet('${field.slug}', this.value)" />
            `;
          } else if (field.type === "select") {
            const opts = (field.options || [])
              .map(
                (o) =>
                  `<option value="${o.value}" ${String(o.value) === String(val) ? "selected" : ""}>${o.label}</option>`,
              )
              .join("");
            inputHtml = `
              <select id="feature-${field.slug}" name="features[${field.slug}]"
                ${requiredAttr} ${disabledAttr}
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors disabled:opacity-50"
                onchange="window.__step2FormSet('${field.slug}', this.value)">
                <option value="">Seçiniz</option>
                ${opts}
              </select>
            `;
          } else if (field.type === "boolean") {
            const isChecked = !!val && val !== "0" && val !== 0 ? "checked" : "";
            inputHtml = `
              <div class="flex items-center gap-2 mt-1">
                <input type="checkbox" id="feature-${field.slug}" name="features[${field.slug}]"
                  value="1" ${isChecked} ${disabledAttr}
                  class="rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 dark:bg-slate-800 h-4 w-4"
                  onchange="window.__step2FormSet('${field.slug}', this.checked ? 1 : 0)" />
                <span class="text-sm text-gray-600 dark:text-slate-400">${field.label}</span>
              </div>
            `;
          } else if (field.type === "multiselect") {
            const selArr = Array.isArray(val) ? val : [];
            const checkboxes = (field.options || [])
              .map((o) => {
                const checked = selArr.includes(o.value) ? "checked" : "";
                return `
                  <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300 cursor-pointer py-0.5">
                    <input type="checkbox" name="features[${field.slug}][]" value="${o.value}" ${checked} ${disabledAttr}
                      class="rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 dark:bg-slate-800"
                      onchange="window.__step2MultiselectToggle('${field.slug}', '${o.value}', this.checked)" />
                    <span class="text-sm">${o.label}</span>
                  </label>
                `;
              })
              .join("");
            inputHtml = `
              <div class="space-y-1 max-h-40 overflow-y-auto border border-gray-300 dark:border-slate-600 rounded-lg p-3 bg-white dark:bg-slate-800">
                ${checkboxes}
              </div>
            `;
          } else {
            inputHtml = `
              <input type="text" id="feature-${field.slug}" name="features[${field.slug}]"
                ${requiredAttr} ${disabledAttr} maxlength="500" value="${val}"
                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors disabled:opacity-50"
                oninput="window.__step2FormSet('${field.slug}', this.value)" />
            `;
          }

          const descHtml = field.description
            ? `<p class="mt-1 text-xs text-gray-400 dark:text-slate-500">${field.description}</p>`
            : "";

          return `
            <div class="dynamic-field" ${hiddenStyle} data-field-slug="${field.slug}" data-field-type="${field.type}">
              <label for="feature-${field.slug}" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                <span>${field.label}</span>${requiredStar}${unitText}
              </label>
              ${inputHtml}
              ${descHtml}
            </div>
          `;
        })
        .join("");
    },

    /**
     * Alias for getFormData — sanitized, dependency-aware payload.
     * Invisible/disabled fields stripped, types normalized.
     * @returns {Object} Clean slug-value pairs safe for backend submission
     */
    sanitizeForm() {
      return this.getFormData();
    },
  };
}

// Global bridge for input sync
window.__step2ActiveInstance = null;
window.__step2FormSet = (slug, val) => {
  const comp = window.__step2ActiveInstance;
  if (comp) {
    comp.form[slug] = val;
    window.dispatchEvent(
      new CustomEvent("feature-changed", { detail: { slug, value: val } }),
    );
  }
};
window.__step2MultiselectToggle = (slug, optVal, checked) => {
  const comp = window.__step2ActiveInstance;
  if (comp) {
    const arr = Array.isArray(comp.form[slug]) ? [...comp.form[slug]] : [];
    if (checked && !arr.includes(optVal)) {
      arr.push(optVal);
    } else if (!checked && arr.includes(optVal)) {
      const idx = arr.indexOf(optVal);
      arr.splice(idx, 1);
    }
    comp.form[slug] = arr;
    window.dispatchEvent(
      new CustomEvent("feature-changed", { detail: { slug, value: arr } }),
    );
  }
};

// Register Alpine component + global window fallback
const registerComponent = () => {
  const A = window.Alpine;
  if (A) {
    A.data("wizardStep2FeaturesComponent", wizardStep2FeaturesComponent);
    // Backward compat alias
    A.data("wizardStep2SchemaComponent", wizardStep2FeaturesComponent);
  }
};

// Also expose on window for inline x-data fallback
window.wizardStep2FeaturesComponent = wizardStep2FeaturesComponent;

if (window.Alpine) {
  registerComponent();
} else {
  document.addEventListener("alpine:init", registerComponent);
}
