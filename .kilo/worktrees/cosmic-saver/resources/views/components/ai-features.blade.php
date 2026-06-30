{{-- ========================================
     AI FEATURES COMPONENT
     Backend ile uyumlu, AI özelliklerini gösterir
     ======================================== --}}

<section class="ds-section bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 relative overflow-hidden">
    {{-- Background Pattern --}}
    <div
        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0iZ3JpZCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNIDQwIDAgTCAwIDAgMCA0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDUpIiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-20">
    </div>

    <div class="ds-container relative z-10">
        <div class="text-center mb-16">
            <div
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500/20 to-blue-500/20
                        backdrop-blur-sm border border-cyan-400/30 rounded-full mb-8 animate-pulse">
                <svg class="w-6 h-6 mr-3 text-cyan-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12a1 1 0 0 1-1-1v-3a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1zm1-8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-2a6 6 0 1 0 0-12 6 6 0 0 0 0 12z" />
                </svg>
                <span class="text-cyan-300 font-semibold">Yapay Zeka Destekli Platform</span>
            </div>

            <h2
                class="text-4xl md:text-5xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-cyan-200 bg-clip-text text-transparent">
                AI ile Emlak Deneyimi
            </h2>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto leading-relaxed">
                Geleneksel emlak hizmetlerini yapay zeka teknolojisiyle birleştirerek,
                size en uygun gayrimenkul çözümlerini sunuyoruz.
            </p>
        </div>

        <div class="ds-grid-4">
            {{-- AI Asistan --}}
            <div
                class="group relative p-8 bg-white/10 backdrop-blur-sm border border-white/20
                       rounded-2xl hover:bg-white/15 transition-all duration-500 transform hover:-translate-y-2">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-pink-500/20 to-red-500/20 rounded-2xl
                           opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-pink-500 to-red-500 rounded-2xl
                               flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        🤖
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">24/7 AI Asistan</h3>
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        Anlık sorularınız için 7/24 status yapay zeka destekli müşteri hizmeti.
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Anında yanıt
                        </div>
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Özelleştirilmiş öneriler
                        </div>
                    </div>
                </div>
            </div>

            {{-- Akıllı Eşleştirme --}}
            <div
                class="group relative p-8 bg-white/10 backdrop-blur-sm border border-white/20
                       rounded-2xl hover:bg-white/15 transition-all duration-500 transform hover:-translate-y-2">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-green-500/20 to-teal-500/20 rounded-2xl
                           opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-500 rounded-2xl
                               flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        🎯
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Akıllı Eşleştirme</h3>
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        İhtiyaçlarınıza göre en uygun gayrimenkulleri otomatik olarak tespit eder.
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Kişiselleştirilmiş sonuçlar
                        </div>
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Gelişmiş filtreler
                        </div>
                    </div>
                </div>
            </div>

            {{-- Piyasa Analizi --}}
            <div
                class="group relative p-8 bg-white/10 backdrop-blur-sm border border-white/20
                       rounded-2xl hover:bg-white/15 transition-all duration-500 transform hover:-translate-y-2">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-indigo-500/20 rounded-2xl
                           opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-2xl
                               flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Piyasa Analizi</h3>
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        AI destekli değer analizi ile piyasa trendlerini ve fiyat tahminlerini sunar.
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Gerçek zamanlı analiz
                        </div>
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Trend tahminleri
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sanal Tur --}}
            <div
                class="group relative p-8 bg-white/10 backdrop-blur-sm border border-white/20
                       rounded-2xl hover:bg-white/15 transition-all duration-500 transform hover:-translate-y-2">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-orange-500/20 to-yellow-500/20 rounded-2xl
                           opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl
                               flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        🥽
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Sanal Tur</h3>
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        360° sanal turlar ile evleri sanki oradaymış gibi gezip inceleyebilirsiniz.
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            360° görünüm
                        </div>
                        <div class="flex items-center text-sm text-cyan-300">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                            </svg>
                            Yüksek çözünürlük
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="text-center mt-16">
            <a href="{{ route('advisors') }}" class="ds-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg px-8 py-4 dark:shadow-none">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                </svg>
                AI Danışmanlarımızla Tanışın
            </a>
        </div>
    </div>
</section>
