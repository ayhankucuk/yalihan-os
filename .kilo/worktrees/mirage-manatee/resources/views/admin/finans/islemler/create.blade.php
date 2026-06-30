@extends('admin.layouts.admin')

@section('title', 'Yeni Finansal İşlem')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Finansal İşlem</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Yeni bir finansal işlem oluşturun</p>
        </div>
        <a href="{{ route('admin.finans.islemler.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 dark:text-slate-300">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Geri Dön
        </a>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.finans.islemler.store') }}" method="POST" 
        class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm p-6 space-y-6 dark:shadow-none dark:border-slate-700">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- İşlem Tipi --}}
            <div>
                <label for="islem_tipi" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    İşlem Tipi <span class="text-red-500">*</span>
                </label>
                <select id="islem_tipi" name="islem_tipi" required
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">Seçiniz</option>
                    <option value="komisyon">Komisyon</option>
                    <option value="odeme">Ödeme</option>
                    <option value="masraf">Masraf</option>
                    <option value="gelir">Gelir</option>
                    <option value="gider">Gider</option>
                </select>
            </div>

            {{-- Miktar --}}
            <div>
                <label for="miktar" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Miktar <span class="text-red-500">*</span>
                </label>
                <input type="number" id="miktar" name="miktar" step="0.01" required
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="0.00">
            </div>

            {{-- Para Birimi --}}
            <div>
                <label for="para_birimi" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Para Birimi
                </label>
                <select id="para_birimi" name="para_birimi"
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                    <option value="TRY" selected>TRY (₺)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                </select>
            </div>

            {{-- Tarih --}}
            <div>
                <label for="tarih" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    İşlem Tarihi <span class="text-red-500">*</span>
                </label>
                <input type="date" id="tarih" name="tarih" required value="{{ date('Y-m-d') }}"
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
            </div>
        </div>

        {{-- Açıklama --}}
        <div>
            <label for="aciklama" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                Açıklama
            </label>
            <textarea id="aciklama" name="aciklama" rows="4"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100"
                placeholder="İşlemle ilgili notlarınızı buraya girebilirsiniz..."></textarea>
        </div>

        {{-- Durum --}}
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                Durum
            </label>
            <select id="status" name="status"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                <option value="bekliyor" selected>Bekleyen</option>
                <option value="onaylandi">Onaylanan</option>
                <option value="tamamlandi">Tamamlanan</option>
            </select>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <a href="{{ route('admin.finans.islemler.index') }}"
                class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 dark:text-slate-300">
                İptal
            </a>
            <button type="submit"
                class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 dark:shadow-none">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Kaydet
            </button>
        </div>
    </form>

    {{-- Info Card --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Bilgilendirme</h3>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                    Finansal işlem oluşturulduktan sonra ilgili kişi ve ilan bilgilerini düzenleme sayfasından ekleyebilirsiniz.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
