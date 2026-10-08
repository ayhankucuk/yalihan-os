{{-- User Management Settings Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Kullanıcı Yönetimi
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Kullanıcı kayıt ve güvenlik ayarları.</p>
    </div>

    <div class="space-y-6">
        <x-admin.form-field label="Yeni Kullanıcı Kaydı" name="user_registration"
            hint="Yeni kullanıcıların kendi başlarına kayıt olmasına izin ver">
            <x-admin.toggle
                name="user_registration"
                :checked="$settings['user_registration'] ?? false"
            />
        </x-admin.form-field>

        <x-admin.form-field label="Şifre Güçlülük Zorunluluğu" name="password_strength"
            hint="Kullanıcıların güçlü şifre kullanmasını zorunlu kıl">
            <x-admin.toggle
                name="password_strength"
                :checked="$settings['password_strength'] ?? false"
            />
        </x-admin.form-field>
    </div>
</div>
