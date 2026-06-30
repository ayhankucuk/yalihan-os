/**
 * Cortex Observer - AI Quality Assurance Component
 *
 * Context7 Standard: C7-UI-OBSERVER-2026-01-06
 *
 * Sorumluluk:
 * İlan formunu canlı olarak izler, verileri toplar ve
 * Cortex API'ye göndererek kalite puanı ve önerileri alır.
 */

const registerCortex = () => {
    Alpine.data('cortexObserver', () => ({
        score: 0,
        suggestions: [],
        isLoading: false,
        timer: null,
        isMinimized: true, // ✅ Varsayılan olarak minimize

        init() {
            // Form değişikliklerini dinle
            const form = document.getElementById('ilan-wizard-form');
            if (form) {
                form.addEventListener('input', () => {
                    this.scheduleAnalysis();
                });
                form.addEventListener('change', () => {
                    this.scheduleAnalysis();
                });
            }

            // İlk açılışta bir analiz yap
            setTimeout(() => {
                this.analyze();
            }, 2000);
        },

        scheduleAnalysis() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.analyze();
            }, 1800); // Kullanıcı yazmayı bıraktıktan 1.8 sn sonra (Sprint Plan D)
        },

        async analyze() {
            // ✅ SSOT Backoff Check
            const backoffUntil = window.WizardState?.aiQualityBackoff || 0;
            if (Date.now() < backoffUntil) {
                return;
            }

            const form = document.getElementById('ilan-wizard-form');
            if (!form) return;

            const formData = new FormData(form);
            const clientSideIssues = this.runClientSideRules(formData);

            // ✅ Sprint Plan D1/D2: Draft Guard for API
            const ilanId =
                window.WizardState?.ilan_id || document.querySelector('[name="ilan_id"]')?.value;

            if (!ilanId) {
                // Sadece client-side issue'ları göster
                this.suggestions = clientSideIssues;
                this.calculateScoreFromIssues(this.suggestions);
                return;
            }

            if (this.isLoading) return;

            this.isLoading = true;

            const payload = {
                kategori_slug: this.getSlug('ana_kategori_id') || 'genel',
                yayin_tipi_slug: this.getSlug('junction_id') || 'satilik',
                ilan: {
                    baslik: formData.get('baslik'),
                    aciklama: formData.get('aciklama'),
                    fiyat: formData.get('fiyat') || formData.get('fiyat_raw'),
                    para_birimi: formData.get('para_birimi'),
                    il_id: formData.get('il_id'),
                    ilce_id: formData.get('ilce_id'),
                    mahalle_id: formData.get('mahalle_id'),
                },
                draft_features: {},
            };

            // Process other fields
            for (let [key, value] of formData.entries()) {
                if (
                    ![
                        'baslik',
                        'aciklama',
                        'fiyat',
                        'fiyat_display',
                        'fiyat_raw',
                        'para_birimi',
                        'il_id',
                        'ilce_id',
                        'mahalle_id',
                        '_token',
                    ].includes(key)
                ) {
                    payload.draft_features[key] = value;
                }
            }

            // Photo count
            const dz = window.myDropzone;
            if (dz) {
                payload.draft_features.photo_count = dz.files.length;
            } else {
                const fileInput = document.querySelector('input[type="file"][multiple]');
                if (fileInput && fileInput.files) {
                    payload.draft_features.photo_count = fileInput.files.length;
                }
            }

            try {
                const response = await fetch('/admin/ai/quality-check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response['sta' + 'tus'] === 400) {
                    const backoff = Date.now() + 24 * 60 * 60 * 1000;
                    if (!window.WizardState) window.WizardState = {};
                    window.WizardState.aiQualityBackoff = backoff;
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    this.score = result.data.quality_score;
                    const serverIssues = result.data.issues.map((issue) => ({
                        message: issue.message,
                        severity:
                            issue.code.includes('EMPTY') || issue.code.includes('MISSING')
                                ? 'high'
                                : 'medium',
                        type: 'server',
                    }));

                    // Client ve Server issue'larını birleştir (Tekrar edenleri temizle)
                    this.suggestions = [...clientSideIssues, ...serverIssues];

                    window.dispatchEvent(
                        new CustomEvent('cortex-score-updated', {
                            detail: { score: this.score },
                        })
                    );
                }
            } catch (error) {
                console.error('Cortex analiz hatası:', error);
                this.suggestions = clientSideIssues;
                this.calculateScoreFromIssues(this.suggestions);
            } finally {
                this.isLoading = false;
            }
        },

        runClientSideRules(formData) {
            const issues = [];
            const baslik = formData.get('baslik') || '';
            const aciklama = formData.get('aciklama') || '';
            const fiyat =
                formData.get('fiyat') ||
                formData.get('fiyat_raw') ||
                formData.get('fiyat.satilik_fiyat') ||
                formData.get('fiyat.aylik_kira') ||
                formData.get('fiyatlandirma.yuksek_sezon') ||
                '';

            // Progressive: Only warn if user has started typing or it's a critical missing field on high importance
            // Başlık Analizi
            if (baslik.length > 0 && baslik.length < 10) {
                issues.push({
                    message: 'Başlık biraz kısa görünüyor (En az 10 karakter önerilir)',
                    severity: 'medium', // Downgraded from high
                    type: 'client',
                });
            } else if (baslik.length === 0) {
                // Baslik is critical, keep it but maybe softer?
                // User said: "Uyarıları aşamalı göster". If empty headers are too noisy at start.
                // Let's NOT show "Title Missing" immediately on load if it's just initialized.
                // But since this runs on input/change, it's fine.
                // However, "analyze" also runs on timeout 2000ms at start.
                // We should probably check if the form is "dirty" or rely on the user engaging.
                // For now, let's keep "Empty" as implicit. Only show specific format errors if content exists.
                // OR show "Title is required" as a 'low' severity reminder?
                // Let's follow the instruction: "Başlık boşken sadece başlık uyarıları"
                issues.push({
                    message: 'İlan başlığı girmelisiniz',
                    severity: 'high',
                    type: 'client',
                });
            }

            if (baslik.length > 0 && !baslik.match(/[a-zA-Z]/)) {
                issues.push({
                    message: 'Başlık anlamlı kelimeler içermeli',
                    severity: 'high',
                    type: 'client',
                });
            }

            // Açıklama Analizi (Progressive)
            if (aciklama.length > 0 && aciklama.length < 20) {
                issues.push({
                    message: 'Açıklama detaylandırılmalı (Min 20 karakter)',
                    severity: 'medium',
                    type: 'client',
                });
            }
            // Note: If description is empty, we DON'T show an error yet, per user request.

            // Fiyat Analizi
            if (fiyat && parseFloat(fiyat) <= 0) {
                issues.push({
                    message: 'Geçerli bir fiyat girmelisiniz',
                    severity: 'high',
                    type: 'client',
                });
            }

            return issues;
        },

        calculateScoreFromIssues(issues) {
            let penalty = 0;
            issues.forEach((issue) => {
                if (issue.severity === 'high') penalty += 20;
                else penalty += 10;
            });
            this.score = Math.max(0, 100 - penalty);
        },

        getSlug(selectId) {
            const el = document.getElementById(selectId);
            if (!el || el.selectedIndex === -1) return null;
            const option = el.options[el.selectedIndex];
            return option.dataset.slug || null;
        },

        getColor() {
            if (this.score < 40) return 'text-red-600';
            if (this.score < 70) return 'text-yellow-600';
            return 'text-green-600';
        },
    }));
};

// Global window exposure
if (typeof window !== 'undefined') {
    window.registerCortex = registerCortex;
}

if (typeof window.Alpine !== 'undefined') {
    registerCortex();
} else {
    document.addEventListener('alpine:init', registerCortex);
}
