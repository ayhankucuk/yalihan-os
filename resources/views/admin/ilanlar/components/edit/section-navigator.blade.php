{{-- 
    İlan Edit — Sticky Section Navigator
    10 bölmə arasında pürüzsüz atlayış + scroll-da aktiv izləmə
--}}
<div x-data="sectionNavigator()" x-init="init()"
     class="sticky top-16 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm">
    
    {{-- Desktop: Horizontal scrollable bar --}}
    <div class="hidden md:block">
        <div class="mx-auto max-w-[1700px] px-4 md:px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide py-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                
                <template x-for="(section, index) in sections" :key="section.id">
                    <a :href="'#' + section.id"
                       @click.prevent="scrollToSection(section.id)"
                       class="group relative shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 whitespace-nowrap"
                       :class="activeId === section.id 
                           ? 'bg-amber-500 text-slate-950 shadow-sm' 
                           : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'">
                        
                        {{-- Section number badge --}}
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                              :class="activeId === section.id 
                                  ? 'bg-slate-950 text-amber-400' 
                                  : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-600'">
                            <span x-text="index + 1"></span>
                        </span>
                        
                        <span x-text="section.label"></span>
                        
                        {{-- Active indicator dot --}}
                        <span x-show="activeId === section.id" class="w-1.5 h-1.5 rounded-full bg-slate-950 animate-pulse"></span>
                    </a>
                </template>

                {{-- Progress bar at bottom --}}
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-100 dark:bg-slate-800">
                    <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-300"
                         :style="'width: ' + progressPercent + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile: Collapsible quick-jump --}}
    <div class="md:hidden px-4 py-2" x-data="{ open: false }">
        <button @click="open = !open" 
                class="w-full flex items-center justify-between px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span x-text="currentSectionLabel"></span>
            </span>
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        
        <div x-show="open" x-collapse class="mt-2 space-y-1">
            <template x-for="(section, index) in sections" :key="section.id">
                <a :href="'#' + section.id"
                   @click.prevent="scrollToSection(section.id); open = false"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors"
                   :class="activeId === section.id 
                       ? 'bg-amber-500 text-slate-950 font-medium' 
                       : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                          :class="activeId === section.id 
                              ? 'bg-slate-950 text-amber-400' 
                              : 'bg-slate-200 dark:bg-slate-700'">
                        <span x-text="index + 1"></span>
                    </span>
                    <span x-text="section.label"></span>
                </a>
            </template>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function sectionNavigator() {
    return {
        activeId: 'section-category',
        progressPercent: 0,
        
        sections: [
            { id: 'section-category',    label: 'Kategori' },
            { id: 'section-basic-info',   label: 'Temel Bilgiler' },
            { id: 'section-price',        label: 'Fiyat' },
            { id: 'section-location',    label: 'Konum & Harita' },
            { id: 'section-fields',      label: 'Özellikler' },
            { id: 'section-site',        label: 'Site / Apartman' },
            { id: 'section-keys',        label: 'Anahtarlar' },
            { id: 'section-photos',      label: 'Fotoğraflar' },
            { id: 'section-person',      label: 'CRM & Kişi' },
            { id: 'section-booking',     label: 'Rezervasyon' },
        ],
        
        get currentSectionLabel() {
            const active = this.sections.find(s => s.id === this.activeId);
            return active ? active.label : 'Bölüm Seç';
        },
        
        init() {
            // Intersection Observer — aktiv bölməni izlə
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.activeId = entry.target.id;
                        }
                    });
                },
                {
                    rootMargin: '-20% 0px -70% 0px',
                    threshold: 0
                }
            );
            
            // Bütün section-ları müşahidə et
            this.sections.forEach(section => {
                const el = document.getElementById(section.id);
                if (el) observer.observe(el);
            });
            
            // Scroll progress
            window.addEventListener('scroll', () => this.updateProgress(), { passive: true });
            this.updateProgress();
        },
        
        scrollToSection(id) {
            const el = document.getElementById(id);
            if (el) {
                const offset = 120; // vitals (64px) + navigator (~56px)
                const top = el.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
                this.activeId = id;
            }
        },
        
        updateProgress() {
            const scrolled = window.scrollY;
            const total = document.body.scrollHeight - window.innerHeight;
            this.progressPercent = total > 0 ? Math.min(100, (scrolled / total) * 100) : 0;
        }
    };
}
</script>
@endpush
@endonce
