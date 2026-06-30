@extends('admin.layouts.admin')

@section('title', 'Yeni Danışman Ekle')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-blue-900">
        {{-- Header --}}
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 mb-8 p-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        👤 Yeni Danışman Ekle
                    </h1>
                    <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">
                        Sisteme yeni danışman kullanıcısı ekleyin
                    </p>
                </div>
                <a href="{{ route('admin.danisman.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                    ← Geri Dön
                </a>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.danisman.store') }}" method="POST" class="space-y-6"
              onsubmit="const btn = document.getElementById('danisman-submit-btn'); const icon = document.getElementById('danisman-submit-icon'); const text = document.getElementById('danisman-submit-text'); const spinner = document.getElementById('danisman-submit-spinner'); if(btn && icon && text && spinner) { btn.disabled = true; icon.classList.add('hidden'); spinner.classList.remove('hidden'); text.textContent = 'Kaydediliyor...'; }">
            @csrf

            {{-- Temel Bilgiler --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">1</span>
                    👤 Temel Bilgiler
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Ad --}}
                    <div>
                        <label for="ad" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Ad *
                        </label>
                        @php
                            $adValue = old('ad', '');
                            if (empty($adValue) && old('name')) {
                                $nameParts = explode(' ', old('name', ''), 2);
                                $adValue = $nameParts[0] ?? '';
                            }
                        @endphp
                        <input type="text" name="ad" id="ad" value="{{ $adValue }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('ad') border-red-500 @enderror @error('name') dark:text-slate-100 @enderror"
                            placeholder="Ad">
                        @error('ad')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Soyad --}}
                    <div>
                        <label for="soyad" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Soyad *
                        </label>
                        @php
                            $soyadValue = old('soyad', '');
                            if (empty($soyadValue) && old('name')) {
                                $nameParts = explode(' ', old('name', ''), 2);
                                $soyadValue = $nameParts[1] ?? '';
                            }
                        @endphp
                        <input type="text" name="soyad" id="soyad" value="{{ $soyadValue }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('soyad') border-red-500 @enderror @error('name') dark:text-slate-100 @enderror"
                            placeholder="Soyad">
                        @error('soyad')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Hidden name field (for backward compatibility) - Ad ve Soyad birleştiriliyor --}}
                    <input type="hidden" name="name" id="name" value="">

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            E-posta *
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('email') border-red-500 @enderror dark:text-slate-100"
                            placeholder="ornek@yalihanemlak.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefon --}}
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Telefon
                        </label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('phone_number') border-red-500 @enderror dark:text-slate-100"
                            placeholder="0532 123 45 67">
                        @error('phone_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Şifre --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Şifre *
                        </label>
                        <input type="password" name="password" id="password" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white @error('password') border-red-500 @enderror dark:text-slate-100"
                            placeholder="Güvenli şifre oluşturun">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Şifre Tekrar --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Şifre Tekrar *
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="Şifreyi tekrar girin">
                    </div>
                </div>
            </div>

            {{-- Profesyonel Bilgiler --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span class="bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">2</span>
                    💼 Profesyonel Bilgiler
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Lisans No --}}
                    <div>
                        <label for="lisans_no" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Lisans Numarası
                        </label>
                        <input type="text" name="lisans_no" id="lisans_no" value="{{ old('lisans_no') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="Gayrimenkul lisans numarası">
                    </div>

                    {{-- Uzmanlık Alanları (Çoklu Seçim) --}}
                    <div class="md:col-span-2">
                        <label for="uzmanlik_alanlari" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Uzmanlık Alanları (Çoklu Seçim)
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:border-slate-700">
                            @php
                                // ✅ Sadece izin verilen 5 uzmanlık alanı
                                $uzmanlikAlanlari = ['Konut', 'Arsa', 'İşyeri', 'Yazlık', 'Turistik Tesis'];
                                $oldValues = old('uzmanlik_alanlari', []);
                            @endphp
                            @foreach($uzmanlikAlanlari as $alan)
                                <label class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors duration-200">
                                    <input type="checkbox"
                                           name="uzmanlik_alanlari[]"
                                           value="{{ $alan }}"
                                           {{ in_array($alan, $oldValues) ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 transition-all duration-200 dark:bg-slate-900">
                                    <span class="ml-2 text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $alan }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Birden fazla uzmanlık alanı seçebilirsiniz</p>
                    </div>

                    {{-- Ofis Adresi --}}
                    <div class="md:col-span-2">
                        <label for="office_address" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Ofis Adresi
                        </label>
                        <textarea name="office_address" id="office_address" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="Ofis adresi...">{{ old('office_address') }}</textarea>
                    </div>

                    {{-- Deneyim Yılı --}}
                    <div>
                        <label for="deneyim_yili" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Deneyim Yılı
                        </label>
                        <input type="number" name="deneyim_yili" id="deneyim_yili" value="{{ old('deneyim_yili', 0) }}" min="0" max="50"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="0">
                    </div>

                    {{-- Ünvan --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Ünvan
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', 'Danışman') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100"
                            placeholder="Emlak Danışmanı">
                    </div>

                    {{-- Pozisyon --}}
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Pozisyon
                        </label>
                        <select style="color-scheme: light dark;" name="position" id="position"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white transition-all duration-200 dark:text-slate-100">
                            <option value="">Seçiniz...</option>
                            @foreach(config('danisman.positions', []) as $key => $label)
                                <option value="{{ $key }}" {{ old('position') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Durum --}}
            <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-8">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
                    <span class="bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">3</span>
                    ⚙️ Durum Ayarları
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Durum --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Durum *
                        </label>
                        <select style="color-scheme: light dark;" name="status" id="status" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white transition-all duration-200 dark:text-slate-100">
                            @foreach(config('danisman.status_options', []) as $key => $label)
                                <option value="{{ $key }}" {{ old('status', 'aktif') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- JavaScript: Ad ve Soyad'ı birleştir --}}
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const adInput = document.getElementById('ad');
                    const soyadInput = document.getElementById('soyad');
                    const nameInput = document.getElementById('name');

                    function updateName() {
                        const ad = adInput.value.trim();
                        const soyad = soyadInput.value.trim();
                        nameInput.value = (ad + ' ' + soyad).trim();
                    }

                    if (adInput && soyadInput && nameInput) {
                        adInput.addEventListener('input', updateName);
                        soyadInput.addEventListener('input', updateName);
                        // İlk yüklemede de güncelle
                        updateName();
                    }
                });
            </script>
            @endpush

            {{-- Form Aksiyonları --}}
            <div class="flex items-center justify-end space-x-4 bg-gray-50 dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-6">
                <a href="{{ route('admin.danisman.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                    İptal
                </a>
                <button type="submit"
                        id="danisman-submit-btn"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 touch-target-optimized">
                    <svg id="danisman-submit-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span id="danisman-submit-text">💾 Danışman Oluştur</span>
                    <svg id="danisman-submit-spinner" class="hidden w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirmation').value;

                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert('Şifreler eşleşmiyor!');
                    return false;
                }

                return true;
            });
        </script>
    @endpush
@endsection
