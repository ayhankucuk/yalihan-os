{{-- Statistics Section — Premium Redesign --}}
@props([
    'activeListings'   => 0,
    'experienceYears'  => 20,
    'happyCustomers'   => 1500,
])

<section class="relative py-20 overflow-hidden bg-white dark:bg-slate-950">

    {{-- Dekoratif arka plan --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-gradient-to-br from-indigo-100 to-violet-100 dark:from-indigo-950 dark:to-violet-950 opacity-60 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-950 dark:to-teal-950 opacity-60 blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Başlık --}}
        <div class="text-center mb-16">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Rakamlarla Yalıhan
            </span>
            <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-slate-50">
                25 Yıllık
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-emerald-500">
                    Güven & Deneyim
                </span>
            </h2>
            <p class="mt-4 text-lg text-gray-500 dark:text-slate-400 max-w-2xl mx-auto">
                Bodrum&rsquo;un en seçkin gayrimenkul portföyünde profesyonel ve güvenilir çözüm ortağınız
            </p>
        </div>

        {{-- Stat Kartları --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-16">

            <div class="group relative bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <div class="stat-number text-4xl font-extrabold text-gray-900 dark:text-slate-50 leading-none mb-1" data-target="{{ $activeListings }}">{{ $activeListings }}</div>
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Aktif İlan</div>
                <div class="mt-3 text-xs text-indigo-500 dark:text-indigo-400 font-medium">Güncel portföy</div>
            </div>

            <div class="group relative bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/40 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-number text-4xl font-extrabold text-gray-900 dark:text-slate-50 leading-none mb-1" data-target="{{ $happyCustomers }}">{{ $happyCustomers }}</div>
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Mutlu Müşteri</div>
                <div class="mt-3 text-xs text-emerald-500 dark:text-emerald-400 font-medium">Memnuniyet garantili</div>
            </div>

            <div class="group relative bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-number text-4xl font-extrabold text-gray-900 dark:text-slate-50 leading-none mb-1" data-target="{{ $experienceYears }}">{{ $experienceYears }}</div>
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Yıl Deneyim</div>
                <div class="mt-3 text-xs text-amber-500 dark:text-amber-400 font-medium">2000&rsquo;den bu yana</div>
            </div>

            <div class="group relative bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/40 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="text-4xl font-extrabold text-gray-900 dark:text-slate-50 leading-none mb-1">15+</div>
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Uzman Danışman</div>
                <div class="mt-3 text-xs text-violet-500 dark:text-violet-400 font-medium">Sertifikalı ekip</div>
            </div>

        </div>

        {{-- Özellikler --}}
        <div class="grid md:grid-cols-3 gap-6">

            <div class="flex gap-4 p-6 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 mb-1">Gelişmiş Arama</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed">Akıllı filtreler ve harita bazlı arama ile hayalinizdeki mülkü kolayca bulun.</p>
                </div>
            </div>

            <div class="flex gap-4 p-6 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 mb-1">AI Destekli Analiz</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed">Yapay zeka ile fiyat analizi, piyasa trendi ve yatırım değerlendirmesi.</p>
                </div>
            </div>

            <div class="flex gap-4 p-6 bg-white dark:bg-slate-800/80 rounded-2xl border border-gray-100 dark:border-slate-700/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0 shadow-md shadow-amber-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 mb-1">Güvenilir Partner</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed">Tüm ilanlar onaylı, tüm işlemler şeffaf ve belgeli güvence altında.</p>
                </div>
            </div>

        </div>
    </div>
</section>

@once
@push('scripts')
<script>
(function() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseInt(el.dataset.target, 10) || 0;
            if (!target) return;
            let start = 0;
            const step = target / 60;
            const tick = () => {
                start = Math.min(start + step, target);
                el.textContent = Math.floor(start).toLocaleString('tr-TR');
                if (start < target) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
            observer.unobserve(el);
        });
    }, { threshold: 0.4 });
    document.querySelectorAll('.stat-number[data-target]').forEach(el => observer.observe(el));
})();
</script>
@endpush
@endonce
