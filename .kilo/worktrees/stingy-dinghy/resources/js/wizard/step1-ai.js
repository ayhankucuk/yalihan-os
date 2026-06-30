/**
 * Step 1 AI Title Generation
 * Context7: AI-powered title generation
 */

import { logger } from './step1-core.js';
import { getWizardContext } from './wizard-context.js';

/**
 * Generate title with AI
 */
export async function generateTitleWithAI() {
    const anaKategoriId = document.getElementById('ana_kategori_id')?.value;
    const altKategoriId = document.getElementById('alt_kategori_id')?.value;
    const ilId = document.getElementById('il_id')?.value;
    const ilceId = document.getElementById('ilce_id')?.value;
    const mahalleId = document.getElementById('mahalle_id')?.value;
    const _yayinTipiId = document.getElementById('junction_id')?.value;
    const ctx = getWizardContext();

    const kategoriSlug = ctx.kategori_slug || '';
    const yayinTipiSlug = ctx.yayin_tipi_slug || '';

    if (!yayinTipiSlug) {
        if (window.toast) window.toast.error('Yayın tipi seçmeden devam edemezsiniz.');
        else alert('Yayın tipi seçmeden devam edemezsiniz.');
        return;
    }
    if (!kategoriSlug) {
        if (window.toast) window.toast.error('Kategori seçmeden devam edemezsiniz.');
        else alert('Kategori seçmeden devam edemezsiniz.');
        return;
    }
    if (!anaKategoriId || !altKategoriId || !ilId || !ilceId) {
        alert('Lütfen önce kategori ve lokasyon bilgilerini seçin');
        return;
    }

    const baslikInput = document.getElementById('baslik');
    if (!baslikInput) return;

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
    const originalValue = baslikInput.value;
    baslikInput.disabled = true;
    baslikInput.value = 'AI başlık üretiliyor...';
    baslikInput.classList.add('opacity-50');

    try {
        // ✅ Phase B/G: Use standard AI title endpoint
        const generateTitleUrl = window.APIConfig?.admin?.aiTitle || '/admin/ilanlar/ai/title';

        // ✅ Phase H: Collect draft features from Step 2
        const draftFeatures =
            typeof window.collectDraftFeatures === 'function' ? window.collectDraftFeatures() : {};

        const response = await fetch(generateTitleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                kategori_slug: kategoriSlug,
                yayin_tipi_slug: yayinTipiSlug,
                ilan: {
                    baslik: '',
                    fiyat: document.getElementById('fiyat')?.value || '',
                    para_birimi: document.getElementById('para_birimi')?.value || 'TRY',
                    il_id: ilId,
                    ilce_id: ilceId,
                    mahalle_id: mahalleId || '',
                },
                draft_features: draftFeatures, // ✅ Phase H
            }),
        });

        const result = await response.json();

        // ✅ Phase B response: {success, data: {text}}
        const generatedTitle = result.data?.text || result.title || '';

        if (result.success && generatedTitle) {
            baslikInput.value = generatedTitle;
            baslikInput.classList.remove('opacity-50');
            baslikInput.classList.add('border-green-500');

            setTimeout(() => {
                baslikInput.classList.remove('border-green-500');
            }, 2000);

            if (result.alternatives && result.alternatives.length > 0) {
                logger.debug('Alternatif başlıklar:', result.alternatives);
            }
        } else if (generatedTitle && generatedTitle !== 'Başlık üretilemedi') {
            baslikInput.value = generatedTitle;
            baslikInput.classList.remove('opacity-50');
            baslikInput.classList.add('border-yellow-500');

            setTimeout(() => {
                baslikInput.classList.remove('border-yellow-500');
            }, 2000);

            if (result.alternatives && result.alternatives.length > 0) {
                logger.debug('Alternatif başlıklar (fallback):', result.alternatives);
            }
        } else {
            baslikInput.value = originalValue;
            alert('Başlık üretilemedi: ' + (result.message || 'Bilinmeyen hata'));
        }
    } catch (error) {
        logger.error('AI başlık üretimi hatası:', error);
        baslikInput.value = originalValue;
        alert('AI başlık üretimi sırasında hata oluştu');
    } finally {
        baslikInput.disabled = false;
        baslikInput.classList.remove('opacity-50');
    }
}

// Export to window for global access
if (typeof window !== 'undefined') {
    window.generateTitleWithAI = generateTitleWithAI;
}
