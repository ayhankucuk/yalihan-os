@extends('layouts.frontend')

@section('title', 'Danışmanlarımız - Yalıhan Emlak')

@section('content')
<div class="relative min-h-screen">
    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6">
                    👥 Danışmanlarımız
                </h1>
                <p class="text-xl sm:text-2xl text-white/90 max-w-3xl mx-auto mb-8">
                    Deneyimli emlak danışmanlarımızla hayallerinizdeki gayrimenkule ulaşın
                </p>
                
                {{-- İstatistikler --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-12 max-w-4xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <div class="text-3xl font-bold text-white mb-2">{{ $stats['total'] ?? 0 }}</div>
                        <div class="text-white/80 text-sm">Toplam Danışman</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <div class="text-3xl font-bold text-white mb-2">{{ $stats['aktif'] ?? 0 }}</div>
                        <div class="text-white/80 text-sm">Aktif Danışman</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <div class="text-3xl font-bold text-white mb-2">{{ $stats['toplam_ilan'] ?? 0 }}</div>
                        <div class="text-white/80 text-sm">Toplam İlan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-10">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
            <form method="GET" action="{{ route('frontend.danismanlar.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Arama --}}
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Arama
                    </label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="{{ request('search') }}"
                           placeholder="İsim, e-posta, ünvan..."
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                </div>

                {{-- Departman --}}
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Departman
                    </label>
                    <select name="department" 
                            id="department"
                            style="color-scheme: light dark;"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">Tüm Departmanlar</option>
                        @foreach($departments ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ request('department') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pozisyon --}}
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Pozisyon
                    </label>
                    <select name="position" 
                            id="position"
                            style="color-scheme: light dark;"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">Tüm Pozisyonlar</option>
                        @foreach($positions ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ request('position') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Aktiflik Durumu --}}
                <div>
                    <label for="aktiflik_durumu" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Durum
                    </label>
                    <select name="aktiflik_durumu" 
                            id="aktiflik_durumu"
                            style="color-scheme: light dark;"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">Tüm Durumlar</option>
                        <option value="aktif" {{ request('aktiflik_durumu') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="pasif" {{ request('aktiflik_durumu') == 'pasif' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                {{-- Sıralama --}}
                <div class="lg:col-span-4">
                    <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Sıralama
                    </label>
                    <select name="sort" 
                            id="sort"
                            style="color-scheme: light dark;"
                            class="w-full md:w-auto px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                        <option value="name_asc" {{ request('sort', 'name_asc') == 'name_asc' ? 'selected' : '' }}>İsme Göre (A-Z)</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>İsme Göre (Z-A)</option>
                        <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Yeni Eklenenler</option>
                        <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Eski Eklenenler</option>
                    </select>
                </div>

                {{-- Butonlar --}}
                <div class="lg:col-span-4 flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:shadow-none">
                        <i class="fas fa-search"></i>
                        Filtrele
                    </button>
                    <a href="{{ route('frontend.danismanlar.index') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-slate-900 dark:text-slate-300">
                        <i class="fas fa-undo"></i>
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
                        // Durum değerini belirle
                        $statusValue = $danisman->status_text ?? null;
                        if (!$statusValue) {
                            $statusValue = $danisman->aktiflik_durumu ? 'aktif' : 'pasif';
                        }
                    @endphp
                    
                    <div class="group bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-200 dark:border-slate-800 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 dark:shadow-none dark:border-slate-700">
                        {{-- Fotoğraf --}}
                        <div class="relative h-64 bg-gradient-to-br from-blue-400 to-purple-500 overflow-hidden">
                            @if($danisman->profile_photo_path)
                                <img src="{{ asset('storage/' . $danisman->profile_photo_path) }}" 
                                     alt="{{ $danisman->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="w-32 h-32 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center dark:bg-slate-900/20">
                                        <i class="fas fa-user text-5xl text-white"></i>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Status Badge --}}
                            @if($statusValue === 'aktif')
                                <div class="absolute top-4 right-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                                        <span class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse dark:bg-slate-900"></span>
                                        Aktif
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- İçerik --}}
                        <div class="p-6">
                            {{-- İsim ve Ünvan --}}
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1 line-clamp-1 dark:text-slate-100">
                                {{ $danisman->name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ $danisman->baslik ?? 'Emlak Danışmanı' }}
                            </p>

                            {{-- Pozisyon ve Departman --}}
                            @if($danisman->position || $danisman->department)
                                <div class="space-y-1 mb-3">
                                    @if($danisman->position)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-briefcase mr-1"></i>
                                            {{ config('danisman.positions.' . $danisman->position, $danisman->position) }}
                                        </div>
                                    @endif
                                    @if($danisman->department)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-building mr-1"></i>
                                            {{ config('danisman.departments.' . $danisman->department, $danisman->department) }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Telefon Numarası --}}
                            @if($danisman->telefon)
                                <div class="mb-3">
                                    <a href="tel:{{ $danisman->telefon }}" 
                                       class="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors duration-200">
                                        <i class="fas fa-phone text-xs"></i>
                                        <span>{{ $danisman->telefon }}</span>
                                    </a>
                                </div>
                            @endif

                            {{-- Uzmanlık Alanları --}}
                            @if($danisman->uzmanlik_alanlari && count($danisman->uzmanlik_alanlari) > 0)
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach(array_slice($danisman->uzmanlik_alanlari, 0, 3) as $alan)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            {{ $alan }}
                                        </span>
                                    @endforeach
                                    @if(count($danisman->uzmanlik_alanlari) > 3)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 dark:bg-slate-900">
                                            +{{ count($danisman->uzmanlik_alanlari) - 3 }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            {{-- İletişim ve Sosyal Medya --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                {{-- Sosyal Medya Linkleri --}}
                                @if($danisman->instagram_profil || $danisman->linkedin_profil || $danisman->whatsapp_numara)
                                    <div class="flex items-center gap-2">
                                        <x-frontend.danisman-social-links :danisman="$danisman" size="sm" variant="minimal" />
                                    </div>
                                @else
                                    <div></div>
                                @endif

                                {{-- Detay Butonu --}}
                                <a href="{{ route('frontend.danismanlar.show', $danisman->id) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-all duration-200 hover:shadow-md active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Profil
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Sanal Danışman Kartı --}}
                <div class="group bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-dashed border-blue-300 dark:border-blue-700 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 dark:shadow-none">
                    <div class="relative h-64 bg-gradient-to-br from-indigo-500 via-blue-500 to-purple-500 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/20"></div>
                        <div class="relative flex flex-col items-center justify-center text-center px-6">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center mb-4 dark:bg-slate-900/20">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11zM9.5 13a4.5 4.5 0 00-4.5 4.5V19a1 1 0 001 1h10a1 1 0 001-1v-1.5A4.5 4.5 0 0012.5 13h-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7h3m-1.5-1.5V9"/></svg>
                            </div>
                            <p class="text-sm uppercase tracking-wide text-white/80">Yapay Zeka Danışmanı</p>
                        </div>
                    </div>
                    <div class="p-6 flex flex-col gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">Yalıhan Sanal Danışman</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">24/7 hizmet veren AI destekli asistanımız ile portföy önerileri alın, süreçleri hızlandırın.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"><i class="fas fa-bolt"></i> Anlık Yanıt</span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"><i class="fas fa-globe"></i> Çok Dilli</span>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <div class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-300">
                                <i class="fas fa-robot"></i>
                                <span>AI Destekli</span>
                            </div>
                            <a href="{{ url('/ai/explore') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-all duration-200 hover:shadow-md active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Görüşmeye Başla
                                <i class="fas fa-arrow-right text-xs"></i>
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
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-slate-900 rounded-full mb-6">
                    <i class="fas fa-user-slash text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    Danışman Bulunamadı
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Arama kriterlerinize uygun danışman bulunamadı. Lütfen filtreleri değiştirerek tekrar deneyin.
                </p>
                <a href="{{ route('frontend.danismanlar.index') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all duration-200 hover:shadow-md active:scale-95">
                    <i class="fas fa-undo"></i>
                    Filtreleri Temizle
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

