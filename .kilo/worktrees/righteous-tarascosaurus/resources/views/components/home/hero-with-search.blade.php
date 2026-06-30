{{-- ========================================
     HERO SECTION WITH LIVE SEARCH
     Veritabanı uyumlu filtreler ve live search
     ======================================== --}}

<section class="ds-hero overflow-hidden">
    {{-- Animated Background Elements --}}
    <div class="absolute inset-0 overflow-hidden opacity-30">
        <div class="ds-float absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl dark:bg-slate-900/10 dark:bg-slate-800/40"></div>
        <div class="ds-float absolute bottom-20 right-10 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl"
            style="animation-delay: -3s;"></div>
        <div class="ds-float absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl"
            style="animation-delay: -1.5s;"></div>
    </div>

    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg width="60" height="60"
            viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" fill-rule="evenodd">
                <g fill="%23ffffff" fill-opacity="0.2">
                    <circle cx="7" cy="7" r="1" />
                    <circle cx="27" cy="7" r="1" />
                    <circle cx="47" cy="7" r="1" />
                    <circle cx="7" cy="27" r="1" />
                    <circle cx="27" cy="27" r="1" />
                    <circle cx="47" cy="27" r="1" />
                    <circle cx="7" cy="47" r="1" />
                    <circle cx="27" cy="47" r="1" />
                    <circle cx="47" cy="47" r="1" />
                </g>
            </g></svg>');">
        </div>
    </div>

    {{-- Content Container --}}
    <div class="ds-hero-content ds-container">
        {{-- Main Heading --}}
        <div class="mb-8">
            <h1
                class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-purple-100 bg-clip-text text-transparent">
                Bodrum'un En Güzel
                <span class="block">Emlak Seçenekleri</span>
            </h1>
            <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto leading-relaxed">
                Hayalinizdeki evi bulmak için AI destekli arama sistemimizi kullanın
            </p>
        </div>

        {{-- AI Destekli Talep Formu --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/20 max-w-6xl mx-auto dark:bg-slate-900/10 dark:bg-slate-800/40">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-white mb-2">🤖 AI Destekli Emlak Talebi</h2>
                <p class="text-blue-100">Talebinizi belirtin, AI sistemimiz size en uygun gayrimenkulleri bulsun!</p>
            </div>

            <form id="ai-demand-form" class="space-y-6" x-data="aiDemandForm()">
                {{-- Temel Bilgiler --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">👤 Ad</label>
                        <input type="text" name="ad" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="Adınız">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">👤 Soyad</label>
                        <input type="text" name="soyad" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="Soyadınız">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">📱 Telefon</label>
                        <input type="tel" name="telefon" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="0533 209 03 02">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">📧 E-posta</label>
                        <input type="email" name="email"
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="ornek@email.com">
                    </div>
                </div>

                {{-- Talep Detayları --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">🏠 İlan Türü</label>
                        <select name="ilan_turu" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white">
                            <option value="">Seçiniz...</option>
                            <option value="Satılık">Satılık</option>
                            <option value="Kiralık">Kiralık</option>
                            <option value="Yazlık Kiralık">Yazlık Kiralık</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">🏘️ Emlak Türü</label>
                        <select name="emlak_turu" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white">
                            <option value="">Seçiniz...</option>
                            <option value="Konut">Konut</option>
                            <option value="Villa">Villa</option>
                            <option value="Arsa">Arsa</option>
                            <option value="İş Yeri">İş Yeri</option>
                            <option value="Yazlık">Yazlık</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">📍 Konum</label>
                        <input type="text" name="konum" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="Bodrum, Muğla">
                    </div>
                </div>

                {{-- Fiyat Aralığı --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">💰 Min Fiyat (TL)</label>
                        <input type="number" name="min_fiyat" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="500.000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-100 mb-2 text-left">💰 Max Fiyat (TL)</label>
                        <input type="number" name="max_fiyat" required
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                            placeholder="2.000.000">
                    </div>
                </div>

                {{-- Detay Açıklama --}}
                <div>
                    <label class="block text-sm font-medium text-blue-100 mb-2 text-left">📝 Detay Açıklama</label>
                    <textarea name="aciklama" rows="3"
                        class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                        placeholder="İstediğiniz özellikler, özel istekler, zaman kısıtları..."></textarea>
                </div>

                {{-- Submit Button --}}
                <div class="text-center">
                    <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-robot mr-2"></i>
                        <span x-text="isSubmitting ? 'AI Analiz Ediyor...' : 'AI Destekli Talep Gönder'"></span>
                    </button>
                </div>

                {{-- AI Analysis Results --}}
                <div x-show="showResults" x-transition
                    class="mt-6 p-6 bg-white/20 backdrop-blur-lg rounded-2xl border border-white/30 dark:bg-slate-900/20">
                    <div class="text-center mb-4">
                        <h3 class="text-xl font-bold text-white">🤖 AI Analiz Sonuçları</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-white/10 rounded-xl dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <div class="text-2xl font-bold text-blue-200" x-text="analysis.oncelik || 'Orta'"></div>
                            <div class="text-sm text-blue-100">Öncelik</div>
                        </div>
                        <div class="text-center p-4 bg-white/10 rounded-xl dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <div class="text-2xl font-bold text-green-200" x-text="analysis.segment || 'Standart'"></div>
                            <div class="text-sm text-blue-100">Segment</div>
                        </div>
                        <div class="text-center p-4 bg-white/10 rounded-xl dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <div class="text-2xl font-bold text-yellow-200" x-text="analysis.aciliyet || 'Normal'"></div>
                            <div class="div class="text-sm text-blue-100">Aciliyet</div>
                        </div>
                        <div class="text-center p-4 bg-white/10 rounded-xl dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <div class="text-2xl font-bold text-purple-200" x-text="(analysis.eslesme_potansiyeli || 70) + '%'"></div>
                            <div class="text-sm text-blue-100">Eşleşme</div>
                        </div>
                    </div>

                    <div x-show="matchingProperties.length > 0" class="mb-6">
                        <h4 class="text-lg font-semibold text-white mb-3">🎯 Eşleşen Gayrimenkuller</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="property in matchingProperties" :key="property.id">
                                <div class="bg-white/10 rounded-xl p-4 dark:bg-slate-900/10 dark:bg-slate-800/40">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-white" x-text="property.ilan_basligi"></span>
                                        <span class="text-xs px-2 py-1 rounded-full"
                                            :class="property.eslesme_orani >= 80 ? 'bg-green-500' : 60 'bg-yellow-500' 'bg-red-500'"
                                            x-text="property.eslesme_orani + '%'"></span>
                                    </div>
                                    <div class="text-xs text-blue-100" x-text="property.fiyat + ' TL'"></div>
                                    <div class="text-xs text-blue-100" x-text="property.konum"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="text-green-200 font-medium mb-2" x-text="successMessage"></div>
                        <div class="text-blue-100 text-sm">Talebiniz CRM sistemine kaydedildi. Danışmanlarımız en kısa sürede sizinle iletişime geçecek.</div>
                    </div>
                </div>
            </form>
        </div>
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            class="absolute z-50 w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 max-h-60 overflow-y-auto dark:bg-slate-900 dark:border-slate-700">
                            <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
                                <div @click="
                                    searchTerm = suggestion.name;
                                    showSuggestions = false;
                                    selectedIndex = -1;
                                "
                                    :class="{
                                        'bg-blue-50 text-blue-900': index === selectedIndex,
                                        'hover:bg-gray-50': index !== selectedIndex
                                    }"
                                    class="px-4 py-3 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0 dark:border-slate-800">
                                    <div class="flex items-center">
                                        <i class="fas fa-map-marker-alt text-blue-500 mr-3"></i>
                                        <div>
                                            <div class="font-medium" x-text="suggestion.name"></div>
                                            <div class="text-sm text-gray-500" x-text="suggestion.full_path"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Property Type --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-blue-100 mb-2 text-left">İlan Tipi</label>
                    <select id="property-type"
                        class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all appearance-none cursor-pointer dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                        x-data="{
                            categories: [],
                            selectedCategory: '',
                            subCategories: []
                        }" x-init="fetch('/api/categories/main')
                            .then(response => response.json())
                            .then(data => categories = data);"
                        @change="
                                selectedCategory = $event.target.value;
                                if (selectedCategory) {
                                    fetch(`/api/categories/sub/${selectedCategory}`)
                                        .then(response => response.json())
                                        .then(data => subCategories = data);
                                } else {
                                    subCategories = [];
                                }
                            ">
                        <option value="">Tümü</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                    <i
                        class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>

                {{-- Sub Category --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-blue-100 mb-2 text-left">Alt Kategori</label>
                    <select id="sub-category"
                        class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all appearance-none cursor-pointer dark:text-slate-100 dark:bg-slate-900/90 dark:text-white"
                        :disabled="!subCategories.length">
                        <option value="">Seçiniz</option>
                        <template x-for="subCategory in subCategories" :key="subCategory.id">
                            <option :value="subCategory.id" x-text="subCategory.name"></option>
                        </template>
                    </select>
                    <i
                        class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>

                {{-- Search Button --}}
                <div class="flex items-end">
                    <button type="button" id="search-button"
                        class="w-full px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center justify-center"
                        @click="
                                const location = document.getElementById('location-search').value;
                                const propertyType = document.getElementById('property-type').value;
                                const subCategory = document.getElementById('sub-category').value;

                                let searchUrl = '/ilanlar?';
                                if (location) searchUrl += `location=${encodeURIComponent(location)}&`;
                                if (propertyType) searchUrl += `category=${propertyType}&`;
                                if (subCategory) searchUrl += `sub_category=${subCategory}&`;

                                window.location.href = searchUrl;
                            ">
                        <i class="fas fa-search mr-2"></i>
                        <span class="hidden sm:inline">Ara</span>
                    </button>
                </div>
            </div>

            {{-- Quick Filters --}}
            <div class="mt-6 pt-6 border-t border-white/20">
                <div class="flex flex-wrap gap-3 justify-center">
                    <span class="text-sm text-blue-100 font-medium">Hızlı Filtreler:</span>
                    <button
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-full text-sm transition-all dark:bg-slate-900/20"
                        @click="window.location.href='/ilanlar?category=1&sub_category=2'">
                        <i class="fas fa-home mr-2"></i>Satılık Daire
                    </button>
                    <button
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-full text-sm transition-all dark:bg-slate-900/20"
                        @click="window.location.href='/ilanlar?category=1&sub_category=6'">
                        <i class="fas fa-crown mr-2"></i>Satılık Villa
                    </button>
                    <button
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-full text-sm transition-all dark:bg-slate-900/20"
                        @click="window.location.href='/ilanlar?category=3&sub_category=44'">
                        <i class="fas fa-tree mr-2"></i>Satılık Arsa
                    </button>
                    <button
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-full text-sm transition-all dark:bg-slate-900/20"
                        @click="window.location.href='/ilanlar?category=4&sub_category=57'">
                        <i class="fas fa-hotel mr-2"></i>Turistik İşletme
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-yellow-400 mb-2" x-data="{ count: 0 }"
                    x-init="$nextTick(() => {
                        const target = 500;
                        const increment = target / 50;
                        const timer = setInterval(() => {
                            count += increment;
                            if (count >= target) {
                                count = target;
                                clearInterval(timer);
                            }
                        }, 50);
                    })" x-text="Math.round(count)">0</div>
                <div class="text-blue-100">Aktif İlan</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-green-400 mb-2" x-data="{ count: 0 }"
                    x-init="$nextTick(() => {
                        const target = 1200;
                        const increment = target / 50;
                        const timer = setInterval(() => {
                            count += increment;
                            if (count >= target) {
                                count = target;
                                clearInterval(timer);
                            }
                        }, 50);
                    })" x-text="Math.round(count)">0</div>
                <div class="text-blue-100">Mutlu Müşteri</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-purple-400 mb-2" x-data="{ count: 0 }"
                    x-init="$nextTick(() => {
                        const target = 15;
                        const increment = target / 50;
                        const timer = setInterval(() => {
                            count += increment;
                            if (count >= target) {
                                count = target;
                                clearInterval(timer);
                            }
                        }, 50);
                    })" x-text="Math.round(count)">0</div>
                <div class="text-blue-100">Yıllık Deneyim</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-pink-400 mb-2" x-data="{ count: 0 }"
                    x-init="$nextTick(() => {
                        const target = 50;
                        const increment = target / 50;
                        const timer = setInterval(() => {
                            count += increment;
                            if (count >= target) {
                                count = target;
                                clearInterval(timer);
                            }
                        }, 50);
                    })" x-text="Math.round(count)">0</div>
                <div class="text-blue-100">Uzman Danışman</div>
            </div>
        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div class="w-6 h-10 border-2 border-white/50 rounded-full flex justify-center">
            <div class="w-1 h-3 bg-white/70 rounded-full mt-2 animate-pulse dark:bg-slate-900/70"></div>
        </div>
    </div>
