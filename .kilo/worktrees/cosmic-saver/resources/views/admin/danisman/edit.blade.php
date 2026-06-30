@php
    use Illuminate\Support\Facades\Storage;
@endphp

@extends('admin.layouts.admin')

@section('title', $danisman->name . ' - Danışman Düzenle')

@section('content')
    <!-- Neo Page Header -->
    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                    <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    {{ $danisman->name }} - Düzenle
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Danışman profil bilgilerini güncelleyin
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <x-neo.button href="{{ route('admin.danisman.show', $danisman) }}" variant="secondary"
                    icon="<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' /></svg>">
                    Görüntüle
                </x-neo.button>
                <x-neo.button href="{{ route('admin.danisman.index') }}" variant="secondary"
                    icon="<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 19l-7-7m0 0l7-7m-7 7h18' /></svg>">
                    Geri
                </x-neo.button>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.danisman.update', $danisman) }}" enctype="multipart/form-data"
        class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Ana Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Temel Bilgiler -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Temel Bilgiler
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Ad --}}
                            <div>
                                <label for="ad"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Ad <span class="text-red-500">*</span>
                                </label>
                                @php
                                    $adValue = old('ad', '');
                                    if (empty($adValue) && $danisman->name) {
                                        $nameParts = explode(' ', $danisman->name, 2);
                                        $adValue = $nameParts[0] ?? '';
                                    }
                                @endphp
                                <input type="text" id="ad" name="ad"
                                    value="{{ $adValue }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('ad') border-red-500 @enderror @error('name') dark:text-slate-100" required
                                    placeholder="Ad">
                                @error('ad')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Soyad --}}
                            <div>
                                <label for="soyad"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Soyad <span class="text-red-500">*</span>
                                </label>
                                @php
                                    $soyadValue = old('soyad', '');
                                    if (empty($soyadValue) && $danisman->name) {
                                        $nameParts = explode(' ', $danisman->name, 2);
                                        $soyadValue = $nameParts[1] ?? '';
                                    }
                                @endphp
                                <input type="text" id="soyad" name="soyad"
                                    value="{{ $soyadValue }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('soyad') border-red-500 @enderror dark:text-slate-100" required
                                    placeholder="Soyad">
                                @error('soyad')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Hidden name field (for backward compatibility) --}}
                            <input type="hidden" name="name" id="name" value="">

                            <div>
                                <label for="email"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    E-posta Adresi <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email"
                                    value="{{ old('email', $danisman->email) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('email') border-red-500 @enderror dark:text-slate-100" required
                                    placeholder="ornek@email.com">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone_number"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Telefon Numarası
                                </label>
                                <input type="tel" id="phone_number" name="phone_number"
                                    value="{{ old('phone_number', $danisman->phone_number) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('phone_number') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="0532 000 0000">
                                @error('phone_number')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Ünvan
                                </label>
                                <input type="text" id="title" name="title"
                                    value="{{ old('title', $danisman->title) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('title') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="Emlak Danışmanı">
                                @error('title')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="position"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Pozisyon
                                </label>
                                <select style="color-scheme: light dark;" id="position" name="position"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('position') border-red-500 @enderror dark:text-slate-100">
                                    <option value="">Seçiniz...</option>
                                    @foreach(config('danisman.positions', []) as $key => $label)
                                        <option value="{{ $key }}" {{ old('position', $danisman->position) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('position')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profesyonel Bilgiler -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Profesyonel Bilgiler
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="lisans_no"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Lisans Numarası
                                </label>
                                <input type="text" id="lisans_no" name="lisans_no"
                                    value="{{ old('lisans_no', $danisman->lisans_no) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('lisans_no') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="Gayrimenkul lisans numarası">
                                @error('lisans_no')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="deneyim_yili"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Deneyim Yılı
                                </label>
                                <input type="number" id="deneyim_yili" name="deneyim_yili"
                                    value="{{ old('deneyim_yili', $danisman->deneyim_yili ?? 0) }}" min="0" max="50"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('deneyim_yili') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="0">
                                @error('deneyim_yili')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="uzmanlik_alanlari"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                    Uzmanlık Alanları (Çoklu Seçim)
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:border-slate-700">
                                    @php
                                        // ✅ Sadece izin verilen 5 uzmanlık alanı
                                        $uzmanlikAlanlari = ['Konut', 'Arsa', 'İşyeri', 'Yazlık', 'Turistik Tesis'];
                                        $oldValues = old('uzmanlik_alanlari', $danisman->uzmanlik_alanlari ?? []);
                                        if (!is_array($oldValues)) {
                                            $oldValues = $oldValues ? [$oldValues] : [];
                                        }
                                        // Eski tek seçim için backward compatibility
                                        if (empty($oldValues) && $danisman->uzmanlik_alani) {
                                            $oldValues = [$danisman->uzmanlik_alani];
                                        }
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

                            <div>
                                <label for="expertise_summary"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Uzmanlık Özeti
                                </label>
                                <textarea id="expertise_summary" name="expertise_summary" rows="3"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('expertise_summary') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="Uzmanlık alanlarınızı açıklayın...">{{ old('expertise_summary', $danisman->expertise_summary) }}</textarea>
                                @error('expertise_summary')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="certificates_info"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Sertifikalar
                                </label>
                                <textarea id="certificates_info" name="certificates_info" rows="3"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('certificates_info') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="Sertifikalarınızı listeleyin...">{{ old('certificates_info', $danisman->certificates_info) }}</textarea>
                                @error('certificates_info')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bio"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Biyografi
                                </label>
                                <textarea id="bio" name="bio" rows="3" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('bio') border-red-500 @enderror"
                                    placeholder="Kendiniz hakkında bilgi verin...">{{ old('bio', $danisman->bio) }}</textarea>
                                @error('bio')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İletişim Bilgileri -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            İletişim Bilgileri
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="office_address"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Ofis Adresi
                                </label>
                                <textarea id="office_address" name="office_address" rows="2"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('office_address') border-red-500 @enderror dark:text-slate-100" placeholder="Ofis adresinizi girin...">{{ old('office_address', $danisman->office_address) }}</textarea>
                                @error('office_address')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="office_phone"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Ofis Telefonu
                                </label>
                                <input type="tel" id="office_phone" name="office_phone"
                                    value="{{ old('office_phone', $danisman->office_phone) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('office_phone') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="0532 000 0000">
                                @error('office_phone')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="whatsapp_number"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    WhatsApp Numarası
                                </label>
                                <input type="tel" id="whatsapp_number" name="whatsapp_number"
                                    value="{{ old('whatsapp_number', $danisman->whatsapp_number) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('whatsapp_number') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="0532 000 0000">
                                @error('whatsapp_number')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sosyal Medya -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Sosyal Medya
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="instagram_profile"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Instagram Profil URL
                                </label>
                                <input type="url" id="instagram_profile" name="instagram_profile"
                                    value="{{ old('instagram_profile', $danisman->instagram_profile) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('instagram_profile') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://instagram.com/...">
                                @error('instagram_profile')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="linkedin_profile"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    LinkedIn Profil URL
                                </label>
                                <input type="url" id="linkedin_profile" name="linkedin_profile"
                                    value="{{ old('linkedin_profile', $danisman->linkedin_profile) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('linkedin_profile') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://linkedin.com/in/...">
                                @error('linkedin_profile')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="facebook_profile"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Facebook Profil URL
                                </label>
                                <input type="url" id="facebook_profile" name="facebook_profile"
                                    value="{{ old('facebook_profile', $danisman->facebook_profile) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('facebook_profile') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://facebook.com/...">
                                @error('facebook_profile')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="twitter_profile"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Twitter/X Profil URL
                                </label>
                                <input type="url" id="twitter_profile" name="twitter_profile"
                                    value="{{ old('twitter_profile', $danisman->twitter_profile) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('twitter_profile') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://twitter.com/...">
                                @error('twitter_profile')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="youtube_channel"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    YouTube Kanal URL
                                </label>
                                <input type="url" id="youtube_channel" name="youtube_channel"
                                    value="{{ old('youtube_channel', $danisman->youtube_channel) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('youtube_channel') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://youtube.com/...">
                                @error('youtube_channel')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="website"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                    Web Sitesi URL
                                </label>
                                <input type="url" id="website" name="website"
                                    value="{{ old('website', $danisman->website) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('website') border-red-500 @enderror dark:text-slate-100"
                                    placeholder="https://...">
                                @error('website')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Profil Fotoğrafı -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Profil Fotoğrafı
                        </h3>

                        @php
                            $pp = $danisman->profile_photo_path;
                            $ppUrl = ($pp && Storage::exists($pp)) ? Storage::url($pp) : null;
                        @endphp
                        @if ($ppUrl)
                            <div class="mb-4">
                                <img src="{{ $ppUrl }}" alt="{{ $danisman->name }}"
                                    class="w-24 h-24 rounded-lg object-cover border-2 border-gray-200 dark:border-slate-700"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div class="w-24 h-24 rounded-lg border-2 border-gray-200 bg-gray-100 flex items-center justify-center text-gray-500 text-xs dark:border-slate-700 dark:bg-slate-900" style="display: none;">
                                    <div class="text-center">
                                        <div class="text-2xl mb-1">📷</div>
                                        <div>Fotoğraf</div>
                                        <div>Yüklenemedi</div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($danisman->profile_photo_path)
                            <div class="mb-4">
                                <div class="w-24 h-24 rounded-lg border-2 border-gray-200 bg-gray-100 flex items-center justify-center text-gray-500 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <div class="text-center">
                                        <div class="text-2xl mb-1">⚠️</div>
                                        <div>Fotoğraf</div>
                                        <div>Bulunamadı</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="profile_photo"
                                class="block text-sm font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                Yeni Fotoğraf
                            </label>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('profile_photo') border-red-500 @enderror dark:text-slate-100">
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Durum -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            Durum
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                    Durum
                                </label>
                                <select style="color-scheme: light dark;" name="status" id="status"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                                    @php
                                        // Status_text varsa onu kullan, yoksa boolean'dan çevir
                                        $currentStatus = old('aktiflik_durumu', $danisman->status_text ?? null);
                                        if (!$currentStatus) {
                                            // Boolean değeri string'e çevir
                                            if (is_bool($danisman->aktiflik_durumu)) {
                                                $currentStatus = $danisman->aktiflik_durumu ? 'aktif' : 'pasif';
                                            } elseif ($danisman->aktiflik_durumu === 1 || $danisman->aktiflik_durumu === '1') {
                                                $currentStatus = 'aktif';
                                            } elseif ($danisman->aktiflik_durumu === 0 || $danisman->aktiflik_durumu === '0') {
                                                $currentStatus = 'pasif';
                                            } else {
                                                $currentStatus = 'aktif'; // Default
                                            }
                                        }
                                    @endphp
                                    @foreach(config('danisman.status_options', []) as $key => $label)
                                        <option value="{{ $key }}" {{ $currentStatus == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Boolean checkbox fallback (hidden, for backward compatibility) --}}
                            <input type="hidden" name="status_boolean" value="{{ $danisman->aktiflik_durumu ? 1 : 0 }}">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <x-neo.button href="{{ route('admin.danisman.index') }}" variant="ghost">
                İptal
            </x-neo.button>
            <x-neo.button type="submit" variant="primary"
                icon="<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>">
                Güncelle
            </x-neo.button>
        </div>
    </form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adInput = document.getElementById('ad');
        const soyadInput = document.getElementById('soyad');
        const nameInput = document.getElementById('name');

        function updateName() {
            if (adInput && soyadInput && nameInput) {
                const ad = adInput.value.trim();
                const soyad = soyadInput.value.trim();
                nameInput.value = (ad + ' ' + soyad).trim();
            }
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

@endsection
