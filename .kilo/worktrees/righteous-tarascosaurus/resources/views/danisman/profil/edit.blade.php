@extends('admin.layouts.admin')

@section('title', 'Profilim')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                Profilim
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Profil bilgilerinizi ve şifrenizi güncelleyin</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg shadow-lg p-4 animate-slide-in">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Profil Bilgileri Formu --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Profil Bilgileri</h3>
        </div>

        <form method="POST" action="{{ route('danisman.profil.update') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Profil Fotoğrafı --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Profil Fotoğrafı</label>
                <div class="flex items-center gap-4">
                    @if($danisman->profile_photo_path)
                        <img src="{{ asset('storage/' . $danisman->profile_photo_path) }}" 
                             alt="{{ $danisman->name }}" 
                             class="w-20 h-20 rounded-full object-cover border-4 border-purple-200 dark:border-purple-800">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($danisman->name, 0, 2)) }}
                        </div>
                    @endif
                    <input type="file" name="profile_photo" accept="image/*"
                           class="block w-full text-sm text-gray-900 dark:text-white
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-purple-50 file:text-purple-700
                                  hover:file:bg-purple-100
                                  dark:file:bg-purple-900/30 dark:file:text-purple-400">
                </div>
                @error('profile_photo')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Ad Soyad --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ad Soyad *</label>
                <input type="text" name="name" value="{{ old('name', $danisman->name) }}" required
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Telefon Bilgileri --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Cep Telefonu</label>
                    <input type="tel" name="phone_number" value="{{ old('phone_number', $danisman->phone_number) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">WhatsApp</label>
                    <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $danisman->whatsapp_number) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ofis Telefonu</label>
                    <input type="tel" name="office_phone" value="{{ old('office_phone', $danisman->office_phone) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                </div>
            </div>

            {{-- Unvan & Ofis Adresi --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ünvan</label>
                    <input type="text" name="title" value="{{ old('title', $danisman->title) }}"
                           placeholder="Örn: Emlak Danışmanı"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ofis Adresi</label>
                    <input type="text" name="office_address" value="{{ old('office_address', $danisman->office_address) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                </div>
            </div>

            {{-- Bio --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Hakkımda</label>
                <textarea name="bio" rows="4"
                          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">{{ old('bio', $danisman->bio) }}</textarea>
            </div>

            {{-- Uzmanlık Özeti --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Uzmanlık Özeti</label>
                <textarea name="expertise_summary" rows="3"
                          placeholder="Hangi alanlarda uzmanım, hizmetlerim..."
                          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">{{ old('expertise_summary', $danisman->expertise_summary) }}</textarea>
            </div>

            {{-- Sosyal Medya --}}
            <div class="border-t border-gray-200 dark:border-slate-800 pt-6 dark:border-slate-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Sosyal Medya Hesapları</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            <i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram
                        </label>
                        <input type="url" name="instagram_profile" value="{{ old('instagram_profile', $danisman->instagram_profile) }}"
                               placeholder="https://instagram.com/..."
                               class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            <i class="fab fa-linkedin text-blue-700 mr-2"></i>LinkedIn
                        </label>
                        <input type="url" name="linkedin_profile" value="{{ old('linkedin_profile', $danisman->linkedin_profile) }}"
                               placeholder="https://linkedin.com/in/..."
                               class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook
                        </label>
                        <input type="url" name="facebook_profile" value="{{ old('facebook_profile', $danisman->facebook_profile) }}"
                               placeholder="https://facebook.com/..."
                               class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            <i class="fas fa-globe text-gray-600 mr-2"></i>Website
                        </label>
                        <input type="url" name="website" value="{{ old('website', $danisman->website) }}"
                               placeholder="https://..."
                               class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end pt-4">
                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Profili Güncelle
                </button>
            </div>
        </form>
    </div>

    {{-- Şifre Değiştirme --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Şifre Değiştir</h3>
        </div>

        <form method="POST" action="{{ route('danisman.profil.password') }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Mevcut Şifre *</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Yeni Şifre *</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">En az 8 karakter, büyük/küçük harf, sayı ve özel karakter içermelidir.</p>
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Yeni Şifre (Tekrar) *</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:text-slate-100">
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-red-600 to-orange-600 rounded-lg hover:from-red-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Şifreyi Değiştir
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    @keyframes slide-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
</style>
@endpush
@endsection
