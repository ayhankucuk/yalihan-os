{{-- Contact Section — Premium Redesign --}}
<section class="relative py-20 overflow-hidden bg-gray-50 dark:bg-slate-900">

    {{-- Dekoratif orb --}}
    <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-indigo-100 dark:bg-indigo-900/20 opacity-50 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-emerald-100 dark:bg-emerald-900/20 opacity-50 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Başlık --}}
        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                İletişim
            </span>
            <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-slate-50">
                Bizimle
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-emerald-500"> İletişime Geçin</span>
            </h2>
            <p class="mt-4 text-lg text-gray-500 dark:text-slate-400 max-w-xl mx-auto">
                Emlak ihtiyaçlarınız için uzman ekibimiz size yardımcı olmaya hazır
            </p>
        </div>

        {{-- İletişim Kartları --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

            {{-- Telefon --}}
            <a href="tel:+905332090302"
               class="group flex items-start gap-4 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1">Telefon</p>
                    <p class="text-base font-bold text-gray-900 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">0533 209 03 02</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Hemen ara</p>
                </div>
            </a>

            {{-- Adres --}}
            <a href="https://maps.google.com/?q=Yalıkavak+Bodrum" target="_blank" rel="noopener"
               class="group flex items-start gap-4 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1">Adres</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors leading-snug">Yalıkavak, Şeyhül İslam Ömer Lütfi Cd. No:10 D:C</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">48400 Bodrum / Muğla</p>
                </div>
            </a>

            {{-- E-posta --}}
            <a href="mailto:info@yalihanemlak.com"
               class="group flex items-start gap-4 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1">E-posta</p>
                    <p class="text-base font-bold text-gray-900 dark:text-slate-100 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">info@yalihanemlak.com</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">7/24 yanıt</p>
                </div>
            </a>
        </div>

        {{-- Çalışma Saatleri + Hızlı Erişim --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Saatler --}}
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-7 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100 mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Çalışma Saatleri
                </h3>
                <div class="space-y-3">
                    @foreach([['Pazartesi – Cuma','09:00 – 18:00',true],['Cumartesi','09:00 – 16:00',true],['Pazar','Kapalı',false]] as [$gun,$saat,$acik])
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100 dark:border-slate-700/60 last:border-0">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $gun }}</span>
                        <span class="text-sm font-semibold {{ $acik ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">{{ $saat }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- CTA --}}
            <div class="relative bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-8 shadow-xl shadow-indigo-900/30 flex flex-col justify-between overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Hemen danışın</h3>
                    <p class="text-indigo-200 text-sm leading-relaxed mb-6">Size özel portföy sunumundan fiyat analizine kadar her konuda yanınızdayız.</p>
                    <a href="tel:+905332090302"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-indigo-700 font-bold text-sm rounded-xl hover:bg-indigo-50 transition-colors duration-200 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Bizi Arayın
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
