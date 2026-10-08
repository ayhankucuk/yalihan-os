{{-- Portal Integration Settings Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
            </svg>
            Portal Entegrasyonları
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Harici emlak portalları ile entegrasyon ayarları.</p>
    </div>

    <div class="space-y-6">
        <x-admin.form-field label="Sahibinden.com API Anahtarı" name="sahibinden_api_key" :error="$errors->first('sahibinden_api_key')">
            <div class="relative">
                <input type="password" id="sahibinden_api_key" name="sahibinden_api_key"
                    value="{{ old('sahibinden_api_key', $settings['sahibinden_api_key'] ?? '') }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-10 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:shadow-md focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-gray-500 dark:shadow-none"
                    placeholder="API anahtarınızı girin">
                <button type="button" onclick="togglePasswordVisibility('sahibinden_api_key')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 transform text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </x-admin.form-field>

        <x-admin.form-field label="Hepsiemlak API Anahtarı" name="hepsiemlak_api_key" :error="$errors->first('hepsiemlak_api_key')">
            <div class="relative">
                <input type="password" id="hepsiemlak_api_key" name="hepsiemlak_api_key"
                    value="{{ old('hepsiemlak_api_key', $settings['hepsiemlak_api_key'] ?? '') }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-10 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:shadow-md focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-gray-500 dark:shadow-none"
                    placeholder="API anahtarınızı girin">
                <button type="button" onclick="togglePasswordVisibility('hepsiemlak_api_key')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 transform text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </x-admin.form-field>
    </div>
</div>
