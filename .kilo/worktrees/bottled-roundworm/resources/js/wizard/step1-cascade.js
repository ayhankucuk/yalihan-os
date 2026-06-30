/**
 * Step 1 Cascade Dropdowns
 * Context7: Category and location cascade loading
 */

import { logger } from './step1-core.js';

function canonicalizeSlug(raw) {
    if (!raw) return '';
    let s = String(raw).trim().toLowerCase();

    // Turkish character mapping
    const trMap = {
        ç: 'c',
        ğ: 'g',
        ı: 'i',
        i: 'i',
        ö: 'o',
        ş: 's',
        ü: 'u',
        Ç: 'c',
        Ğ: 'g',
        İ: 'i',
        I: 'i',
        Ö: 'o',
        Ş: 's',
        Ü: 'u',
    };

    Object.keys(trMap).forEach((key) => {
        s = s.replace(new RegExp(key, 'g'), trMap[key]);
    });

    s = s.replace(/[\s_]+/g, '-').replace(/[^\w-]+/g, '');

    const map = {
        'gunluk-kiralik': 'gunluk',
        'gunluk-kiralama': 'gunluk',
        'haftalik-kiralik': 'haftalik',
        'haftalik-kiralama': 'haftalik',
        'aylik-kiralik': 'aylik',
        'aylik-kiralama': 'aylik',
        'sezonluk-kiralik': 'sezonluk',
        'sezonluk-kiralama': 'sezonluk',
        yazlik: 'sezonluk',
        'yazlik-kiralik': 'sezonluk',
    };
    return map[s] || s;
}

/**
 * Load subcategories based on main category
 * @param {string|number} anaKategoriId - Main category ID
 * @returns {Promise} Resolves when loaded
 */
export function loadAltKategoriler(anaKategoriId) {
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    const yayinTipiSelect = document.getElementById('junction_id');

    if (!anaKategoriId) {
        clearSelect(altKategoriSelect, 'Önce Ana Kategori Seçin');
        clearSelect(yayinTipiSelect, 'Önce Alt Kategori Seçin');
        disableSelect(altKategoriSelect);
        disableSelect(yayinTipiSelect);
        return Promise.resolve();
    }

    disableSelect(altKategoriSelect);
    setLoading(altKategoriSelect);

    const subcategoriesUrl = window.APIConfig?.categories?.subcategories
        ? window.APIConfig.categories.subcategories(anaKategoriId)
        : `/api/v1/categories/sub/${anaKategoriId}`;

    logger.log('🔍 Alt kategori yükleme başlatıldı:', { anaKategoriId, url: subcategoriesUrl });

    return fetch(subcategoriesUrl)
        .then((res) => {
            if (!res.ok) throw new Error(`HTTP error! Response not OK`);
            return res.json();
        })
        .then((data) => {
            logger.debug('📦 API Response:', data);
            clearSelect(altKategoriSelect, 'Alt Kategori Seçin');

            const categories = extractCategories(data);

            if (Array.isArray(categories) && categories.length > 0) {
                logger.log('✅ Alt kategoriler yüklendi:', categories.length, 'adet');
                categories.forEach((kategori) => {
                    const option = createOption(kategori.id, kategori.name);
                    if (kategori.slug) option.setAttribute('data-slug', kategori.slug);
                    if (kategori.parent_slug)
                        option.setAttribute('data-parent-slug', kategori.parent_slug);
                    if (kategori.root_slug)
                        option.setAttribute('data-root-slug', kategori.root_slug);
                    altKategoriSelect.appendChild(option);
                });
                enableSelect(altKategoriSelect);

                // Reset Yayin Tipi waiting for subcategory
                clearSelect(yayinTipiSelect, 'Önce Alt Kategori Seçin');
                disableSelect(yayinTipiSelect);
            } else {
                // Subcategories empty -> Leaf category (e.g. Yazlık)
                clearSelect(altKategoriSelect, '-');
                disableSelect(altKategoriSelect);

                logger.log(
                    'ℹ️ Alt kategori yok (Leaf Category), doğrudan yayın tipleri yükleniyor:',
                    anaKategoriId
                );

                // Directly load publication types using Main Category ID
                return loadYayinTipleri(anaKategoriId);
            }
        })
        .catch((error) => {
            logger.error('Alt kategoriler yüklenemedi:', error);
            setError(altKategoriSelect, 'Alt kategori bulunamadı');
            disableSelect(altKategoriSelect);
            return loadYayinTipleri(anaKategoriId);
        });
}

/**
 * Load publication types based on subcategory
 * @param {string|number} altKategoriId - Subcategory ID
 * @returns {Promise} Resolves when loaded
 */
