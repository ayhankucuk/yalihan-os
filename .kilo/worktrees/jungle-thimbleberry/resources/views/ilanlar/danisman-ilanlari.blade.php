@extends('layouts.frontend')

@section('title', $danisman->name . ' - Diğer İlanları')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('home') }}" class="hover:text-primary-600">Ana Sayfa</a></li>
                <li><span class="material-symbols-outlined text-xs">chevron_right</span></li>
                <li><a href="{{ route('ilanlar.index') }}" class="hover:text-primary-600">İlanlar</a></li>
                <li><span class="material-symbols-outlined text-xs">chevron_right</span></li>
                <li class="text-gray-900 dark:text-slate-100 dark:text-white">{{ $danisman->name }} - İlanları</li>
            </ol>
        </nav>

        <!-- Danışman Bilgileri -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-gray-200 dark:border-slate-800 p-6 mb-8 dark:border-slate-700">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        @php
                            $avatarUrl = $danisman->profile_photo_path
                                ? asset('storage/' . $danisman->profile_photo_path)
                                : ($danisman->avatar ? asset('storage/' . $danisman->avatar) : null);
                        @endphp
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $danisman->name }}"
                                class="w-16 h-16 rounded-full object-cover border-2 border-white">
                        @else
                            <span class="material-symbols-outlined text-white text-2xl">person</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $danisman->name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ $danisman->title ?? 'Emlak Danışmanı' }}</p>
                        @if ($danisman->phone_number ?? $danisman->telefon)
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                <span class="material-symbols-outlined mr-1">call</span>
                                <a href="tel:{{ $danisman->phone_number ?? $danisman->telefon }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $danisman->phone_number ?? $danisman->telefon }}
                                </a>
                            </p>
                        @endif
                        @if ($danisman->email)
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                <span class="material-symbols-outlined mr-1">mail</span>
                                <a href="mailto:{{ $danisman->email }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $danisman->email }}
                                </a>
                            </p>
                        @endif

                        {{-- Social Media Links --}}
                        @php
                            $hasSocialMedia = !empty($danisman->instagram_profile) ||
                                             !empty($danisman->linkedin_profile) ||
                                             !empty($danisman->facebook_profile) ||
                                             !empty($danisman->twitter_profile) ||
                                             !empty($danisman->youtube_channel) ||
                                             !empty($danisman->tiktok_profile) ||
                                             !empty($danisman->whatsapp_number) ||
                                             !empty($danisman->telegram_username) ||
                                             !empty($danisman->website);
                        @endphp
                        @if($hasSocialMedia)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Sosyal Medya</p>
                                <x-frontend.danisman-social-links :danisman="$danisman" size="sm" />
                            </div>
                        @endif
                    </div>
                </div>
                <div class="md:ml-auto text-center md:text-right">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $ilanlar->total() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Aktif İlan</div>
                </div>
            </div>
        </div>

        <!-- İlanlar -->
        @if ($ilanlar->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($ilanlar as $ilan)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow dark:bg-slate-900 dark:shadow-none">
                        <!-- Property Image -->
                        <div class="relative h-48 bg-gray-200">
                            <img src="{{ $ilan->kapak_fotografi_url ?? asset('images/default-property.jpg') }}"
                                alt="{{ $ilan->ilan_basligi }}" class="w-full h-full object-cover">

                            <!-- Property Type Badge -->
                            <span
                                class="absolute top-3 left-3 px-2 py-1 text-xs font-semibold rounded-full shadow-sm
                                {{ $ilan->ilan_turu == 'satilik' || $ilan->yayinlama_tipi == 'Satılık' ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white' : 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white' }}">
                                {{ $ilan->yayinlama_tipi ?? ucfirst($ilan->ilan_turu) }}
                            </span>

                            @if ($ilan->one_cikan)
                                <span
                                    class="absolute top-3 right-3 px-2 py-1 text-xs font-semibold rounded-full bg-orange-500 text-white shadow-sm dark:shadow-none">
                                    Öne Çıkan
                                </span>
                            @endif
                        </div>

                        <!-- Property Details -->
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 dark:text-slate-100 dark:text-white">
                                {{ $ilan->ilan_basligi ?? ($ilan->baslik ?? 'İlan Başlığı') }}
                            </h3>

                            <p class="text-gray-600 text-sm mb-3 flex items-center">
                                <span class="material-symbols-outlined mr-1">location_on</span>
                                {{ $ilan->tam_adres ?? $ilan->adres_mahalle . ', ' . $ilan->adres_ilce . ', ' . $ilan->adres_il }}
                            </p>

                            <div class="flex items-center justify-between mb-3">
                                <x-price-converter :price="$ilan->fiyat" :currency="$ilan->para_birimi ?? 'TRY'" :rentalType="$ilan->kiralama_turu" />
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ucfirst($ilan->emlak_turu) }}
                                </div>
                            </div>

                            <!-- Property Features -->
                            @if ($ilan->oda_sayisi || $ilan->banyo_sayisi || $ilan->net_metrekare)
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                                    @if ($ilan->oda_sayisi)
                                        <span class="flex items-center">
                                            <span class="material-symbols-outlined mr-1">bed</span>
                                            {{ $ilan->oda_sayisi }}
                                        </span>
                                    @endif
                                    @if ($ilan->banyo_sayisi)
                                        <span class="flex items-center">
                                            <span class="material-symbols-outlined mr-1">bathtub</span>
                                            {{ $ilan->banyo_sayisi }}
                                        </span>
                                    @endif
                                    @if ($ilan->net_metrekare)
                                        <span class="flex items-center">
                                            <span class="material-symbols-outlined mr-1">crop_square</span>
                                            {{ $ilan->net_metrekare }}m²
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <!-- View Details Button -->
                            <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                class="block w-full text-center bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors">
                                <span class="material-symbols-outlined mr-2">visibility</span>
                                Detayları Gör
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if ($ilanlar->hasPages())
                <div class="mt-12">
                    {{ $ilanlar->links() }}
                </div>
            @endif
        @else
            <!-- No Results -->
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4 dark:bg-slate-900">
                    <span class="material-symbols-outlined text-gray-400 text-2xl">search</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2 dark:text-slate-100 dark:text-white">Bu danışmana ait status ilan bulunamadı</h3>
                <p class="text-gray-600 mb-6">{{ $danisman->name }} şu anda yayında olan bir ilanı bulunmuyor.</p>
                <a href="{{ route('ilanlar.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <span class="material-symbols-outlined mr-2">arrow_back</span>
                    Tüm İlanlara Dön
                </a>
            </div>
        @endif
    </div>
@endsection
