@extends('layouts.frontend')

@push('styles')
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Custom scrollbar for checklist areas */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .form-checkbox:checked {
            background-color: #2563EB;
            border-color: #2563EB;
        }
    </style>
@endpush

@section('title', 'İlanlar - Yalıhan Emlak')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900 pb-20">

        {{-- Minimal Hero --}}
        <div class="bg-blue-700 dark:bg-blue-900 text-white py-12 relative overflow-hidden">
             <div class="absolute inset-0 bg-black opacity-10"></div>
            <div class="container mx-auto px-4 lg:px-8 relative z-10">
                <nav class="flex mb-4 text-sm text-blue-200">
                    <a href="/" class="hover:text-white transition-colors">Ana Sayfa</a>
                    <span class="mx-2">/</span>
                    <span class="text-white">İlanlar</span>
                </nav>
                <h1 class="text-4xl font-bold tracking-tight">Emlak İlanları</h1>
                <p class="text-blue-100 mt-2 max-w-2xl text-lg opacity-90">Bodrum'un en seçkin portföyü içerisinde arama yapın.</p>
            </div>
        </div>

        <div class="container mx-auto px-4 lg:px-8 mt-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                {{-- LEFT SIDEBAR FILTERS --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 sticky top-24 border border-gray-100 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-6">
                             <h3 class="font-bold text-gray-900 dark:text-white text-lg dark:text-slate-100">Filtreler</h3>
                             <a href="{{ route('ilanlar.index') }}" class="text-xs text-blue-600 hover:underline">Temizle</a>
                        </div>

                        <form action="{{ route('ilanlar.index') }}" method="GET" id="filterForm">

                            {{-- Keyword --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Anahtar Kelime</label>
                                <div class="relative">
                                    <input type="text" name="q" value="{{ request('q') }}" placeholder="İlan no, semt..."
                                           class="w-full h-11 pl-10 pr-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow dark:bg-slate-900 dark:border-slate-700">
                                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Offer Type (Main Switch) --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">İlan Türü</label>
                                <select name="yayin_tipi" class="w-full h-11 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer dark:bg-slate-900 dark:border-slate-700" onchange="this.form.submit()">
                                    <option value="">Tümü</option>
                                    <option value="satilik" {{ request('yayin_tipi') == 'satilik' ? 'selected' : '' }}>Satılık</option>
                                    <option value="kiralik" {{ request('yayin_tipi') == 'kiralik' ? 'selected' : '' }}>Kiralık</option>
                                </select>
                            </div>

                            {{-- Property Type --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Emlak Tipi</label>
                                <select name="kategori_id" class="w-full h-11 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer dark:bg-slate-900 dark:border-slate-700">
                                    <option value="">Tümü</option>
                                    @php
                                         $categories = \App\Models\IlanKategori::anaKategoriler()->active()->orderBy('name')->get();
                                    @endphp
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('kategori_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Location (Hierarchy) --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Bölge (İlçe)</label>
                                <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar border border-gray-100 dark:border-slate-800 rounded-lg p-3 bg-gray-50 dark:bg-gray-700/50 dark:bg-slate-900">
                                    @php
                                        // Cache-friendly way would be better, but direct query is ok for now
                                        $districts = \App\Models\Ilce::whereHas('ilanlar', function($q){
                                            $q->active();
                                        })
                                        ->select('id', 'ilce_adi')
                                        ->distinct()
                                        ->orderBy('ilce_adi')
                                        ->get();
                                    @endphp

                                    @if($districts->isEmpty())
                                        <p class="text-xs text-gray-400 italic">Lokasyon bulunamadı.</p>
                                    @endif

                                    @foreach($districts as $district)
                                        <label class="flex items-center space-x-3 cursor-pointer group p-1 hover:bg-white dark:hover:bg-gray-600 rounded transition-colors">
                                            <input type="checkbox" name="ilce[]" value="{{ $district->ilce_adi }}"
                                                {{ in_array($district->ilce_adi, (array)request('ilce')) ? 'checked' : '' }}
                                                class="form-checkbox text-blue-600 rounded border-gray-300 focus:ring-blue-500 h-4 w-4 transition duration-150 ease-in-out">
                                            <span class="text-sm text-gray-700 dark:text-slate-200 group-hover:text-blue-600 dark:text-slate-300">{{ $district->ilce_adi }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Bedrooms & Bathrooms --}}
                            <div class="grid grid-cols-2 gap-3 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Oda</label>
                                    <select name="oda_sayisi" class="w-full h-10 px-2 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                        <option value="">Seçiniz</option>
                                        @foreach(range(1, 6) as $i)
                                            <option value="{{ $i }}" {{ request('oda_sayisi') == $i ? 'selected' : '' }}>{{ $i }}+</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Banyo</label>
                                     <select name="banyo_sayisi" class="w-full h-10 px-2 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                        <option value="">Seçiniz</option>
                                        @foreach(range(1, 4) as $i)
                                            <option value="{{ $i }}" {{ request('banyo_sayisi') == $i ? 'selected' : '' }}>{{ $i }}+</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Price Range --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Fiyat Aralığı</label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="min_fiyat" value="{{ request('min_fiyat') }}" placeholder="Min" class="w-1/2 h-10 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                    <span class="text-gray-400 font-light">-</span>
                                    <input type="number" name="max_fiyat" value="{{ request('max_fiyat') }}" placeholder="Max" class="w-1/2 h-10 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                </div>
                            </div>

                            {{-- Size Range --}}
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">Metrekare (m²)</label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="min_m2" value="{{ request('min_m2') }}" placeholder="Min" class="w-1/2 h-10 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                    <span class="text-gray-400 font-light">-</span>
                                    <input type="number" name="max_m2" value="{{ request('max_m2') }}" placeholder="Max" class="w-1/2 h-10 px-3 rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-900 dark:border-slate-700">
                                </div>
                            </div>

                           {{-- Features (Checkboxes) --}}
                           <div class="mb-8 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg border border-gray-100 dark:border-slate-800 dark:bg-slate-900">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3 ml-1">Özellikler</label>
                                <div class="space-y-3">
                                    {{-- Common Features mapped to DB logic or scope --}}
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="ozellikler[]" value="havuz" {{ in_array('havuz', (array)request('ozellikler')) ? 'checked' : '' }} class="form-checkbox text-blue-600 rounded border-gray-300 focus:ring-blue-500 h-4 w-4">
                                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Havuzlu</span>
                                    </label>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="ozellikler[]" value="deniz_manzarasi" {{ in_array('deniz_manzarasi', (array)request('ozellikler')) ? 'checked' : '' }} class="form-checkbox text-blue-600 rounded border-gray-300 focus:ring-blue-500 h-4 w-4">
                                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Deniz Manzaralı</span>
                                    </label>
                                     <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="ozellikler[]" value="otopark" {{ in_array('otopark', (array)request('ozellikler')) ? 'checked' : '' }} class="form-checkbox text-blue-600 rounded border-gray-300 focus:ring-blue-500 h-4 w-4">
                                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Otopark</span>
                                    </label>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="ozellikler[]" value="bahce" {{ in_array('bahce', (array)request('ozellikler')) ? 'checked' : '' }} class="form-checkbox text-blue-600 rounded border-gray-300 focus:ring-blue-500 h-4 w-4">
                                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Bahçeli</span>
                                    </label>
                                </div>
                           </div>

                            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-md hover:shadow-lg transform active:scale-95 flex items-center justify-center gap-2 dark:shadow-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Sonuçları Filtrele
                            </button>
                        </form>
                    </div>
                </div>

                {{-- RIGHT MAIN CONTENT --}}
                <div class="lg:col-span-3">

                    {{-- Top Bar --}}
                    <div class="flex flex-wrap items-center justify-between mb-6 bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 dark:shadow-none">
                        <div class="flex items-center gap-2">
                             <h2 class="text-lg font-bold text-gray-800 dark:text-white dark:text-slate-200">
                                {{ isset($totalCount) ? $totalCount : ($ilanlar ? $ilanlar->total() : 0) }} İlan
                            </h2>
                            <span class="text-gray-400 text-sm">Listeleniyor</span>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Sıralama:</span>
                            <select onchange="window.location.href=this.value" class="h-10 pl-3 pr-8 text-sm rounded-lg border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-slate-200 focus:ring-blue-500 cursor-pointer dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700">
                                <option value="{{ request()->fullUrlWithQuery(['sirala' => 'yeni']) }}" {{ request('sirala') == 'yeni' ? 'selected' : '' }}>En Yeni</option>
                                <option value="{{ request()->fullUrlWithQuery(['sirala' => 'fiyat_artan']) }}" {{ request('sirala') == 'fiyat_artan' ? 'selected' : '' }}>Fiyat (Artan)</option>
                                <option value="{{ request()->fullUrlWithQuery(['sirala' => 'fiyat_azalan']) }}" {{ request('sirala') == 'fiyat_azalan' ? 'selected' : '' }}>Fiyat (Azalan)</option>
                                <option value="{{ request()->fullUrlWithQuery(['sirala' => 'metrekare_azalan']) }}" {{ request('sirala') == 'metrekare_azalan' ? 'selected' : '' }}>En Geniş (m²)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Listings Grid --}}
                    @if (isset($ilanlar) && $ilanlar->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach ($ilanlar as $ilan)
                                {{-- Simple Property Card Component Re-use --}}
                                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group border border-gray-100 dark:border-slate-800 h-full flex flex-col">
                                    {{-- Image Section --}}
                                    <div class="relative h-60 overflow-hidden">
                                        <a href="{{ route('ilanlar.show', $ilan->slug) }}" class="block w-full h-full">
                                            @if($ilan->kapak_fotografi)
                                                <img src="{{ $ilan->kapak_fotografi }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                            @else
                                                <img src="https://source.unsplash.com/random/400x300?house" alt="Placeholder" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 filter grayscale">
                                            @endif

                                            {{-- Gradient Overlay --}}
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                                        </a>

                                        {{-- Badges --}}
                                        <div class="absolute top-3 left-3 flex gap-2">
                                            <span class="bg-white/95 backdrop-blur-sm text-gray-900 text-xs font-bold px-3 py-1.5 rounded-md uppercase tracking-wider shadow-sm dark:text-slate-100 dark:shadow-none dark:bg-slate-900/95 dark:text-white">
                                                {{ $ilan->yayin_tipi == 'kiralik' ? 'Kiralık' : 'Satılık' }}
                                            </span>
                                            @if($ilan->one_cikan)
                                                <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1.5 rounded-md uppercase tracking-wider shadow-sm flex items-center gap-1 dark:shadow-none">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    Fırsat
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Price Tag (Bottom Right) --}}
                                        <div class="absolute bottom-3 right-3">
                                            <span class="text-xl font-bold text-white drop-shadow-md">
                                                {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Content Section --}}
                                    <div class="p-5 flex-1 flex flex-col">
                                        <div class="mb-4 flex-1">
                                             <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-2 leading-tight hover:text-blue-600 transition-colors mb-2 dark:text-slate-100">
                                                <a href="{{ route('ilanlar.show', $ilan->slug) }}">{{ $ilan->baslik }}</a>
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                {{ $ilan->ilce->ilce_adi ?? '' }} / {{ $ilan->il->il_adi ?? 'Bodrum' }}
                                            </p>
                                        </div>

                                        {{-- Key Specs --}}
                                        <div class="grid grid-cols-3 gap-2 py-3 border-t border-gray-100 dark:border-slate-800">
                                            <div class="text-center">
                                                <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-0.5">Oda</span>
                                                <span class="block text-gray-800 dark:text-slate-200 font-semibold">{{ $ilan->oda_sayisi ?? '-' }}</span>
                                            </div>
                                             <div class="text-center border-l border-gray-100 dark:border-slate-800">
                                                <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-0.5">Banyo</span>
                                                <span class="block text-gray-800 dark:text-slate-200 font-semibold">{{ $ilan->banyo_sayisi ?? '-' }}</span>
                                            </div>
                                             <div class="text-center border-l border-gray-100 dark:border-slate-800">
                                                <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-0.5">Alan</span>
                                                <span class="block text-gray-800 dark:text-slate-200 font-semibold">{{ $ilan->net_m2 ?? '-' }} m²</span>
                                            </div>
                                        </div>

                                        {{-- Footer / Agent --}}
                                        <div class="pt-4 mt-auto border-t border-gray-100 dark:border-slate-800 flex items-center justify-between">
                                             <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                                                     @if($ilan->danisman && $ilan->danisman->profile_photo_path)
                                                        <img src="{{ '/storage/'.$ilan->danisman->profile_photo_path }}" class="w-full h-full object-cover">
                                                     @else
                                                        <svg class="w-full h-full text-gray-400 p-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                                     @endif
                                                </div>
                                                <div class="text-xs">
                                                    <p class="font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $ilan->danisman->name ?? 'Yalıhan' }}</p>
                                                    <p class="text-gray-500">Danışman</p>
                                                </div>
                                             </div>

                                             <a href="{{ route('ilanlar.show', $ilan->slug) }}" class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center gap-1 group-hover:gap-2 transition-all">
                                                 Detaylar
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                             </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                         {{-- Pagination --}}
                        <div class="mt-12 flex justify-center">
                            {{ $ilanlar->withQueryString()->links() }}
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow p-12 text-center border border-gray-100 dark:border-slate-800 dark:shadow-none">
                            <div class="w-20 h-20 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 dark:text-slate-200">Aradığınız kriterlere uygun ilan bulunamadı.</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">Farklı anahtar kelimeler deneyebilir veya filtreleri temizleyerek daha geniş bir arama yapabilirsiniz.</p>
                            <a href="{{ route('ilanlar.index') }}" class="inline-block mt-6 px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold shadow-lg transition-transform hover:scale-105">Filtreleri Temizle</a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection
