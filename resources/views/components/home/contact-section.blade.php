{{-- Contact Section — Fine Design --}}
<section class="py-20 bg-[#F8F6F1] dark:bg-[#0A1628]">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section Header --}}
        <div class="text-center mb-14">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#C9A84C] mb-4">
                İletişim
            </p>
            <h2 class="text-3xl font-bold tracking-tight text-[#0A1628] dark:text-white" style="font-family: 'Manrope', sans-serif;">
                Danışmanımızla Görüşün
            </h2>
            <p class="mt-3 text-base text-[#6B7280] dark:text-white/60 max-w-xl mx-auto">
                Bodrum'da gayrimenkul ihtiyaçlarınız için uzman ekibimiz yanınızda
            </p>
        </div>

        {{-- Contact Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            {{-- Telefon --}}
            <a href="tel:+905332090302"
               class="group flex items-start gap-4 bg-white dark:bg-[#0D1E38] rounded-2xl border border-gray-200 dark:border-white/10 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-[#C9A84C]/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C9A84C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-[#6B7280] dark:text-white/50 uppercase tracking-wider mb-1">Telefon</p>
                    <p class="text-base font-bold text-[#0A1628] dark:text-white group-hover:text-[#C9A84C] transition-colors">0533 209 03 02</p>
                    <p class="text-xs text-[#6B7280] dark:text-white/50 mt-1">Hemen ara</p>
                </div>
            </a>

            {{-- WhatsApp --}}
            <a href="https://wa.me/905332090302"
               target="_blank" rel="noopener"
               class="group flex items-start gap-4 bg-white dark:bg-[#0D1E38] rounded-2xl border border-gray-200 dark:border-white/10 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-[#C9A84C]/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C9A84C]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-[#6B7280] dark:text-white/50 uppercase tracking-wider mb-1">WhatsApp</p>
                    <p class="text-base font-bold text-[#0A1628] dark:text-white group-hover:text-[#C9A84C] transition-colors">WhatsApp ile Ulaşın</p>
                    <p class="text-xs text-[#6B7280] dark:text-white/50 mt-1">Hızlı dönüş</p>
                </div>
            </a>

            {{-- E-posta --}}
            <a href="mailto:{{ config('company.email') }}"
               class="group flex items-start gap-4 bg-white dark:bg-[#0D1E38] rounded-2xl border border-gray-200 dark:border-white/10 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-11 h-11 rounded-xl bg-[#C9A84C]/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C9A84C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-[#6B7280] dark:text-white/50 uppercase tracking-wider mb-1">E-posta</p>
                    <p class="text-base font-bold text-[#0A1628] dark:text-white group-hover:text-[#C9A84C] transition-colors">{{ config('company.email') }}</p>
                    <p class="text-xs text-[#6B7280] dark:text-white/50 mt-1">7/24 yanıt</p>
                </div>
            </a>
        </div>

        {{-- Human CTA --}}
        <div class="max-w-2xl mx-auto w-full">
            <div class="bg-[#0A1628] rounded-2xl p-8 flex flex-col justify-between">
                <div>
                    <div class="w-11 h-11 rounded-xl bg-[#C9A84C]/15 flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-[#C9A84C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2" style="font-family: 'Manrope', sans-serif;">Danışmanımızla Görüşün</h3>
                    <p class="text-sm text-white/60 leading-relaxed mb-6">
                        Size özel portföy sunumundan fiyat analizine kadar her konuda yanınızdayız.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="tel:+905332090302"
                       class="inline-flex items-center gap-2 px-5 py-3 bg-[#C9A84C] text-white font-semibold text-sm rounded-xl hover:bg-[#B8973F] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Hemen Ara
                    </a>
                    <a href="https://wa.me/905332090302" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-5 py-3 border border-white/20 text-white font-semibold text-sm rounded-xl hover:bg-white/10 transition-colors">
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
