@extends('layouts.frontend')

@section('title', $danisman->name . ' - Danışman Profili - Yalıhan Emlak')

@section('content')
<div class="relative min-h-screen">
    {{-- Page Header — Kurumsal Banner + Danışman Profil --}}
    <div style="background:#0F2A5C; padding-top:7rem; padding-bottom:2.5rem;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumb --}}
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.25rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.55);">
                <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">Ana Sayfa</a>
                <span>›</span>
                <a href="{{ route('frontend.danismanlar.index') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">Danışmanlar</a>
                <span>›</span>
                <span style="color:#fff;">{{ $danisman->name }}</span>
            </div>

            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                {{-- Fotoğraf --}}
                <div class="flex-shrink-0">
                    @if($danisman->profile_photo_path)
                        <img src="{{ asset('storage/' . $danisman->profile_photo_path) }}"
                             alt="{{ $danisman->name }}"
                             class="w-28 h-28 md:w-36 md:h-36 rounded-full object-cover"
                             style="border:3px solid rgba(255,255,255,0.3);box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                    @else
                        <div class="w-28 h-28 md:w-36 md:h-36 rounded-full flex items-center justify-center"
                             style="background:rgba(255,255,255,0.12);border:3px solid rgba(255,255,255,0.25);">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Bilgiler --}}
                <div class="flex-1 text-center md:text-left">
                    <h1 style="font-size:clamp(1.5rem,2.5vw,2rem);font-weight:800;color:#fff;margin-bottom:0.25rem;letter-spacing:-0.01em;">
                        {{ $danisman->name }}
                    </h1>
                    <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);margin-bottom:0.875rem;">
                        {{ $danisman->baslik ?? 'Emlak Danışmanı' }}
                    </p>

                    {{-- Pozisyon ve Departman --}}
                    @if($danisman->position || $danisman->department)
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-4">
                            @if($danisman->position)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.85);">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                                    {{ config('danisman.positions.' . $danisman->position, $danisman->position) }}
                                </span>
                            @endif
                            @if($danisman->department)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.85);">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                    {{ config('danisman.departments.' . $danisman->department, $danisman->department) }}
                                </span>
                            @endif
                        </div>
                    @endif

                    {{-- Telefon ve Sosyal Medya --}}
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                        @if($danisman->telefon)
                            <a href="tel:{{ $danisman->telefon }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-semibold text-sm transition-all duration-200"
                               style="background:rgba(255,255,255,0.15);color:#fff;"
                               onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                <x-icon name="telefon" class="w-4 h-4" />
                                <span>{{ $danisman->telefon }}</span>
                            </a>
                        @endif
                        @if($danisman->instagram_profil || $danisman->linkedin_profil || $danisman->whatsapp_numara)
                            <x-frontend.danisman-social-links :danisman="$danisman" size="md" variant="default" />
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- İstatistikler --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 text-center dark:border-slate-700">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ $performans['aktif_ilan'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Aktif İlan</div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 text-center dark:border-slate-700">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">{{ $performans['toplam_ilan'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Toplam İlan</div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 text-center dark:border-slate-700">
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-1">{{ $performans['onayli_yorum'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Yorum</div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 text-center dark:border-slate-700">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-1">
                    @if($performans['ortalama_rating'] > 0)
                        {{ number_format($performans['ortalama_rating'], 1) }} ⭐
                    @else
                        -
                    @endif
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Ortalama Puan</div>
            </div>
        </div>
    </div>

    {{-- İçerik --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Sol Kolon - Hakkında --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Hakkında --}}
                @if($danisman->bio || $danisman->expertise_summary)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Hakkında
                        </h2>
                        @if($danisman->bio)
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                {!! nl2br(e($danisman->bio)) !!}
                            </div>
                        @endif
                        @if($danisman->expertise_summary)
                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Uzmanlık Özeti</h3>
                                <p class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $danisman->expertise_summary }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Uzmanlık Alanları --}}
                @if($danisman->uzmanlik_alanlari && count($danisman->uzmanlik_alanlari) > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Uzmanlık Alanları
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($danisman->uzmanlik_alanlari as $alan)
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                                    {{ $alan }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- İlanlar --}}
                @if($ilanlar->count() > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                Aktif İlanlar ({{ $performans['aktif_ilan'] ?? 0 }})
                            </h2>
                            <a href="{{ route('danisman.ilanlar', $danisman->id) }}" 
                               class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Tümünü Gör <span class="material-symbols-outlined ml-1" style="font-size:16px;vertical-align:middle">arrow_forward</span>
                            </a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($ilanlar as $ilan)
                                <a href="{{ route('ilanlar.show', $ilan->id) }}" 
                                   class="group block bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-all duration-200 dark:bg-slate-900">
                                    @if($ilan->fotograflar->first())
                                        <img src="{{ asset('storage/' . $ilan->fotograflar->first()->dosya_yolu) }}" 
                                             alt="{{ $ilan->baslik }}"
                                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-48 bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-500 dark:text-gray-400" style="font-size:48px">home</span>
                                        </div>
                                    @endif
                                    <div class="p-4">
                                        <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 mb-2 dark:text-slate-100">
                                            {{ $ilan->baslik }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            {{ $ilan->il->il_adi ?? '' }} / {{ $ilan->ilce->ilce_adi ?? '' }}
                                        </p>
                                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Yorumlar --}}
                @if($danisman->onayliDanismanYorumlari->count() > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">
                            Müşteri Yorumları ({{ $performans['onayli_yorum'] ?? 0 }})
                        </h2>
                        <div class="space-y-4">
                            @foreach($danisman->onayliDanismanYorumlari as $yorum)
                                <div class="border-b border-gray-200 dark:border-slate-800 pb-4 last:border-0 last:pb-0 dark:border-slate-700">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $yorum->author_name }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span class="material-symbols-outlined {{ $yorum->rating >= $i ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" style="font-size:14px;font-variation-settings:'FILL' {{ $yorum->rating >= $i ? 1 : 0 }}">star</span>
                                                @endfor
                                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $yorum->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 dark:text-slate-200 text-sm leading-relaxed dark:text-slate-300">{{ $yorum->yorum }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sağ Kolon - İletişim --}}
            <div class="space-y-6">
                {{-- İletişim Bilgileri --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        İletişim
                    </h2>
                    <div class="space-y-4">
                        @if($danisman->telefon)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400" style="font-size:20px">call</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Telefon</p>
                                    <a href="tel:{{ $danisman->telefon }}" class="text-gray-900 dark:text-white font-medium hover:text-blue-600 dark:hover:text-blue-400 transition-colors dark:text-slate-100">
                                        {{ $danisman->telefon }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($danisman->office_phone)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400" style="font-size:20px">phone_in_talk</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Ofis Telefonu</p>
                                    <a href="tel:{{ $danisman->office_phone }}" class="text-gray-900 dark:text-white font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors dark:text-slate-100">
                                        {{ $danisman->office_phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($danisman->email)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 dark:text-green-400" style="font-size:20px">mail</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">E-posta</p>
                                    <a href="mailto:{{ $danisman->email }}" class="text-gray-900 dark:text-white font-medium hover:text-green-600 dark:hover:text-green-400 transition-colors dark:text-slate-100">
                                        {{ $danisman->email }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($danisman->office_address)
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-purple-600 dark:text-purple-400" style="font-size:20px">location_on</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Ofis Adresi</p>
                                    <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $danisman->office_address }}</p>
                                </div>
                            </div>
                        @endif

                        @if($danisman->lisans_no)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400" style="font-size:20px">verified</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Lisans No</p>
                                    <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $danisman->lisans_no }}</p>
                                </div>
                            </div>
                        @endif

                        @if($danisman->deneyim_yili)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400" style="font-size:20px">calendar_month</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Deneyim</p>
                                    <p class="text-gray-900 dark:text-white font-medium dark:text-slate-100">{{ $danisman->deneyim_yili }} Yıl</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Sosyal Medya --}}
                @if($danisman->instagram_profil || $danisman->linkedin_profil || $danisman->whatsapp_numara || $danisman->website)
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Sosyal Medya
                        </h2>
                        <div class="flex flex-wrap gap-3">
                            <x-frontend.danisman-social-links :danisman="$danisman" size="md" variant="default" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

