{{-- ========================================
     HERO SECTION - SADELEŞTİRİLMİŞ
     Temiz, modern ve responsive tasarım
     ======================================== --}}

<section
    class="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 via-blue-700 to-emerald-600 overflow-hidden">
    {{-- Subtle Background Elements --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-white rounded-full blur-3xl dark:bg-slate-900"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-emerald-400 rounded-full blur-3xl"></div>
    </div>

    {{-- Content Container --}}
    <div class="relative z-10 text-center px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
        {{-- Main Heading --}}
        <div class="mb-12">
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                Bodrum'un En Güzel
                <span class="block text-emerald-200">Emlak Seçenekleri</span>
            </h1>
            <p class="text-lg sm:text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto leading-relaxed">
                Hayalinizdeki evi bulmak için modern arama sistemimizi kullanın
            </p>
        </div>

        {{-- Simple Search Box --}}
        <div
            class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 sm:p-8 shadow-2xl border border-white/20 max-w-4xl mx-auto dark:bg-slate-900/10 dark:bg-slate-800/40">
            <div class="text-center mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">🏠 Emlak Arama</h2>
                <p class="text-blue-100 text-sm sm:text-base">Aradığınız özellikleri belirtin</p>
            </div>

            <form action="{{ route('ilanlar.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- İlan Türü --}}
                    <div>
                        <select name="ilan_turu"
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all text-sm dark:text-slate-100 dark:bg-slate-900/90 dark:text-white">
                            <option value="">İlan Türü</option>
                            <option value="Satılık">Satılık</option>
                            <option value="Kiralık">Kiralık</option>
                            <option value="Yazlık Kiralık">Yazlık Kiralık</option>
                        </select>
                    </div>

                    {{-- Emlak Türü --}}
                    <div>
                        <select name="emlak_turu"
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all text-sm dark:text-slate-100 dark:bg-slate-900/90 dark:text-white">
                            <option value="">Emlak Türü</option>
                            <option value="Konut">Konut</option>
                            <option value="Villa">Villa</option>
                            <option value="Arsa">Arsa</option>
                            <option value="İş Yeri">İş Yeri</option>
                        </select>
                    </div>

                    {{-- Fiyat Aralığı --}}
                    <div>
                        <select name="fiyat_araligi"
                            class="w-full px-4 py-3 bg-white/90 text-gray-900 rounded-xl border-0 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all text-sm dark:text-slate-100 dark:bg-slate-900/90 dark:text-white">
                            <option value="">Fiyat Aralığı</option>
                            <option value="0-500000">0 - 500.000 TL</option>
                            <option value="500000-1000000">500.000 - 1.000.000 TL</option>
                            <option value="1000000-2000000">1.000.000 - 2.000.000 TL</option>
                            <option value="2000000+">2.000.000+ TL</option>
                        </select>
                    </div>

                    {{-- Arama Butonu --}}
                    <div>
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                            🔍 Ara
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Company Info --}}
        <div class="mt-12 text-center">
            <div
                class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-lg rounded-full px-6 py-3 border border-white/20 dark:bg-slate-900/10 dark:bg-slate-800/40">
                <svg class="w-5 h-5 text-emerald-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-white text-sm">Yalıkavak, Bodrum</span>
            </div>
            <div class="mt-4 text-blue-100 text-sm">
                <p>📞 0533 209 03 02 | 📧 info@yalihanemlak.com</p>
            </div>
        </div>
    </div>
</section>
