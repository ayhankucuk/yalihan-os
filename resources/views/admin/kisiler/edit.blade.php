@extends('admin.layouts.admin')

@section('title', 'Kişi Düzenle: ' . $kisi->ad . ' ' . $kisi->soyad)

@php
    /**
     * @var \App\Models\Kisi $kisi
     * @var \Illuminate\Database\Eloquent\Collection|\App\Models\Il[] $iller
     * @var \Illuminate\Database\Eloquent\Collection|\App\Models\Ilce[] $ilceler
     * @var \Illuminate\Database\Eloquent\Collection|\App\Models\Mahalle[] $mahalleler
     * @var \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $danismanlar
     * @var \Illuminate\Database\Eloquent\Collection|\App\Modules\Crm\Models\Etiket[] $etiketler
     * @var array $kisiEtiketIds
     * @var \Illuminate\Support\MessageBag $errors
     */
@endphp

@section('styles')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer />
    {{-- Context7: Location Wizard Script --}}
    {{-- Context7: Location Wizard Script (Inlined below) --}}
    <style></style>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    {{ $kisi->ad }} {{ $kisi->soyad }} - Düzenle
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kişi bilgilerini güncelleyin</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.kisiler.index') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all duration-200">
                    ← Geri Dön
                </a>
                <button onclick="deleteKisi({{ $kisi->id }})"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium text-sm rounded-lg hover:bg-red-700 shadow-sm hover:shadow transition-all duration-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sil
                </button>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Lütfen aşağıdaki hataları düzeltin:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.kisiler.update', $kisi) }}" method="POST" x-data="kisiEditFormData({{ $kisi->id }})" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Temel Bilgiler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-6 flex items-center">
                        <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</span>
                        Temel Bilgiler
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Ad -->
                        <div class="mb-6">
                            <label for="ad" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Ad *</span>
                            </label>
                            <input type="text" name="ad" id="ad" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="Müşteri adını girin..." x-model="formData.ad"
                                @input.debounce.500ms="checkDuplicate('ad', $event.target.value)">
                        </div>

                        <!-- Soyad -->
                        <div class="mb-6">
                            <label for="soyad" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Soyad *</span>
                            </label>
                            <input type="text" name="soyad" id="soyad" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="Müşteri soyadını girin..." x-model="formData.soyad"
                                @input.debounce.500ms="checkDuplicate('soyad', $event.target.value)">
                        </div>

                        <!-- Telefon -->
                        <div class="mb-6">
                            <label for="telefon" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Telefon *</span>
                            </label>
                            <input type="tel" name="telefon" id="telefon" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="05XX XXX XXXX" x-model="formData.telefon"
                                @input.debounce.500ms="checkDuplicate('telefon', $event.target.value)">

                            <!-- Telefon Duplicate Warning -->
                            <div x-show="duplicateWarnings.telefon" class="mt-2">
                                <div class="flex items-start space-x-2 p-2 bg-red-50 border border-red-200 rounded">
                                    <svg class="w-4 h-4 text-red-500 mt-0.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-red-800 text-sm font-medium">Bu telefon numarası başka bir müşteriye
                                            kayıtlı!</p>
                                        <p class="text-red-600 text-xs" x-text="duplicateWarnings.telefon"></p>
                                        <a :href="duplicateLinks.telefon" target="_blank"
                                            class="text-red-700 underline text-xs">Kayıtlı müşteriyi görüntüle</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- E-posta -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">E-posta</span>
                            </label>
                            <input type="email" name="email" id="email"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="ornek@email.com" x-model="formData.email"
                                @input.debounce.500ms="checkDuplicate('email', $event.target.value)">

                            <!-- Email Duplicate Warning -->
                            <div x-show="duplicateWarnings.email" class="mt-2">
                                <div class="flex items-start space-x-2 p-2 bg-red-50 border border-red-200 rounded">
                                    <svg class="w-4 h-4 text-red-500 mt-0.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-red-800 text-sm font-medium">Bu e-posta adresi başka bir müşteriye
                                            kayıtlı!</p>
                                        <p class="text-red-600 text-xs" x-text="duplicateWarnings.email"></p>
                                        <a :href="duplicateLinks.email" target="_blank"
                                            class="text-red-700 underline text-xs">Kayıtlı müşteriyi görüntüle</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Durum (Context7: aktiflik_durumu boolean) -->
                        <div class="mb-6">
                            <label for="aktiflik_durumu" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Durum *</span>
                            </label>
                            <select style="color-scheme: light dark;" id="aktiflik_durumu" name="aktiflik_durumu"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                x-model="formData.aktiflik_durumu">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </div>

                        <!-- Kişi Tipi -->
                        <div class="mb-6">
                            <label for="kisi_tipi" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Kişi Tipi</span>
                            </label>
                            <select style="color-scheme: light dark;" id="kisi_tipi" name="kisi_tipi"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                x-model="formData.kisi_tipi">
                                <option value="">Seçiniz...</option>
                                <option value="Müşteri">Müşteri</option>
                                <option value="Potansiyel">Potansiyel</option>
                                <option value="Ev Sahibi">Ev Sahibi</option>
                                <option value="Alıcı">Alıcı</option>
                                <option value="Kiracı">Kiracı</option>
                                <option value="Satıcı">Satıcı</option>
                                <option value="Yatırımcı">Yatırımcı</option>
                                <option value="Tedarikçi">Tedarikçi</option>
                            </select>
                        </div>


                        <!-- CRM Durumu (Context7) -->
                        <div class="mb-6">
                            <label for="crm_surec_asamasi"
                                class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">CRM Durumu
                                    *</span>
                            </label>
                            <select style="color-scheme: light dark;" id="crm_surec_asamasi" name="crm_surec_asamasi"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                x-model="formData.crm_surec_asamasi">
                                <option value="">Seçin...</option>
                                <option value="yeni">Yeni</option>
                                <option value="gorusme">Görüşme</option>
                                <option value="takip">Takip</option>
                                <option value="tamamlandi">Tamamlandı</option>
                                <option value="kaybedildi">Kaybedildi</option>
                            </select>
                        </div>

                        <!-- Kaynak (Context7: Field removed - database column doesn't exist) -->

                        <!-- Danışman -->
                        <div class="mb-6">
                            <label for="danisman_id" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Danışman
                                    *</span>
                            </label>
                            <select style="color-scheme: light dark;" id="danisman_id" name="danisman_id"
                                x-model="formData.danisman_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                <option value="">Danışman seçin...</option>
                                @foreach ($danismanlar ?? [] as $danisman)
                                    <option value="{{ $danisman->id }}">
                                        {{ $danisman->name }} ({{ $danisman->email }})
                                        @if (isset($danisman->source) && $danisman->source == 'danisman_model')
                                            - Danışman Modeli
                                        @elseif(isset($danisman->role))
                                            - {{ ucfirst($danisman->role->name) }}
                                        @else
                                            - Danışman
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('danisman_id'))
                                <div class="text-red-500 text-sm mt-1">{{ $errors->first('danisman_id') }}</div>
                            @endif
                        </div>

                        <!-- Etiketler (Multiple Select) -->
                        <div class="mb-6">
                            <label for="etiketler_ids" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Etiketler</span>
                            </label>
                            <select id="etiketler_ids" name="etiketler_ids[]" multiple
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                style="min-height: 120px; color-scheme: light dark;">
                                @foreach ($etiketler as $etiket)
                                    <option value="{{ $etiket->id }}"
                                        {{ in_array($etiket->id, old('etiketler_ids', $kisiEtiketIds)) ? 'selected' : '' }}>
                                        {{ $etiket->ad }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-start gap-1">
                                <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Çoklu seçim:</strong> Ctrl (Windows) veya Cmd (Mac) tuşuna basılı tutarak
                                    birden fazla etiket seçebilirsiniz.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Adres Bilgileri --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700"
                x-data="locationWizard()" x-init="init()">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-6 flex items-center">
                        <span class="bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</span>
                        Adres Bilgileri
                    </h2>
                    {{-- Address Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- İl --}}
                        <div>
                            <label for="il_id" class="block text-sm font-medium text-gray-900 dark:text-slate-100 mb-2">
                                İl <span class="text-red-500">*</span>
                            </label>
                            <select name="il_id" id="il_id" required x-model="selectedCity"
                                @change="fetchDistricts()"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors">
                                <option value="">İl Seçin</option>
                                @foreach ($iller as $il)
                                    <option value="{{ $il->id }}" {{ old('il_id', $kisi->il_id) == $il->id ? 'selected' : '' }}>{{ $il->il_adi }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- İlçe --}}
                        <div>
                            <label for="ilce_id" class="block text-sm font-medium text-gray-900 dark:text-slate-100 mb-2">
                                İlçe <span class="text-red-500">*</span>
                            </label>
                            <select name="ilce_id" id="ilce_id" required x-model="selectedDistrict"
                                @change="fetchNeighborhoods()" :disabled="!selectedCity || loadingDistricts"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="" x-text="loadingDistricts ? 'İlçeler yükleniyor...' : 'İlçe Seçin'"></option>
                                <template x-for="district in districts" :key="district.id">
                                    <option :value="district.id" x-text="district.name || district.ilce_adi"
                                        :selected="district.id == '{{ old('ilce_id', $kisi->ilce_id) }}'"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Mahalle --}}
                        <div>
                            <label for="mahalle_id" class="block text-sm font-medium text-gray-900 dark:text-slate-100 mb-2">
                                Mahalle <span class="text-red-500">*</span>
                            </label>
                            <select name="mahalle_id" id="mahalle_id" required x-model="selectedNeighborhood"
                                :disabled="!selectedDistrict || loadingNeighborhoods"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="" x-text="loadingNeighborhoods ? 'Mahalleler yükleniyor...' : 'Mahalle Seçin'"></option>
                                <template x-for="hood in neighborhoods" :key="hood.id">
                                    <option :value="hood.id" x-text="hood.name || hood.mahalle_adi"
                                        :selected="hood.id == '{{ old('mahalle_id', $kisi->mahalle_id) }}'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="lat" id="lat" value="{{ $kisi->lat }}">
                    <input type="hidden" name="lng" id="lng" value="{{ $kisi->lng }}">

                    <div class="mb-2 mt-4">
                        <label for="adres_detay" class="block text-sm font-medium text-gray-900 dark:text-slate-100 mb-2">
                            Adres Detayı
                        </label>
                        <textarea name="adres_detay" id="adres_detay" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 text-sm"
                            placeholder="Sokak, cadde, bina numarası, daire no vb. detay bilgiler">{{ old('adres_detay', $kisi->adres_detay ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-6 flex items-center">
                        <span class="bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">3</span>
                        Notlar
                    </h2>

                    <div class="mb-6">
                        <label for="notlar" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white-text dark:text-slate-100">Müşteri
                                Notları</span>
                        </label>
                        <textarea id="notlar" name="notlar" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                            placeholder="Müşteri ile ilgili notlar...">{{ old('notlar', $kisi->notlar) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Form Butonları -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.kisiler.index') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-slate-200 font-medium text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    İptal
                </a>
                <button type="submit" id="kisi-edit-submit-btn"
                    class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg id="kisi-edit-submit-icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg id="kisi-edit-submit-spinner" class="hidden w-4 h-4 mr-2 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span id="kisi-edit-submit-text">Değişiklikleri Kaydet</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Location Cascade (Vanilla JS - Context7 Standard)
        // Location Wizard Logic (Inlined from resources/js/admin/location-wizard.js for stability)
        window.locationWizard = function() {
            return {
                selectedCity: '{{ old('il_id', $kisi->il_id ?? '') }}',
                selectedDistrict: '{{ old('ilce_id', $kisi->ilce_id ?? '') }}',
                selectedNeighborhood: '{{ old('mahalle_id', $kisi->mahalle_id ?? '') }}',

                districts: [],
                neighborhoods: [],

                loadingDistricts: false,
                loadingNeighborhoods: false,

                async init() {
                    if (this.selectedCity) {
                        await this.fetchDistricts();
                        if (this.selectedDistrict) {
                            await this.fetchNeighborhoods();
                        }
                    }
                },

                async fetchDistricts() {
                    if (!this.selectedCity) {
                        this.districts = [];
                        this.selectedDistrict = '';
                        this.neighborhoods = [];
                        this.selectedNeighborhood = '';
                        return;
                    }

                    this.loadingDistricts = true;
                    try {
                        const response = await fetch(`/api/v1/location/districts/${this.selectedCity}`);
                        const result = await response.json();

                        if (result.success && Array.isArray(result.data)) {
                            this.districts = result.data;
                        }
                    } catch (error) {
                        console.error('İlçeler yüklenemedi:', error);
                    } finally {
                        this.loadingDistricts = false;
                    }
                },

                async fetchNeighborhoods() {
                    if (!this.selectedDistrict) {
                        this.neighborhoods = [];
                        this.selectedNeighborhood = '';
                        return;
                    }

                    this.loadingNeighborhoods = true;
                    try {
                        const response = await fetch(`/api/v1/location/neighborhoods/${this.selectedDistrict}`);
                        const result = await response.json();

                        if (result.success && Array.isArray(result.data)) {
                            this.neighborhoods = result.data;
                        }
                    } catch (error) {
                        console.error('Mahalleler yüklenemedi:', error);
                    } finally {
                        this.loadingNeighborhoods = false;
                    }
                }
            };
        };

        // Alpine Component for Edit Form (Context7)
        function kisiEditFormData(kisiId) {
            return {
                kisiId: kisiId,
                formData: {
                    ad: '{{ old('ad', $kisi->ad) }}',
                    soyad: '{{ old('soyad', $kisi->soyad) }}',
                    telefon: '{{ old('telefon', $kisi->telefon) }}',
                    email: '{{ old('email', $kisi->email) }}',
                    kisi_tipi: '{{ old('kisi_tipi', $kisi->kisi_tipi) }}',
                    crm_surec_asamasi: '{{ old('crm_surec_asamasi', $kisi->crm_surec_asamasi?->value ?? $kisi->crm_surec_asamasi) }}',
                    danisman_id: '{{ old('danisman_id', $kisi->danisman_id ?? '') }}',
                    aktiflik_durumu: '{{ old('aktiflik_durumu', $kisi->aktiflik_durumu) }}',

                    notlar: '{{ old('notlar', $kisi->notlar) }}'
                },
                isRestoringData: false, // Flag to prevent double loading
                duplicateWarnings: {
                    telefon: false,
                    email: false
                },
                duplicateLinks: {
                    telefon: '',
                    email: ''
                },

                init() {
                    console.log('✅ Kişi Edit Form initialized for ID:', this.kisiId);
                    console.log('📊 Form Data:', this.formData);
                },



                async checkDuplicate(field, value) {
                    if (!value || value.length < 3) {
                        this.duplicateWarnings[field] = false;
                        return;
                    }

                    try {
                        const response = await fetch(`/api/kisiler/check-duplicate`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                field: field,
                                value: value,
                                exclude_id: this.kisiId
                            })
                        });

                        const data = await response.json();
                        if (data.exists) {
                            this.duplicateWarnings[field] = data.message;
                            this.duplicateLinks[field] = `/admin/kisiler/${data.kisi_id}`;
                        } else {
                            this.duplicateWarnings[field] = false;
                        }
                    } catch (error) {
                        console.error('Duplicate check error:', error);
                    }
                }
            };
        }
        window.kisiEditFormData = kisiEditFormData;

        // Delete Function (Context7 Standard)
        async function deleteKisi(kisiId) {
            if (!confirm('Bu kişiyi silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
                return;
            }

            try {
                const response = await fetch(`/admin/kisiler/${kisiId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Success toast
                    if (window.toast) {
                        window.toast.success('Kişi başarıyla silindi!');
                    }

                    // Redirect to index
                    setTimeout(() => {
                        window.location.href = '/admin/kisiler';
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Silme işlemi başarısız');
                }
            } catch (error) {
                console.error('Silme hatası:', error);

                // Error toast
                if (window.toast) {
                    window.toast.error('Silme işlemi başarısız: ' + error.message);
                } else {
                    alert('Silme işlemi başarısız: ' + error.message);
                }
            }
        }

        // Export to window
        window.deleteKisi = deleteKisi;

        console.log('✅ Kişiler edit page initialized (Context7 Vanilla JS)');
    </script>
@endpush
