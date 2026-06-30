{{-- ========================================
     HERO SECTION WITH LIVE SEARCH
     Yalıhan Emlak — Bodrum PropTech Platform
     FA=0 | material-symbols=0 | ds-*=0
     ======================================== --}}

<section class="relative min-h-[82vh] flex items-center overflow-hidden"
         style="background: linear-gradient(135deg, #0F2A5C 0%, #1a3d7a 45%, #0D5FA3 100%);">

    {{-- Animated blur orbs --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-16 left-8 w-72 h-72 rounded-full opacity-20 blur-3xl"
             style="background:radial-gradient(circle, #3b82f6, transparent); animation: floatOrb 8s ease-in-out infinite;"></div>
        <div class="absolute bottom-16 right-8 w-96 h-96 rounded-full opacity-15 blur-3xl"
             style="background:radial-gradient(circle, #10b981, transparent); animation: floatOrb 10s ease-in-out infinite; animation-delay:-3s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full opacity-10 blur-3xl"
             style="background:radial-gradient(circle, #C9A84C, transparent); animation: floatOrb 12s ease-in-out infinite; animation-delay:-1.5s;"></div>
    </div>

    {{-- Dot pattern overlay --}}
    <div class="absolute inset-0 opacity-[0.07] pointer-events-none" aria-hidden="true"
         style="background-image: radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px); background-size: 30px 30px;"></div>

    {{-- Content --}}
    <div class="relative z-10 w-full max-w-7xl mx-auto px-6 md:px-12 py-24">

        {{-- Heading --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-1.5 mb-6">
                <x-icon name="konum" class="w-4 h-4 text-yellow-300" />
                <span class="text-xs font-semibold text-yellow-200 tracking-wider uppercase">Bodrum Yarımadası</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-5 leading-tight tracking-tight">
                Bodrum'un En Güzel
                <span class="block mt-1"
                      style="background: linear-gradient(90deg, #C9A84C, #f0d080); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    Emlak Seçenekleri
                </span>
            </h1>
            <p class="text-lg md:text-xl text-blue-100 max-w-2xl mx-auto leading-relaxed">
                Hayalinizdeki evi bulmak için AI destekli arama sistemimizi kullanın
            </p>
        </div>

        {{-- Search Box --}}
        <div class="max-w-4xl mx-auto bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-2xl mb-10"
             x-data="{
                 lokasyon: '',
                 kategori: '',
                 yayinTipi: 'satilik',
                 aramayaGit() {
                     let url = '{{ route('ilanlar.index') }}?yayin_tipi=' + this.yayinTipi;
                     if (this.kategori) url += '&kategori_slug=' + this.kategori;
                     if (this.lokasyon) url += '&search=' + encodeURIComponent(this.lokasyon);
                     window.location.href = url;
                 }
             }">

            {{-- Satılık / Kiralık tabs --}}
            <div class="flex gap-2 mb-5">
                <button @click="yayinTipi='satilik'"
                        :class="yayinTipi==='satilik'
                            ? 'bg-white text-blue-800 shadow-sm'
                            : 'bg-white/10 text-white hover:bg-white/20'"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200">
                    Satılık
                </button>
                <button @click="yayinTipi='kiralik'"
                        :class="yayinTipi==='kiralik'
                            ? 'bg-white text-blue-800 shadow-sm'
                            : 'bg-white/10 text-white hover:bg-white/20'"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200">
                    Kiralık
                </button>
                <button @click="yayinTipi='yazlik-kiralik'"
                        :class="yayinTipi==='yazlik-kiralik'
                            ? 'bg-white text-blue-800 shadow-sm'
                            : 'bg-white/10 text-white hover:bg-white/20'"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200">
                    Yazlık Kiralık
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                {{-- Konum --}}
                <div class="relative">
                    <x-icon name="konum" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                    <input type="text"
                           x-model="lokasyon"
                           @keydown.enter="aramayaGit()"
                           placeholder="İl, İlçe veya Mahalle..."
                           class="w-full bg-white text-slate-800 pl-10 pr-4 py-3.5 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-blue-400 placeholder-slate-400">
                </div>

                {{-- Kategori --}}
                <div class="relative">
                    <x-icon name="ev" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                    <select x-model="kategori"
                            class="w-full bg-white text-slate-800 pl-10 pr-8 py-3.5 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-blue-400 appearance-none cursor-pointer">
                        <option value="">Tüm Kategoriler</option>
                        <option value="konut">Konut</option>
                        <option value="villa">Villa</option>
                        <option value="arsa-arazi">Arsa & Arazi</option>
                        <option value="isyeri">Ticari Mülk</option>
                        <option value="yazlik">Yazlık</option>
                    </select>
                    <x-icon name="asagi-chevron" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                </div>

                {{-- Ara butonu --}}
                <button @click="aramayaGit()"
                        class="flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-500 to-yellow-400 hover:from-yellow-400 hover:to-yellow-300 text-slate-900 font-bold py-3.5 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-yellow-400/30 active:scale-[0.98]">
                    <x-icon name="arama" class="w-5 h-5" />
                    <span>Ara</span>
                </button>
            </div>

            {{-- Hızlı Filtreler --}}
            <div class="mt-4 pt-4 border-t border-white/15 flex flex-wrap gap-2 items-center">
                <span class="text-xs font-semibold text-blue-200 mr-1">Hızlı:</span>
                <a href="{{ route('ilanlar.index', ['yayin_tipi' => 'satilik', 'kategori_slug' => 'villa']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-full border border-white/15 transition-all">
                    <x-icon name="ev" class="w-3.5 h-3.5" />
                    Satılık Villa
                </a>
                <a href="{{ route('arsa') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-full border border-white/15 transition-all">
                    <x-icon name="harita" class="w-3.5 h-3.5" />
                    Satılık Arsa
                </a>
                <a href="{{ route('villas.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-full border border-white/15 transition-all">
                    <x-icon name="takvim" class="w-3.5 h-3.5" />
                    Yazlık Kiralık
                </a>
                <a href="{{ route('ilanlar.international') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-full border border-white/15 transition-all">
                    <x-icon name="ag" class="w-3.5 h-3.5" />
                    Uluslararası
                </a>
            </div>
        </div>

        {{-- İstatistik Sayaçları --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto"
             x-data="{
                 counts: [0, 0, 0, 0],
                 targets: [500, 1200, 20, 50],
                 started: false
             }"
             x-intersect.once="
                 if (!started) {
                     started = true;
                     targets.forEach((target, i) => {
                         let current = 0;
                         const step = Math.ceil(target / 50);
                         const timer = setInterval(() => {
                             current = Math.min(current + step, target);
                             counts[i] = current;
                             if (current >= target) clearInterval(timer);
                         }, 30);
                     });
                 }
             ">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-extrabold text-yellow-300 mb-1" x-text="counts[0] + '+'">500+</div>
                <div class="text-sm text-blue-200">Aktif İlan</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-extrabold text-emerald-300 mb-1" x-text="counts[1] + '+'">1200+</div>
                <div class="text-sm text-blue-200">Mutlu Müşteri</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-extrabold text-sky-300 mb-1" x-text="counts[2] + '+'">20+</div>
                <div class="text-sm text-blue-200">Yıllık Deneyim</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-extrabold text-purple-300 mb-1" x-text="counts[3] + '+'">50+</div>
                <div class="text-sm text-blue-200">Uzman Danışman</div>
            </div>
        </div>

    </div>

    {{-- Scroll indicator --}}
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 opacity-60" aria-hidden="true">
        <div class="w-5 h-8 border-2 border-white/50 rounded-full flex justify-center pt-1.5">
            <div class="w-1 h-2 bg-white/70 rounded-full" style="animation: scrollDot 1.5s ease-in-out infinite;"></div>
        </div>
    </div>
</section>

<style>
    @keyframes floatOrb {
        0%, 100% { transform: translateY(0px) scale(1); }
        50%       { transform: translateY(-24px) scale(1.05); }
    }
    @keyframes scrollDot {
        0%, 100% { opacity: 1; transform: translateY(0); }
        50%       { opacity: 0.3; transform: translateY(6px); }
    }
</style>