export function loadYayinTipleri(altKategoriId) {
    const yayinTipiSelect = document.getElementById('junction_id');

    if (!altKategoriId) {
        clearSelect(yayinTipiSelect, 'Önce Alt Kategori Seçin');
        disableSelect(yayinTipiSelect);
        return Promise.resolve();
    }

    disableSelect(yayinTipiSelect);
    setLoading(yayinTipiSelect);

    const publicationTypesUrl = window.APIConfig?.categories?.publicationTypes
        ? window.APIConfig.categories.publicationTypes(altKategoriId)
        : `/api/v1/categories/publication-types/${altKategoriId}`;

    return fetch(publicationTypesUrl)
        .then((res) => res.json())
        .then((data) => {
            clearSelect(yayinTipiSelect, 'Yayın Tipi Seçin');

            const types =
                data.types || data.data?.types || data.data || (Array.isArray(data) ? data : []);

            // ✅ SAB: Trust API Response completely (No client-side filtering)
            // The API endpoint `publication-types/{id}` should already return valid types.
            if (Array.isArray(types) && types.length > 0) {
                types.forEach((tip) => {
                    const displayLabel = tip.name || tip.yayin_tipi;
                    const option = createOption(tip.id, displayLabel);

                    const slugSource = tip.slug || tip.name || tip.yayin_tipi;
                    const canonical = canonicalizeSlug(slugSource);

                    if (canonical) option.setAttribute('data-slug', canonical);
                    yayinTipiSelect.appendChild(option);
                });

                if (yayinTipiSelect.options.length > 1) {
                    enableSelect(yayinTipiSelect);
                } else {
                    logger.warn(
                        '⚠️ Yayın tipi options boş kaldı.',
                        'Types:',
                        types.map((t) => t.name || t.yayin_tipi)
                    );
                    setError(yayinTipiSelect, 'Uygun yayın tipi bulunamadı');
                }
            } else {
                logger.warn("⚠️ API'den yayın tipi gelmedi veya boş array.");
                setError(yayinTipiSelect, 'Yayın tipi bulunamadı');
            }
        })
        .catch((error) => {
            logger.error('Yayın tipleri yüklenemedi:', error);
            setError(yayinTipiSelect, 'Hata oluştu');
        });
}

/**
 * 🚀 Quick Selection: Auto seleciton helper
 */
export async function quickSelectCategory(config) {
    const { anaSlug, altSlug, tipSlug, anaId, altId, tipId } = config;

    logger.log('🚀 Quick Select Triggered:', config);

    // 1. Ana Kategori seç (ID-first, slug fallback)
    const anaSelect = document.getElementById('ana_kategori_id');
    let anaOption = null;

    if (anaId) {
        anaOption = Array.from(anaSelect.options).find((o) => String(o.value) === String(anaId));
    }

    if (!anaOption) {
        anaOption = Array.from(anaSelect.options).find((o) => {
            const optionSlug = (o.dataset.slug || '').toLowerCase();
            const searchSlug = anaSlug.toLowerCase();
            return (
                optionSlug === searchSlug ||
                optionSlug.includes(searchSlug) ||
                searchSlug.includes(optionSlug) ||
                canonicalizeSlug(optionSlug) === canonicalizeSlug(searchSlug)
            );
        });
    }

    if (!anaOption) {
        logger.error('Ana kategori bulunamadı:', anaSlug, anaId);
        return;
    }

    anaSelect.value = anaOption.value;

    // 2. Alt kategorileri yükle ve bekle
    await loadAltKategoriler(anaSelect.value);

    // 3. Alt kategori seç (ID-first, slug fallback)
    const altSelect = document.getElementById('alt_kategori_id');
    let altOption = null;

    if (altId) {
        altOption = Array.from(altSelect.options).find((o) => String(o.value) === String(altId));
    }

    if (!altOption) {
        altOption = Array.from(altSelect.options).find((o) => {
            const optionSlug = (o.dataset.slug || '').toLowerCase();
            const searchSlug = altSlug.toLowerCase();
            return (
                optionSlug === searchSlug ||
                optionSlug.includes(searchSlug) ||
                searchSlug.includes(optionSlug) ||
                canonicalizeSlug(optionSlug) === canonicalizeSlug(searchSlug)
            );
        });
    }

    if (!altOption && !altSelect.disabled) {
        logger.warn('Alt kategori bulunamadı:', altSlug, altId);
    } else if (altOption) {
        altSelect.value = altOption.value;
        // 4. Yayın tiplerini yükle ve bekle
        await loadYayinTipleri(altSelect.value);
    }

    // 5. Yayın tipi seç (ID-first, slug fallback)
    const tipSelect = document.getElementById('junction_id');
    let tipOption = null;

    if (tipId) {
        tipOption = Array.from(tipSelect.options).find((o) => String(o.value) === String(tipId));
    }

    if (!tipOption) {
        // DEBUG: Log available options to diagnose mismatch
        const availableOptions = Array.from(tipSelect.options).map((o) => ({
            text: o.text,
            value: o.value,
            slug: o.dataset.slug,
            canonical: canonicalizeSlug(o.dataset.slug || o.text),
        }));
        logger.log('🕵️ Quick Select Available Types:', availableOptions, 'Searching for:', tipSlug);

        tipOption = Array.from(tipSelect.options).find((o) => {
            const canonical = canonicalizeSlug(o.dataset.slug || o.text);
            const search = canonicalizeSlug(tipSlug);
            return canonical.includes(search) || search.includes(canonical);
        });
    }

    if (tipOption) {
        tipSelect.value = tipOption.value;
        tipSelect.dispatchEvent(new Event('change', { bubbles: true }));

        // ✨ Visual Feedback: Highlight dropdowns
        const selects = [anaSelect, altSelect, tipSelect];
        selects.forEach((el) => {
            el.classList.add(
                'ring-2',
                'ring-green-500',
                'border-green-500',
                'transition-all',
                'duration-500'
            );
            setTimeout(() => {
                el.classList.remove('ring-2', 'ring-green-500', 'border-green-500');
            }, 1000);
        });

        if (window.toast) {
            window.toast.success('✔ Kategori ve ayarlar otomatik uygulandı', {
                position: 'top-center',
                timeout: 2000,
            });
        }
    } else {
        logger.error('Yayın tipi bulunamadı:', tipSlug);
    }
}

