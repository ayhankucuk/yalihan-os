@extends('layouts.frontend')

@section('title', 'Portföyümüz - Yalıhan Emlak')

@section('content')
    <div class="bg-gray-50 dark:bg-slate-900 min-h-screen font-sans">
        {{-- Hero Header --}}
        <div class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center">
                <h1 class="text-3xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4 tracking-tight dark:text-slate-100">
                    Hayalinizdeki Evi Keşfedin
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 dark:text-slate-200 max-w-2xl mx-auto">
                    Bodrum'un en seçkin portföyleri, sizin için özenle seçildi.
                </p>
            </div>
        </div>

        {{-- Filter Section (Accessible & Clean) --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 p-6 sm:p-8">
                <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- İlce/Bölge --}}
                    <div>
                        <label for="location" class="block text-sm font-bold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Bölge Seçin</label>
                        <select id="location" name="location" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-base text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow dark:bg-slate-900 dark:text-slate-100">
                            <option value="">Tüm Bölgeler</option>
                            <option value="yalikavak">Yalıkavak</option>
                            <option value="bodrum-merkez">Bodrum Merkez</option>
                            <option value="gumbet">Gümbet</option>
                            <option value="turgutreis">Turgutreis</option>
                        </select>
                    </div>

                    {{-- Emlak Tipi --}}
                    <div>
                        <label for="type" class="block text-sm font-bold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Emlak Tipi</label>
                        <select id="type" name="type" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-base text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow dark:bg-slate-900 dark:text-slate-100">
                            <option value="">Tümü</option>
                            <option value="villa">Müstakil Villa</option>
                            <option value="daire">Daire</option>
                            <option value="arsa">Arsa</option>
                        </select>
                    </div>

                    {{-- Fiyat Aralığı --}}
                    <div>
                        <label for="price_range" class="block text-sm font-bold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Fiyat Aralığı</label>
                        <select id="price_range" name="price" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-base text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow dark:bg-slate-900 dark:text-slate-100">
                            <option value="">Fark etmez</option>
                            <option value="0-5000000">5 Milyon TL altı</option>
                            <option value="5000000-15000000">5 - 15 Milyon TL</option>
                            <option value="15000000+">15 Milyon TL üzeri</option>
                        </select>
                    </div>

                    {{-- Search Button --}}
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg px-6 py-3 rounded-lg shadow-md transition-transform transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:shadow-none">
                            İlan Ara
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Listings Grid --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex justify-between items-center mb-8 border-b border-gray-200 dark:border-slate-800 pb-4 dark:border-slate-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    Öne Çıkan Fırsatlar
                </h2>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $stats['active_properties'] ?? 0 }} İlan Bulundu</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse ($properties as $ilan)
                    {{-- Card Item --}}
                    <article class="flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group dark:shadow-none dark:border-slate-700">
                        {{-- Image Wrapper --}}
                        <div class="relative h-64 sm:h-72 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            <a href="{{ route('ilanlar.show', $ilan->id) }}" class="block h-full w-full">
                                @php
                                    $image = $ilan->fotograflar->first()
                                        ? \Illuminate\Support\Facades\Storage::url($ilan->fotograflar->first()->dosya_yolu)
                                        : asset('images/default-property.jpg');
                                @endphp
                                <img src="{{ $image }}"
                                     alt="{{ $ilan->baslik }}"
                                     class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-in-out"
                                     loading="lazy">
                            </a>
                            <div class="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold uppercase tracking-wide px-3 py-1.5 rounded-full shadow-sm dark:shadow-none">
                                {{ $ilan->yayinTipi->yayin_tipi ?? 'İlan' }}
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                <div class="text-white font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $ilan->ilce->ilce_adi ?? '' }}, {{ $ilan->mahalle->mahalle_adi ?? '' }}
                                </div>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex flex-col flex-1 p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 leading-tight group-hover:text-blue-600 transition-colors dark:text-slate-100">
                                <a href="{{ route('ilanlar.show', $ilan->id) }}">{{ $ilan->baslik }}</a>
                            </h3>

                            {{-- Features --}}
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-slate-200 mb-6">
                                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-md dark:bg-slate-900">
                                    <span class="font-semibold">{{ $ilan->brut_m2 ?? $ilan->net_m2 }}</span> m²
                                </div>
                                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-md dark:bg-slate-900">
                                    <span class="font-semibold">{{ $ilan->oda_sayisi }}</span> Oda
                                </div>
                                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-md dark:bg-slate-900">
                                    {{ $ilan->bina_yasi == 0 ? 'Yeni' : $ilan->bina_yasi . ' Yaş' }}
                                </div>
                            </div>

                            {{-- Price & Actions --}}
                            <div class="mt-auto pt-4 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-0.5">Satış Fiyatı</p>
                                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">
                                        {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('ilanlar.show', $ilan->id) }}" class="flex-1 sm:flex-none text-center bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-semibold py-2.5 px-4 rounded-lg transition-colors dark:bg-slate-900 dark:text-slate-200">
                                        Detaylar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full text-center py-20">
                        <p class="text-gray-500">Henüz aktif ilan bulunmamaktadır.</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-16">
                {{ $properties->links() }}
            </div>

            {{-- Need Help / Trust Section --}}
            <div class="mt-20 bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-8 sm:p-12 text-center border border-blue-100 dark:border-blue-800">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Aradığınız Evi Bulamadınız mı?</h3>
                <p class="text-lg text-gray-600 dark:text-slate-200 mb-8 max-w-2xl mx-auto">
                    Uzman danışmanlarımız, kriterlerinize en uygun portföyleri sizin için araştırsın.
                    Güvenilir hizmet ve şeffaf süreç garantisiyle yanınızdayız.
                </p>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg px-8 py-4 rounded-xl shadow-lg transition-transform hover:-translate-y-1">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    Danışmanla Görüşün
                </a>
            </div>
        </section>
    </div>
@endsection