</section>

<script>
function aiDemandForm() {
    return {
        isSubmitting: false,
        showResults: false,
        analysis: {},
        matchingProperties: [],
        successMessage: '',

        async submitForm(event) {
            event.preventDefault();
            this.isSubmitting = true;

            try {
                const formData = new FormData(event.target);
                const demandData = Object.fromEntries(formData.entries());

                // API'ye gönder
                const response = await fetch('/api/crm/homepage-demand', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(demandData)
                });

                const result = await response.json();

                if (result.success) {
                    this.analysis = result.analysis;
                    this.matchingProperties = result.matching_properties || [];
                    this.successMessage = result.message;
                    this.showResults = true;

                    // Formu temizle
                    event.target.reset();

                    // Başarı mesajını göster
                    this.showToast('Talebiniz başarıyla alındı!', 'success');

                    // Scroll to results
                    this.$nextTick(() => {
                        document.querySelector('#ai-demand-form').scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    });
                } else {
                    this.showToast('Hata: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                this.showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        showToast(message, type = 'info') {
            // Toast notification göster
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' : 'bg-blue-600'
            }`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    }
}

// Form submit event listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ai-demand-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const formInstance = Alpine.$data(form);
            if (formInstance && formInstance.submitForm) {
                formInstance.submitForm(event);
            }
        });
    }
});
</script>

{{-- Custom CSS for animations --}}
<style>
    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }

    @keyframes float-delayed {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-15px) rotate(-180deg);
        }
    }

    @keyframes float-slow {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-25px) rotate(90deg);
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: float-delayed 8s ease-in-out infinite;
        animation-delay: 2s;
    }

    .animate-float-slow {
        animation: float-slow 10s ease-in-out infinite;
        animation-delay: 4s;
    }
</style>
