@extends('layouts.frontend')

@section('content')
{{-- Apex Coming-Soon Landing Page — YALIHAN OS Migration Placeholder --}}

<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-[#0A1628] via-[#0F2240] to-[#0A1628] relative overflow-hidden">

    {{-- Background decorative elements --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        {{-- Gold accent line top --}}
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-[#C9A84C]/30 to-transparent"></div>
        {{-- Subtle grid pattern --}}
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23C9A84C&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <div class="relative z-10 max-w-2xl mx-auto px-6 text-center">

        {{-- Logo mark --}}
        <div class="mb-8 flex justify-center">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#C9A84C] to-[#8B6914] flex items-center justify-center shadow-2xl shadow-[#C9A84C]/20">
                <span class="text-3xl font-display font-bold text-[#0A1628]">YE</span>
            </div>
        </div>

        {{-- Status badge --}}
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-[#C9A84C]/30 bg-[#C9A84C]/10 mb-8">
            <span class="w-2 h-2 rounded-full bg-[#C9A84C] animate-pulse"></span>
            <span class="text-sm font-medium text-[#C9A84C]">Yeni Web Sitemiz Hazırlanıyor</span>
        </div>

        {{-- Heading --}}
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-6 leading-tight">
            Yalıhan Emlak
        </h1>

        {{-- Subheading --}}
        <p class="text-lg md:text-xl text-slate-300 mb-4 font-medium">
            Bodrum'da Lüks Gayrimenkul
        </p>
        <p class="text-base text-slate-400 mb-12 max-w-md mx-auto leading-relaxed">
            Yeni web sitemiz son rötuşları yapılıyor. Çok yakında burada olacak.
        </p>

        {{-- Contact CTA --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="mailto:info@yalihanemlak.com.tr"
               class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-[#C9A84C] text-[#0A1628] font-semibold hover:bg-[#D4B55A] transition-all duration-200 shadow-lg shadow-[#C9A84C]/25 hover:shadow-[#C9A84C]/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Bize Ulaşın
            </a>
            <a href="tel:+902523330000"
               class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl border border-white/20 text-white font-semibold hover:bg-white/5 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                +90 252 333 00 00
            </a>
        </div>

        {{-- Legal links --}}
        <div class="flex items-center justify-center gap-6 text-sm">
            <a href="{{ route('legal.privacy') }}"
               class="text-slate-500 hover:text-[#C9A84C] transition-colors duration-200">
                Gizlilik Politikası
            </a>
            <span class="text-slate-700">|</span>
            <a href="{{ route('legal.terms') }}"
               class="text-slate-500 hover:text-[#C9A84C] transition-colors duration-200">
                Kullanım Şartları
            </a>
            <span class="text-slate-700">|</span>
            <a href="{{ route('legal.data-deletion') }}"
               class="text-slate-500 hover:text-[#C9A84C] transition-colors duration-200">
                Veri Silme
            </a>
        </div>

        {{-- Footer --}}
        <div class="mt-12 pt-8 border-t border-white/5">
            <p class="text-sm text-slate-600">
                &copy; {{ date('Y') }} Yalıhan Emlak. Bodrum, Muğla, Türkiye.
            </p>
        </div>

    </div>
</div>
@endsection
