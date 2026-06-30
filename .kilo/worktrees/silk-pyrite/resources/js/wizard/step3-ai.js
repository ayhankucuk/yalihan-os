/**
 * Step 3 AI Handler
 * Context7: AI açıklama üretimi
 */

import { logger } from './step1-core.js';
import { getWizardContext } from './wizard-context.js';

/**
 * Generate description with AI
 */
export async function generateDescriptionWithAI() {
    const baslik = document.getElementById('baslik')?.value;
    const altKategoriId = document.getElementById('alt_kategori_id')?.value;
    const ilId = document.getElementById('il_id')?.value;
    const ilceId = document.getElementById('ilce_id')?.value;
    const ctx = getWizardContext();
    const adaNo = document.getElementById('ada_no')?.value;
    const parselNo = document.getElementById('parsel_no')?.value;
    const fiyat = document.getElementById('fiyat')?.value;
    const paraBirimi = document.getElementById('para_birimi')?.value;

    const kategoriSlugEarly = ctx.kategori_slug || '';
    const yayinTipiSlugEarly = ctx.yayin_tipi_slug || '';

    if (!yayinTipiSlugEarly) {
        if (window.toast) window.toast.error('Yayın tipi seçmeden devam edemezsiniz.');
        else alert('Yayın tipi seçmeden devam edemezsiniz.');
        return;
    }
    if (!kategoriSlugEarly) {
        if (window.toast) window.toast.error('Kategori seçmeden devam edemezsiniz.');
        else alert('Kategori seçmeden devam edemezsiniz.');
        return;
    }
    if (!baslik || !altKategoriId || !ilId || !ilceId) {
        if (window.toast) {
            window.toast.error('Lütfen önce temel bilgileri doldurun');
        } else {
            // eslint-disable-next-line no-alert
            alert('Lütfen önce temel bilgileri doldurun');
        }
        return;
    }

    const aciklamaTextarea = document.getElementById('aciklama');
    if (!aciklamaTextarea) return;

    // ✅ Phase 23: Budget Guard
    const budgetCheckUrl = window.APIConfig?.admin?.aiBudgetCheck;
    if (budgetCheckUrl) {
        try {
            const budgetResponse = await fetch(budgetCheckUrl);
            const budgetStatus = await budgetResponse.json();

            if (budgetStatus.success) {
                const data = budgetStatus.data;
                if (!data.allowed || data.level > 0.95) {
                    const message = !data.allowed
                        ? 'AI bütçe sınırı aşıldı: ' + data.reason
                        : 'AI bütçe sınırı aşıldı / Fallback Mode';
                    if (window.toast) window.toast.warning(message);
                    else alert(message);
                    return;
                }
            }
        } catch (e) {
            // Budget check endpoint not available — proceed without guard
        }
    }

    // Loading state
    const originalValue = aciklamaTextarea.value;
    aciklamaTextarea.disabled = true;
    aciklamaTextarea.classList.add('opacity-50');

    // Progress indicator
    let progressCounter = 0;
    const progressMessages = [
        'AI açıklama üretiliyor...',
        'TKGM verileri analiz ediliyor...',
        'Yakın lokasyonlar (POI) tespit ediliyor...',
        'AI modeli çalışıyor...',
        'Profesyonel açıklama hazırlanıyor...',
    ];

    const updateProgress = () => {
        const message = progressMessages[progressCounter % progressMessages.length];
        aciklamaTextarea.value = `${message} (${progressCounter + 1}/${progressMessages.length})`;
        progressCounter++;
    };

    updateProgress();
    const progressInterval = setInterval(updateProgress, 3000); // Her 3 saniyede bir güncelle

    // AbortController for timeout
    // eslint-disable-next-line no-undef
    const abortController = new AbortController();
    const timeoutId = setTimeout(() => {
        abortController.abort();
    }, 120000); // 120 saniye (2 dakika) timeout

    try {
        // ✅ Phase B/G: Use standard AI description endpoint
        const generateUrl = window.APIConfig?.admin?.aiDescription || '/admin/ai/description';

        // Get slug attributes for Phase B payload
        const kategoriSlug =
            document
                .getElementById('alt_kategori_id')
                ?.selectedOptions[0]?.getAttribute('data-slug') || '';
        const yayinTipiSlug =
            document.getElementById('junction_id')?.selectedOptions[0]?.getAttribute('data-slug') ||
            '';

        // ✅ Phase H: Collect draft features from Step 2
        const draftFeatures =
            typeof window.collectDraftFeatures === 'function' ? window.collectDraftFeatures() : {};

        const response = await fetch(generateUrl, {
            method: 'POST',
            signal: abortController.signal, // Timeout için
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                kategori_slug: kategoriSlug,
                yayin_tipi_slug: yayinTipiSlug,
                ilan: {
                    baslik: baslik,
                    aciklama: '',
                    fiyat: fiyat,
                    para_birimi: paraBirimi,
                    il_id: ilId,
                    ilce_id: ilceId,
                    ada_no: adaNo || '',
                    parsel_no: parselNo || '',
                },
                draft_features: draftFeatures, // ✅ Phase H
            }),
        });

        clearTimeout(timeoutId);
        clearInterval(progressInterval);

        const result = await response.json();

        // ✅ Phase B response: {success, data: {text}}
        const generatedDescription = result.data?.text || result.description || '';

        if (result.success && generatedDescription) {
            aciklamaTextarea.value = generatedDescription;
            aciklamaTextarea.classList.remove('opacity-50');
            aciklamaTextarea.classList.add('border-green-500');

            // Success animation
            setTimeout(() => {
                aciklamaTextarea.classList.remove('border-green-500');
            }, 2000);

            if (window.toast) {
                window.toast.success('AI açıklama başarıyla üretildi');
            }

            // 📊 Phase 7.1: Telemetry - Log suggested and applied description
            const altKategoriId_tele = document.getElementById('alt_kategori_id')?.value;
            const yayinTipiId_tele = document.getElementById('junction_id')?.value;
            const requestId_tele = 'ai_desc_' + Date.now();

            logTelemetry({
                kategori_id: altKategoriId_tele,
                junction_id: yayinTipiId_tele,
                feature_slug: 'ai_description',
                confidence: 0.9, // Default for generated descriptions
                source_tipi: 'mixed',
                aksiyon: 'suggested',
                neden: 'AI açıklama üretildi',
                neden_detay: { length: generatedDescription.length },
                istek_id: requestId_tele,
            });

            logTelemetry({
                kategori_id: altKategoriId_tele,
                junction_id: yayinTipiId_tele,
                feature_slug: 'ai_description',
                confidence: 1.0,
                source_tipi: 'mixed',
                aksiyon: 'user_applied',
                neden: 'AI açıklama otomatik olarak uygulandı',
                neden_detay: { length: generatedDescription.length },
                istek_id: requestId_tele,
            });

            logger.log('✅ AI açıklama üretildi');
        } else {
            aciklamaTextarea.value = originalValue;
            const errorMessage = result.message || 'Bilinmeyen hata';
            if (window.toast) {
                window.toast.error(`Açıklama üretilemedi: ${errorMessage}`);
            } else {
                // eslint-disable-next-line no-alert
                alert(`Açıklama üretilemedi: ${errorMessage}`);
            }
            logger.error('❌ AI açıklama üretimi başarısız:', errorMessage);
        }
    } catch (error) {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);

        logger.error('❌ AI açıklama üretimi hatası:', error);
        aciklamaTextarea.value = originalValue;

        let errorMessage = 'AI açıklama üretimi sırasında hata oluştu';
        if (error.name === 'AbortError') {
            errorMessage =
                'İstek zaman aşımına uğradı (2 dakika). Lütfen tekrar deneyin veya daha sonra tekrar deneyin.';
        } else if (error.message) {
            errorMessage = `Hata: ${error.message}`;
        }

        if (window.toast) {
            window.toast.error(errorMessage);
        } else {
            // eslint-disable-next-line no-alert
            alert(errorMessage);
        }
    } finally {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);
        aciklamaTextarea.disabled = false;
        aciklamaTextarea.classList.remove('opacity-50');
    }
}

/**
 * 📊 Phase 7.1: Telemetry Helper
 */
async function logTelemetry(payload) {
    try {
        await fetch('/api/v1/wizard/telemetry/feature-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
    } catch (e) {

    }
}

// Export to window for global access
if (typeof window !== 'undefined') {
    window.generateDescriptionWithAI = generateDescriptionWithAI;
}
