{{-- Hero Section — Yalıhan Emlak Premium
     FA=0 | material-symbols=0 | ds-*=0
--}}
<section class="relative min-h-[85vh] flex items-center overflow-hidden"
         style="background: linear-gradient(135deg, #0a1f45 0%, #0F2A5C 55%, #1a3d7a 100%);">

    {{-- Background pattern --}}
    <div class="absolute inset-0 opacity-[0.06] pointer-events-none" aria-hidden="true"
         style="background-image: radial-gradient(circle, rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 28px 28px;"></div>

    {{-- Blur orbs --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-20 -left-20 w-96 h-96 rounded-full opacity-20 blur-3xl"
             style="background: radial-gradient(circle, #3b82f6, transparent);"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full opacity-15 blur-3xl"
             style="background: radial-gradient(circle, #C9A84C, transparent);"></div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-6 md:px-12">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            {{-- Sol kolon: metin --}}
            <div class="text-white space-y-8">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-1.5">
                    <x-icon name="konum" class="w-4 h-4 text-yellow-300" />
                    <span class="text-xs font-semibold text-yellow-200 tracking-wider uppercase">Bodrum, Muğla</span>
                </div>

                <h1 class="text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-tight tracking-tight">
                    <span class="block"
                          style="background: linear-gradient(90deg, #C9A84C, #f0d080); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        Bodrum'da Güvenilir
                    </span>
                    <span class="block mt-2 text-white">Emlak Partneri</span>
                </h1>

                <p class="text-xl text-blue-100 leading-relaxed max-w-lg">
                    20+ yıllık deneyimimizle hayalinizdeki evi bulmanıza yardımcı oluyoruz.
                    Yalıhan Emlak ailesi olarak, en iyi hizmeti sunmak için buradayız.
                </p>

                {{-- Arama kutusu --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-5 border border-white/20"
                     x-data="{ q: '' }">
                    <div class="grid md:grid-cols-3 gap-3">
                        <div class="relative md:col-span-2">
                            <x-icon name="arama" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                            <input type="text"
                                   x-model="q"
                                   @keydown.enter="window.location.href='{{ route('ilanlar.index') }}?search='+encodeURIComponent(q)"
                                   placeholder="Şehir, İlçe veya Mahalle..."
                                   class="w-full bg-white text-slate-800 pl-10 pr-4 py-3.5 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-blue-400 placeholder-slate-400">
                        </div>
                        <a :href="'{{ route('ilanlar.index') }}?search='+encodeURIComponent(q)"
                           class="flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-500 to-yellow-400 hover:from-yellow-400 hover:to-yellow-300 text-slate-900 font-bold py-3.5 px-4 rounded-xl transition-all duration-200 shadow-lg active:scale-[0.98]">
                            <x-icon name="arama" class="w-5 h-5" />
                            <span>Ara</span>
                        </a>
                    </div>
                </div>

                {{-- İstatistikler --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4">
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-yellow-300">500+</div>
                        <div class="text-sm text-blue-200 mt-0.5">Mutlu Müşteri</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-emerald-300">1000+</div>
                        <div class="text-sm text-blue-200 mt-0.5">Satılan Mülk</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-sky-300">20+</div>
                        <div class="text-sm text-blue-200 mt-0.5">Yıl Deneyim</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-purple-300">15+</div>
                        <div class="text-sm text-blue-200 mt-0.5">Uzman Ekip</div>
                    </div>
                </div>
            </div>

            {{-- Sağ kolon: görsel --}}
            <div class="relative hidden lg:block">
                <div class="relative">
                    <img src="{{ asset('images/hero-house.png') }}"
                         alt="{{ __('frontend.hero_image_alt', ['default' => 'Bodrum Lüks Konut']) }}"
                         class="w-full h-auto rounded-3xl shadow-2xl transition-transform duration-700 hover:scale-[1.02]">

                    {{-- Floating kart 1 --}}
                    <div class="absolute -left-10 top-16 bg-white/10 backdrop-blur-lg rounded-2xl p-4 shadow-xl border border-white/20"
                         style="animation: floatCard 6s ease-in-out infinite;">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #3b82f6, #10b981);">
                                <x-icon name="ev" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="text-xs text-blue-200">Premium Konutlar</div>
                                <div class="text-sm font-bold text-white">250+ İlan</div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating kart 2 --}}
                    <div class="absolute -right-8 bottom-16 bg-white/10 backdrop-blur-lg rounded-2xl p-4 shadow-xl border border-white/20"
                         style="animation: floatCard 8s ease-in-out infinite; animation-delay:-2s;">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #10b981, #3b82f6);">
                                <x-icon name="grafik" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="text-xs text-blue-200">Piyasa Değeri</div>
                                <div class="text-sm font-bold text-white">AI Destekli</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
    @keyframes floatCard {
        0%, 100% { transform: translateY(0px); }
        50%       { transform: translateY(-16px); }
    }
</style>
