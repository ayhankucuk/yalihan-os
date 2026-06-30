{{-- AI Destekli Hero Section --}}
<section class="relative min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0">
        <div
            class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0iZ3JpZCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNIDQwIDAgTCAwIDAgMCA0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDUpIiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30">
        </div>

        {{-- Floating AI Particles --}}
        <div class="absolute inset-0">
            @for ($i = 0; $i < 20; $i++)
                <div class="absolute w-1 h-1 bg-blue-400 rounded-full animate-pulse"
                    style="top: {{ rand(10, 90) }}%; left: {{ rand(10, 90) }}%;
                            animation-delay: {{ rand(0, 3000) }}ms;
                            animation-duration: {{ rand(2000, 4000) }}ms;">
                </div>
            @endfor
        </div>
    </div>

    {{-- Content --}}
    <div class="relative z-10 container mx-auto px-6 flex items-center min-h-screen">
        <div class="max-w-4xl mx-auto text-center text-white">
            {{-- AI Badge --}}
            <div
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-cyan-500/20 to-blue-500/20
                        backdrop-blur-sm border border-cyan-400/30 rounded-full mb-8 animate-pulse">
                <svg class="w-5 h-5 mr-2 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12a1 1 0 0 1-1-1v-3a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1zm1-8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-2a6 6 0 1 0 0-12 6 6 0 0 0 0 12z" />
                </svg>
                <span class="text-sm font-medium text-cyan-300">AI Destekli Emlak Platformu</span>
            </div>

            {{-- Main Title --}}
            <h1
                class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-cyan-200 bg-clip-text text-transparent">
                Yalıhan Emlak
            </h1>

            <p class="text-xl md:text-2xl mb-8 text-blue-100 leading-relaxed">
                <span class="bg-gradient-to-r from-cyan-300 to-blue-300 bg-clip-text text-transparent font-semibold">
                    Yapay Zeka
                </span> ile güçlendirilmiş emlak deneyimi.<br>
                <span class="text-gray-300">Yalıkavak & Bodrum'da hayalinizdeki evi bulun.</span>
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="{{ route('ilanlar.index') }}"
                    class="group px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600
                              hover:from-cyan-400 hover:to-blue-500 rounded-xl font-semibold
                              transform hover:scale-105 transition-all duration-300 shadow-2xl
                              hover:shadow-cyan-500/25">
                    <span class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2 group-hover:animate-spin" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        AI ile Ev Ara
                    </span>
                </a>

                <a href="{{ route('advisors') }}"
                    class="px-8 py-4 border-2 border-white/30 hover:border-white/60
                              backdrop-blur-sm rounded-xl font-semibold
                              hover:bg-white/10 transition-all duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                    AI Danışmanlarımız
                </a>
            </div>

            {{-- AI Features Preview --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div
                    class="group p-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl
                           hover:bg-white/10 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-pink-500 to-red-500 rounded-xl
                               flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        🤖
                    </div>
                    <h3 class="font-semibold mb-2">AI Asistan</h3>
                    <p class="text-sm text-gray-300">Anlık sorularınız için 7/24 yapay zeka desteği</p>
                </div>

                <div
                    class="group p-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl
                           hover:bg-white/10 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-500 rounded-xl
                               flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        🎯
                    </div>
                    <h3 class="font-semibold mb-2">Akıllı Eşleştirme</h3>
                    <p class="text-sm text-gray-300">İhtiyaçlarınıza göre otomatik öneriler</p>
                </div>

                <div
                    class="group p-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl
                           hover:bg-white/10 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-xl
                               flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        📊
                    </div>
                    <h3 class="font-semibold mb-2">Piyasa Analizi</h3>
                    <p class="text-sm text-gray-300">AI destekli değer analizi ve trend tahminleri</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
    </div>
</section>
