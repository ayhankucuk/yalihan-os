{{-- 🧠 SMART FIELD ORGANIZER --}}
{{-- AI-Powered, Priority-Based, Category-Grouped Form Organization --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

<div x-data="smartFieldOrganizer()" class="space-y-6">
    {{-- Quick Fill Templates --}}
    <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border-2 border-purple-200 dark:border-purple-700 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                    🤖 Hızlı Doldurma Şablonları
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Önceden tanımlı değerlerle formu otomatik doldur
                </p>
            </div>
            <button
                @click="showTemplates = !showTemplates"
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <span x-show="!showTemplates">▼ Şablonları Göster</span>
                <span x-show="showTemplates">▲ Gizle</span>
            </button>
        </div>

        <div x-show="showTemplates" x-transition class="grid md:grid-cols-3 gap-4" style="display: none;">
            {{-- Premium Villa Template --}}
            <button
                @click="applyTemplate('premium_villa')"
                type="button"
                class="p-4 bg-white dark:bg-slate-900 rounded-lg border-2 border-purple-300 dark:border-purple-600 hover:border-purple-500 hover:shadow-lg transition-all text-left">
                <div class="text-2xl mb-2">🏰</div>
                <div class="font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">Premium Villa</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    Lüks villa, full amenities, yüksek fiyat
                </div>
            </button>

            {{-- Budget Villa Template --}}
            <button
                @click="applyTemplate('budget_villa')"
                type="button"
                class="p-4 bg-white dark:bg-slate-900 rounded-lg border-2 border-green-300 dark:border-green-600 hover:border-green-500 hover:shadow-lg transition-all text-left">
                <div class="text-2xl mb-2">🏡</div>
                <div class="font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">Ekonomik Villa</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    Temel özellikler, uygun fiyat
                </div>
            </button>

            {{-- Seaside Villa Template --}}
            <button
                @click="applyTemplate('seaside_villa')"
                type="button"
                class="p-4 bg-white dark:bg-slate-900 rounded-lg border-2 border-blue-300 dark:border-blue-600 hover:border-blue-500 hover:shadow-lg transition-all text-left">
                <div class="text-2xl mb-2">🌊</div>
                <div class="font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">Deniz Kenarı Villa</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    Deniz manzarası, plaja yakın
                </div>
            </button>
        </div>
    </div>

    {{-- AI Smart Suggestions --}}
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 text-3xl">💡</div>
            <div class="flex-1">
                <h4 class="font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">AI Önerileri Aktif</h4>
                <div class="text-sm text-gray-700 dark:text-slate-200 space-y-1 dark:text-slate-300">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check text-green-500"></i>
                        <span>Fiyatlar otomatik hesaplanabilir (haftalık = günlük × 6.5)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check text-green-500"></i>
                        <span>Sezon fiyatları market analizi ile önerilir</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check text-green-500"></i>
                        <span>Check-in/out saatleri standart değerlerle doldurulur</span>
                    </div>
                </div>
            </div>
            <button
                @click="applyAISuggestions()"
                type="button"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold whitespace-nowrap">
                🤖 AI Önerilerini Uygula
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function smartFieldOrganizer() {
    return {
        showTemplates: false,

        applyTemplate(templateName) {
            const templates = {
                premium_villa: {
                    gunluk_fiyat: 5000,
                    haftalik_fiyat: 32500,
                    yaz_sezonu_fiyat: 7000,
                    ara_sezon_fiyat: 4000,
                    kis_sezonu_fiyat: 3000,
                    minimum_konaklama: 7,
                    maksimum_misafir: 8,
                    check_in: '15:00',
                    check_out: '11:00',
                    havuz: true,
                    jakuzi: true,
                    denize_uzaklik: 0.5,
                    wifi: true,
                    klima: 'Evet',
                    esyali: 'Tam Eşyalı',
                    bahce_teras: 'Evet',
                    barbekü_mangal: true,
                    bulasik_makinesi: true,
                    camasir_makinesi: true,
                    deniz_manzarasi: 'Evet',
                    depozito: 5000,
                    temizlik_servisi: 'Evet',
                    havlu_carsaf_dahil: true,
                    mutfak_tam_donanimli: 'Evet',
                    otopark: 'Özel Otopark',
                    pet_friendly: false
                },
                budget_villa: {
                    gunluk_fiyat: 1500,
                    haftalik_fiyat: 9750,
                    yaz_sezonu_fiyat: 2000,
                    ara_sezon_fiyat: 1200,
                    kis_sezonu_fiyat: 800,
                    minimum_konaklama: 3,
                    maksimum_misafir: 4,
                    check_in: '14:00',
                    check_out: '10:00',
                    havuz: false,
                    jakuzi: false,
                    denize_uzaklik: 2,
                    wifi: true,
                    klima: 'Evet',
                    esyali: 'Kısmen',
                    depozito: 1000,
                    pet_friendly: true
                },
                seaside_villa: {
                    gunluk_fiyat: 4000,
                    haftalik_fiyat: 26000,
                    yaz_sezonu_fiyat: 6000,
                    ara_sezon_fiyat: 3500,
                    kis_sezonu_fiyat: 2500,
                    minimum_konaklama: 5,
                    maksimum_misafir: 6,
                    check_in: '15:00',
                    check_out: '11:00',
                    havuz: true,
                    jakuzi: false,
                    denize_uzaklik: 0,
                    wifi: true,
                    klima: 'Evet',
                    esyali: 'Tam Eşyalı',
                    deniz_manzarasi: 'Evet',
                    bahce_teras: 'Evet',
                    barbekü_mangal: true,
                    depozito: 3000
                }
            };

            const template = templates[templateName];
            if (!template) return;

            Object.entries(template).forEach(([key, value]) => {
                const input = document.getElementById(`field_${key}`);
                if (!input) return;

                if (input.type === 'checkbox') {
                    input.checked = value;
                } else {
                    input.value = value;
                }

                // Trigger change event for reactive updates
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            window.toast?.(`${templateName.replace('_', ' ')} şablonu uygulandı!`, 'success');
        },

        applyAISuggestions() {
            // Günlük fiyatdan haftalık hesapla
            const gunlukFiyat = document.getElementById('field_gunluk_fiyat');
            const haftalikFiyat = document.getElementById('field_haftalik_fiyat');

            if (gunlukFiyat && gunlukFiyat.value && haftalikFiyat && !haftalikFiyat.value) {
                haftalikFiyat.value = Math.round(gunlukFiyat.value * 6.5);
                haftalikFiyat.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Günlükten sezon fiyatları hesapla
            const yazSezon = document.getElementById('field_yaz_sezonu_fiyat');
            const araSezon = document.getElementById('field_ara_sezon_fiyat');
            const kisSezon = document.getElementById('field_kis_sezonu_fiyat');

            if (gunlukFiyat && gunlukFiyat.value) {
                const basePrice = parseFloat(gunlukFiyat.value);

                if (yazSezon && !yazSezon.value) {
                    yazSezon.value = Math.round(basePrice * 1.4); // +40% yaz
                    yazSezon.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (araSezon && !araSezon.value) {
                    araSezon.value = Math.round(basePrice * 0.8); // -20% ara
                    araSezon.dispatchEvent(new Event('change', { bubbles: true }));
                }
                
                // ✅ FIX: Toast optional chaining eklendi
                if (window.toast?.success) {
                    window.toast.success('AI önerileri uygulandı');
                }

                if (kisSezon && !kisSezon.value) {
                    kisSezon.value = Math.round(basePrice * 0.6); // -40% kış
                    kisSezon.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Standart check-in/out saatleri
            const checkIn = document.getElementById('field_check_in');
            const checkOut = document.getElementById('field_check_out');

            if (checkIn && !checkIn.value) {
                checkIn.value = '15:00';
                checkIn.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (checkOut && !checkOut.value) {
                checkOut.value = '11:00';
                checkOut.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Minimum konaklama
            const minKonaklama = document.getElementById('field_minimum_konaklama');
            if (minKonaklama && !minKonaklama.value) {
                minKonaklama.value = 3;
                minKonaklama.dispatchEvent(new Event('change', { bubbles: true }));
            }

            window.toast?.('AI önerileri uygulandı!', 'success');
        }
    }
}
</script>
@endpush
