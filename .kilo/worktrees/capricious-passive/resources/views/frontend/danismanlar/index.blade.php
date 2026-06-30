@extends('layouts.frontend')

@section('title', 'Danışmanlarımız — Yalıhan Emlak')

@push('styles')
<style>
    .danisman-card { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1), box-shadow 0.3s; }
    .danisman-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(10,22,45,0.18); }
    .danisman-card:hover .danisman-photo { transform: scale(1.06); }
    .danisman-photo { transition: transform 0.7s; }
</style>
@endpush

@section('content')
<div class="relative min-h-screen">

    {{-- Page Header — Kurumsal Banner --}}
    <div style="background:#0F2A5C; padding-top:7rem; padding-bottom:2.5rem;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.55);">
                <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">Ana Sayfa</a>
                <span>›</span>
                <span style="color:#fff;">Danışmanlar</span>
            </div>
            <h1 style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;color:#fff;margin-bottom:0.5rem;letter-spacing:-0.01em;">
                Danışmanlarımız
            </h1>
            <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);max-width:40rem;">
                Deneyimli emlak danışmanlarımızla hayallerinizdeki gayrimenkule ulaşın.
            </p>
        </div>
    </div>

    {{-- Filtreler --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-10">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
            <form method="GET" action="{{ route('frontend.danismanlar.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Arama --}}
                <div>
                    <label for="search" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Arama</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               placeholder="İsim, ünvan..."
                               class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-sm">
                    </div>
                </div>

                {{-- Departman --}}
                <div>
                    <label for="department" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Departman</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">apartment</span>
                        <select name="department" id="department"
                                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg bg-white text-slate-900 focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-sm appearance-none">
                            <option value="">Tüm Departmanlar</option>
                            @foreach($departments ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('department') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Pozisyon --}}
                <div>
                    <label for="position" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Pozisyon</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">work</span>
                        <select name="position" id="position"
                                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg bg-white text-slate-900 focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-sm appearance-none">
                            <option value="">Tüm Pozisyonlar</option>
                            @foreach($positions ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('position') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Sıralama --}}
                <div>
                    <label for="sort" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sıralama</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">sort</span>
                        <select name="sort" id="sort"
                                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg bg-white text-slate-900 focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-sm appearance-none">
                            <option value="name_asc" {{ request('sort', 'name_asc') == 'name_asc' ? 'selected' : '' }}>İsme Göre (A-Z)</option>
                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>İsme Göre (Z-A)</option>
                            <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Yeni Eklenenler</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="lg:col-span-4 flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.5rem;background:linear-gradient(135deg,#0A1628,#0d2044);color:#fff;font-weight:700;font-size:0.875rem;border-radius:0.625rem;border:none;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.background='linear-gradient(135deg,#0d2044,#004ac6)'"
                            onmouseout="this.style.background='linear-gradient(135deg,#0A1628,#0d2044)'">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                        Filtrele
                    </button>
                    <a href="{{ route('frontend.danismanlar.index') }}"
                       class="inline-flex items-center gap-2 px-6 py-2.5 border border-slate-300 bg-white text-slate-700 font-semibold text-sm rounded-lg hover:bg-slate-50 transition-all duration-200">
                        <span class="material-symbols-outlined text-[18px]">undo</span>
                        Temizle
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Danışmanlar Listesi --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if($danismanlar->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($danismanlar as $danisman)
                    @php
                        $statusValue = $danisman->status_text ?? ($danisman->aktiflik_durumu ? 'aktif' : 'pasif');
                    @endphp

                    <div class="danisman-card bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden">
                        {{-- Fotoğraf --}}
                        <div class="relative h-60 overflow-hidden" style="background:linear-gradient(135deg,#0A1628 0%,#0d2044 100%);">
                            @if($danisman->profile_photo_path)
                                <img src="{{ asset('storage/' . $danisman->profile_photo_path) }}"
                                     alt="{{ $danisman->name }}"
                                     class="danisman-photo w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background:rgba(201,168,76,0.15);">
                                        <span class="material-symbols-outlined text-[56px]" style="color:rgba(201,168,76,0.7);">person</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Aktif badge --}}
                            @if($statusValue === 'aktif')
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500 text-white">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>Aktif
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- İçerik --}}
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-slate-900 mb-0.5 line-clamp-1">{{ $danisman->name }}</h3>
                            <p class="text-sm text-slate-500 mb-3">{{ $danisman->baslik ?? 'Emlak Danışmanı' }}</p>

                            {{-- Pozisyon / Departman --}}
                            @if($danisman->position || $danisman->department)
                                <div class="space-y-1 mb-3">
                                    @if($danisman->position)
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <span class="material-symbols-outlined text-[14px]">work</span>
                                            {{ config('danisman.positions.' . $danisman->position, $danisman->position) }}
                                        </div>
                                    @endif
                                    @if($danisman->department)
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <span class="material-symbols-outlined text-[14px]">apartment</span>
                                            {{ config('danisman.departments.' . $danisman->department, $danisman->department) }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Telefon --}}
                            @if($danisman->telefon)
                                <a href="tel:{{ $danisman->telefon }}"
                                   class="inline-flex items-center gap-1.5 text-sm text-primary font-medium hover:text-blue-700 transition-colors mb-3">
                                    <span class="material-symbols-outlined text-[15px]">call</span>
                                    {{ $danisman->telefon }}
                                </a>
                            @endif

                            {{-- Uzmanlık alanları --}}
                            @if($danisman->uzmanlik_alanlari && count($danisman->uzmanlik_alanlari) > 0)
                                <div class="flex flex-wrap gap-1.5 mb-4">
                                    @foreach(array_slice($danisman->uzmanlik_alanlari, 0, 3) as $alan)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700">{{ $alan }}</span>
                                    @endforeach
                                    @if(count($danisman->uzmanlik_alanlari) > 3)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">+{{ count($danisman->uzmanlik_alanlari) - 3 }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Footer --}}
                            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                                @if($danisman->instagram_profil || $danisman->linkedin_profil || $danisman->whatsapp_numara)
                                    <x-frontend.danisman-social-links :danisman="$danisman" size="sm" variant="minimal" />
                                @else
                                    <div></div>
                                @endif

                                <a href="{{ route('frontend.danismanlar.show', $danisman->id) }}"
                                   style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.45rem 1rem;background:linear-gradient(135deg,#0A1628,#0d2044);color:#fff;font-weight:700;font-size:0.8rem;border-radius:0.5rem;text-decoration:none;transition:all 0.2s;"
                                   onmouseover="this.style.background='linear-gradient(135deg,#0d2044,#004ac6)'"
                                   onmouseout="this.style.background='linear-gradient(135deg,#0A1628,#0d2044)'">
                                    Profil
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- AI Danışman Kartı --}}
                <div class="danisman-card bg-white rounded-2xl shadow-md border border-dashed border-blue-300 overflow-hidden">
                    <div class="relative h-60 flex items-center justify-center" style="background:linear-gradient(135deg,#0A1628 0%,#1a3a6b 50%,#0d2044 100%);">
                        <div class="text-center px-6">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 mx-auto" style="background:rgba(201,168,76,0.15);">
                                <svg class="w-8 h-8" style="color:rgba(201,168,76,0.85);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11zM9.5 13a4.5 4.5 0 00-4.5 4.5V19a1 1 0 001 1h10a1 1 0 001-1v-1.5A4.5 4.5 0 0012.5 13h-3z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7h3m-1.5-1.5V9"/>
                                </svg>
                            </div>
                            <p class="text-xs uppercase tracking-widest font-bold" style="color:rgba(201,168,76,0.8);">Yapay Zeka Danışmanı</p>
                        </div>
                    </div>
                    <div class="p-5 flex flex-col gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Yalıhan Sanal Danışman</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">24/7 hizmet veren AI destekli asistanımız ile portföy önerileri alın, süreçleri hızlandırın.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">
                                <span class="material-symbols-outlined text-[13px]">bolt</span> Anlık Yanıt
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-50 text-purple-700 font-semibold">
                                <span class="material-symbols-outlined text-[13px]">language</span> Çok Dilli
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-1.5 text-sm text-slate-500">
                                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                                <span>AI Destekli</span>
                            </div>
                            <a href="{{ url('/ai/explore') }}"
                               style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.45rem 1rem;background:linear-gradient(135deg,#0A1628,#0d2044);color:#fff;font-weight:700;font-size:0.8rem;border-radius:0.5rem;text-decoration:none;transition:all 0.2s;"
                               onmouseover="this.style.background='linear-gradient(135deg,#0d2044,#004ac6)'"
                               onmouseout="this.style.background='linear-gradient(135deg,#0A1628,#0d2044)'">
                                Görüşmeye Başla
                                <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-12">
                {{ $danismanlar->links('vendor.pagination.tailwind') }}
            </div>

        @else
            {{-- Boş Durum --}}
            <div class="text-center py-20">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-6" style="background:linear-gradient(135deg,#0A1628,#0d2044);">
                    <span class="material-symbols-outlined text-[36px]" style="color:rgba(201,168,76,0.8);">person_off</span>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Danışman Bulunamadı</h3>
                <p class="text-slate-500 mb-6 max-w-md mx-auto">Arama kriterlerinize uygun danışman bulunamadı. Lütfen filtreleri değiştirerek tekrar deneyin.</p>
                <a href="{{ route('frontend.danismanlar.index') }}"
                   style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.5rem;background:linear-gradient(135deg,#0A1628,#0d2044);color:#fff;font-weight:700;font-size:0.875rem;border-radius:0.625rem;text-decoration:none;"
                   onmouseover="this.style.background='linear-gradient(135deg,#0d2044,#004ac6)'"
                   onmouseout="this.style.background='linear-gradient(135deg,#0A1628,#0d2044)'">
                    <span class="material-symbols-outlined text-[18px]">undo</span>
                    Filtreleri Temizle
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
