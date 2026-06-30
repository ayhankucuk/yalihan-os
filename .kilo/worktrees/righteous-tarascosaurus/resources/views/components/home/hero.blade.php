{{-- Hero Section - Premium Real Estate Design --}}
<section class="relative min-h-[85vh] flex items-center overflow-hidden bg-gradient-to-r from-gray-900 to-gray-800">
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0"
            style="background-image: url('data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
        </div>
    </div>

    {{-- Content Container --}}
    <div class="ds-container relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            {{-- Left Column: Text Content --}}
            <div class="text-white space-y-8">
                <h1 class="text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight">
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">
                        İstanbul'da Güvenilir
                    </span>
                    <span class="block mt-2">Emlak Partneri</span>
                </h1>

                <p class="text-xl text-gray-300 leading-relaxed">
                    25 yıllık deneyimimizle hayalinizdeki evi bulmanıza yardımcı oluyoruz.
                    Yalıhan Emlak ailesi olarak, en iyi hizmeti sunmak için buradayız.
                </p>

                {{-- Search Box --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 space-y-4 dark:bg-slate-900/10 dark:bg-slate-800/40">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="relative">
                            <input type="text" placeholder="Şehir/İlçe"
                                class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <i
                                class="fas fa-map-marker-alt absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <div class="relative">
                            <input type="date"
                                class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <i class="fas fa-calendar absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button
                            class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-emerald-500 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-emerald-600 transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                            <i class="fas fa-search mr-2"></i>
                            Ara
                        </button>
                    </div>
                </div>

                {{-- Statistics --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-8">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-400">500+</div>
                        <div class="text-gray-400 mt-1">Mutlu Müşteri</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-emerald-400">1000+</div>
                        <div class="text-gray-400 mt-1">Satılan Ev</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-400">25</div>
                        <div class="text-gray-400 mt-1">Yıl Deneyim</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-emerald-400">15</div>
                        <div class="text-gray-400 mt-1">Uzman Ekip</div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Image --}}
            <div class="relative hidden lg:block">
                <div class="absolute -right-20 top-1/2 -translate-y-1/2 w-[140%]">
                    <img src="{{ asset('images/hero-house.png') }}" alt="Luxury Home"
                        class="w-full h-auto rounded-3xl shadow-2xl transform hover:scale-105 transition-transform duration-500">

                    {{-- Floating Elements --}}
                    <div
                        class="absolute -left-10 top-20 bg-white/10 backdrop-blur-lg rounded-2xl p-4 shadow-xl animate-float dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-emerald-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-home text-2xl text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-300">Premium Konutlar</div>
                                <div class="text-lg font-semibold text-white">250+ Liste</div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="absolute -right-10 bottom-20 bg-white/10 backdrop-blur-lg rounded-2xl p-4 shadow-xl animate-float-delayed dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-blue-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line text-2xl text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-300">Piyasa Değeri</div>
                                <div class="text-lg font-semibold text-white">AI Destekli</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Custom Styles --}}
<style>
    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: float 6s ease-in-out infinite;
        animation-delay: 2s;
    }
</style>
