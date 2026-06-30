@extends('layouts.owner')

@section('title', 'İlan Düzenle — ' . $ilan->baslik)

@section('content')
<div class="mb-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.ilanlar.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                &larr; İlanlarım
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('owner.ilanlar.show', $ilan->id) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                {{ Str::limit($ilan->baslik, 40) }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-sm text-gray-400">Düzenle</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">İlan Düzenle</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            İlan No: <span class="font-medium text-gray-700 dark:text-slate-300">#{{ $ilan->ilan_no ?? $ilan->id }}</span>
            &nbsp;·&nbsp;
            Durum: <span class="font-medium text-gray-700 dark:text-slate-300">{{ ucfirst($ilan->yayin_durumu?->value ?? 'taslak') }}</span>
        </p>
    </div>

    {{-- Silme Butonu --}}
    @if($ilan->yayin_durumu !== 'yayinda')
    <form action="{{ route('owner.ilanlar.destroy', $ilan->id) }}" method="POST"
          onsubmit="return confirm('Bu ilanı silmek istediğinizden emin misiniz?')">
        @csrf
        @method('DELETE')
        <button type="submit"
            class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-sm
                   hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
                   dark:border-red-700 dark:bg-slate-800 dark:text-red-400 dark:hover:bg-slate-700">
            İlanı Kaldır
        </button>
    </form>
    @endif
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm font-medium text-red-800 dark:text-red-400">Lütfen aşağıdaki hataları düzeltin:</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li class="text-sm text-red-700 dark:text-red-300">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('owner.ilanlar.update', $ilan->id) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    {{-- Temel Bilgiler --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Temel Bilgiler</h2>
        </div>
        <div class="space-y-5 p-6">

            {{-- Başlık --}}
            <div>
                <label for="baslik" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    İlan Başlığı <span class="text-red-500">*</span>
                </label>
                <input type="text" id="baslik" name="baslik" value="{{ old('baslik', $ilan->baslik) }}" required
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:focus:border-indigo-400
                           @error('baslik') border-red-500 @enderror">
                @error('baslik')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Açıklama --}}
            <div>
                <label for="aciklama" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="aciklama" name="aciklama" rows="5"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white
                           @error('aciklama') border-red-500 @enderror">{{ old('aciklama', $ilan->aciklama) }}</textarea>
            </div>

            {{-- Kategori --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="ana_kategori_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                        Ana Kategori <span class="text-red-500">*</span>
                    </label>
                    <select id="ana_kategori_id" name="ana_kategori_id" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                               dark:border-slate-600 dark:bg-slate-700 dark:text-white
                               @error('ana_kategori_id') border-red-500 @enderror">
                        <option value="">Seçiniz...</option>
                        @foreach ($anaKategoriler as $kategori)
                            <option value="{{ $kategori->id }}"
                                {{ old('ana_kategori_id', $ilan->ana_kategori_id ?? $ilan->kategori_id) == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="alt_kategori_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alt Kategori</label>
                    <select id="alt_kategori_id" name="alt_kategori_id"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                               dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <option value="">Seçiniz...</option>
                        @foreach ($anaKategoriler as $kategori)
                            @foreach ($kategori->children ?? [] as $alt)
                                <option value="{{ $alt->id }}"
                                    {{ old('alt_kategori_id', $ilan->alt_kategori_id) == $alt->id ? 'selected' : '' }}>
                                    {{ $alt->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Fiyat --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Fiyat Bilgileri</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-3">
            <div>
                <label for="fiyat_gosterim_modu" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    Fiyat Gösterimi <span class="text-red-500">*</span>
                </label>
                <select id="fiyat_gosterim_modu" name="fiyat_gosterim_modu" required
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="exact" {{ old('fiyat_gosterim_modu', $ilan->fiyat_gosterim_modu) === 'exact' ? 'selected' : '' }}>Kesin Fiyat</option>
                    <option value="on_request" {{ old('fiyat_gosterim_modu', $ilan->fiyat_gosterim_modu) === 'on_request' ? 'selected' : '' }}>Fiyat Sorulacak</option>
                    <option value="hidden" {{ old('fiyat_gosterim_modu', $ilan->fiyat_gosterim_modu) === 'hidden' ? 'selected' : '' }}>Gizli</option>
                </select>
            </div>
            <div>
                <label for="fiyat" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Fiyat</label>
                <input type="number" id="fiyat" name="fiyat" value="{{ old('fiyat', $ilan->fiyat) }}" min="0" step="1"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label for="para_birimi" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    Para Birimi <span class="text-red-500">*</span>
                </label>
                <select id="para_birimi" name="para_birimi" required
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="TRY" {{ old('para_birimi', $ilan->para_birimi) === 'TRY' ? 'selected' : '' }}>₺ TRY</option>
                    <option value="USD" {{ old('para_birimi', $ilan->para_birimi) === 'USD' ? 'selected' : '' }}>$ USD</option>
                    <option value="EUR" {{ old('para_birimi', $ilan->para_birimi) === 'EUR' ? 'selected' : '' }}>€ EUR</option>
                    <option value="GBP" {{ old('para_birimi', $ilan->para_birimi) === 'GBP' ? 'selected' : '' }}>£ GBP</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Konum --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Konum Bilgileri</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            <div>
                <label for="il_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    İl <span class="text-red-500">*</span>
                </label>
                <select id="il_id" name="il_id" required
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white
                           @error('il_id') border-red-500 @enderror">
                    <option value="">Seçiniz...</option>
                    @foreach ($iller as $il)
                        <option value="{{ $il->id }}" {{ old('il_id', $ilan->il_id) == $il->id ? 'selected' : '' }}>
                            {{ $il->il_adi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="adres" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açık Adres</label>
                <input type="text" id="adres" name="adres" value="{{ old('adres', $ilan->adres) }}"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Mülk Özellikleri --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Mülk Özellikleri <span class="text-xs font-normal text-gray-400">(opsiyonel)</span></h2>
        </div>
        <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-3">
            <div>
                <label for="metrekare" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alan (m²)</label>
                <input type="number" id="metrekare" name="metrekare" value="{{ old('metrekare', $ilan->metrekare) }}" min="0"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label for="oda_sayisi" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Oda Sayısı</label>
                <input type="text" id="oda_sayisi" name="oda_sayisi" value="{{ old('oda_sayisi', $ilan->oda_sayisi) }}"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Örn: 3+1">
            </div>
            <div>
                <label for="bina_yasi" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bina Yaşı</label>
                <input type="number" id="bina_yasi" name="bina_yasi" value="{{ old('bina_yasi', $ilan->bina_yasi) }}" min="0"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Eylem Butonları --}}
    <div class="flex items-center justify-between pb-6">
        <a href="{{ route('owner.ilanlar.show', $ilan->id) }}"
            class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
            &larr; Detay sayfasına dön
        </a>
        <div class="flex items-center gap-3">
            <a href="{{ route('owner.ilanlar.show', $ilan->id) }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm
                       hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                İptal
            </a>
            <button type="submit"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                       hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       dark:hover:bg-indigo-500">
                Değişiklikleri Kaydet
            </button>
        </div>
    </div>
</form>
@endsection
