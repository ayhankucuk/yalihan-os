@extends('admin.layouts.admin')

@section('title', 'Yeni Proje Oluştur')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Başlık --}}
        <div class="mb-6">
            <nav class="mb-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.takim.projeler.index') }}"
                    class="hover:text-gray-700 dark:hover:text-gray-200">Projeler</a>
                <svg class="mx-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>Yeni Proje</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Yeni Proje Oluştur</h1>
        </div>

        {{-- Form --}}
        <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-slate-900 dark:shadow-none">
            <form action="{{ route('admin.takim.projeler.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Proje Adı --}}
                    <div class="md:col-span-2">
                        <label for="proje_adi"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Proje Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="proje_adi" id="proje_adi" value="{{ old('proje_adi') }}" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white"
                            placeholder="Proje adını girin">
                        @error('proje_adi')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Açıklama --}}
                    <div class="md:col-span-2">
                        <label for="aciklama"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Açıklama
                        </label>
                        <textarea name="aciklama" id="aciklama" rows="3"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white"
                            placeholder="Proje hakkında kısa bir açıklama">{{ old('aciklama') }}</textarea>
                        @error('aciklama')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Başlangıç Tarihi --}}
                    <div>
                        <label for="baslangic_tarihi"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Başlangıç Tarihi <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="baslangic_tarihi" id="baslangic_tarihi"
                            value="{{ old('baslangic_tarihi', date('Y-m-d')) }}" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        @error('baslangic_tarihi')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bitiş Tarihi --}}
                    <div>
                        <label for="bitis_tarihi"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Bitiş Tarihi
                        </label>
                        <input type="date" name="bitis_tarihi" id="bitis_tarihi" value="{{ old('bitis_tarihi') }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                        @error('bitis_tarihi')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Durum --}}
                    <div>
                        <label for="yayin_durumu"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Durum <span class="text-red-500">*</span>
                        </label>
                        <select name="yayin_durumu" id="yayin_durumu" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="planlama" @selected(old('yayin_durumu') == 'planlama')>Planlama</option>
                            <option value="devam_ediyor" @selected(old('yayin_durumu') == 'devam_ediyor')>
                                Devam Ediyor</option>
                            <option value="beklemede" @selected(old('yayin_durumu') == 'beklemede')>
                                Beklemede</option>
                            <option value="tamamlandi" @selected(old('yayin_durumu') == 'tamamlandi')>
                                Tamamlandı</option>
                            <option value="iptal" @selected(old('yayin_durumu') == 'iptal')>İptal</option>
                        </select>
                        @error('yayin_durumu')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Öncelik --}}
                    <div>
                        <label for="oncelik"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Öncelik <span class="text-red-500">*</span>
                        </label>
                        <select name="oncelik" id="oncelik" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="dusuk" {{ old('oncelik') == 'dusuk' ? 'selected' : '' }}>Düşük</option>
                            <option value="orta" {{ old('oncelik', 'orta') == 'orta' ? 'selected' : '' }}>Orta</option>
                            <option value="yuksek" {{ old('oncelik') == 'yuksek' ? 'selected' : '' }}>Yüksek</option>
                            <option value="kritik" {{ old('oncelik') == 'kritik' ? 'selected' : '' }}>Kritik</option>
                        </select>
                        @error('oncelik')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sorumlu --}}
                    <div>
                        <label for="user_id"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Proje Sorumlusu <span class="text-red-500">*</span>
                        </label>
                        <select name="user_id" id="user_id" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                            <option value="">Seçiniz</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bütçe --}}
                    <div>
                        <label for="budget"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Bütçe (₺)
                        </label>
                        <input type="number" name="budget" id="budget" value="{{ old('budget') }}" min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white"
                            placeholder="0.00">
                        @error('budget')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Butonlar --}}
                <div
                    class="mt-8 flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-slate-800">
                    <a href="{{ route('admin.takim.projeler.index') }}"
                        class="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 transition-all duration-200 hover:bg-gray-50 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700">
                        İptal
                    </a>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2 text-white transition-all duration-200 hover:scale-105 hover:bg-blue-700 active:scale-95">
                        Proje Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
