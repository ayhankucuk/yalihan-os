@extends('admin.layouts.admin')

@section('title', 'Talep Detayı: ' . $talep->id)

@push('styles')
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .detail-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .dark .detail-card {
            background-color: #1f2937;
            /* gray-800 */
        }

        .detail-p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            /* gray-200 */
            font-size: 1.125rem;
            /* text-lg */
            font-weight: 600;
            /* font-semibold */
        }

        .dark .detail-p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 {
            border-bottom-color: #374151;
            /* gray-700 */
        }

        .detail-list {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
        }

        .detail-list dt {
            font-weight: 500;
            /* font-medium */
            color: #6b7280;
            /* gray-500 */
        }

        .dark .detail-list dt {
            color: #9ca3af;
            /* gray-400 */
        }

        .feature-tag {
            display: inline-block;
            background-color: #e0e7ff;
            /* indigo-100 */
            color: #4338ca;
            /* indigo-800 */
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            /* text-sm */
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .dark .feature-tag {
            background-color: #3730a3;
            /* indigo-800 */
            color: #e0e7ff;
            /* indigo-100 */
        }
    </style>
@endpush

@section('content')
    <div class="content-header mb-6">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">
                        <i class="fas fa-clipboard-list mr-2 text-purple-600"></i>
                        Talep Detayı #{{ $talep->id }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        Müşteri talebinin tüm detayları ve statusu.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('admin.talepler.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i>Taleplere Dön
                    </a>
                    <a href="{{ route('admin.talepler.edit', $talep->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <i class="fas fa-edit mr-2"></i>Düzenle
                    </a>
                    <form action="{{ route('admin.talepler.destroy', $talep->id) }}" method="POST"
                        onsubmit="return confirm('Bu talebi silmek istediğinizden emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-trash-alt mr-2"></i>Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        {{-- Yapay Zeka Önerileri - Smart Match Widget --}}
        <div class="mt-6 mb-6">
            <div class="mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100 flex items-center">
                    <i class="fas fa-magic mr-2 text-blue-600 dark:text-blue-400"></i>
                    Yapay Zeka Önerileri
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Bu talebe uygun ilanlar AI tarafından analiz ediliyor...
                </p>
            </div>
            <x-ai.smart-match-widget :talepId="$talep->id" />
        </div>

        <div class="detail-grid">
            <!-- Müşteri ve Talep Bilgileri -->
            <div class="detail-card">
                <h3 class="detail-p-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:text-slate-200 dark:bg-slate-900/50">Talep ve Müşteri Bilgileri</h3>
                <dl class="detail-list">
                    <dt>Müşteri</dt>
                    <dd>
                        @if ($talep->kisi)
                            <a href="{{ route('admin.kisiler.show', $talep->kisi_id) }}"
                                class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $talep->kisi->tam_ad }}
                            </a>
                        @else
                            <span class="text-red-500">İlişkili Müşteri Yok</span>
                        @endif
                    </dd>

                    <dt>Sorumlu Danışman</dt>
                    <dd>{{ $talep->kullanici->name ?? 'Atanmamış' }}</dd>

                    <dt>Talep Tipi</dt>
                    <dd><span class="px-2.5 py-0.5 rounded-full text-sm font-medium {{ $talep->talep_tipi === 'Alım' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : ($talep->talep_tipi === 'Kiralama' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200') }}">{{ $talep->talep_tipi }}</span>
                    </dd>

                    <dt>Durum</dt>
                    <dd><span class="px-2.5 py-0.5 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-slate-200 dark:bg-slate-900">{{ ucfirst($talep->talep_durumu?->value ?? $talep->talep_durumu ?? 'Bilinmiyor') }}</span></dd>

                    <dt>Kategori</dt>
                    <dd>{{ $talep->kategori->name ?? 'Belirtilmemiş' }}</dd>

                    <dt>Açıklama</dt>
                    <dd>{{ $talep->aciklama ?? '-' }}</dd>
                </dl>
            </div>

            <!-- Aranan Konum Bilgileri -->
            <div class="detail-card">
                <h3 class="detail-p-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:text-slate-200 dark:bg-slate-900/50">Aranan Konum</h3>
                <dl class="detail-list">
                    <dt>Tam Adres</dt>
                    <dd>{{ $talep->tam_adres ?? 'Genel arama' }}</dd>

                    <dt>Ülke</dt>
                    <dd>{{ $talep->ulke->name ?? '-' }}</dd>

                    <dt>Bölge</dt>
                    <dd>{{ $talep->bolge->name ?? '-' }}</dd>

                    <dt>Şehir</dt>
                    <dd>{{ $talep->il->il_adi ?? '-' }}</dd>

                    <dt>İlçe</dt>
                    <dd>{{ $talep->ilce->name ?? '-' }}</dd>

                    <dt>Mahalle</dt>
                    <dd>{{ $talep->mahalle->name ?? '-' }}</dd>
                </dl>
            </div>

            <!-- Aranan Kriterler -->
            <div class="detail-card">
                <h3 class="detail-p-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:text-slate-200 dark:bg-slate-900/50">Aranan Kriterler</h3>
                <dl class="detail-list">
                    <dt>Fiyat Aralığı</dt>
                    <dd>{{ number_format($talep->min_fiyat, 0) }} - {{ number_format($talep->max_fiyat, 0) }}
                        {{ $talep->para_birimi }}</dd>

                    <dt>Oda Sayısı Aralığı</dt>
                    <dd>{{ $talep->min_oda_sayisi ?? 'Farketmez' }} - {{ $talep->max_oda_sayisi ?? 'Farketmez' }}
                    </dd>

                    <dt>Metrekare Aralığı</dt>
                    <dd>{{ $talep->min_metrekare ?? 'Farketmez' }} m² - {{ $talep->max_metrekare ?? 'Farketmez' }} m²
                    </dd>
                </dl>
            </div>

            <!-- Ek Özellikler -->
            <div class="detail-card">
                <h3 class="detail-p-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:text-slate-200 dark:bg-slate-900/50">İstenen Ek Özellikler</h3>
                <div class="p-6">
                    @if (!empty($talep->aranan_ozellikler_json))
                        @foreach ($talep->aranan_ozellikler_json as $ozellik)
                            <span class="feature-tag">{{ $ozellik }}</span>
                        @endforeach
                    @else
                        <p class="text-gray-500 dark:text-gray-400">Belirtilen ek özellik bulunmamaktadır.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('admin.talepler.eslesen', ['talep' => $talep->id]) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                <i class="fas fa-search-location mr-2"></i>
                Bu Talebe Uygun İlanları Bul
            </a>
        </div>
    </div>
@endsection
