/**
 * İl-İlçe-Mahalle Seçim Sistemi
 *
 * - İl seçilince ilçeler yüklenir ve mahalle sıfırlanır
 * - İlçe seçilince mahalleler yüklenir
 * - Select2 ile gelişmiş arama yapılabilir
 */
document.addEventListener('DOMContentLoaded', function () {
    // Select elementlerinin referanslarını al
    const ilSelect = document.getElementById('il');
    const ilceSelect = document.getElementById('ilce');
    const mahalleSelect = document.getElementById('mahalle');

    // Hata gösterge divleri
    const ilError = document.getElementById('il_error');
    const ilceError = document.getElementById('ilce_error');
    const mahalleError = document.getElementById('mahalle_error');

    // Seçili değerleri sakla (düzenle sayfası için)
    const selectedIl = ilSelect ? (ilSelect.dataset.selected || ilSelect.value) : '';
    const selectedIlce = ilceSelect ? (ilceSelect.dataset.selected || '') : '';
    const selectedMahalle = mahalleSelect ? (mahalleSelect.dataset.selected || '') : '';

    initializeSelect2();

    function triggerChanged(sel) {
        if (typeof $.fn !== 'undefined' && $.fn.select2) {
            window.$(sel).trigger('change.select2');
        } else {
            sel.dispatchEvent(new Event('change'));
        }
    }

    // İl değişince ilçeleri yükle
    ilSelect && ilSelect.addEventListener('change', function () {
        const ilId = ilSelect.value;
        ilError && ilError.classList.add('hidden');
        resetIlce();
        resetMahalle();

        if (ilId) {
            loadIlceler(ilId);
        }
    });

    // İlçe değişince mahalleleri yükle
    ilceSelect && ilceSelect.addEventListener('change', function () {
        const ilceId = ilceSelect.value;
        ilceError && ilceError.classList.add('hidden');
        resetMahalle();

        if (ilceId) {
            loadMahalleler(ilceId);
        }
    });

    // Mahalle seçildiğinde hata mesajını gizle
    mahalleSelect && mahalleSelect.addEventListener('change', function () {
        if (mahalleSelect.value) {
            mahalleError && mahalleError.classList.add('hidden');
        }
    });

    // Form gönderildiğinde validasyon
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const il = ilSelect ? ilSelect.value : '';
        const ilce = ilceSelect ? ilceSelect.value : '';
        const mahalle = mahalleSelect ? mahalleSelect.value : '';
        let hasError = false;

        // Hata mesajlarını temizle
        ilError && ilError.classList.add('hidden');
        ilceError && ilceError.classList.add('hidden');
        mahalleError && mahalleError.classList.add('hidden');

        // Validasyonları kontrol et
        if (!il && ilError) {
            ilError.classList.remove('hidden');
            hasError = true;
        }

        if (!ilce && ilceError) {
            ilceError.classList.remove('hidden');
            hasError = true;
        }

        if (!mahalle && mahalleError) {
            mahalleError.classList.remove('hidden');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            const target = document.getElementById('il');
            if (target) {
                try {
                    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
                } catch {
                    target.scrollIntoView();
                }
            }
            return false;
        }

        return true;
    });

    // Düzenleme sayfası için mevcut değerleri yükle
        if (selectedIl && ilSelect) {
            ilSelect.value = selectedIl;
            triggerChanged(ilSelect);

        // İlçe ve mahalleleri yükle
        if (selectedIlce) {
            setTimeout(function () {
                loadIlcelerWithSelected(selectedIl, selectedIlce, selectedMahalle);
            }, 300);
        }
    }

    // Select2 initialize fonksiyonu
    function initializeSelect2() {
        if (typeof $.fn === 'undefined' || !$.fn.select2) return;
        window.$(ilSelect).select2({
            placeholder: '-- İl Seçin --',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return 'Sonuç bulunamadı';
                },
                searching: function () {
                    return 'Aranıyor...';
                },
                inputTooShort: function () {
                    return 'Lütfen en az 2 karakter girin';
                },
            },
            theme: 'classic',
        });

        window.$(ilceSelect).select2({
            placeholder: '-- Önce İl Seçin --',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return 'Sonuç bulunamadı';
                },
                searching: function () {
                    return 'Aranıyor...';
                },
            },
            theme: 'classic',
        });

        window.$(mahalleSelect).select2({
            placeholder: '-- Önce İlçe Seçin --',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return 'Sonuç bulunamadı';
                },
                searching: function () {
                    return 'Aranıyor...';
                },
            },
            theme: 'classic',
        });
    }

    // İlçeleri yükle
    function loadIlceler(ilId) {
        ilceSelect.disabled = true;
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        triggerChanged(ilceSelect);

        const url = (window.APIConfig && window.APIConfig.location)
            ? window.APIConfig.location.districts(ilId)
            : `/api/v1/location/districts/${ilId}`;
        fetch(url)
            .then((res) => res.json())
            .then(function (result) {
                ilceSelect.innerHTML = '<option value="">-- İlçe Seçin --</option>';

                const data = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : []);
                if (data.length > 0) {
                    data.forEach(function (ilce) { ilceSelect.append(new Option(ilce.name || ilce.ilce_adi || ilce.ad, ilce.id)) });
                    ilceSelect.disabled = false;
                } else {
                    ilceSelect.append(new Option('Bu ile ait ilçe bulunamadı', ''));
                }
                triggerChanged(ilceSelect);
            })
            .catch(function (xhr) {
                console.error('İlçe yükleme hatası:', xhr);
                ilceSelect.innerHTML = '<option value="">Hata oluştu!</option>';
                ilceSelect.disabled = true;
                triggerChanged(ilceSelect);
            });
    }

    // Mahalleleri yükle
    function loadMahalleler(ilceId) {
        mahalleSelect.disabled = true;
        mahalleSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        triggerChanged(mahalleSelect);

        const url2 = (window.APIConfig && window.APIConfig.location)
            ? window.APIConfig.location.neighborhoods(ilceId)
            : `/api/v1/location/neighborhoods/${ilceId}`;
        fetch(url2)
            .then((res) => res.json())
            .then(function (result) {
                mahalleSelect.innerHTML = '<option value="">-- Mahalle Seçin --</option>';

                const data = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : []);
                if (data.length > 0) {
                    data.forEach(function (mahalle) { mahalleSelect.append(new Option(mahalle.name || mahalle.mahalle_adi || mahalle.ad, mahalle.id)) });
                    mahalleSelect.disabled = false;
                } else {
                    mahalleSelect.append(new Option('Bu ilçeye ait mahalle bulunamadı', ''));
                }
                triggerChanged(mahalleSelect);
            })
            .catch(function (xhr) {
                console.error('Mahalle yükleme hatası:', xhr);
                mahalleSelect.innerHTML = '<option value="">Hata oluştu!</option>';
                mahalleSelect.disabled = true;
                triggerChanged(mahalleSelect);
            });
    }

    // İlçeleri yükle ve seçili getir (düzenleme sayfası için)
    function loadIlcelerWithSelected(ilId, selectedIlceId, selectedMahalleId) {
        const url3 = (window.APIConfig && window.APIConfig.location)
            ? window.APIConfig.location.districts(ilId)
            : `/api/v1/location/districts/${ilId}`;
        fetch(url3)
            .then((res) => res.json())
            .then(function (result) {
                ilceSelect.innerHTML = '<option value="">-- İlçe Seçin --</option>';

                const data = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : []);
                if (data.length > 0) {
                    data.forEach(function (ilce) { ilceSelect.append(new Option(ilce.name || ilce.ilce_adi || ilce.ad, ilce.id)) });
                    ilceSelect.disabled = false;

                    // Seçili ilçeyi ayarla
                    if (selectedIlceId) {
                        ilceSelect.value = selectedIlceId;
                        triggerChanged(ilceSelect);

                        // Mahalleleri seçili ile yükle
                        if (selectedMahalleId) {
                            setTimeout(function () {
                                loadMahallelerWithSelected(selectedIlceId, selectedMahalleId);
                            }, 300);
                        }
                    }
                }

                triggerChanged(ilceSelect);
            })
            .catch(function (xhr) {
                console.error('İlçe yükleme hatası:', xhr);
            });
    }

    // Mahalleleri yükle ve seçili getir (düzenleme sayfası için)
    function loadMahallelerWithSelected(ilceId, selectedMahalleId) {
        const url4 = (window.APIConfig && window.APIConfig.location)
            ? window.APIConfig.location.neighborhoods(ilceId)
            : `/api/v1/location/neighborhoods/${ilceId}`;
        fetch(url4)
            .then((res) => res.json())
            .then(function (result) {
                mahalleSelect.innerHTML = '<option value="">-- Mahalle Seçin --</option>';

                const data = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : []);
                if (data.length > 0) {
                    data.forEach(function (mahalle) { mahalleSelect.append(new Option(mahalle.name || mahalle.mahalle_adi || mahalle.ad, mahalle.id)) });
                    mahalleSelect.disabled = false;

                    // Seçili mahalleyi ayarla
                    if (selectedMahalleId) {
                        mahalleSelect.value = selectedMahalleId;
                        triggerChanged(mahalleSelect);
                    }
                }

                triggerChanged(mahalleSelect);
            })
            .catch(function (xhr) {
                console.error('Mahalle yükleme hatası:', xhr);
            });
    }

    // İlçe seçimini sıfırla
    function resetIlce() {
        ilceSelect.innerHTML = '<option value="">-- Önce İl Seçin --</option>';
        ilceSelect.disabled = true;
        triggerChanged(ilceSelect);
    }

    // Mahalle seçimini sıfırla
    function resetMahalle() {
        mahalleSelect.innerHTML = '<option value="">-- Önce İlçe Seçin --</option>';
        mahalleSelect.disabled = true;
        triggerChanged(mahalleSelect);
    }
});
