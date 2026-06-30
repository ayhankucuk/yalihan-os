@extends('layouts.frontend')

@section('content')

    {{-- ═══════════════════════════════════════════════════════════
         HERO — Arama Tabları
    ════════════════════════════════════════════════════════════════ --}}
    <x-yaliihan.hero-section
        title="Bodrum'da Hayalinizdeki Mülkü Bulun"
        subtitle="Yalıkavak'tan Bodrum Merkez'e, Ege kıyılarının en seçkin gayrimenkul portföyü"
        :locations="$locations"
        :property-types="$propertyTypes"
    >
        <x-slot name="search">
            <x-yaliihan.hero-search-tabs
                :locations="$locations"
                :property-types="$propertyTypes"
                :villa-locations="$villaLocations"
            />
        </x-slot>
    </x-yaliihan.hero-section>

    {{-- ═══════════════════════════════════════════════════════════
         İSTATİSTİKLER
    ════════════════════════════════════════════════════════════════ --}}
    <x-home.statistics
        :active-listings="$stats['active_listings']"
        :experience-years="$stats['experience_years']"
        :happy-customers="$stats['happy_customers']"
    />

    {{-- ═══════════════════════════════════════════════════════════
         ÖNCÜ İLANLAR
    ════════════════════════════════════════════════════════════════ --}}
    <x-home.featured-properties :properties="$featuredProperties" />

    {{-- ═══════════════════════════════════════════════════════════
         SATLIK KONUTLAR
    ════════════════════════════════════════════════════════════════ --}}
    @if($satılıkKonut->count())
    <section class="py-16 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Satılık Konutlar</h2>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Bodrum ve çevresinde satılık villa, daire ve müstakil evler</p>
                </div>
                <a href="{{ route('ilanlar.index', ['yayin_tipi' => 'satilik']) }}"
                   class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                    Tümünü Gör →
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($satılıkKonut as $ilan)
                    <x-yaliihan.property-card :ilan="$ilan" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         YAZLIK & KİRALIK
    ════════════════════════════════════════════════════════════════ --}}
    @if($yazlıkIlanları->count())
    <section class="py-16 bg-gradient-to-b from-sky-50 to-white dark:from-sky-950 dark:to-gray-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Yazlık & Kiralık</h2>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Sezonluk kiralık yazlıklar, tatil villalar</p>
                </div>
                <a href="{{ route('ilanlar.index', ['kategori' => 'yazlik-kiralama']) }}"
                   class="text-sm font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300 transition-colors">
                    Tümünü Gör →
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($yazlıkIlanları as $ilan)
                    <x-ilan-card-rental :ilan="$ilan" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         ARSA & PARSEL
    ════════════════════════════════════════════════════════════════ --}}
    @if($arsaIlanları->count())
    <section class="py-16 bg-amber-50 dark:bg-amber-950/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Arsa & Parsel</h2>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Bodrum bölgesinde yatırımlık arsa ve arazi fırsatları</p>
                </div>
                <a href="{{ route('ilanlar.index', ['kategori' => 'arsa-arazi']) }}"
                   class="text-sm font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors">
                    Tümünü Gör →
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($arsaIlanları as $ilan)
                    <x-yaliihan.property-card :ilan="$ilan" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         POPÜLER MAHALLELER
    ════════════════════════════════════════════════════════════════ --}}
    @if($populerMahalleler->count())
    <section class="py-16 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Popüler Bölgeler</h2>
                <p class="mt-3 text-gray-500 dark:text-gray-400">En çok ilan bulunan Bodrum mahalleleri</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($populerMahalleler as $mahalle)
                <a href="{{ route('ilanlar.index', ['mahalle' => $mahalle->mahalle_adi]) }}"
                   class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 p-5 text-white hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                    <div class="font-semibold text-sm mb-1 group-hover:underline">{{ $mahalle->mahalle_adi }}</div>
                    @if($mahalle->ilce)
                    <div class="text-xs opacity-75">{{ $mahalle->ilce->ilce_adi }}</div>
                    @endif
                    <div class="text-xs opacity-60 mt-2">{{ $mahalle->ilan_sayisi }} ilan</div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         YURT DIŞI İLANLAR
    ════════════════════════════════════════════════════════════════ --}}
    @if($yurtDışıIlanları->count())
    <section class="py-16 bg-gradient-to-b from-indigo-50 to-white dark:from-indigo-950/30 dark:to-gray-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Yurt Dışı İlanlar</h2>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        {{ count($yurtDisiUlkeler) }} ülkede seçkin gayrimenkul fırsatları
                    </p>
                </div>
                <a href="{{ route('ilanlar.index', ['kategori' => 'yurt-disi']) }}"
                   class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                    Tümünü Gör →
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($yurtDışıIlanları as $ilan)
                    <x-yaliihan.property-card :ilan="$ilan" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         NEDEN YALIHAN — WHY CHOOSE US
    ════════════════════════════════════════════════════════════════ --}}
    <x-home.why-choose-us />

    {{-- ═══════════════════════════════════════════════════════════
         İLETİŞİM / CTA
    ════════════════════════════════════════════════════════════════ --}}
    <x-home.contact-section />

@endsection
