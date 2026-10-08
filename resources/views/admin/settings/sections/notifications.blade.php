{{-- Notifications Settings Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Bildirim Ayarları
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Sistem bildirimlerini nasıl almak istediğinizi seçin.</p>
    </div>

    <div class="space-y-4">
        <x-admin.form-field label="E-posta Bildirimleri" name="email_notifications"
            hint="Rezervasyon ve sistem uyarıları için e-posta gönder">
            <x-admin.toggle
                name="email_notifications"
                :checked="filter_var($settings['email_notifications'] ?? true, FILTER_VALIDATE_BOOLEAN)"
            />
        </x-admin.form-field>

        <x-admin.form-field label="WhatsApp Bildirimleri" name="whatsapp_notifications"
            hint="Rezervasyon ve operasyonel akışlar için WhatsApp mesajı gönder">
            <x-admin.toggle
                name="whatsapp_notifications"
                :checked="filter_var($settings['whatsapp_notifications'] ?? true, FILTER_VALIDATE_BOOLEAN)"
            />
        </x-admin.form-field>

        <x-admin.form-field label="Telegram Bildirimleri" name="telegram_notifications"
            hint="VIP sinyaller ve sistem logları için Telegram bildirimleri gönder">
            <x-admin.toggle
                name="telegram_notifications"
                :checked="filter_var($settings['telegram_notifications'] ?? true, FILTER_VALIDATE_BOOLEAN)"
            />
        </x-admin.form-field>
    </div>
</div>