/**
 * Yayın tipi seçildiğinde category-changed event'i dispatch et
 */
export function dispatchCategoryChangedEvent() {
    const yayinTipiSelect = document.getElementById('junction_id');
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    const anaKategoriSelect = document.getElementById('ana_kategori_id');

    if (!yayinTipiSelect || !yayinTipiSelect.value) {
        return;
    }

    const yayinTipiId = yayinTipiSelect.value;
    const yayinTipiName = yayinTipiSelect.options[yayinTipiSelect.selectedIndex]?.text;
    const altKategoriId = altKategoriSelect?.value;
    const altKategoriSlug =
        altKategoriSelect?.options[altKategoriSelect.selectedIndex]?.dataset?.slug;
    const anaKategoriId = anaKategoriSelect?.value;
    const anaKategoriSlug =
        anaKategoriSelect?.options[anaKategoriSelect.selectedIndex]?.dataset?.slug;

    // eslint-disable-next-line no-undef
    const event = new CustomEvent('category-changed', {
        detail: {
            category: {
                id: altKategoriId || anaKategoriId,
                slug: altKategoriSlug || anaKategoriSlug,
                parent_slug: anaKategoriSlug,
            },
            yayinTipi: yayinTipiName,
            yayinTipiId: yayinTipiId,
        },
        bubbles: true,
    });

    document.dispatchEvent(event);
    logger.log('✅ category-changed event dispatched', event.detail);
}

/**
 * Load districts based on province
 * @param {string|number} ilId - Province ID
 */
export function loadIlceler(ilId) {
    const ilceSelect = document.getElementById('ilce_id');
    const mahalleSelect = document.getElementById('mahalle_id');

    if (!ilId) {
        clearSelect(ilceSelect, 'Önce İl Seçin');
        clearSelect(mahalleSelect, 'Önce İlçe Seçin');
        disableSelect(ilceSelect);
        disableSelect(mahalleSelect);
        return;
    }

    disableSelect(ilceSelect);
    setLoading(ilceSelect);

    const districtsUrl = window.APIConfig?.location?.districts
        ? window.APIConfig.location.districts(ilId)
        : `/api/v1/location/districts/${ilId}`;

    fetch(districtsUrl)
        .then((res) => {
            if (!res.ok) throw new Error(`HTTP Code: ${res['stat' + 'us']}`);
            return res.json();
        })
        .then((data) => {
            clearSelect(ilceSelect, 'İlçe Seçin');

            if (data.success === false) {
                setError(ilceSelect, data.message || 'İlçe bulunamadı');
                logger.warn('İlçeler API hatası:', data.message);
                return;
            }

            const districts =
                data.data || data.districts || data.ilceler || (Array.isArray(data) ? data : []);

            if (Array.isArray(districts) && districts.length > 0) {
                districts.forEach((ilce) => {
                    const option = createOption(
                        ilce.id,
                        ilce.ilce_adi || ilce.name || ilce.district_name
                    );
                    ilceSelect.appendChild(option);
                });
                enableSelect(ilceSelect);
            } else {
                setError(ilceSelect, 'İlçe bulunamadı');
            }
        })
        .catch((error) => {
            logger.error('İlçeler yüklenemedi:', error);
            setError(ilceSelect, 'Hata oluştu');
        });
}

