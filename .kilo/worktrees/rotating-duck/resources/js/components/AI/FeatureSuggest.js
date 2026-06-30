import AIService from '../../admin/services/AIService.js';
import { FormValidator } from '../../admin/form-validator.js';

function ensureUiState(el) {
    let uiStatus = el.querySelector('[data-ai-state]');
    if (!uiStatus) {
        uiStatus = document.createElement('div');
        uiStatus.setAttribute('data-ai-state', 'true');
        // Role removed to avoid Context7 violation
        // aria-live="polite" is sufficient for state updates
        uiStatus.setAttribute('aria-live', 'polite');
        uiStatus.className = 'mt-2 text-sm text-gray-600';
        el.appendChild(uiStatus);
    }
    return uiStatus;
}

function collectContext(form) {
    const data = {};
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach((i) => {
        const k = i.name || i.id;
        if (!k) return;
        const v = i.type === 'checkbox' ? i.checked : i.value;
        data[k] = v;
    });
    return data;
}

async function handleSuggest(form) {
    const uiStatus = ensureUiState(form);
    uiStatus.textContent = 'Öneriler yükleniyor…';
    uiStatus.setAttribute('aria-busy', 'true');
    const validator = new FormValidator('#' + (form.id || ''));
    const required = form.querySelectorAll('[data-ai-required]');
    let ok = true;
    required.forEach((el) => {
        if (!el.value || String(el.value).trim() === '') {
            validator.showFieldError(el, 'Bu alan zorunludur');
            ok = false;
        }
    });
    if (!ok) {
        uiStatus.textContent = 'Eksik alanlar var';
        uiStatus.setAttribute('aria-busy', 'false');
        return;
    }
    try {
        const context = collectContext(form);
        const res = await AIService.suggestFeatures(context, { rateMs: 300 });
        uiStatus.setAttribute('aria-busy', 'false');
        if (!res || res.success === false) {
            uiStatus.textContent = 'Öneri alınamadı';
            return;
        }
        const box = form.querySelector('[data-ai-suggestions]') || ensureUiState(form);
        box.textContent = '';
        if (Array.isArray(res.data)) {
            const ul = document.createElement('ul');
            ul.className = 'mt-2 space-y-1';
            res.data.forEach((s) => {
                const li = document.createElement('li');
                li.textContent = String(s && s.label ? s.label : s);
                ul.appendChild(li);
            });
            box.appendChild(ul);
        } else if (res.data && res.data.html) {
            box.innerHTML = res.data.html;
        } else {
            box.textContent = JSON.stringify(res.data);
        }
    } catch (e) {
        uiStatus.setAttribute('aria-busy', 'false');
        uiStatus.textContent = 'Hata: ' + (e && e.message ? e.message : 'Bilinmeyen hata');
    }
}

function init(selector = '[data-ai-suggest="true"]') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll(selector).forEach((form) => {
            const btn = form.querySelector('[data-ai-suggest-button]');
            const uiStatus = ensureUiState(form);
            uiStatus.textContent = '';
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    handleSuggest(form);
                });
            }
        });
    });
}

try {
    if (typeof window !== 'undefined') window.FeatureSuggest = { init };
} catch (e) {}

export default { init };
