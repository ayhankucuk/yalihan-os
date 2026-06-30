@extends('admin.layouts.admin')

@section('title', 'Yeni Özellik Kategorisi')

@section('content_header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">Yeni Özellik Kategorisi</h1>
        <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg
                   hover:bg-gray-200 dark:hover:bg-gray-600 hover:scale-105 active:scale-95
                   focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
                   transition-all duration-200 shadow-sm hover:shadow-md">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
    </div>
@endsection

@section('content')
    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700
                transition-all duration-300 ease-in-out hover:shadow-xl">
        <div class="p-6">
            <form action="{{ route('admin.ozellikler.kategoriler.store') }}" method="POST" x-data="{ showAdvanced: false }"
                onsubmit="const btn = document.getElementById('ozellik-kategori-submit-btn'); const icon = document.getElementById('ozellik-kategori-submit-icon'); const text = document.getElementById('ozellik-kategori-submit-text'); const spinner = document.getElementById('ozellik-kategori-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Kaydediliyor...'; }">
                @csrf
                @if (isset($errors) && (is_object($errors) ? $errors->any() : count($errors) > 0))
                    <div
                        class="bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6
                                transition-all duration-200 animate-pulse">
                        <p class="font-semibold mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            Lütfen aşağıdaki hataları düzeltin:
                        </p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            @foreach ((is_object($errors) ? $errors->all() : $errors) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <x-admin.input name="name" label="Kategori Adı" :required="true" :value="old('name')" placeholder="Örn: Genel Özellikler" />

                        <x-admin.textarea name="description" label="Açıklama" :value="old('description')" rows="4" placeholder="Kategori hakkında kısa bilgi..." />

                        <div class="mb-4">
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                Uygulama Alanı
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="col-span-full mb-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Seçili emlak türlerini aşağıdan işaretleyiniz. Seçilenler <span class="text-blue-600 font-bold">renkli</span> görünecektir.
                                    </span>
                                </div>

                                <div class="col-span-full sm:col-span-2">
                                    <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-slate-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all group dark:border-slate-700"
                                           :class="{'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500': $el.querySelector('input').checked}">
                                        <input type="checkbox"
                                               onchange="if(this.checked) { document.querySelectorAll('.applies-to-item').forEach(el => el.checked = false); this.checked = true; } else { this.checked = false; }"
                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 dark:text-white">
                                            Tüm Emlak Türleri
                                        </span>
                                    </label>
                                </div>

                                @foreach(($ilanKategorileri ?? collect()) as $ilanKategori)
                                    @php
                                        $isSelected = in_array($ilanKategori->slug, (array)old('applies_to', []));
                                    @endphp
                                    <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-slate-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all group dark:border-slate-700"
                                           :class="{'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500': {{ $isSelected ? 'true' : 'false' }}, 'hover:border-blue-300': !{{ $isSelected ? 'true' : 'false' }}}"
                                           x-data="{ checked: {{ $isSelected ? 'true' : 'false' }} }"
                                           @click="checked = !checked"
                                           :class="checked ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-white dark:bg-gray-800'">
                                        <input type="checkbox" name="applies_to[]" value="{{ $ilanKategori->slug }}"
                                               x-model="checked"
                                               class="applies-to-item w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 dark:text-white">
                                            {{ $ilanKategori->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <x-admin.input name="display_order" type="number" label="Sıra" :value="old('display_order', 0)" />

                        <x-admin.toggle name="aktiflik_durumu" label="Aktiflik Durumu" :checked="old('aktiflik_durumu', true)" />
                    </div>
                    <div>
                        <div class="mb-4">
                            <button type="button" @click="showAdvanced=!showAdvanced"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400
                                       hover:text-indigo-700 dark:hover:text-indigo-300
                                       hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                                       rounded-lg transition-all duration-200
                                       hover:scale-105 active:scale-95
                                       focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <svg class="w-4 h-4 transition-transform duration-200"
                                    :class="showAdvanced ? 'rotate-45' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Gelişmiş Alanlar
                            </button>
                        </div>
                        <div x-show="showAdvanced" x-cloak x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2">
                            <div class="mb-4">
                                <label for="icon" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                    İkon
                                </label>
                                <input type="text" id="icon" name="icon" value="{{ old('icon') }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg
                                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                           transition-all duration-200
                                           hover:border-gray-400 dark:hover:border-gray-500"
                                    placeholder="Örn: fas fa-home">
                                @error('icon')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="renk" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                    Renk
                                </label>
                                <input type="color" id="renk" name="renk" value="{{ old('renk', '#3B82F6') }}"
                                    class="w-full h-12 px-2 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-lg
                                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                           transition-all duration-200
                                           hover:border-gray-400 dark:hover:border-gray-500
                                           cursor-pointer">
                                @error('renk')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                        class="inline-flex items-center px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg
                               hover:bg-gray-300 dark:hover:bg-gray-600 hover:scale-105 active:scale-95
                               focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
                               transition-all duration-200 shadow-sm hover:shadow-md">
                        İptal
                    </a>
                    <button type="submit" id="ozellik-kategori-submit-btn"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-none">
                        <svg id="ozellik-kategori-submit-icon" class="fas fa-save mr-2"></svg>
                        <span id="ozellik-kategori-submit-text">Kaydet</span>
                        <svg id="ozellik-kategori-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
