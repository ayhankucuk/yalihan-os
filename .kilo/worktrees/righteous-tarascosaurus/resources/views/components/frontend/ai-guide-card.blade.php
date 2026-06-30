@props([
    'title' => 'AI ile Sana Özel Gayrimenkul Önerileri',
    'description' => 'Bütçe, lokasyon ve yatırım hedeflerini gir; AI, dünyadaki portföyümüzden en iyi seçenekleri analiz etsin.',
    'actionLabel' => 'AI Rehberini Başlat',
    'endpoint' => route('ai.explore'),
])

<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-700 dark:via-indigo-700 dark:to-purple-700 text-white shadow-xl">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -left-20 top-10 h-72 w-72 rounded-full bg-white/20 blur-3xl dark:bg-slate-900/20"></div>
        <div class="absolute right-10 -bottom-10 h-56 w-56 rounded-full bg-purple-400/30 blur-3xl"></div>
    </div>

    <div class="relative px-8 py-10 sm:px-12 sm:py-12 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
        <div class="space-y-5 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1 text-xs font-semibold uppercase tracking-wide dark:bg-slate-900/15">
                <i class="fas fa-robot"></i>
                Kayak.ai İlhamlı Akıllı Rehber
            </div>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight">
                {{ $title }}
            </h2>
            <p class="text-base sm:text-lg text-white/80 max-w-xl">
                {{ $description }}
            </p>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <button type="button" data-ai-guide-endpoint="{{ $endpoint }}" class="inline-flex items-center justify-center gap-3 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-700 shadow-lg transition-all duration-200 hover:shadow-2xl hover:-translate-y-0.5 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 dark:bg-slate-900">
                    <i class="fas fa-wand-magic-sparkles text-base"></i>
                    {{ $actionLabel }}
                </button>

                <a href="{{ route('ai.explore') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 hover:text-white transition-colors duration-200">
                    Nasıl çalışıyor?
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-left text-sm font-medium">
            <div class="rounded-2xl bg-white/15 backdrop-blur-sm px-6 py-5 dark:bg-slate-900/15">
                <p class="text-3xl font-bold">40+</p>
                <p class="mt-1 text-white/80">Ülkede portföy</p>
            </div>
            <div class="rounded-2xl bg-white/15 backdrop-blur-sm px-6 py-5 dark:bg-slate-900/15">
                <p class="text-3xl font-bold">120+</p>
                <p class="mt-1 text-white/80">Premium proje</p>
            </div>
            <div class="rounded-2xl bg-white/15 backdrop-blur-sm px-6 py-5 dark:bg-slate-900/15">
                <p class="text-3xl font-bold">24s</p>
                <p class="mt-1 text-white/80">AI ön onay analizi</p>
            </div>
            <div class="rounded-2xl bg-white/15 backdrop-blur-sm px-6 py-5 dark:bg-slate-900/15">
                <p class="text-3xl font-bold">7/24</p>
                <p class="mt-1 text-white/80">Danışman desteği</p>
            </div>
        </div>
    </div>
</section>

