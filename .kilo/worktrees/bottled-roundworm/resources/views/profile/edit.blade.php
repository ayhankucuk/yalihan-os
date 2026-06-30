@php
    use Illuminate\Support\Facades\Storage;
@endphp

@extends('admin.layouts.admin')

@section('title', 'Profil Yönetimi')

@section('content')
    <div class="content-header mb-4">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">Profil</h1>
                    <p class="text-sm text-gray-500">Kişisel bilgilerinizi güncelleyin</p>
                </div>
                <div class="flex items-center space-x-3">
                    <x-neo.button href="{{ route('admin.dashboard.index') }}" variant="secondary"
                        icon="<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 19l-7-7m0 0l7-7m-7 7h18' /></svg>">
                        Dashboard'a Geri Dön
                    </x-neo.button>
                </div>
            </div>
        </div>
    </div>

    @if (session('mesaj'))
        <div class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-lg" role="alert">
            <div class="flex">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('mesaj') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PATCH')

        <!-- Profil Bilgileri -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700">
            <div class="p-4">
                <h2 class="text-sm font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Profil Bilgileri</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Ad Soyad -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Ad Soyad <span class="text-red-500">*</span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('name')) ? 'text-red-600 dark:text-red-400' : '' }} dark:text-slate-100"
                            placeholder="Ad ve soyadınızı girin...">
                        @error('name')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- E-posta -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            E-posta Adresi <span class="text-red-500">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                            required class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('email')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="ornek@email.com">
                        @error('email')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unvan -->
                    <div class="space-y-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Unvan</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $user->title) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('title')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="Unvanınızı girin...">
                        @error('title')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pozisyon -->
                    <div class="space-y-2">
                        <label for="position" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Pozisyon</label>
                        <input id="position" type="text" name="position" value="{{ old('position', $user->position) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('position')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="Pozisyonunuzu girin...">
                        @error('position')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Profil Fotoğrafı -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="profile_photo" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Profil Fotoğrafı</label>
                        <input id="profile_photo" type="file" name="profile_photo" accept="image/*"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('profile_photo')) ? 'border-red-500' : '' }} dark:text-slate-100">
                        @error('profile_photo')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @if ($user->profile_photo_path)
                            @php $upp=$user->profile_photo_path; $uppUrl=($upp && Storage::exists($upp))?Storage::url($upp):null; @endphp
                            <div class="mt-3 flex items-center space-x-3">
                                @if($uppUrl)
                                    <img src="{{ $uppUrl }}" alt="{{ $user->name }}"
                                         class="h-16 w-16 rounded-full object-cover border-2 border-blue-200"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="h-16 w-16 rounded-full bg-gray-200 hidden items-center justify-center text-gray-500">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @else
                                    <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm text-gray-600">Mevcut fotoğraf</p>
                                    <p class="text-xs text-gray-500">{{ basename($user->profile_photo_path) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- İletişim Bilgileri -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700">
            <div class="p-4">
                <h2 class="text-sm font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">İletişim Bilgileri</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Telefon -->
                    <div class="space-y-2">
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Telefon</label>
                        <input id="phone_number" type="tel" name="phone_number"
                            value="{{ old('phone_number', $user->phone_number) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('phone_number')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="05XX XXX XXXX">
                        @error('phone_number')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- WhatsApp -->
                    <div class="space-y-2">
                        <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">WhatsApp</label>
                        <input id="whatsapp_number" type="tel" name="whatsapp_number"
                            value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('whatsapp_number')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="05XX XXX XXXX">
                        @error('whatsapp_number')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Adres -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="office_address" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Adres</label>
                        <textarea id="office_address" name="office_address" rows="3"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical {{ (is_object($errors) && $errors->has('office_address')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="Adres bilgilerinizi girin...">{{ old('office_address', $user->office_address) }}</textarea>
                        @error('office_address')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sosyal Medya -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700">
            <div class="p-4">
                <h2 class="text-sm font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Sosyal Medya</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- LinkedIn -->
                    <div class="space-y-2">
                        <label for="linkedin_profile" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">LinkedIn</label>
                        <input id="linkedin_profile" type="url" name="linkedin_profile"
                            value="{{ old('linkedin_profile', $user->linkedin_profile) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('linkedin_profile')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="https://linkedin.com/in/kullaniciadi">
                        @error('linkedin_profile')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Twitter -->
                    <div class="space-y-2">
                        <label for="twitter_profile" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Twitter</label>
                        <input id="twitter_profile" type="url" name="twitter_profile"
                            value="{{ old('twitter_profile', $user->twitter_profile) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('twitter_profile')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="https://twitter.com/kullaniciadi">
                        @error('twitter_profile')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Instagram -->
                    <div class="space-y-2">
                        <label for="instagram_profile" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Instagram</label>
                        <input id="instagram_profile" type="url" name="instagram_profile"
                            value="{{ old('instagram_profile', $user->instagram_profile) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('instagram_profile')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="https://instagram.com/kullaniciadi">
                        @error('instagram_profile')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Facebook -->
                    <div class="space-y-2">
                        <label for="facebook_profile" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Facebook</label>
                        <input id="facebook_profile" type="url" name="facebook_profile"
                            value="{{ old('facebook_profile', $user->facebook_profile) }}"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 {{ (is_object($errors) && $errors->has('facebook_profile')) ? 'border-red-500' : '' }} dark:text-slate-100"
                            placeholder="https://facebook.com/kullaniciadi">
                        @error('facebook_profile')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Biyografi -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700">
            <div class="p-4">
                <h2 class="text-sm font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Biyografi</h2>

                <div class="space-y-2">
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Hakkımda</label>
                    <textarea id="bio" name="bio" rows="4"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical {{ (is_object($errors) && $errors->has('bio')) ? 'border-red-500' : '' }} dark:text-slate-100"
                        placeholder="Kendiniz hakkında kısa bir açıklama yazın...">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Butonları -->
        <div class="flex justify-end space-x-3">
            <x-neo.button type="button" variant="ghost" onclick="window.history.back()">
                İptal
            </x-neo.button>
            <x-neo.button type="submit" variant="primary"
                icon="<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>">
                Profili Güncelle
            </x-neo.button>
        </div>
    </form>
@endsection

@push('styles')
    <!-- Context7 Design System utilities are already included in master layout -->
@endpush

@push('scripts')
    <script>
        // Form field focus effects
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('.w-full px-3 py-2 rounded-md border border-gray-200 bg-white dark:bg-slate-900 text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors, .w-full px-3 py-2 rounded-md border border-gray-200 bg-white dark:bg-slate-900 text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical dark:border-slate-700 dark:placeholder:text-slate-500');

            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-200');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-200');
                });
            });
        });
    </script>
@endpush