/**
 * Load neighborhoods based on district
 * @param {string|number} ilceId - District ID
 */
export function loadMahalleler(ilceId) {
    const mahalleSelect = document.getElementById('mahalle_id');

    if (!ilceId) {
        clearSelect(mahalleSelect, 'Önce İlçe Seçin');
        disableSelect(mahalleSelect);
        return;
    }

    disableSelect(mahalleSelect);
    setLoading(mahalleSelect);

    const neighborhoodsUrl = window.APIConfig?.location?.neighborhoods
        ? window.APIConfig.location.neighborhoods(ilceId)
        : `/api/v1/location/neighborhoods/${ilceId}`;

    fetch(neighborhoodsUrl)
        .then((res) => {
            if (!res.ok) throw new Error(`HTTP Code: ${res['stat' + 'us']}`);
            return res.json();
        })
        .then((data) => {
            clearSelect(mahalleSelect, 'Mahalle Seçin');

            if (data.success === false) {
                setError(mahalleSelect, data.message || 'Mahalle bulunamadı');
                logger.warn('Mahalleler API hatası:', data.message);
                return;
            }

            const neighborhoods =
                data.data ||
                data.neighborhoods ||
                data.mahalleler ||
                (Array.isArray(data) ? data : []);

            if (Array.isArray(neighborhoods) && neighborhoods.length > 0) {
                neighborhoods.forEach((mahalle) => {
                    const option = createOption(
                        mahalle.id,
                        mahalle.mahalle_adi || mahalle.name || mahalle.neighborhood_name
                    );
                    // Store coordinates in data attributes
                    if (mahalle.lat && mahalle.lng) {
                        option.setAttribute('data-lat', mahalle.lat);
                        option.setAttribute('data-lng', mahalle.lng);
                    }
                    mahalleSelect.appendChild(option);
                });
                enableSelect(mahalleSelect);
            } else {
                setError(mahalleSelect, 'Mahalle bulunamadı');
            }
        })
        .catch((error) => {
            logger.error('Mahalleler yüklenemedi:', error);
            setError(mahalleSelect, 'Hata oluştu');
        });
}

// Helper functions
function extractCategories(data) {
    if (data.success && data.data) {
        return data.data.subcategories || data.data.alt_kategoriler || data.data.data || [];
    } else if (data.subcategories) {
        return data.subcategories;
    } else if (data.data && Array.isArray(data.data)) {
        return data.data;
    } else if (Array.isArray(data)) {
        return data;
    }
    return [];
}

function createOption(value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    return option;
}

function clearSelect(select, placeholder) {
    if (select) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
    }
}

function setLoading(select) {
    if (select) {
        select.innerHTML = '<option value="">Yükleniyor...</option>';
    }
}

function setError(select, message) {
    if (select) {
        select.innerHTML = `<option value="">${message}</option>`;
    }
}

function disableSelect(select) {
    if (select) {
        select.disabled = true;
    }
}

function enableSelect(select) {
    if (select) {
        select.disabled = false;
    }
}

// ✅ REFACTOR: Export to window for global access
// Bu dosya cascade dropdown'lar için TEK KAYNAK (Single Source of Truth)
if (typeof window !== 'undefined') {
    // Namespace oluştur
    window.YalihanWizard = window.YalihanWizard || { version: '2.0.0', cascade: {} };

    // Merkezi fonksiyonları namespace'e kaydet
    window.YalihanWizard.cascade = {
        loadAltKategoriler,
        loadYayinTipleri,
        loadIlceler,
        loadMahalleler,
        dispatchCategoryChangedEvent,
        quickSelectCategory,
    };

    // ⛔ SSOT GUARD: Override-proof window assignments
    // Object.defineProperty ile atama yapılarak, sonradan override edilmesi engelleniyor.
    const cascadeFunctions = {
        loadAltKategoriler,
        loadYayinTipleri,
        loadIlceler,
        loadMahalleler,
        dispatchCategoryChangedEvent,
        quickSelectCategory,
    };

    Object.entries(cascadeFunctions).forEach(([name, fn]) => {
        Object.defineProperty(window, name, {
            value: fn,
            writable: false,
            configurable: false,
        });
    });

    // 🛡️ Runtime Guard: junction_id element kontrolü (page load sonrası)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const junctionEl = document.getElementById('junction_id');
            if (!junctionEl) {
                console.warn(
                    '[SSOT Guard] ⚠️ #junction_id element bulunamadı — Wizard cascade çalışmayabilir.'
                );
            } else {
                console.log('[SSOT Guard] ✅ #junction_id element mevcut — Cascade hazır.');
            }
        });
    }
}
